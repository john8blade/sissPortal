<?php

function sendMail($assunto, $msg, $emailremetente, $nomeRemetente, $destino, $nomeDestino)
{
    include_once 'class.phpmailer.php';

    $mail = new PHPMailer(); //INICIA A CLASSE

    $mail->SMTPDebug = 3; # 0: off; 1: client; 2: 1+server, 3: 2+connection

    $mail->IsSMTP(); //Habilita envio SMPT
    $mail->SMTPAuth = true; //Ativa email autenticado
    $mail->Host = 'smtp.office365.com'; //Servidor de envio smtp.bhz.terra.com.br
    $mail->Port = '587'; //Porta de envio
    $mail->SMTPSecure = 'tls'; // TLS

    $mail->Username = 'ti.suporte@hiest.com.br'; //email para smtp autenticado
    $mail->Password = 'HsT022018'; //seleciona a porta de envio

    $mail->From = "passaporteindustrial@httec.com.br";
    $mail->FromName = utf8_decode($nomeRemetente); //remetene nome

    $mail->IsHTML(true);

    $mail->Subject = utf8_decode($assunto); //assunto
    $mail->Body = utf8_decode($msg); //mensagem

    $mail->AddAddress($destino, utf8_decode($nomeDestino)); //email e nome do destino

    ob_start();
    $enviado = $mail->Send();
    $debugoutput = ob_get_clean();
    if ($enviado) {
        return "";
    } else {
        if (!is_dir("logs")) {
            mkdir("logs", 0777);
        }

        $logfile = "logs/mail" . date("YmdHis") . ".log";
        $debugoutput = str_ireplace(['<br />', '<br>', '<br/>', '<br >'], "", $debugoutput);
        $message = '[' . date('Y-m-d H:i:s') . '] ' . USUARIO_NOME . ' Device: ' . $_SERVER['HTTP_USER_AGENT'] . "<br>";
        $file = file_put_contents($logfile, str_ireplace(['<br />', '<br>', '<br/>', '<br >', "\r\n\r\n"], "\r\n", ($message . $debugoutput)));
        return "Não foi possível liberar passaportes. Houve um erro ao tentar enviar os e-mails: <a href='/{$logfile}' target='_blank'>Detalhes Técnicos</a>";
    }
}

#function enviarEmail($assunto, $mensagem, $remetente, $remetenteNome, $destinatarios)
function enviarEmail($assunto, $mensagem, $remetente, $destinatarios)
{

    include_once 'class.phpmailer.php';

    $mail = new PHPMailer();

        $mail->IsSMTP();
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->SMTPDebug = 3; # 0: off; 1: client; 2: 1+server, 3: 2+connection

        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';

        $mail->Host = 'smtp.office365.com';
        $mail->Port = '587';
        $mail->Username = 'naoresponda@httec.com.br';
        $mail->Password = 'Pux42261';
        
        $mail->From = utf8_decode($remetente[0]);
        $mail->FromName = utf8_decode($remetente[1]);        

        $mail->IsHTML(true);

        $mail->Subject = utf8_decode($assunto);
        $mail->Body = utf8_decode($mensagem);

        foreach ($destinatarios as $email => $nome):
            $mail->AddAddress($email, utf8_decode($nome));
        endforeach;

        if (!$mail->send()) {
            echo 'PHPMailer Erro: ' . $mail->ErrorInfo;
        } else {
            echo 'O email foi enviado.';
        }

}