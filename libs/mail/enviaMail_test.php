<?php

require_once('PHPMailer/PHPMailerAutoload.php');

class EnviarMail {

    public static function send($from, $fromsub, $mails, $mensaje, $titulo) {
        
        //$body = file_get_contents("http://10.30.17.81/site_media/html/core/mail_datafill.html");
        //echo $body;
        $body = "Hello, Quitar del spam para recibir los correos :)";
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

            $mail->Body    = $body;
            $mail->CharSet = 'utf-8';
            $r = $mail->send();
        } catch (phpmailerException $e) {  $r = $e->errorMessage(); }
        var_dump($r);
        return ($r);
    }
}

$mails[] = 'jarancibia@cygtel.com';
$mails[] = 'oswaldo.espana@osctelecoms.com';
EnviarMail::send('test@entel.pe','TEST',$mails,'TEST','TEST');