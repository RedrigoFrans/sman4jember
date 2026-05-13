<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kode OTP Reset Password</title>
</head>
<body style="margin:0; padding:0; background-color:#F7FAF8; font-family: 'Segoe UI', Arial, sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#F7FAF8; padding:40px 0;">
    <tr>
      <td align="center">
        <table width="480" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:20px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.06);">
          
          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(135deg,#2B5A41,#4A8B64); padding:36px 40px 28px; text-align:center;">
              <p style="margin:0 0 8px; font-size:13px; color:rgba(255,255,255,0.7); letter-spacing:2px; font-weight:600; text-transform:uppercase;">Perpustakaan Digital</p>
              <h1 style="margin:0; font-size:22px; color:#ffffff; font-weight:700; letter-spacing:0.5px;">SMA NEGERI 4 JEMBER</h1>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:36px 40px;">
              <p style="margin:0 0 6px; font-size:16px; color:#1E1E1E; font-weight:600;">Halo, {{ $user->name }}!</p>
              <p style="margin:0 0 28px; font-size:14px; color:#6B7280; line-height:1.6;">
                Kami menerima permintaan untuk mengubah password akun Anda. Gunakan kode OTP di bawah ini:
              </p>

              <!-- OTP Box -->
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="padding:24px; background:#F0F9F4; border-radius:16px; border:2px dashed #A8D5B8;">
                    <p style="margin:0 0 8px; font-size:12px; color:#679B7B; font-weight:700; letter-spacing:2px; text-transform:uppercase;">Kode Verifikasi Anda</p>
                    <p style="margin:0; font-size:44px; font-weight:800; letter-spacing:10px; color:#2B5A41; font-family:'Courier New', monospace;">{{ $otp }}</p>
                  </td>
                </tr>
              </table>

              <p style="margin:24px 0 0; font-size:13px; color:#9CA3AF; text-align:center;">
                ⏱ Kode ini berlaku selama <strong style="color:#2B5A41;">10 menit</strong>
              </p>

              <!-- Divider -->
              <hr style="border:none; border-top:1px solid #F3F4F6; margin:28px 0;">

              <p style="margin:0; font-size:13px; color:#9CA3AF; line-height:1.6;">
                Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini. Password Anda tidak akan berubah tanpa kode OTP ini.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:20px 40px 28px; text-align:center; border-top:1px solid #F3F4F6;">
              <p style="margin:0; font-size:12px; color:#9CA3AF;">
                © {{ date('Y') }} Perpustakaan Digital SMA Negeri 4 Jember
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
