<?php

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    function testEmail() {
        $recipients = explode(",", SEND_TO);
        $sended = sendEmail("Test Email", "This is a test email from Proxmox Syncer by Karlosoft.", $recipients);
        if ($sended) {
            successMessage("Test Email sended successfully.");
        } else {
            errorMessage("Test Email sending error.");
        }
    }

    function sendReport() {
        global $syncStats;
        if ((SEND_EMAIL == "no") || (SEND_EMAIL == "on_error" && $syncStats["isok"])) {
            return;
        }

        $syncStats["time"] = gmdate("H:i:s", $syncStats["time"]);

        if ($syncStats["isok"]) {
            $subject = "✅ Proxmox Syncer Report";
            $data = "Proxmox Syncer ended <b style='color:green;'>successfully</b>.<br><br>";
        } else {
            $subject = "❌ WARNING: Proxmox Syncer ended with errors";
            $data = "Proxmox Syncer ended with <b style='color:red;'>errors</b>.<br><br>";
        }

        $data .= "Started: " . $syncStats["started"] . "<br>";
        $data .= "Duration: " . $syncStats["time"] . "<br>";
        $data .= "Detected Files: " . $syncStats["detected"] . "<br>";
        $data .= "Lost Files: " . $syncStats["lost"] . "<br>";
        $data .= "Uploaded Files: " . $syncStats["uploaded"] . "<br>";
        $data .= "Deleted Files: " . $syncStats["deleted"] . "<br>";

        if (!$syncStats["isok"]) {
            $data .= "<b>Errors:</b> <br>";
            foreach ($syncStats["errors"] as $error) {
                $data .= "    " . $error . "<br>";
            }
        }

        $data .= "<br><br><br>This is an automatic email from Proxmox Syncer by Karlosoft.";

        $recipients = explode(",", SEND_TO);
        $sended = sendEmail($subject, $data, $recipients);
        if ($sended) {
            successMessage("Report Email sended successfully.");
        } else {
            errorMessage("Report Email sending error.");
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