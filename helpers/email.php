<?php

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    function testEmail() {
        $recipients = explode(",", SEND_TO);
        $sent = sendEmail("Test email", "This is a test email from Proxmox FTP Syncer by Karlosoft.", $recipients);
        if ($sent) {
            successMessage("Test email sent successfully.");
        } else {
            errorMessage("Failed to send the test email.");
        }
    }

    function sendReport() {
        global $syncStats;
        if ((SEND_EMAIL == "no") || (SEND_EMAIL == "on_error" && $syncStats["isok"])) {
            return;
        }

        $syncStats["time"] = gmdate("H:i:s", $syncStats["time"]);

        if ($syncStats["isok"]) {
            $subject = "✅ Proxmox FTP Syncer report";
            $data = "Proxmox FTP Syncer finished <b style='color:green;'>successfully</b>.<br><br>";
        } else {
            $subject = "❌ WARNING: Proxmox FTP Syncer finished with errors";
            $data = "Proxmox FTP Syncer finished with <b style='color:red;'>errors</b>.<br><br>";
        }

        $data .= "Started: " . $syncStats["started"] . "<br>";
        $data .= "Duration: " . $syncStats["time"] . "<br>";
        $data .= "Detected files: " . $syncStats["detected"] . "<br>";
        $data .= "Lost files: " . $syncStats["lost"] . "<br>";
        $data .= "Uploaded files: " . $syncStats["uploaded"] . "<br>";
        $data .= "Deleted files: " . $syncStats["deleted"] . "<br>";

        if (!$syncStats["isok"]) {
            $data .= "<b>Errors:</b> <br>";
            foreach ($syncStats["errors"] as $error) {
                $data .= "    " . $error . "<br>";
            }
        }

        $data .= "<br><br><br>This is an automated email from Proxmox FTP Syncer by Karlosoft.";

        $recipients = explode(",", SEND_TO);
        $sent = sendEmail($subject, $data, $recipients);
        if ($sent) {
            successMessage("Report email sent successfully.");
        } else {
            errorMessage("Failed to send the report email.");
        }
    }

    function sendEmail($subject, $data, $recipients) {
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
        foreach ($recipients as $recipient) {
            $mail->addAddress($recipient);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $data;
        if (!$mail->send()) {
            errorMessage($mail->ErrorInfo);
            return false;
        }

        return true;
    } 
?>
