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
        global $emailStats;
        if (SEND_EMAIL == "no") {
            return;
        }
        if (SEND_EMAIL == "on_error" && $emailStats["isok"]) {
            return;
        }

        //convert time to h:m:s
        $emailStats["time"] = gmdate("H:i:s", $emailStats["time"]);

        if ($emailStats["isok"]) {
            $subject = "Proxmox Syncer Report";
            $data = "Proxmox Syncer ended <b style='color:green;'>successfully</b>.<br><br>";
            $data .= "Time: " . $emailStats["time"] . "<br>";
            $data .= "Detected files: " . $emailStats["detected"] . "<br>";
            $data .= "Lost files: " . $emailStats["lost"] . "<br>";
            $data .= "Uploaded files: " . $emailStats["uploaded"] . "<br>";
            $data .= "Deleted files: " . $emailStats["deleted"] . "<br>";
        } else {
            $subject = "CAUTION: Proxmox Syncer ended with errors";
            $data = "Proxmox Syncer ended with <b style='color:red;'>errors</b>.<br><br>";
            $data .= "Time: " . $emailStats["time"] . "<br>";
            $data .= "Detected files: " . $emailStats["detected"] . "<br>";
            $data .= "Lost files: " . $emailStats["lost"] . "<br>";
            $data .= "Uploaded files: " . $emailStats["uploaded"] . "<br>";
            $data .= "Deleted files: " . $emailStats["deleted"] . "<br><br>";
            $data .= "<b>Errors:</b> <br>";
            foreach ($emailStats["errors"] as $error) {
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
        } else {
            return true;
        }
    } 
?>