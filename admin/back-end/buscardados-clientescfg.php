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

// Verifica domínio de origem (Referer)
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
   echo json_encode(['erro' => true, 'mensagem' => 'Nenhum dado recebido na requisição.']);
   exit;
}

// Verifica tempo de inatividade da sessão
$tempoLimite = retornaTempoLimite(1);
$tempoInativo = time() - ($_SESSION['admin']['ultimo_acesso'] ?? 0);
if ($tempoInativo > $tempoLimite) {
   session_unset();
   session_destroy();
   echo json_encode(['erro' => true, 'mensagem' => 'Sessão expirada por inatividade, volte e faça o login novamente.']);
   header('Content-Type: application/json', true, 400);
   exit;
}

// Decodifica o JSON
$resposta = json_decode($jsonData, true);
if (json_last_error() !== JSON_ERROR_NONE) {
   echo json_encode([
      'erro' => true,
      'mensagem' => 'Erro no JSON recebido: ' . json_last_error_msg(),
      'jsonRecebido' => $jsonData
   ]);
   exit;
}

// Verifica se CPF foi enviado
if (!isset($resposta['cpf'])) {
   echo json_encode(['erro' => true, 'mensagem' => 'CPF não informado.']);
   exit;
}

$cpf = $resposta['cpf'];

try {
   $sql = "SELECT 
               clientes.idecli, clientes.codcli, clientes.cpfcli, clientes.nomcli, clientes.img64,
               clientes_cfg.STAACSPERFIL, clientes_cfg.MTVNEGACSPERFIL,
               clientes_cfg.STAACSCOMPRA, clientes_cfg.MTVNEGACSCOMPRA,
               clientes_cfg.STAACSVENDA, clientes_cfg.MTVNEGACSVENDA,
               clientes_cfg.STAACSDEPOSITAR, clientes_cfg.MTVNEGACSDEPOSITAR,
               clientes_cfg.STAACSSACAR, clientes_cfg.MTVNEGACSSACAR,
               clientes_cfg.STAACSHISTORICO, clientes_cfg.MTVNEGACSHISTORICO,
               clientes_cfg.STAACSSAC, clientes_cfg.MTVNEGACSSAC
           FROM clientes
           LEFT JOIN clientes_cfg ON clientes.idecli = clientes_cfg.idecli
           WHERE clientes.cpfcli = :cpf
           LIMIT 1";

   $stmt = $pdo->prepare($sql);
   $stmt->bindParam(':cpf', $cpf, PDO::PARAM_STR);
   $stmt->execute();
   $dados = $stmt->fetch(PDO::FETCH_ASSOC);

   if ($dados) {
      http_response_code(200);
      echo json_encode($dados);
   } else {
      http_response_code(404);
      echo json_encode(['erro' => true, 'mensagem' => 'Cliente não encontrado.']);
   }
} catch (PDOException $e) {
   http_response_code(500);
   echo json_encode(['erro' => true, 'mensagem' => 'Erro no banco de dados: ' . $e->getMessage()]);
} catch (Exception $e) {
   http_response_code(500);
   echo json_encode(['erro' => true, 'mensagem' => 'Erro inesperado: ' . $e->getMessage()]);
}
