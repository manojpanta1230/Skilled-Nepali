<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';

function send_mail($to, $subject, $body, $altBody = '') {
    $mail = new PHPMailer(true);
    try {
        // SMTP Settings
        $mail->isSMTP();
        $mail->Host       = 'skillednepali.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'inquiry@skillednepali.com';
        $mail->Password   = 'adgjl@900';
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';

        // Recipients
        $mail->setFrom('inquiry@skillednepali.com', 'Skilled Nepali');
        $mail->addReplyTo('inquiry@skillednepali.com', 'Support - Skilled Nepali');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        // Anti-Spam: Always provide a text-only version
        if ($altBody) {
            $mail->AltBody = $altBody;
        } else {
            // Automatically generate plain text from HTML
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>'], ["\n", "\n", "\n\n"], $body));
        }

        // Additional Headers for Deliverability
        $mail->XMailer = 'Skilled Nepali Mailer';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
