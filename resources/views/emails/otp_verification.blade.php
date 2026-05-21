<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifikasi Akun Perpustakaan</title>
</head>
<body style="margin:0; padding:0; background-color:#F0F7F4; font-family:'Segoe UI', Arial, sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#F0F7F4; padding:48px 0;">
    <tr>
      <td align="center">
        <table width="500" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:24px; overflow:hidden; box-shadow:0 8px 32px rgba(43,90,65,0.10);">

          <!-- ── Header ── -->
          <tr>
            <td style="background:linear-gradient(135deg,#2B5A41 0%,#3D8B64 60%,#52A87C 100%); padding:40px 40px 32px; text-align:center;">
              <!-- Icon -->
              <div style="display:inline-block; background:rgba(255,255,255,0.15); border-radius:50%; width:64px; height:64px; line-height:64px; text-align:center; margin-bottom:16px;">
                <span style="font-size:30px;">✉️</span>
              </div>
              <p style="margin:0 0 6px; font-size:12px; color:rgba(255,255,255,0.65); letter-spacing:3px; font-weight:700; text-transform:uppercase;">Perpustakaan Digital</p>
              <h1 style="margin:0; font-size:20px; color:#ffffff; font-weight:800; letter-spacing:0.5px;">SMA NEGERI 4 JEMBER</h1>
            </td>
          </tr>

          <!-- ── Badge Tujuan ── -->
          <tr>
            <td style="text-align:center; padding:0;">
              <span style="display:inline-block; margin-top:-14px; background:#ffffff; border:2px solid #2B5A41; color:#2B5A41; font-size:11px; font-weight:700; letter-spacing:2px; text-transform:uppercase; padding:5px 18px; border-radius:20px;">
                @if($purpose === 'register')
                  Verifikasi Pendaftaran
                @else
                  Verifikasi Aktivasi Akun
                @endif
              </span>
            </td>
          </tr>

          <!-- ── Body ── -->
          <tr>
            <td style="padding:32px 40px 28px;">

              <p style="margin:0 0 6px; font-size:16px; color:#1A1A1A; font-weight:700;">Halo, {{ $name }}! 👋</p>
              <p style="margin:0 0 28px; font-size:14px; color:#6B7280; line-height:1.7;">
                @if($purpose === 'register')
                  Terima kasih telah mendaftar di Perpustakaan Digital. Gunakan kode di bawah untuk menyelesaikan pendaftaran akun Anda.
                @else
                  Kami menerima permintaan aktivasi akun perpustakaan Anda. Gunakan kode verifikasi di bawah untuk mengaktifkan akun.
                @endif
              </p>

              <!-- OTP Box -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                <tr>
                  <td align="center" style="background:linear-gradient(135deg,#F0FBF5,#E8F5EE); border-radius:18px; border:2px dashed #6BBE96; padding:28px 20px;">
                    <p style="margin:0 0 10px; font-size:11px; color:#4A8B64; font-weight:700; letter-spacing:3px; text-transform:uppercase;">Kode Verifikasi OTP</p>
                    <p style="margin:0; font-size:48px; font-weight:900; letter-spacing:12px; color:#2B5A41; font-family:'Courier New', Courier, monospace; text-shadow:0 2px 4px rgba(43,90,65,0.12);">{{ $otp }}</p>
                    <p style="margin:12px 0 0; font-size:12px; color:#6B7280;">
                      ⏱&nbsp; Berlaku selama <strong style="color:#2B5A41;">10 menit</strong>
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Tips -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                <tr>
                  <td style="background:#FFF8E1; border-radius:12px; border-left:4px solid #F59E0B; padding:14px 16px;">
                    <p style="margin:0; font-size:13px; color:#92400E; line-height:1.6;">
                      🔒 <strong>Keamanan:</strong> Jangan berikan kode ini kepada siapa pun, termasuk petugas perpustakaan.
                    </p>
                  </td>
                </tr>
              </table>

              <hr style="border:none; border-top:1px solid #F3F4F6; margin:0 0 20px;">

              <p style="margin:0; font-size:12px; color:#9CA3AF; line-height:1.7;">
                @if($purpose === 'register')
                  Jika Anda tidak merasa mendaftar, abaikan email ini. Tidak ada tindakan lebih lanjut yang diperlukan.
                @else
                  Jika Anda tidak merasa mengajukan aktivasi akun ini, segera hubungi petugas perpustakaan.
                @endif
              </p>
            </td>
          </tr>

          <!-- ── Footer ── -->
          <tr>
            <td style="background:#F9FAFB; padding:20px 40px 24px; text-align:center; border-top:1px solid #F3F4F6;">
              <p style="margin:0 0 4px; font-size:13px; color:#374151; font-weight:600;">Perpustakaan Digital SMA Negeri 4 Jember</p>
              <p style="margin:0; font-size:11px; color:#9CA3AF;">© {{ date('Y') }} · Email ini dikirim otomatis, jangan membalas email ini.</p>
            </td>
          </tr>

        </table>

        <!-- Disclaimer bawah -->
        <p style="margin:20px 0 0; font-size:11px; color:#9CA3AF; text-align:center;">
          Kode ini diminta dari sistem perpustakaan digital kami.
        </p>
      </td>
    </tr>
  </table>

</body>
</html>
