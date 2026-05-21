<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Mail\AccountVerificationMail;
use App\Models\Kelas;
use App\Models\Member;
use App\Models\User;
use App\Services\FonnteService;
use App\Services\MemberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        $user = Auth::user();

        // Cek status keanggotaan
        if ($user->role === 'anggota' && $user->member) {
            $status = $user->member->status;
            
            if ($status !== 'aktif') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $messages = [
                    'pending'   => 'Akun Anda masih menunggu persetujuan admin. Silakan tunggu konfirmasi.',
                    'ditolak'   => 'Mohon maaf, pendaftaran akun Anda ditolak oleh admin. Alasan: ' . ($user->member->rejection_reason ?? 'Tidak ada keterangan.'),
                    'suspended' => 'Akun Anda ditangguhkan (suspended). Silakan hubungi admin.',
                    'nonaktif'  => 'Akun Anda telah dinonaktifkan. Silakan hubungi admin.',
                ];
                
                $errorMessage = $messages[$status] ?? 'Akun Anda tidak aktif.';
                return back()->withErrors(['email' => $errorMessage])->onlyInput('email');
            }
        }

        $request->session()->regenerate();

        $redirect = $user->role === 'anggota'
            ? route('home')
            : route('dashboard');

        return redirect()->intended($redirect)->with('success', 'Login berhasil! Selamat datang.');
    }

    // ── Register OTP (WhatsApp) ──────────────────────────────────────

    public function registerSendOtp(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $normalizedPhone = !empty($data['phone']) ? $this->normalizePhone($data['phone']) : null;
        $otp = (string) random_int(100000, 999999);
        $cacheKey = 'web_register_otp_' . strtolower($data['email']);

        Cache::put($cacheKey, [
            'name'          => $data['name'],
            'email'         => strtolower($data['email']),
            'phone'         => $normalizedPhone,
            'password_hash' => Hash::make($data['password']),
            'otp_hash'      => Hash::make($otp),
            'attempts'      => 0,
            'expires_at'    => now()->addMinutes(10)->toDateTimeString(),
        ], now()->addMinutes(10));

        try {
            Mail::to($data['email'])->send(new AccountVerificationMail($otp, $data['name'], 'register'));
        } catch (\Throwable $e) {
            Cache::forget($cacheKey);
            return back()->withErrors(['email' => 'Gagal mengirim OTP ke Email. Pastikan alamat email valid.']);
        }

        return back()->with([
            'otp_sent'   => true,
            'otp_email'  => strtolower($data['email']),
            'email_hint' => strtolower($data['email']),
        ]);
    }

    public function registerVerifyOtp(Request $request, MemberService $memberService)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        $cacheKey = 'web_register_otp_' . strtolower($data['email']);
        $otpData  = Cache::get($cacheKey);

        if (!$otpData) {
            return back()->withErrors(['otp' => 'Kode OTP sudah kadaluwarsa. Silakan daftar ulang.']);
        }

        if (now()->greaterThan($otpData['expires_at'])) {
            Cache::forget($cacheKey);
            return back()->withErrors(['otp' => 'Kode OTP sudah kadaluwarsa. Silakan daftar ulang.']);
        }

        if (($otpData['attempts'] ?? 0) >= 5) {
            Cache::forget($cacheKey);
            return back()->withErrors(['otp' => 'Percobaan OTP terlalu banyak. Silakan daftar ulang.']);
        }

        if (!Hash::check($data['otp'], $otpData['otp_hash'])) {
            $otpData['attempts'] = ($otpData['attempts'] ?? 0) + 1;
            Cache::put($cacheKey, $otpData, now()->addMinutes(10));
            return back()->withErrors(['otp' => 'Kode OTP tidak valid. Sisa percobaan: ' . (5 - $otpData['attempts'])]);
        }

        if (User::where('email', $otpData['email'])->exists()) {
            Cache::forget($cacheKey);
            return back()->withErrors(['otp' => 'Email sudah digunakan oleh akun lain.']);
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'name'              => $otpData['name'],
                'email'             => $otpData['email'],
                'email_verified_at' => now(),
                'password'          => $otpData['password_hash'],
                'role'              => 'anggota',
            ]);

            $memberService->register($user, [
                'name'  => $otpData['name'],
                'phone' => $otpData['phone'],
                'type'  => 'umum',
                'status_override' => 'pending',
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['otp' => 'Registrasi gagal. Silakan coba lagi.']);
        }

        Cache::forget($cacheKey);

        return redirect()->route('login')->with('success', 'Registrasi berhasil! Akun Anda sedang menunggu persetujuan admin. Silakan cek kembali nanti.');
    }

    public function showRegister()
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request, MemberService $memberService)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', 'string', 'min:6'],
            'phone'    => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => 'anggota',
        ]);

        // Register publik = selalu umum
        $data['type'] = 'umum';
        $memberService->register($user, $data);

        // Jangan auto-login, redirect ke login dengan pesan pending
        return redirect()->route('login')->with('success', 'Registrasi berhasil! Akun Anda sedang menunggu persetujuan admin. Silakan cek kembali nanti.');
    }

    // ── Claim Aktivasi OTP (Email) ───────────────────────────────────

    public function claimActivateSendOtp(Request $request)
    {
        $data = $request->validate([
            'member_id' => 'required|exists:members,id',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:6|confirmed',
        ]);

        $member = Member::where('id', $data['member_id'])
            ->whereNull('user_id')
            ->firstOrFail();

        $otp      = (string) random_int(100000, 999999);
        $cacheKey = 'web_claim_otp_' . $member->id;

        Cache::put($cacheKey, [
            'member_id'     => $member->id,
            'email'         => strtolower($data['email']),
            'password_hash' => Hash::make($data['password']),
            'otp_hash'      => Hash::make($otp),
            'attempts'      => 0,
            'expires_at'    => now()->addMinutes(10)->toDateTimeString(),
        ], now()->addMinutes(10));

        Mail::to($data['email'])->send(new AccountVerificationMail($otp, $member->name, 'activate'));

        // Simpan status OTP ke session lalu redirect ke GET agar props stabil
        $request->session()->put('claim_otp_sent',  true);
        $request->session()->put('claim_otp_email', strtolower($data['email']));

        return redirect()->route('claim.show');
    }

    public function claimActivateVerifyOtp(Request $request)
    {
        $data = $request->validate([
            'member_id' => 'required|exists:members,id',
            'email'     => 'required|email',
            'otp'       => 'required|string|size:6',
        ]);

        $cacheKey = 'web_claim_otp_' . $data['member_id'];
        $otpData  = Cache::get($cacheKey);

        if (!$otpData) {
            return back()->withErrors(['otp' => 'Kode OTP sudah kadaluwarsa. Silakan minta kode baru.']);
        }

        if (now()->greaterThan($otpData['expires_at'])) {
            Cache::forget($cacheKey);
            return back()->withErrors(['otp' => 'Kode OTP sudah kadaluwarsa. Silakan minta kode baru.']);
        }

        if (($otpData['attempts'] ?? 0) >= 5) {
            Cache::forget($cacheKey);
            return back()->withErrors(['otp' => 'Percobaan OTP terlalu banyak. Silakan minta kode baru.']);
        }

        if (!Hash::check($data['otp'], $otpData['otp_hash'])) {
            $otpData['attempts'] = ($otpData['attempts'] ?? 0) + 1;
            Cache::put($cacheKey, $otpData, now()->addMinutes(10));
            return back()->withErrors(['otp' => 'Kode OTP tidak valid. Sisa percobaan: ' . (5 - $otpData['attempts'])]);
        }

        $member = Member::where('id', $data['member_id'])->whereNull('user_id')->firstOrFail();

        if (User::where('email', $otpData['email'])->exists()) {
            Cache::forget($cacheKey);
            return back()->withErrors(['otp' => 'Email sudah digunakan oleh akun lain.']);
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'name'              => $member->name,
                'email'             => $otpData['email'],
                'email_verified_at' => now(),
                'password'          => $otpData['password_hash'],
                'role'              => 'anggota',
            ]);

            $member->update([
                'user_id'     => $user->id,
                'status'      => 'aktif',
                'verified_at' => now(),
            ]);

            $user->assignRole('anggota');
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['otp' => 'Aktivasi akun gagal. Silakan coba lagi.']);
        }

        Cache::forget($cacheKey);
        $request->session()->forget(['claim_found_member', 'claim_otp_sent', 'claim_otp_email']);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', "Selamat datang, {$member->name}! Akun berhasil diaktivasi.");
    }

    // ── Klaim Akun (NIS/NIP) ──

    public function showClaim(Request $request)
    {
        $foundMember = $request->session()->get('claim_found_member');
        $otpSent     = $request->session()->get('claim_otp_sent', false);
        $otpEmail    = $request->session()->get('claim_otp_email');

        return Inertia::render('Auth/ClaimAccount', [
            'foundMember' => $foundMember,
            'otpSent'     => $otpSent,
            'otpEmail'    => $otpEmail,
        ]);
    }

    public function claimReset(Request $request)
    {
        $request->session()->forget(['claim_found_member', 'claim_otp_sent', 'claim_otp_email']);
        return redirect()->route('claim.show');
    }

    public function claimLookup(Request $request)
    {
        $request->validate(['nis_nip' => 'required|string|max:30']);

        $member = Member::where('nis_nip', $request->nis_nip)
            ->whereNull('user_id')
            ->first();

        if (!$member) {
            return back()->withErrors(['nis_nip' => 'NIS/NIP tidak ditemukan atau akun sudah diaktivasi. Hubungi admin.']);
        }

        // Simpan ke session lalu redirect ke GET agar refresh tidak 405
        $request->session()->put('claim_found_member', [
            'id'     => $member->id,
            'name'   => $member->name,
            'type'   => $member->type,
            'nis_nip' => $member->nis_nip,
        ]);

        return redirect()->route('claim.show');
    }

    public function claimActivate(Request $request)
    {
        $data = $request->validate([
            'member_id' => 'required|exists:members,id',
            'email'     => 'required|email|unique:users,email',
            'password'  => ['required', 'confirmed', 'string', 'min:6'],
        ]);

        $member = Member::where('id', $data['member_id'])->whereNull('user_id')->firstOrFail();

        $user = User::create([
            'name'     => $member->name,
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => 'anggota',
        ]);

        $member->update([
            'user_id'     => $user->id,
            'status'      => 'aktif',
            'verified_at' => now(),
        ]);

        $user->assignRole('anggota');
        Auth::login($user);

        return redirect()->route('home')->with('success', "Selamat datang, {$member->name}! Akun berhasil diaktivasi.");
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    // ── Helpers ─────────────────────────────────────────────────────

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($phone, '0')) return '62' . substr($phone, 1);
        if (str_starts_with($phone, '8')) return '62' . $phone;
        return $phone;
    }

    private function maskPhone(string $phone): string
    {
        $len = strlen($phone);
        if ($len <= 4) return str_repeat('*', $len);
        return substr($phone, 0, 4) . str_repeat('*', max(0, $len - 7)) . substr($phone, -3);
    }
}
