<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 40px 0;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; border: 1px solid #e9ecef;">
        <tr>
            <td style="padding: 20px; text-align: center; background-color: #198754; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                <h1 style="color: #ffffff; margin: 0; font-size: 20px;">E-BoardMate</h1>
            </td>
        </tr>
        <tr>
            <td style="padding: 30px; color: #212529; line-height: 1.6;">
                <p>Hello <strong>{{ $reservation->guest_name }}</strong>,</p>
                <p>We have successfully received your reservation request for <strong>{{ $boardingHouse->name }}</strong>. It is currently pending review by the landlord.</p>
                
                <div style="background-color: #f4fbf7; border: 1px solid #d1e7dd; border-radius: 6px; padding: 20px; text-align: center; margin: 20px 0;">
                    <span style="font-size: 12px; color: #198754; font-weight: bold; text-transform: uppercase;">Your Tracking Code</span><br>
                    <strong style="font-size: 24px; color: #0f5132; letter-spacing: 2px;">{{ $reservation->reference_code }}</strong>
                </div>

                <p>Please save this code. You can use it at any time on our website to track the status of your reservation.</p>
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="{{ url('/track-reservation') }}" style="background-color: #198754; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">Track Reservation Now</a>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>