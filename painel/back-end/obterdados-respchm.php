<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");

include_once '../../uses/conexao.php';
include_once '../../uses/funcoes.php';

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

// Valida sessão do cliente
if (!isset($_SESSION['cliente'])) {
   http_response_code(401);
   echo json_encode(['mensagem' => 'Sessão expirada ou inválida']);
   exit;
}

$codemp = 1;
$tempoLimite = retornaTempoLimite($codemp);
if ((time() - ($_SESSION['cliente']['ultimo_acesso'] ?? 0)) > $tempoLimite) {
   session_destroy();
   http_response_code(401);
   echo json_encode(['mensagem' => 'Sessão expirada por inatividade']);
   exit;
}

$_SESSION['cliente']['ultimo_acesso'] = time();

// Lê e decodifica JSON recebido
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!isset($data['CODREG'])) {
   http_response_code(400);
   echo json_encode(['mensagem' => 'Parâmetro CODREG ausente.']);
   exit;
}

$codreg = (int)$data['CODREG'];

try {
   $sql = "SELECT NUMPTC, DCRCHM, TXTCHM, IMG64CHM FROM clientes_resp WHERE CODREG = :codreg LIMIT 1";
   $stmt = $pdo->prepare($sql);
   $stmt->bindParam(':codreg', $codreg, PDO::PARAM_INT);
   $stmt->execute();
   $resposta = $stmt->fetch(PDO::FETCH_ASSOC);

   if ($resposta) {
      echo json_encode(['resposta' => $resposta]);
   } else {
      echo json_encode(['mensagem' => 'Nenhuma resposta encontrada.']);
   }
} catch (PDOException $e) {
   http_response_code(500);
   echo json_encode(['mensagem' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
