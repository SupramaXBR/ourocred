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

// Valida Referer
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

// Captura JSON recebido
$jsonData = file_get_contents('php://input');
if (!$jsonData) {
   echo json_encode(['mensagem' => 'Nenhum dado recebido na requisição.']);
   exit;
}

// Valida sessão do cliente
$tempoLimite = retornaTempoLimite(1);
$tempoInativo = time() - $_SESSION['cliente']['ultimo_acesso'];
if ($tempoInativo > $tempoLimite) {
   session_unset();
   session_destroy();
   echo json_encode(['mensagem' => 'Sessão expirada por inatividade.']);
   http_response_code(400);
   exit;
}

// Decodifica JSON
$resposta = json_decode($jsonData, true);
if (json_last_error() !== JSON_ERROR_NONE) {
   echo json_encode([
      'mensagem' => 'Erro no JSON recebido: ' . json_last_error_msg(),
      'jsonRecebido' => $jsonData
   ]);
   exit;
}

// Valida token e dados principais
if (isset($resposta['IDECLI'], $resposta['VLRSAQ'], $resposta['TPOSAQ'], $resposta['TOKEN'], $_SESSION['token']) && $_SESSION['token'] === $resposta['TOKEN']) {

   $vsIdeCli = $resposta['IDECLI'];
   $vfVlrSaq = $resposta['VLRSAQ'];
   $vsTipoSaq = $resposta['TPOSAQ'];
   $vsUsrIns = 'User';
   $vsCarteira = 'Reais';
   $vsDcrMov = 'Solicitacao de Saque';
   $vsStaMov = 'S';
   $vfSaldo_Simples = 0.0000;
   $vfSaldo_Classic = 0.0000;
   $vfSaldo_Standard = 0.0000;
   $vfSaldo_Premium = 0.0000;
   $vfVlrBseClc = number_format(RetornarValorGrama(), 2, '.', '');
   $vsIdeMov = gerarIDEMOV();

   // Valida saldo atual
   $saldoAtual = obterSaldoReais($vsIdeCli);
   if ($vfVlrSaq > $saldoAtual) {
      http_response_code(403);
      echo json_encode(['mensagem' => 'Saldo insuficiente para esta operação.']);
      exit;
   }

   $vfVlrSaq = ($vfVlrSaq * -1);

   try {
      // Inserir na tabela clientes_saldo
      $sqlSaldo = "INSERT INTO clientes_saldo (
                        IDEMOV, IDECLI, DTAMOV, TPOMOV, DCRMOV,
                        VLRBSECLC, STAMOV, USRINS, carteira,
                        saldo_reais, saldo_simple, saldo_classic, saldo_standard, saldo_premium
                     ) VALUES (
                        :idemov, :idecli, NOW(), 'Saida', :dcrmov,
                        :vlrbseclc, :stamov, :usrins, :carteira,
                        :saldo_reais, :saldo_simple, :saldo_classic, :saldo_standard, :saldo_premium
                     )";

      $stmtSaldo = $pdo->prepare($sqlSaldo);
      $stmtSaldo->bindParam(':idemov', $vsIdeMov);
      $stmtSaldo->bindParam(':idecli', $vsIdeCli);
      $stmtSaldo->bindParam(':dcrmov', $vsDcrMov);
      $stmtSaldo->bindParam(':vlrbseclc', $vfVlrBseClc);
      $stmtSaldo->bindParam(':stamov', $vsStaMov);
      $stmtSaldo->bindParam(':usrins', $vsUsrIns);
      $stmtSaldo->bindParam(':carteira', $vsCarteira);
      $stmtSaldo->bindParam(':saldo_reais', $vfVlrSaq);
      $stmtSaldo->bindParam(':saldo_simple', $vfSaldo_Simples);
      $stmtSaldo->bindParam(':saldo_classic', $vfSaldo_Classic);
      $stmtSaldo->bindParam(':saldo_standard', $vfSaldo_Standard);
      $stmtSaldo->bindParam(':saldo_premium', $vfSaldo_Premium);

      if (!$stmtSaldo->execute()) {
         http_response_code(500);
         echo json_encode(['mensagem' => 'Erro ao inserir no saldo do cliente.']);
         exit;
      }

      // Inserir na tabela clientes_saque
      $sqlSaque = "INSERT INTO clientes_saque (
                        IDECLI, IDEMOV, TPOSAQ, STASAQ, VLRSAQ, USRINS, DTAINS
                     ) VALUES (
                        :idecli, :idemov, :tposaq, 'A', :vlrsaq, :usrins, NOW()
                     )";

      $stmtSaque = $pdo->prepare($sqlSaque);
      $stmtSaque->bindParam(':idecli', $vsIdeCli);
      $stmtSaque->bindParam(':idemov', $vsIdeMov);
      $stmtSaque->bindParam(':tposaq', $vsTipoSaq);
      $stmtSaque->bindParam(':vlrsaq', $vfVlrSaq);
      $stmtSaque->bindParam(':usrins', $vsUsrIns);

      if (!$stmtSaque->execute()) {
         http_response_code(500);
         echo json_encode(['mensagem' => 'Erro ao inserir na solicitação de saque.']);
         exit;
      }

      echo json_encode(['status' => 'ok', 'mensagem' => 'Solicitação de saque registrada com sucesso.']);
   } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['mensagem' => 'Erro PDO: ' . $e->getMessage()]);
   } catch (Exception $e) {
      http_response_code(500);
      echo json_encode(['mensagem' => 'Erro inesperado: ' . $e->getMessage()]);
   }
} else {
   http_response_code(401);
   echo json_encode(['mensagem' => 'Acesso negado: Token inválido ou ausente.']);
}
