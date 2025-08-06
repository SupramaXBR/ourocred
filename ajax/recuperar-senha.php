<?php

include_once('../uses/conexao.php');
include_once('../uses/funcoes.php');
require '../uses/phpmailer/PHPMailerAutoload.php';

// Recebe os dados da requisição
$resposta = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['mensagem' => 'Erro no JSON recebido.']);
    exit;
}

// Verifica se o CPF foi enviado
if (!isset($resposta['cpfcli']) || empty($resposta['cpfcli'])) {
    echo json_encode(['mensagem' => 'CPF não informado.']);
    exit;
}

$cpfcli = $resposta['cpfcli'];
$msgsmtp = 'vazio';

try {
    // Consulta ao banco para verificar o CPF
    $sql = "SELECT email, nomcli, idecli, md5pw FROM clientes WHERE cpfcli = :cpfcli";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':cpfcli', $cpfcli, PDO::PARAM_STR);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        $email  = $cliente['email'];
        $nomcli = $cliente['nomcli'];
        $idecli = $cliente['idecli'];
        $md5pw  = $cliente['md5pw'];
        
        $emailusr = $email;

        $emailserver = 'naoresponda@ourocreddtvm.com.br'; 
        $senhaserver = 'Ourocred@100';

        // Instância do PHPMailer
        $mail = new PHPMailer(true);

        // Configurações de envio
        //$mail->SMTPDebug = 3;                 // Modo debug (opcional para testes)
        $mail->isSMTP();                        // Usar SMTP
        $mail->Host = 'email-ssl.com.br';      // Servidor SMTP
        $mail->SMTPAuth = true;                // Autenticação habilitada
        $mail->Username = $emailserver;        // E-mail remetente
        $mail->Password = $senhaserver;        // Senha do remetente
        $mail->Port = 587;                     // Porta SMTP (587 ou 465)
        $mail->CharSet = 'UTF-8';              // <-- ESSENCIAL PARA ACENTOS

        $mail->setFrom($emailserver, 'OuroCred - Confirme seu e-Mail');
        $mail->addAddress($vsEmlCli, $vsNomCli); // Destinatário
        $mail->isHTML(true);
        $mail->Subject = 'OuroCred - Recuper/Alterar Senha';  //Assunto da Mensagem

        $mail->Body    = '<!DOCTYPE html>
                            <html lang="pt-BR">
                            <head>
                                <meta charset="UTF-8">
                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                <title>Recuperação de Senha</title>
                                <style>
                                    body {
                                    font-family: Arial, sans-serif;
                                    background-color: #f9f9f9;
                                    margin: 0;  
                                    padding: 0;
                                }
                                .email-container {
                                    max-width: 600px;
                                    margin: 20px auto;
                                    background-color: #ffffff;
                                    border: 1px solid #ddd;
                                    border-radius: 8px;
                                    overflow: hidden;
                                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                                }
                                .email-header {
                                    background-color: #0066ff;
                                    color: #ffffff;
                                    padding: 20px;
                                    text-align: center;
                                }
                                .email-header img {
                                    max-height: 50px;
                                    margin-bottom: 10px;
                                }
                                .email-body {
                                    padding: 20px;
                                    color: #333333;
                                }
                                .email-body h1 {
                                    font-size: 20px;
                                    margin-bottom: 10px;
                                }
                                .email-body p {
                                    font-size: 16px;
                                    line-height: 1.5;
                                }
                                .email-footer {
                                    text-align: center;
                                    margin: 20px 0;
                                }
                                .btn-reset {
                                    display: inline-block;
                                    padding: 10px 20px;
                                    font-size: 16px;
                                    color: #ffffff;
                                    background-color: #0066ff;
                                    text-decoration: none;      
                                    border-radius: 5px;
                                }
                                .btn-reset:hover {
                                    background-color: #0056cc;
                                }
                                </style>
                            </head>
                        <body>
                            <table class="email-container">
                                <tr>
                                    <td class="email-header">
                                        <img src="https://ourocreddtvm.com.br/imagens/emaillogo.png" alt="Logo da OuroCred">
                                        <h2>Recuperação de Senha</h2>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="email-body">
                                        <h1>Olá, '.$nomcli.'!</h1>
                                        <p>Recebemos uma solicitação para redefinir sua senha na OuroCred. Se você fez esta solicitação, clique no botão abaixo para criar uma nova senha.</p>
                                        <div class="email-footer">
                                            <a href="https://ourocreddtvm.com.br/recuperarsenha/mudarsenha.php?ide='.$idecli.'&hash='.$md5pw.'" style="color: white;" class="btn-reset">Redefinir Senha</a>
                                        </div>
                                        <p>Se você não solicitou a redefinição de senha, ignore este e-mail. Sua conta permanecerá segura.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: center; padding: 10px; font-size: 12px; color: #888;">
                                        <p>OuroCred DTVM LTDA<br>Várzea Grande, Mato Grosso</p>
                                        <p>Este é um e-mail automático, por favor, não responda.</p>
                                    </td>
                                </tr>
                            </table>
                        </body>
                    </html>'; 

        //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        if(!$mail->send()) {
            $msgsmtp = $mail->ErrorInfo;
        } else {
            $msgsmtp = 'sucesso';
        }

        
    } else {
        $email = "nao-cadastrado";       
        $msgsmtp = 'nao enviou o emai';
    }

    // Resposta em JSON
    $response = [
        'mensagem' => $email,
        'msgsmtp'  => $msgsmtp,
        'staenv'   => 'S'
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (PDOException $e) {
    // Tratamento de erro na consulta
    $response = ['mensagem' => 'Erro ao acessar o banco de dados: ' . $e->getMessage(),
                 'msgsmtp'  => $msgsmtp,
                 'staenv'   => 'N'];
    header('Content-Type: application/json', true, 500);
    echo json_encode($response);
}

?>