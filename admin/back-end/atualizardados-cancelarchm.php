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

// Lê o JSON recebido
$jsonData = file_get_contents('php://input');

if (!$jsonData) {
   echo json_encode(['mensagem' => 'Nenhum dado recebido na requisição.']);
   exit;
}

// Verifica tempo de inatividade da sessão
$tempoLimite = retornaTempoLimite(1);
$tempoInativo = time() - $_SESSION['admin']['ultimo_acesso'];
if ($tempoInativo > $tempoLimite) {
   session_unset();
   session_destroy();
   echo json_encode(['mensagem' => 'Sessão expirada por inatividade, volte e faça o login novamente.']);
   header('Content-Type: application/json', true, 400);
   exit;
}

// Decodifica o JSON
$resposta = json_decode($jsonData, true);
if (json_last_error() !== JSON_ERROR_NONE) {
   echo json_encode([
      'mensagem' => 'Erro no JSON recebido: ' . json_last_error_msg(),
      'jsonRecebido' => $jsonData
   ]);
   exit;
}

// Verifica se os dados foram recebidos corretamente
if (isset($resposta['CODREG'], $resposta['TOKEN'], $_SESSION['token']) && $_SESSION['token'] === $resposta['TOKEN']) {
   $vsCodReg = $resposta['CODREG'];
   $vsUsrAlt = 'User';

   try {
      $sql = "UPDATE clientes_chm 
                SET STACHM = 'C', USRALT = :usralt, DTAALT = NOW()
                WHERE CODREG = :codreg";

      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':codreg', $vsCodReg, PDO::PARAM_INT);
      $stmt->bindParam(':usralt', $vsUsrAlt, PDO::PARAM_STR);

      if (!$stmt->execute()) {
         http_response_code(500);
         echo json_encode(['mensagem' => 'Erro ao cancelar o chamado no banco de dados']);
         exit;
      }

      http_response_code(200);
      echo json_encode(['mensagem' => 'Chamado cancelado com sucesso!']);
   } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['mensagem' => 'Erro no banco de dados: ' . $e->getMessage()]);
   } catch (Exception $e) {
      http_response_code(500);
      echo json_encode(['mensagem' => 'Erro inesperado: ' . $e->getMessage()]);
   }
} else {
   http_response_code(401);
   echo json_encode(['mensagem' => 'Acesso negado: Token inválido ou ausente!']);
}
