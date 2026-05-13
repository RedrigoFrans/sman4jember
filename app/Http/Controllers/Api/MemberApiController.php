<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MemberApiController extends Controller
{
    /**
     * POST /api/v1/member/fcm-token
     */
    public function storeFcmToken(Request $request)
    {
        $request->validate([
            'token'  => 'required|string',
            'device' => 'nullable|string|max:50',
        ]);

        $user = $request->user();

        FcmToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id' => $user->id,
                'device'  => $request->device ?? 'unknown',
            ]
        );

        return response()->json(['message' => 'FCM token saved']);
    }

    /**
     * GET /api/v1/member/profile
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load('member.kelas');
        return response()->json(['user' => $user]);
    }

    /**
     * PUT /api/v1/member/profile
     * Update nomor HP saja (JSON body).
     */
    public function updateProfile(Request $request)
    {
        $user   = $request->user();
        $member = $user->member;

        if (!$member) {
            return response()->json(['message' => 'Member tidak ditemukan'], 404);
        }

        // Validasi: bisa handle JSON maupun multipart
        $request->validate([
            'phone'  => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = [];

        // Ambil phone dari JSON atau multipart
        $phone = $request->input('phone');
        if (!empty($phone)) {
            $data['phone'] = $phone;
        }

        // Handle file upload avatar
        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            if ($member->photo && Storage::disk('public')->exists($member->photo)) {
                Storage::disk('public')->delete($member->photo);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['photo'] = $path;
        }

        if (!empty($data)) {
            $member->update($data);
        }

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user'    => $user->fresh()->load('member.kelas'),
        ]);
    }

    /**
     * POST /api/v1/member/change-password
     * Ganti password dengan memverifikasi password lama.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password lama tidak sesuai.'],
            ]);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Password berhasil diubah']);
    }

    /**
     * POST /api/v1/member/send-otp
     * Kirim OTP 6 digit ke email user yang sedang login (untuk reset password).
     * OTP disimpan di Cache selama 10 menit.
     */
    public function sendOtp(Request $request)
    {
        $user = $request->user();

        // Generate OTP 6 digit
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Simpan di cache dengan key unik per user, expire 10 menit
        $cacheKey = 'password_otp_' . $user->id;
        Cache::put($cacheKey, $otp, now()->addMinutes(10));

        // Kirim email
        try {
            Mail::html(
                view('emails.otp', ['otp' => $otp, 'user' => $user])->render(),
                function ($message) use ($user) {
                    $message->to($user->email, $user->name)
                            ->subject('Kode OTP Reset Password — SMA Negeri 4 Jember');
                }
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengirim email OTP. Coba lagi nanti.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json([
            'message' => 'Kode OTP telah dikirim ke ' . $user->email,
            'email'   => $user->email,
        ]);
    }

    /**
     * POST /api/v1/member/reset-password-otp
     * Verifikasi OTP dan set password baru (tanpa perlu password lama).
     */
    public function resetPasswordWithOtp(Request $request)
    {
        $request->validate([
            'otp'          => 'required|string|size:6',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user     = $request->user();
        $cacheKey = 'password_otp_' . $user->id;
        $stored   = Cache::get($cacheKey);

        if (!$stored) {
            return response()->json(['message' => 'Kode OTP sudah kadaluarsa. Minta OTP baru.'], 422);
        }

        if ($stored !== $request->otp) {
            return response()->json(['message' => 'Kode OTP tidak valid.'], 422);
        }

        // OTP valid: hapus dari cache dan update password
        Cache::forget($cacheKey);
        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Password berhasil diubah dengan OTP.']);
    }
}
