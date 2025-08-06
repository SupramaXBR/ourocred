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

// 🛡️ Validação de origem (Referer)
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

// 🔒 Validação de sessão e credenciais
if (!isset($_SESSION['admin'])) {
   http_response_code(401);
   echo json_encode(['mensagem' => 'Sessão expirada ou inválida']);
   exit;
}

$codemp = 1;
$tempoLimite = retornaTempoLimite($codemp);
if ((time() - ($_SESSION['admin']['ultimo_acesso'] ?? 0)) > $tempoLimite) {
   session_destroy();
   http_response_code(401);
   echo json_encode(['mensagem' => 'Sessão expirada por inatividade']);
   exit;
}

$_SESSION['admin']['ultimo_acesso'] = time();

// 🔐 Validação JSON recebido
$input = file_get_contents("php://input");
$dados = json_decode($input, true);

if (!isset($dados['IDECLI'], $dados['STACTAATV'], $dados['TOKEN'])) {
   http_response_code(400);
   echo json_encode(['mensagem' => 'Parâmetros ausentes.']);
   exit;
}

// 🔐 Validação do token
if (!isset($_SESSION['admin']['token']) || $_SESSION['admin']['token'] !== $dados['TOKEN']) {
   http_response_code(403);
   echo json_encode(['mensagem' => 'Token inválido ou sessão comprometida.']);
   exit;
}

// 🎯 Validação de valor
$idecli = trim($dados['IDECLI']);
$status = strtoupper(trim($dados['STACTAATV']));

if (!in_array($status, ['S', 'N'])) {
   http_response_code(400);
   echo json_encode(['mensagem' => 'Valor de status inválido.']);
   exit;
}

// 🛠️ Atualiza o status no banco
try {
   $sql = "UPDATE clientes SET STACTAATV = :status WHERE IDECLI = :idecli";
   $stmt = $pdo->prepare($sql);
   $stmt->bindParam(':status', $status);
   $stmt->bindParam(':idecli', $idecli);
   $stmt->execute();

   if ($stmt->rowCount()) {
      echo json_encode(['mensagem' => 'Status atualizado com sucesso.']);
   } else {
      echo json_encode(['mensagem' => 'Nenhuma alteração feita.']);
   }
} catch (PDOException $e) {
   http_response_code(500);
   echo json_encode(['mensagem' => 'Erro ao atualizar: ' . $e->getMessage()]);
}
