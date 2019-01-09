<?php

require_once(ROOT.DS.'libs/mail/PHPMailer/PHPMailerAutoload.php');

class EnviarMail {

    public static function send($from, $fromsub, $mails, $mensaje, $titulo) {
        $mails[] = 'jarancibia@cygtel.com';
        try {

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->SMTPDebug = 0;
            $mail->Host = "10.68.143.121";
            $mail->Port = 25;

            $mail->setFrom($from, $fromsub);
            //$mail->addReplyTo($correo);
            $mail->isHTML(true);
            $mail->Subject = $titulo;

            foreach ($mails as $key => $value) {
                $mail->AddAddress($value);
            }

            $mail->Body    = $mensaje;
            $mail->CharSet = 'utf-8';
            $r = $mail->send();
        } catch (phpmailerException $e) {  $r = $e->errorMessage(); }
        
        return ($r);
    }
}