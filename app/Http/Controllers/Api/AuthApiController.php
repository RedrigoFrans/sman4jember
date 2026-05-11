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
        $data = $request->validate([
            'member_id' => 'required|exists:members,id',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:6|confirmed',
        ]);

        $member = Member::where('id', $data['member_id'])->whereNull('user_id')->first();

        if (!$member) {
            return response()->json([
                'message' => 'Anggota tidak valid atau sudah diaktivasi.'
            ], 400);
        }

        $user = User::create([
            'name'     => $member->name,
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => 'anggota',
        ]);

        $member->update([
            'user_id'     => $user->id,
            'status'      => 'aktif', // Langsung aktif
            'verified_at' => now(),
        ]);

        $user->assignRole('anggota');

        // Buat token langsung login
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Akun berhasil diaktivasi.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('member.kelas')
        ]);
    }

    /**
     * Forgot Password - Step 1: Kirim OTP ke email
     */
    public function forgotSendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email tidak terdaftar di sistem kami.'
            ], 404);
        }

        $otp = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token'      => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        Mail::to($user->email)->send(new OtpMail($otp, $user->name));

        return response()->json([
            'message' => 'Kode OTP telah dikirim ke email Anda.',
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
}
