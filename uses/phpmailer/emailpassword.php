<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ecoflora - Recuperar Senha</title> 
<link rel="icon" href="logos/logo.ico">
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f0f0f0;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }
    
    .login-container {
        background-color: #fff;
        border-radius: 15px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }
    
    .login-container h2 {
        text-align: center;
    }
    
    .login-container input[type="text"],
    .login-container input[type="password"] {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
    }
    
    .login-container input[type="submit"] {
        width: 100%;
        background-color: #4CAF50;
        color: white;
        padding: 10px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }
    
    .login-container input[type="submit"]:hover {
        background-color: #45a049;
    }
</style>
</head>
<body>
<?php

include 'conexao.php';
include 'funcoes.php';

$emailusr = ($_POST['email']);
$emailserver = 'noreply@ecoflora.eco.br'; 
$senhaserver = 'Ecoflora100@';



$sql = "SELECT IDEUSU, NOMUSU, SENUSU FROM usuario WHERE EMAILUSU = '".$emailusr."';"; // Exemplo: selecionar o nome onde o id é 1

$result = $mysqli->query($sql);
// Verificar se algum resultado foi retornado
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    // script enviado pela localweb - funcionando
    require 'PHPMailerAutoload.php';

    $mail = new PHPMailer;
                       
    //$mail->SMTPDebug = 3;                 // Habilita modo debug na saída
    $mail->isSMTP();                        // Setar o uso do SMTP
    $mail->Host = 'email-ssl.com.br';  	// Servidor smtp 
    $mail->SMTPAuth = true;                 // Habilita a autenticação do form
    $mail->Username = $emailserver;       // Conta de e-mail que realizará o envio
    $mail->Password = $senhaserver;       // Senha da conta de e-mail
    //$mail->SMTPSecure = 'tls';            // Habilitar uso do TLS (plesk 11.5 ou utilizando contas do Gmail)
    $mail->Port = 587;                       // Porta de conexão 
    $mail->From = $emailserver; 			// e-mail From deve ser o mesmo de "username" (contadeEmail)
    $mail->FromName = 'Ecoflora - Atualize sua senha'; 				// Nome que será exibido ao receber a mensagem. 
    $mail->addAddress($emailusr, $row['NOMUSU']); // Destinatário 
    //$mail->addAddress('ellen@example.com');               	// Nome do destinatário
    //$mail->addReplyTo('info@example.com', 'Information');  	//Responder para 
    //$mail->addCC('cc@example.com'); // Adicionar cópia para o recebimento. 
    //$mail->addBCC('bcc@example.com'); // Adicionar cópia oculta para o recebimento.

    //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
    //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
    $mail->isHTML(true);                                  // Set email format to HTML

    $mail->Subject = 'Ecoflora - Atualizar de Senha';  //Assunto da Mensagem

    $mail->Body    = '<h2>Olá '.$row['IDEUSU'].' <h2><br><br>
                     Você esqueceu sua senha de acesso ao portal da Ecoflora.eco.br<br>
                     Clique no link abaixo para <b>atualizar sua senha</b><br>
                     <p>https://www.ecoflora.eco.br/desktop/atualizarsenha.php?id='.$row['IDEUSU'].'&pmd5='.$row['SENUSU'].'</p><br><br>
                     <i>EcoFlora Preservação Ambiental</i>'; 
    //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
    if(!$mail->send()) {
        echo '<p>Message could not be sent.</p>';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
    } else {
        echo '<p>Mensagem enviada com sucesso para <b>'.$emailusr.'</b><br> verifique a caixa de spam - <a href="../login.php"> Login </a></p>';
    }

} else {
    echo '<p>Email não cadastrado <a href="../index.php"> Inicio </a></p>';
}

?>
</body>
</html>


