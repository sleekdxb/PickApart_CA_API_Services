<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <link rel="icon" href="https://pick-a-part.ca/email/email_ca_logo.png" type="image/x-icon">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body style="background-color: rgba(255, 227, 179, 0.24);">
    <div class="container" style="container" style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 50px; background-color: rgb(252, 252, 252)">
        <div style=" margin-bottom: 20px;">
            <img src="https://sleekdxb.com/wp-content/uploads/2024/12/Pickapart_logo_small.png" alt="PickaPart Logo" style="height: 50px; width: auto;">
        </div>
        <h4>Hi, {{ $name }},</h4>
        <h2>{{ $subject }}</h2>
        <p>{{ $upper_info }}  <span> pick-a-part.ca</span></p>
        <p>{{ $emailMessage }}</p>
        
       <!-- <div style="text-align: center;">
          <form style="text-align: center; item-align: center; margin: 20px 10; text-align: center;">
            <div style="display: flex; justify-content: center; gap: 15px;">
                    <input type="text" value="" style="margin-left:10px; width: 30px; height: 40px; text-align: center; font-size: 18px;" readonly>
            </div>-->
            <!--<button type="button" style="margin-top: 20px; padding: 13px 30px; background-color: #028174-->
            <!--; color: white; border: none; border-radius: 5px; cursor: pointer;">Verify Email</button>-->
        </form>
        </div>

        <p>{{ $but_info }}  <a href="https://pick-a-part.ca/home" target="_blank">Login In To Your Account</a></p>
        <p>If you have any issues with Account, please contact <a href="mailto:support@pick-a-part.ca">support@pick-a-part.ca</a>.</p>

        <div style="margin-top: 30px;">
            <p>Cheers,</p>
            <p>The PickAPart Canda Team</p>
        </div>

        <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">

        <div style="text-align: center;">
            <img src="https://sleekdxb.com/wp-content/uploads/2024/12/Apple-Badge.png" alt="Apple Badge" style="height: 40px; margin-right: 10px;">
            <img src="https://sleekdxb.com/wp-content/uploads/2024/12/Google-Badge.png" alt="Google Badge" style="height: 40px;">
        </div>

        <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">

        <div style="text-align: center;">
            <a href="#" style="margin: 0 10px;"><img src="https://sleekdxb.com/wp-content/uploads/2024/12/Inst.png" alt="Instagram" style="height: 30px;"></a>
            <a href="#" style="margin: 0 10px;"><img src="https://sleekdxb.com/wp-content/uploads/2024/12/twitter.png" alt="Twitter" style="height: 30px;"></a>
            <a href="#" style="margin: 0 10px;"><img src="https://sleekdxb.com/wp-content/uploads/2024/12/FB.png" alt="Facebook" style="height: 30px;"></a>
        </div>

        <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">

        <footer style="text-align: center; font-size: 0.8rem; color: #555;">
            <p>&copy; {{ date('Y') }} Pick-a-part.ca. All rights reserved.</p>
            <p>  </p>
            <div>
                <a href="https://pick-a-part.ca/platformSpecificPrivacyPolicyScreen" style="margin: 0 10px;">Privacy Policy</a>
                <a href="https://pick-a-part.ca/platformSpecificTermsAndConditionsScreen" style="margin: 0 10px;">Terms of Service</a>
                <a href="#" style="margin: 0 10px;">Help Center</a>
               
            </div>
        </footer>
    </div>
</body>
</html>



