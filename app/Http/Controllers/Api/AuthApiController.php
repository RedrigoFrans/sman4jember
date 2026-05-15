<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Member;
use App\Services\MemberService;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Cache;

class AuthApiController extends Controller
{
    /**
     * Login dan dapatkan token Sanctum.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // Cek jika anggota masih pending
        if ($user->role === 'anggota' && $user->member && $user->member->status === 'pending') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Akun Anda masih menunggu persetujuan admin. Silakan tunggu konfirmasi.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('member.kelas')
        ]);
    }

    /**
     * Register Publik (Otomatis tipe Umum)
     */
    public function register(Request $request, MemberService $memberService)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone'    => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => 'anggota',
        ]);

        // Register publik API = selalu umum
        $data['type'] = 'umum';
        $member = $memberService->register($user, $data);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil! Akun sedang menunggu verifikasi admin.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('member')
        ], 201);
    }
    public function registerSendOtp(Request $request, FonnteService $fonnte)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'phone'    => ['required', 'string', 'max:20', 'regex:/^(08|628|8)[0-9]{8,13}$/'],
            'password' => 'required|string|min:6|confirmed',
        ]);

        $normalizedPhone = $this->normalizeIndonesianPhoneNumber($data['phone']);

        $otp = (string) random_int(100000, 999999);

        $cacheKey = $this->publicRegisterCacheKey($data['email']);

        Cache::put($cacheKey, [
            'name'          => $data['name'],
            'email'         => strtolower($data['email']),
            'phone'         => $normalizedPhone,
            'password_hash' => Hash::make($data['password']),
            'otp_hash'      => Hash::make($otp),
            'attempts'      => 0,
            'expires_at'    => now()->addMinutes(10)->toDateTimeString(),
        ], now()->addMinutes(10));

        $message = "Kode OTP pendaftaran e-library Anda adalah: {$otp}. Kode berlaku 10 menit. Jangan berikan kode ini kepada siapa pun.";

        try {
            $fonnte->sendMessage($normalizedPhone, $message);
        } catch (\Throwable $e) {
            Cache::forget($cacheKey);

            return response()->json([
                'message' => 'Gagal mengirim OTP ke WhatsApp. Silakan coba lagi atau hubungi admin.',
            ], 500);
        }

        return response()->json([
            'message'    => 'Kode OTP pendaftaran telah dikirim ke WhatsApp.',
            'phone_hint' => $this->maskPhoneNumber($normalizedPhone),
        ]);
    }

    public function registerVerifyOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        $cacheKey = $this->publicRegisterCacheKey($data['email']);
        $otpData = Cache::get($cacheKey);

        if (!$otpData) {
            return response()->json([
                'message' => 'Kode OTP sudah kadaluwarsa. Silakan daftar ulang.'
            ], 422);
        }

        if (strtolower($data['email']) !== strtolower($otpData['email'])) {
            return response()->json([
                'message' => 'Email tidak sesuai dengan permintaan pendaftaran.'
            ], 422);
        }

        if (now()->greaterThan($otpData['expires_at'])) {
            Cache::forget($cacheKey);

            return response()->json([
                'message' => 'Kode OTP sudah kadaluwarsa. Silakan daftar ulang.'
            ], 422);
        }

        if (($otpData['attempts'] ?? 0) >= 5) {
            Cache::forget($cacheKey);

            return response()->json([
                'message' => 'Percobaan OTP terlalu banyak. Silakan daftar ulang.'
            ], 429);
        }

        if (!Hash::check($data['otp'], $otpData['otp_hash'])) {
            $otpData['attempts'] = ($otpData['attempts'] ?? 0) + 1;
            Cache::put($cacheKey, $otpData, now()->addMinutes(10));

            return response()->json([
                'message' => 'Kode OTP tidak valid.'
            ], 422);
        }

        if (User::where('email', $otpData['email'])->exists()) {
            Cache::forget($cacheKey);

            return response()->json([
                'message' => 'Email sudah digunakan oleh akun lain.'
            ], 422);
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

            $member = Member::create([
                'user_id'     => $user->id,
                'name'        => $otpData['name'],
                'type'        => 'umum',
                'phone'       => $otpData['phone'],
                'status'      => 'aktif',
                'verified_at' => now(),
            ]);

            $user->assignRole('anggota');

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Registrasi gagal. Silakan coba lagi.',
            ], 500);
        }

        Cache::forget($cacheKey);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Registrasi berhasil.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user->load('member')
        ], 201);
    }

    /**
     * Dapatkan data user yang login
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('member.kelas')
        ]);
    }

    /**
     * Logout dan hapus semua token saat ini
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil logout'
        ]);
    }

    /**
     * Cari Siswa/Guru berdasarkan NIS/NIP untuk Klaim Akun
     */
    public function claimLookup(Request $request)
    {
        $request->validate([
            'nis_nip' => 'required|string|max:30',
        ]);

        $member = Member::where('nis_nip', $request->nis_nip)
            ->whereNull('user_id')
            ->first();

        if (!$member) {
            return response()->json([
                'message' => 'NIS/NIP tidak ditemukan atau akun sudah diaktivasi. Hubungi admin.'
            ], 404);
        }

        return response()->json([
            'message' => 'Anggota ditemukan',
            'data' => [
                'id' => $member->id,
                'name' => $member->name,
                'type' => $member->type,
                'nis_nip' => $member->nis_nip,
            ]
        ]);
    }

    /**
     * Aktivasi akun (Set Email + Password) 
     */
    public function claimActivate(Request $request)
    {
        return response()->json([
            'message' => 'Endpoint aktivasi lama sudah dinonaktifkan. Gunakan /auth/claim-activate/send-otp lalu /auth/claim-activate/verify-otp.'
        ], 410);
    }

    public function claimActivateSendOtp(Request $request)
    {
        $data = $request->validate([
            'member_id' => 'required|exists:members,id',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:6|confirmed',
        ]);

        $member = Member::where('id', $data['member_id'])
            ->whereNull('user_id')
            ->first();

        if (!$member) {
            return response()->json([
                'message' => 'Anggota tidak valid atau sudah diaktivasi.'
            ], 400);
        }

        if (!in_array($member->type, ['guru', 'siswa'])) {
            return response()->json([
                'message' => 'Claim aktivasi hanya untuk guru atau siswa.'
            ], 422);
        }

        $otp = (string) random_int(100000, 999999);
        $cacheKey = $this->claimActivationCacheKey($member->id);

        Cache::put($cacheKey, [
            'member_id'     => $member->id,
            'email'         => strtolower($data['email']),
            'password_hash' => Hash::make($data['password']),
            'otp_hash'      => Hash::make($otp),
            'attempts'      => 0,
            'expires_at'    => now()->addMinutes(10)->toDateTimeString(),
        ], now()->addMinutes(10));

        Mail::to($data['email'])->send(new OtpMail($otp, $member->name));

        return response()->json([
            'message' => 'Kode OTP aktivasi telah dikirim ke email Anda.',
        ]);
    }

    public function claimActivateVerifyOtp(Request $request)
    {
        $data = $request->validate([
            'member_id' => 'required|exists:members,id',
            'email'     => 'required|email',
            'otp'       => 'required|string|size:6',
        ]);

        $cacheKey = $this->claimActivationCacheKey($data['member_id']);
        $otpData = Cache::get($cacheKey);

        if (!$otpData) {
            return response()->json([
                'message' => 'Kode OTP sudah kadaluwarsa. Silakan minta kode baru.'
            ], 422);
        }

        if (strtolower($data['email']) !== strtolower($otpData['email'])) {
            return response()->json([
                'message' => 'Email tidak sesuai dengan permintaan aktivasi.'
            ], 422);
        }

        if (now()->greaterThan($otpData['expires_at'])) {
            Cache::forget($cacheKey);

            return response()->json([
                'message' => 'Kode OTP sudah kadaluwarsa. Silakan minta kode baru.'
            ], 422);
        }

        if (($otpData['attempts'] ?? 0) >= 5) {
            Cache::forget($cacheKey);

            return response()->json([
                'message' => 'Percobaan OTP terlalu banyak. Silakan minta kode baru.'
            ], 429);
        }

        if (!Hash::check($data['otp'], $otpData['otp_hash'])) {
            $otpData['attempts'] = ($otpData['attempts'] ?? 0) + 1;
            Cache::put($cacheKey, $otpData, now()->addMinutes(10));

            return response()->json([
                'message' => 'Kode OTP tidak valid.'
            ], 422);
        }

        $member = Member::where('id', $data['member_id'])
            ->whereNull('user_id')
            ->first();

        if (!$member) {
            Cache::forget($cacheKey);

            return response()->json([
                'message' => 'Anggota tidak valid atau sudah diaktivasi.'
            ], 400);
        }

        if (User::where('email', $otpData['email'])->exists()) {
            Cache::forget($cacheKey);

            return response()->json([
                'message' => 'Email sudah digunakan oleh akun lain.'
            ], 422);
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

            return response()->json([
                'message' => 'Aktivasi akun gagal. Silakan coba lagi.',
            ], 500);
        }

        Cache::forget($cacheKey);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Akun berhasil diaktivasi.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user->load('member.kelas')
        ]);
    }
    /**
     * Forgot Password - Step 1: Kirim OTP ke email
     */
    public function forgotSendOtp(Request $request, FonnteService $fonnte)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::with('member')->where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email tidak terdaftar di sistem kami.'
            ], 404);
        }

        if (!$user->member || !$user->member->phone) {
            return response()->json([
                'message' => 'Nomor WhatsApp belum terdaftar. Hubungi admin/perpustakaan.'
            ], 422);
        }

        $otp = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token'      => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        $message = "Kode OTP reset password e-library Anda adalah: {$otp}. Kode berlaku 10 menit. Jangan berikan kode ini kepada siapa pun.";

        try {
            $fonnte->sendMessage($user->member->phone, $message);
        } catch (\Throwable $e) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return response()->json([
                'message' => 'Gagal mengirim OTP ke WhatsApp. Silakan coba lagi atau hubungi admin.',
            ], 500);
        }

        return response()->json([
            'message'    => 'Kode OTP telah dikirim ke WhatsApp yang terdaftar.',
            'phone_hint' => $this->maskPhoneNumber($user->member->phone),
        ]);
    }

    /**
     * Forgot Password - Step 2: Verifikasi OTP
     */
    public function forgotVerifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord || !Hash::check($request->otp, $resetRecord->token)) {
            return response()->json([
                'message' => 'Kode OTP tidak valid.'
            ], 422);
        }

        // Check expiration (10 minutes)
        $createdAt = \Carbon\Carbon::parse($resetRecord->created_at);
        if ($createdAt->addMinutes(10)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'message' => 'Kode OTP sudah kadaluwarsa. Silakan minta kode baru.'
            ], 422);
        }

        // Generate reset_token for next step
        $resetToken = Str::random(60);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token'      => Hash::make($resetToken),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'message'     => 'OTP valid.',
            'reset_token' => $resetToken,
        ]);
    }

    /**
     * Forgot Password - Step 3: Reset password
     */
    public function forgotResetPassword(Request $request)
    {
        $request->validate([
            'email'       => 'required|email',
            'reset_token' => 'required|string',
            'password'    => 'required|string|min:6|confirmed',
        ]);

        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord || !Hash::check($request->reset_token, $resetRecord->token)) {
            return response()->json([
                'message' => 'Permintaan reset tidak valid. Ulangi dari awal.'
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'Email pengguna tidak ditemukan.'
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Password berhasil diperbarui. Silakan login dengan password baru.',
        ]);
    }
    private function claimActivationCacheKey(int $memberId): string
    {
        return 'claim_activation_otp_' . $memberId;
    }

    private function publicRegisterCacheKey(string $email): string
    {
        return 'public_register_otp_' . strtolower($email);
    }

    private function normalizeIndonesianPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        if (str_starts_with($phone, '8')) {
            return '62' . $phone;
        }

        return $phone;
    }
    private function maskPhoneNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        $length = strlen($digits);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($digits, 0, 4) . str_repeat('*', max(0, $length - 7)) . substr($digits, -3);
    }
}
