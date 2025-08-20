<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $subject ?? 'Pick-a-part.ca Email' }}</title>
  <link rel="icon" href="https://pick-a-part.ca/email/email_ca_logo.png" type="image/x-icon" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>
<body style="background-color: #fff3e0; font-family: Arial, sans-serif; margin: 0; padding: 0;">
  <div style="max-width: 600px; margin: auto; background-color: #fcfcfc; padding: 40px; border-radius: 8px;">
    <!-- Header -->
    <div style="text-align: center; margin-bottom: 20px;">
      <img src="https://pick-a-part.ca/email/email_ca_logo.png" alt="Pick-a-Part Logo" style="height: 50px;" />
    </div>

    <!-- Greeting -->
    <h4>Hi {{ $name ?? 'there' }},</h4>
    <h2>{{ $subject ?? 'Your Pick-a-part.ca Verification Code' }}</h2>
    <p>Thank you for creating your <strong>Pick-a-part.ca</strong> account. Please enter the following code to verify your email address:</p>

    <!-- Verification Code -->
    <div style="text-align: center; margin: 20px 0;">
      <div style="display: inline-block; background-color: #fff; padding: 12px 24px; font-size: 19px; font-weight: bold; border: 1px solid #ccc; border-radius: 6px;">
        {{ implode('', $data['data']) ?? 'XXXXXX' }}
      </div>
    </div>

    <p>Please do not share this code with anyone.</p>
    <p>If you have any issues with verification, please contact us at <a href="mailto:support@pick-a-part.ca">support@pick-a-part.ca</a>.</p>

    <!-- Sign-off -->
    <div style="margin-top: 30px;">
      <p>Cheers,</p>
      <p>PickAPart Canada</p>
    </div>

    <!-- App Badges -->
    <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;" />
    <div style="text-align: center;">
      <img src="https://sleekdxb.com/wp-content/uploads/2024/12/Apple-Badge.png" alt="Download on Apple Store" title="Download on Apple Store" style="height: 40px; margin-right: 10px;" />
      <img src="https://sleekdxb.com/wp-content/uploads/2024/12/Google-Badge.png" alt="Get it on Google Play" title="Get it on Google Play" style="height: 40px;" />
    </div>

    <!-- Social Links -->
    <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;" />
    <div style="text-align: center;">
      <a href="#" style="margin: 0 8px;"><img src="https://sleekdxb.com/wp-content/uploads/2024/12/Inst.png" alt="Instagram" style="height: 30px;" /></a>
      <a href="#" style="margin: 0 8px;"><img src="https://sleekdxb.com/wp-content/uploads/2024/12/twitter.png" alt="Twitter" style="height: 30px;" /></a>
      <a href="#" style="margin: 0 8px;"><img src="https://sleekdxb.com/wp-content/uploads/2024/12/FB.png" alt="Facebook" style="height: 30px;" /></a>
    </div>

    <!-- Footer -->
    <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;" />
    <footer style="text-align: center; font-size: 12px; color: #888;">
      <p>&copy; {{ date('Y') }} Pick-a-part.ca. All rights reserved.</p>
      <div style="margin-top: 10px;">
        <a href="https://pick-a-part.ca/platformSpecificPrivacyPolicyScreen" style="margin: 0 10px; color: #888;">Privacy Policy</a> |
        <a href="https://pick-a-part.ca/platformSpecificTermsAndConditionsScreen" style="margin: 0 10px; color: #888;">Terms of Service</a> |
        <a href="#" style="margin: 0 10px; color: #888;">Help Center</a>
      </div>
    </footer>
  </div>
</body>
</html>
