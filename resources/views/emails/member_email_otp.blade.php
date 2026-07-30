<!DOCTYPE html>
<html lang="ur">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0;">
    <tr><td align="center">
      <table role="presentation" width="440" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);">
        <tr><td style="background:#1a3d7c;padding:20px 28px;">
          <div style="color:#ffffff;font-size:18px;font-weight:bold;">Mess Billing</div>
        </td></tr>
        <tr><td style="padding:28px;">
          @if($memberName)
            <p style="margin:0 0 12px;color:#333;font-size:15px;">Assalam-o-Alaikum {{ $memberName }},</p>
          @endif
          <p style="margin:0 0 20px;color:#555;font-size:14px;line-height:1.6;">
            Aap ki email verify karne ke liye ye code darj karein:
          </p>
          <div style="text-align:center;margin:8px 0 20px;">
            <span style="display:inline-block;background:#f0f4fa;color:#1a3d7c;font-size:32px;font-weight:bold;letter-spacing:8px;padding:14px 24px;border-radius:10px;">{{ $otp }}</span>
          </div>
          <p style="margin:0 0 6px;color:#888;font-size:13px;line-height:1.6;">
            Ye code <strong>{{ $validMinutes }} minute</strong> me expire ho jayega.
          </p>
          <p style="margin:0;color:#888;font-size:13px;line-height:1.6;">
            Agar aap ne ye request nahi kiya, is email ko ignore karein.
          </p>
        </td></tr>
        <tr><td style="background:#fafafa;padding:16px 28px;border-top:1px solid #eee;">
          <p style="margin:0;color:#aaa;font-size:12px;">Ye ek automated email hai, is ka jawab na dein.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
