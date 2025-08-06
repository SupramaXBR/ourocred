<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");

include_once('../../uses/conexao.php');
include_once('../../uses/funcoes.php');

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
   isset($resposta['IDEMOV'], $resposta['IDECLI'], $resposta['TOKEN'], $_SESSION['token']) &&
   $_SESSION['token'] === $resposta['TOKEN']
) {
   $idemov = $resposta['IDEMOV'];
   $idecli = $resposta['IDECLI'];

   try {
      $pdo->beginTransaction();

      // Atualiza clientes_saque como Cancelado
      $sqlSaque = "UPDATE clientes_saque 
                   SET STASAQ = 'C', DTAALT = NOW()
                   WHERE IDEMOV = :idemov AND IDECLI = :idecli";
      $stmtSaque = $pdo->prepare($sqlSaque);
      $stmtSaque->bindParam(':idemov', $idemov, PDO::PARAM_STR);
      $stmtSaque->bindParam(':idecli', $idecli, PDO::PARAM_STR);
      if (!$stmtSaque->execute()) {
         throw new Exception("Erro ao cancelar saque.");
      }

      // Atualiza clientes_saldo como Negado
      $sqlSaldo = "UPDATE clientes_saldo 
                   SET STAMOV = 'N'
                   WHERE IDEMOV = :idemov AND IDECLI = :idecli";
      $stmtSaldo = $pdo->prepare($sqlSaldo);
      $stmtSaldo->bindParam(':idemov', $idemov, PDO::PARAM_STR);
      $stmtSaldo->bindParam(':idecli', $idecli, PDO::PARAM_STR);
      if (!$stmtSaldo->execute()) {
         throw new Exception("Erro ao negar movimentação.");
      }

      $pdo->commit();
      http_response_code(200);
      echo json_encode(['mensagem' => 'Saque negado com sucesso!']);
   } catch (Exception $e) {
      $pdo->rollBack();
      http_response_code(500);
      echo json_encode(['mensagem' => 'Erro: ' . $e->getMessage()]);
   }
} else {
   http_response_code(401);
   echo json_encode(['mensagem' => 'Acesso negado: Token inválido ou dados incompletos!']);
}
