<?php

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    function testEmail() {
        $sended = sendEmail("Test Email", "This is a test email from Proxmox Syncer for by Karlosoft.");
        if ($sended) {
            successMessage("Test Email sended successfully.");
        } else {
            errorMessage("Test Email sending error.");
        }
    }

    function sendEmail($subject, $data) {
        $mail = new PHPMailer();

        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host       = EMAIL_HOST;
        $mail->Port       = EMAIL_PORT;

        //$mail->SMTPDebug  = 1;
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = "ssl";

        $mail->Username   = EMAIL_ADD;
        $mail->From       = EMAIL_ADD;
        $mail->FromName   = EMAIL_NAME;
        $mail->Password   = EMAIL_PASSWD;
        $mail->addAddress(SEND_TO);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $data;
        if (!$mail->send()) {
            errorMessage($mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    } 
?>