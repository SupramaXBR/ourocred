<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");

include_once('../../uses/conexao.php');
include_once('../../uses/funcoes.php');
require_once '../../uses/PHPMailer-master/src/PHPMailer.php';
require_once '../../uses/PHPMailer-master/src/SMTP.php';
require_once '../../uses/PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!$pdo) {
   http_response_code(500);
   die(json_encode(['mensagem' => 'Erro de conexão com o banco de dados.']));
}

$dominiosPermitidos = [
   'www.ourocreddtvm.com.br',
   'ourocreddtvm.com.br',
   '192.168.18.88'
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

$jsonData = file_get_contents('php://input');
if (!$jsonData) {
   echo json_encode(['mensagem' => 'Nenhum dado recebido na requisição.']);
   exit;
}

$tempoLimite = retornaTempoLimite(1);
$tempoInativo = time() - ($_SESSION['admin']['ultimo_acesso'] ?? 0);
if ($tempoInativo > $tempoLimite) {
   session_unset();
   session_destroy();
   echo json_encode(['mensagem' => 'Sessão expirada por inatividade, volte e faça o login novamente.']);
   header('Content-Type: application/json', true, 400);
   exit;
}

$resposta = json_decode($jsonData, true);
if (json_last_error() !== JSON_ERROR_NONE) {
   echo json_encode([
      'mensagem' => 'Erro no JSON recebido: ' . json_last_error_msg(),
      'jsonRecebido' => $jsonData
   ]);
   exit;
}

if (
   isset($resposta['IDEMOV'], $resposta['IDECLI'], $resposta['IMG64CPR'], $resposta['TOKEN'], $_SESSION['token']) &&
   $_SESSION['token'] === $resposta['TOKEN']
) {
   $idemov = $resposta['IDEMOV'];
   $idecli = $resposta['IDECLI'];
   $img64  = trim($resposta['IMG64CPR']);
   $stamail = strtoupper($resposta['STAMAIL'] ?? 'N');

   if (empty($img64)) {
      http_response_code(400);
      echo json_encode(['mensagem' => 'Comprovante em PDF é obrigatório.']);
      exit;
   }

   try {
      $pdo->beginTransaction();

      $sqlSaque = "UPDATE clientes_saque 
                   SET IMG64CPR = :img64, STASAQ = 'F', DTAALT = NOW()
                   WHERE IDEMOV = :idemov AND IDECLI = :idecli";

      $stmtSaque = $pdo->prepare($sqlSaque);
      $stmtSaque->bindParam(':img64', $img64, PDO::PARAM_STR);
      $stmtSaque->bindParam(':idemov', $idemov, PDO::PARAM_STR);
      $stmtSaque->bindParam(':idecli', $idecli, PDO::PARAM_STR);

      if (!$stmtSaque->execute()) {
         throw new Exception("Erro ao atualizar a tabela clientes_saque");
      }

      $sqlSaldo = "UPDATE clientes_saldo 
                   SET STAMOV = 'A'
                   WHERE IDEMOV = :idemov AND IDECLI = :idecli";

      $stmtSaldo = $pdo->prepare($sqlSaldo);
      $stmtSaldo->bindParam(':idemov', $idemov, PDO::PARAM_STR);
      $stmtSaldo->bindParam(':idecli', $idecli, PDO::PARAM_STR);

      if (!$stmtSaldo->execute()) {
         throw new Exception("Erro ao atualizar a tabela clientes_saldo");
      }

      $pdo->commit();

      if ($stamail === 'S') {
         $sqlInfo = "SELECT clientes_saque.TPOSAQ, clientes_saque.VLRSAQ, clientes.NOMCLI, clientes.EMAIL 
                     FROM clientes_saque 
                     LEFT JOIN clientes ON clientes_saque.IDECLI = clientes.IDECLI
                     WHERE clientes_saque.IDEMOV = :idemov AND clientes_saque.IDECLI = :idecli";

         $stmtInfo = $pdo->prepare($sqlInfo);
         $stmtInfo->bindParam(':idemov', $idemov);
         $stmtInfo->bindParam(':idecli', $idecli);
         $stmtInfo->execute();
         $dados = $stmtInfo->fetch(PDO::FETCH_ASSOC);

         if ($dados) {
            $emailDestino = $dados['EMAIL'];
            $vsNomCli = $dados['NOMCLI'];
            $vsValor = number_format(abs($dados['VLRSAQ']), 2, ',', '.');
            $vsTipo = $dados['TPOSAQ'];

            if ($emailDestino) {
               $mail = new PHPMailer(true);
               $mail->isSMTP();
               $mail->Host = 'email-ssl.com.br';
               $mail->SMTPAuth = true;
               $mail->Username = retornarCampoEmpresa(1, 'EMAILNOREPLY');
               $mail->Password = retornarCampoEmpresa(1, 'PWNOREPLY');
               $mail->Port = 587;
               $mail->CharSet = 'UTF-8';

               $mail->setFrom($mail->Username, 'OuroCred DTVM');
               $mail->addAddress($emailDestino, $vsNomCli);
               $mail->isHTML(true);
               $mail->Subject = "Comprovante do Saque #$idemov";

               $mail->Body = '
               <!DOCTYPE html>
               <html lang="pt-BR">
               <head>
                  <meta charset="UTF-8">
                  <meta name="viewport" content="width=device-width, initial-scale=1.0">
                  <title>Comprovante de Saque</title>
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
                           <h2>Comprovante de Saque</h2>
                        </td>
                     </tr>
                     <tr>
                        <td class="email-body">
                           <h1>Olá, ' . htmlspecialchars($vsNomCli) . '!</h1>
                           <p>Seu saque foi processado com sucesso.</p>
                           <p><strong>ID da Movimentação:</strong> ' . htmlspecialchars($idemov) . '</p>
                           <p><strong>Valor:</strong> R$ ' . htmlspecialchars($vsValor) . '</p>
                           <p><strong>Tipo:</strong> ' . htmlspecialchars($vsTipo) . '</p>
                           <p>O comprovante de pagamento está em anexo.</p>
                           <div class="email-footer">
                              <p>Se tiver qualquer dúvida, estamos à disposição.</p>
                           </div>
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

               $mail->AltBody = "Saque $idemov aprovado.\nValor: R$ $vsValor\nTipo: $vsTipo";

               if (preg_match('/^data:application\/pdf;base64,(.*)$/', $img64, $match)) {
                  $pdfDecoded = base64_decode($match[1]);
                  $fileTemp = sys_get_temp_dir() . '/comprovante_' . time() . '.pdf';
                  file_put_contents($fileTemp, $pdfDecoded);
                  $mail->addAttachment($fileTemp, 'comprovante.pdf');
               }

               $mail->send();

               if (isset($fileTemp) && file_exists($fileTemp)) {
                  unlink($fileTemp);
               }
            }
         }
      }

      http_response_code(200);
      echo json_encode(['mensagem' => 'Saque finalizado com sucesso!']);
   } catch (Exception $e) {
      $pdo->rollBack();
      http_response_code(500);
      echo json_encode(['mensagem' => 'Erro: ' . $e->getMessage()]);
   }
} else {
   http_response_code(401);
   echo json_encode(['mensagem' => 'Acesso negado: Token inválido ou dados incompletos!']);
}
