<?php

namespace Application\Utils;

use PHPMailer;

class MailService {
    public static function send($from, $fromsub, $mails, $body, $subject) {
        $mails[] = 'jonathan.arancibia@entel.pe';
        try {

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->SMTPDebug = 0;
            $mail->Host = "10.68.143.121";
            $mail->Port = 25;

            $mail->setFrom($from, $fromsub);
            //$mail->addReplyTo($correo);
            $mail->isHTML(true);
            $mail->Subject = $subject;

            foreach ($mails as $key => $value) {
                $mail->AddAddress($value);
            }

            $mail->Body    = $body;
            $mail->CharSet = 'utf-8';
            $r = $mail->send();
        } catch (phpmailerException $e) {  $r = $e->errorMessage(); }

        return ($r);
    }
}