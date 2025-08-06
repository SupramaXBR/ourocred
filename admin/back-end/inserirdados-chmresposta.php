
<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");

include_once '../../uses/conexao.php';
include_once '../../uses/funcoes.php';
require_once '../../uses/PHPMailer-master/src/PHPMailer.php';
require_once '../../uses/PHPMailer-master/src/SMTP.php';
require_once '../../uses/PHPMailer-master/src/Exception.php';

if (!$pdo) {
    http_response_code(500);
    die(json_encode(['mensagem' => 'Erro de conexão com o banco de dados.']));
}

// Verifica domínio de origem (Referer)
$dominiosPermitidos = [
    'www.ourocreddtvm.com.br',
    'ourocreddtvm.com.br',
    '192.168.18.88' // Ambiente de teste (LAMP)
];

if (!empty($_SERVER['HTTP_REFERER'])) {
    $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if (!in_array($referer, $dominiosPermitidos)) {
        error_log("Acesso negado! Origem inválida: $referer");
        die("Acesso negado!");
    }
} else {
    die("Atenção: Nenhum Referer detectado!");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$codemp = 1;

if (!isset($_SESSION['admin'])) {
    session_destroy();
    http_response_code(401);
    echo json_encode(['mensagem' => 'Sessão inválida']);
    exit;
}

$tempoLimite = retornaTempoLimite($codemp);
if ((time() - ($_SESSION['admin']['ultimo_acesso'] ?? 0)) > $tempoLimite) {
    session_destroy();
    http_response_code(401);
    echo json_encode(['mensagem' => 'Sessão expirada por inatividade']);
    exit;
}

if (!isset($_SESSION['admin']['token']) || $_SESSION['admin']['token'] !== ($_SESSION['token'] ?? '')) {
    session_destroy();
    http_response_code(401);
    echo json_encode(['mensagem' => 'Token inválido']);
    exit;
}

$credenciais = obterCredenciaisAdminEmpresa($codemp);
if (!$credenciais || $_SESSION['admin']['usuario'] !== $credenciais['USRADMIN'] || md5($_SESSION['admin']['senha']) !== $credenciais['PWADMIN']) {
    session_destroy();
    http_response_code(401);
    echo json_encode(['mensagem' => 'Credenciais inválidas']);
    exit;
}

$_SESSION['admin']['ultimo_acesso'] = time();

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || !isset($data['CODREG'], $data['IDECLI'], $data['NUMPTC'], $data['DCRCHM'], $data['TXTCHM'], $data['TOKEN'])) {
    http_response_code(400);
    echo json_encode(['mensagem' => 'Dados incompletos.']);
    exit;
}

if ($data['TOKEN'] !== $_SESSION['token']) {
    http_response_code(401);
    echo json_encode(['mensagem' => 'Token inválido.']);
    exit;
}

$vsCodReg = (int)$data['CODREG'];
$vsIdeCli = trim($data['IDECLI']);
$vsNumPtc = trim($data['NUMPTC']);
$vsDcrChm = trim($data['DCRCHM']);
$vsTxtChm = trim($data['TXTCHM']);
$vsImg64Chm = $data['IMG64CHM'] ?? null;
$vsUsrIns = $_SESSION['admin']['usuario'];
$enviarEmail = isset($data['ENVIAR_EMAIL']) && $data['ENVIAR_EMAIL'] === true;

try {
    $stmt = $pdo->prepare("INSERT INTO clientes_resp (CODREG, IDECLI, NUMPTC, DCRCHM, TXTCHM, IMG64CHM, USRINS, DTAINS) VALUES (:codreg, :idecli, :numptc, :dcrchm, :txtchm, :img64chm, :usrins, NOW())");
    $stmt->bindParam(':codreg', $vsCodReg);
    $stmt->bindParam(':idecli', $vsIdeCli);
    $stmt->bindParam(':numptc', $vsNumPtc);
    $stmt->bindParam(':dcrchm', $vsDcrChm);
    $stmt->bindParam(':txtchm', $vsTxtChm);
    $stmt->bindParam(':img64chm', $vsImg64Chm);
    $stmt->bindParam(':usrins', $vsUsrIns);
    $stmt->execute();

    $update = $pdo->prepare("UPDATE clientes_chm SET STACHM = 'F', DTAALT = NOW() WHERE CODREG = :codreg");
    $update->bindParam(':codreg', $vsCodReg);
    $update->execute();

    if ($enviarEmail) {
        $emailDestino = obterEmailCliente($vsIdeCli);
        $vsNomCli = obterNomeCliente($vsIdeCli);
        $vsDcrChmPai = obterDescChmPai($vsNumPtc);
        if ($emailDestino) {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'email-ssl.com.br';
            $mail->SMTPAuth = true;
            $mail->Username = retornarCampoEmpresa(1,'EMAILNOREPLY');
            $mail->Password = retornarCampoEmpresa(1,'PWNOREPLY');
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($mail->Username, 'OuroCred DTVM');
            $mail->addAddress($emailDestino, $vsNomCli);
            $mail->isHTML(true);
            $mail->Subject = "Resposta ao seu chamado #$vsNumPtc";

            $mail->Body = '<!DOCTYPE html>
            <html lang="pt-BR">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Resposta ao Chamado</title>
                <style>
                    body {font-family: Arial, sans-serif; background-color: #f9f9f9; margin: 0; padding: 0;}
                    .email-container {max-width: 600px; margin: 20px auto; background-color: #ffffff; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);}
                    .email-header {background-color: #0066ff; color: #ffffff; padding: 20px; text-align: center;}
                    .email-header img {max-height: 50px; margin-bottom: 10px;}
                    .email-body {padding: 20px; color: #333333;}
                    .email-body h1 {font-size: 20px; margin-bottom: 10px;}
                    .email-body p {font-size: 16px; line-height: 1.5;}
                    .email-footer {text-align: center; margin: 20px 0;}
                </style>
            </head>
            <body>
                <table class="email-container">
                    <tr>
                        <td class="email-header">
                            <img src="https://ourocreddtvm.com.br/imagens/emaillogo.png" alt="Logo da OuroCred">
                            <h2>Resposta ao seu Chamado</h2>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-body">
                            <h1>Olá, '.$vsNomCli.'!</h1>
                            <p>Recebemos sua solicitação e nossa equipe já analisou seu chamado.</p>
                            <p><strong>Protocolo:</strong> '.$vsNumPtc.'</p>
                            <p><strong>Descrição:</strong><br>'.$vsDcrChm.'</p>
                            <p><strong>Referencia:</strong><br>'.$vsDcrChmPai.'</p>
                            <p><strong>Resposta da Equipe:</strong><br>'.$vsTxtChm.'</p>
                            <div class="email-footer"><p>Se tiver mais dúvidas, estamos à disposição.</p></div>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: center; padding: 10px; font-size: 12px; color: #888;">
                            <p>OuroCred DTVM LTDA<br>Várzea Grande, Mato Grosso</p>
                            <p>Este é um e-mail automático. Não é necessário respondê-lo.</p>
                        </td>
                    </tr>
                </table>
            </body>
            </html>';

            $mail->AltBody = "Resposta ao chamado \nProtocolo: $vsNumPtc\nDescrição: $vsDcrChm\nResposta: $vsTxtChm";

            // Anexar arquivo, se existir
            if (!empty($vsImg64Chm)) {
                $tempPath = sys_get_temp_dir() . '/anexo_chamado_' . time();
                if (preg_match('/^data:(.*?);base64,(.*)$/', $vsImg64Chm, $matches)) {
                    $fileType = $matches[1];
                    $fileData = base64_decode($matches[2]);
                    $ext = (strpos($fileType, 'pdf') !== false) ? 'pdf' : 'jpg';
                    $filename = $tempPath . '.' . $ext;
                    file_put_contents($filename, $fileData);
                    $mail->addAttachment($filename, 'anexo_chamado.' . $ext);
                }
            }

            $mail->send();
            // Remove o arquivo temporário após envio do e-mail
            if (isset($filename) && file_exists($filename)) {
                unlink($filename);
            }            
        }
    }

    echo json_encode(['mensagem' => 'Resposta registrada com sucesso.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['mensagem' => 'Erro ao enviar e-mail: ' . $e->getMessage()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['mensagem' => 'Erro no banco: ' . $e->getMessage()]);
}
?>
