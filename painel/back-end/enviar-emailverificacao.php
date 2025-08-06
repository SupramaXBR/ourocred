<?php
    session_start();
    header("Content-Type: application/json; charset=UTF-8");
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("Content-Security-Policy: default-src 'self'");

    include_once('../../uses/conexao.php');
    include_once('../../uses/funcoes.php');
    require '../../uses/phpmailer/PHPMailerAutoload.php';

    if (!$pdo) {
        http_response_code(500);
        die(json_encode(['mensagem' => 'Erro de conexão com o banco de dados.']));
    }
    //referer
    $dominiosPermitidos = [
        'www.ourocreddtvm.com.br',
        'ourocreddtvm.com.br',
        '192.168.18.88' // Ambiente de teste (LAMP)
    ];

    // Verifica se o Referer foi enviado
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);

        // Se o domínio do referer não estiver na lista, bloqueia o acesso
        if (!in_array($referer, $dominiosPermitidos)) {
            error_log("Acesso negado! Origem inválida: $referer");
            die("Acesso negado!");
        }
    } else {   
        die("Atenção: Nenhum Referer detectado!");
    }

    // Lê o JSON recebido
    $jsonData = file_get_contents('php://input');

    $inputJSON = file_get_contents('php://input');

    // Verifica se há dados na requisição
    if (!$jsonData) {
        echo json_encode(['mensagem' => 'Nenhum dado recebido na requisição.']);
        exit;
    }

    $tempoLimite = retornaTempoLimite(1); // definido na tabela empresa
    // Verifica tempo de inatividade
    $tempoInativo = time() - $_SESSION['cliente']['ultimo_acesso'];
    if ($tempoInativo > $tempoLimite) {
        session_unset();
        session_destroy();
        echo json_encode(['mensagem' => 'Sessão Expirada por inatividade, volte e faça o login novamente.']);
        header('Content-Type: application/json', true, 400);
        exit;
    }


    // Decodifica o JSON
    $resposta = json_decode($jsonData, true);

    // Verifica se a decodificação foi bem-sucedida
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'mensagem' => 'Erro no JSON recebido: ' . json_last_error_msg(),
            'jsonRecebido' => $jsonData  // Exibe o JSON para debug
        ]);
        exit;
    }

    // Verifica se os dados foram recebidos
    if (isset($resposta['IDECLI'], $resposta['TOKEN'], $_SESSION['token']) && $_SESSION['token'] === $resposta['TOKEN']) {

    // clientes
    $vsIdeMov = gerarIDEMOV();
    $vsIdeCli = $resposta['IDECLI'];
    $vsEmlCli = $resposta['EMLCLI'];
    $vsNomCli = $resposta['NOMCLI']; 
    $vsMd5pw  = $resposta['MD5PW']; 

    //token da resposta
    $token = $resposta['TOKEN'];


    try {
        //rotina de envio de email da localweb -- inicio
        $emailserver = retornarCampoEmpresa(1,'EMAILNOREPLY'); 
        $senhaserver = retornarCampoEmpresa(1,'PWNOREPLY'); 
                      
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
        $mail->Subject = 'OuroCred - Confirme seu e-Mail';
        $mail->Body    = '<!DOCTYPE html>
                            <html lang="pt-BR">
                            <head>
                                <meta charset="UTF-8">
                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                <title>Confirmação de Cadastro</title>
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
                                    .btn-confirm {
                                        display: inline-block;
                                        padding: 10px 20px;
                                        font-size: 16px;
                                        color: #ffffff;
                                        background-color: #0066ff;
                                        text-decoration: none;
                                        border-radius: 5px;
                                    }
                                    .btn-confirm:hover {
                                        background-color: #0066ff;
                                    }
                                </style>
                            </head>
                            <body>
                                <table class="email-container">
                                    <tr>
                                        <td class="email-header">
                                            <img src="https://ourocreddtvm.com.br/imagens/emaillogo.png" alt="Logo da OuroCred">
                                            <h2>Email de Confirmação</h2>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="email-body">
                                            <h1>Olá, '.$vsNomCli.' !</h1>
                                            <p>Para completar seu cadastro e aproveitar todos os nossos serviços, é necessário confirmar o seu e-mail.</p>
                                            <div class="email-footer">
                                                <a href="https://ourocreddtvm.com.br/confirm.php?ide='.$vsIdeCli.'&hash='.$vsMd5pw.'" style="color: white;" class="btn-confirm">Confirmar Cadastro</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center; padding: 10px; font-size: 12px; color: #888;">
                                            <p>OuroCred DTVM LTDA<br>Várzea Grande, Mato Grosso</p>
                                            <p>Se você não solicitou a confirmação, por favor, ignore este e-mail.</p>
                                        </td>
                                    </tr>
                                </table>
                            </body>
                        </html>'; 

        // enviando o email de confirmação de criação de conta
        if(!$mail->send()) {
            $msgsmtp = $mail->ErrorInfo;
        } else {
            $msgsmtp = 'sucesso';
        }                           

    } catch (Exception $e) {
        // Tratamento de erros genéricos
        $response = ['mensagem' => $msgsmtp . 'Erro ao enviar o e-mail: ' . $e->getMessage()];
        header('Content-Type: application/json', true, 500);
        echo json_encode($response);
        exit;
    }

    // inserção do log_vrfemail
    try {       

        $sql_LogVrfEmail = "INSERT INTO log_vrfemail (IDECLI, TOKEN, IPUSUARIO, NAVUSUARIO, REFERER, STAENV, STARSP, EMAIL) 
                         VALUES (:idecli, :token, :ipusuario, :navusuario, :referer, :staenv, :starsp, :email)";  

        $stmt_LogVrfEmail = $pdo->prepare($sql_LogVrfEmail);

        // Bind dos parâmetros
        $stmt_LogVrfEmail->bindParam(':idecli', $vsIdeCli, PDO::PARAM_STR);        
        $stmt_LogVrfEmail->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt_LogVrfEmail->bindValue(":ipusuario", $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);
        $stmt_LogVrfEmail->bindValue(":navusuario", $_SERVER['HTTP_USER_AGENT'], PDO::PARAM_STR);
        $stmt_LogVrfEmail->bindValue(":referer", $_SERVER['HTTP_REFERER'], PDO::PARAM_STR);

        $stmt_LogVrfEmail->bindValue(":staenv", 'S', PDO::PARAM_STR);
        $stmt_LogVrfEmail->bindValue(":starsp", 'N', PDO::PARAM_STR);
        $stmt_LogVrfEmail->bindValue(":email", $vsEmlCli, PDO::PARAM_STR);                        


        if (!$stmt_LogVrfEmail->execute()) {
            header('Content-Type: application/json', true, 500);
            echo json_encode(['mensagem' => 'Erro ao atualizar o registro de [Log Verif. email]']);
            exit;
        }


        if (atualizaEmailCliente($vsIdeCli, $vsEmlCli) == false) {
            header('Content-Type: application/json', true, 500);
            echo json_encode(['mensagem' => 'Erro ao atualizar o registro de clientes']);
            exit;            
        }

        // Resposta de sucesso ao front-end;
        $response = ['mensagem' => 'Email de confirmação enviado com Sucesso!'];
        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (PDOException $e) {
        // Erro na inserção
        $response = ['mensagem' => 'Erro ao inserir dados: ' . $e->getMessage()];
        header('Content-Type: application/json', true, 500);
        echo json_encode($response);
    } catch (Exception $e) {
        // Tratamento de erros genéricos
        $response = ['mensagem' => 'Erro inesperado: ' . $e->getMessage()];
        header('Content-Type: application/json', true, 500);
        echo json_encode($response);
    }

} else {
    // Tratamento de erros genéricos
    $response = ['mensagem' => 'Acesso negado: Token inválido ou ausente!'];
    header('Content-Type: application/json', true, 500);
    echo json_encode($response);    
}

?>
