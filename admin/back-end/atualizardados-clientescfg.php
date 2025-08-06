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
   die(json_encode(['erro' => true, 'mensagem' => 'Erro de conexão com o banco de dados.']));
}

// Verifica domínio de origem
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

// Lê o JSON recebido
$jsonData = file_get_contents('php://input');
if (!$jsonData) {
   echo json_encode(['erro' => true, 'mensagem' => 'Nenhum dado recebido.']);
   exit;
}

// Valida tempo de sessão
$tempoLimite = retornaTempoLimite(1);
$tempoInativo = time() - ($_SESSION['admin']['ultimo_acesso'] ?? 0);
if ($tempoInativo > $tempoLimite) {
   session_unset();
   session_destroy();
   http_response_code(400);
   echo json_encode(['erro' => true, 'mensagem' => 'Sessão expirada por inatividade.']);
   exit;
}

// Decodifica JSON
$dados = json_decode($jsonData, true);
if (json_last_error() !== JSON_ERROR_NONE) {
   echo json_encode(['erro' => true, 'mensagem' => 'Erro no JSON: ' . json_last_error_msg()]);
   exit;
}

// Verifica token
if (!isset($dados['TOKEN']) || $dados['TOKEN'] !== ($_SESSION['admin']['token'] ?? '')) {
   http_response_code(401);
   echo json_encode(['erro' => true, 'mensagem' => 'Token inválido ou ausente.']);
   exit;
}

// Verifica se IDECLI foi enviado
if (empty($dados['idecli'])) {
   echo json_encode(['erro' => true, 'mensagem' => 'IDECLI não informado.']);
   exit;
}

// Atualização direta
try {
   $sql = "UPDATE clientes_cfg SET
               STAACSPERFIL = :STAACSPERFIL,
               MTVNEGACSPERFIL = :MTVNEGACSPERFIL,
               STAACSCOMPRA = :STAACSCOMPRA,
               MTVNEGACSCOMPRA = :MTVNEGACSCOMPRA,
               STAACSVENDA = :STAACSVENDA,
               MTVNEGACSVENDA = :MTVNEGACSVENDA,
               STAACSDEPOSITAR = :STAACSDEPOSITAR,
               MTVNEGACSDEPOSITAR = :MTVNEGACSDEPOSITAR,
               STAACSSACAR = :STAACSSACAR,
               MTVNEGACSSACAR = :MTVNEGACSSACAR,
               STAACSHISTORICO = :STAACSHISTORICO,
               MTVNEGACSHISTORICO = :MTVNEGACSHISTORICO,
               STAACSSAC = :STAACSSAC,
               MTVNEGACSSAC = :MTVNEGACSSAC,
               DTAALT = NOW()
           WHERE IDECLI = :idecli";

   $stmt = $pdo->prepare($sql);

   $stmt->bindValue(':idecli', $dados['idecli']);
   $stmt->bindValue(':STAACSPERFIL', $dados['STAACSPERFIL'] ?? 'S');
   $stmt->bindValue(':MTVNEGACSPERFIL', $dados['MTVNEGACSPERFIL'] ?? '');
   $stmt->bindValue(':STAACSCOMPRA', $dados['STAACSCOMPRA'] ?? 'S');
   $stmt->bindValue(':MTVNEGACSCOMPRA', $dados['MTVNEGACSCOMPRA'] ?? '');
   $stmt->bindValue(':STAACSVENDA', $dados['STAACSVENDA'] ?? 'S');
   $stmt->bindValue(':MTVNEGACSVENDA', $dados['MTVNEGACSVENDA'] ?? '');
   $stmt->bindValue(':STAACSDEPOSITAR', $dados['STAACSDEPOSITAR'] ?? 'S');
   $stmt->bindValue(':MTVNEGACSDEPOSITAR', $dados['MTVNEGACSDEPOSITAR'] ?? '');
   $stmt->bindValue(':STAACSSACAR', $dados['STAACSSACAR'] ?? 'S');
   $stmt->bindValue(':MTVNEGACSSACAR', $dados['MTVNEGACSSACAR'] ?? '');
   $stmt->bindValue(':STAACSHISTORICO', $dados['STAACSHISTORICO'] ?? 'S');
   $stmt->bindValue(':MTVNEGACSHISTORICO', $dados['MTVNEGACSHISTORICO'] ?? '');
   $stmt->bindValue(':STAACSSAC', $dados['STAACSSAC'] ?? 'S');
   $stmt->bindValue(':MTVNEGACSSAC', $dados['MTVNEGACSSAC'] ?? '');

   if ($stmt->execute()) {
      echo json_encode(['erro' => false, 'mensagem' => 'Configurações atualizadas com sucesso!']);
   } else {
      http_response_code(500);
      echo json_encode(['erro' => true, 'mensagem' => 'Erro ao atualizar configurações.']);
   }
} catch (PDOException $e) {
   http_response_code(500);
   echo json_encode(['erro' => true, 'mensagem' => 'Erro no banco: ' . $e->getMessage()]);
}
