<?php
session_start();

// Cabeçalhos de segurança HTTP
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");

include_once('../../uses/conexao.php');
include_once('../../uses/funcoes.php');

// Verifica conexão com banco
if (!$pdo) {
   http_response_code(500);
   die(json_encode(['mensagem' => 'Erro de conexão com o banco de dados.']));
}

// Verifica domínio de origem (Referer)
$dominiosPermitidos = [
   'www.ourocreddtvm.com.br',
   'ourocreddtvm.com.br',
   '192.168.18.88' // Ambiente local
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

// Verifica tempo de inatividade da sessão
$tempoLimite = retornaTempoLimite(1);
$tempoInativo = time() - $_SESSION['admin']['ultimo_acesso'];
if ($tempoInativo > $tempoLimite) {
   session_unset();
   session_destroy();
   echo json_encode(['mensagem' => 'Sessão expirada por inatividade, volte e faça login novamente.']);
   http_response_code(401);
   exit;
}

// Lê o corpo JSON
$jsonData = file_get_contents('php://input');
if (!$jsonData) {
   http_response_code(400);
   echo json_encode(['mensagem' => 'Nenhum dado recebido na requisição.']);
   exit;
}

// Decodifica JSON
$dados = json_decode($jsonData, true);

if ($dados['PWADMIN'] !== obterPWADMIN(1)) {
   $dados['PWADMIN'] = md5($dados['PWADMIN']);
}

if (json_last_error() !== JSON_ERROR_NONE) {
   http_response_code(400);
   echo json_encode([
      'mensagem' => 'Erro no JSON recebido: ' . json_last_error_msg()
   ]);
   exit;
}

// (Opcional) Verificação de token interno
if (!isset($dados['USRADMIN']) || !isset($_SESSION['token'])) {
   http_response_code(401);
   echo json_encode(['mensagem' => 'Acesso negado: Token ou dados ausentes.']);
   exit;
}

// Monta o UPDATE
try {
   $sql = "UPDATE empresa SET 
   NOMEMP = :NOMEMP,
   CNPJ = :CNPJ,
   ENDEMP = :ENDEMP,
   EMAILCONTATO = :EMAILCONTATO,
   EMAILNOREPLY = :EMAILNOREPLY,
   PWNOREPLY = :PWNOREPLY,
   USRADMIN = :USRADMIN,
   PWADMIN = :PWADMIN,
   LNKFACEBOOK = :LNKFACEBOOK,
   LNKTWITTER = :LNKTWITTER,
   LNKINSTAGRAM = :LNKINSTAGRAM,
   LNKYOUTUBE = :LNKYOUTUBE,
   LNKWHATSAPP = :LNKWHATSAPP,
   TXTTERMOS = :TXTTERMOS,
   VLRDSCGRMVDA = :VLRDSCGRMVDA,
   QTDDIASIMPLE = :QTDDIASIMPLE,
   PERDSCSIMPLE = :PERDSCSIMPLE,
   QTDDIACLASSIC = :QTDDIACLASSIC,
   PERDSCCLASSIC = :PERDSCCLASSIC,
   QTDDIASTANDARD = :QTDDIASTANDARD,
   PERDSCSTANDARD = :PERDSCSTANDARD,
   QTDDIAPREMIUM = :QTDDIAPREMIUM,
   PERDSCPREMIUM = :PERDSCPREMIUM,
   TPOLMT_SEG = :TPOLMT_SEG,
   MAXKBIMGPER = :MAXKBIMGPER,
   MAXKBIMGDOC = :MAXKBIMGDOC,
   MAXKBIMGEND = :MAXKBIMGEND
WHERE CODEMP = 1";

   $stmt = $pdo->prepare($sql);

   // Bind individual — padrão ninja
   $stmt->bindParam(':NOMEMP', $dados['NOMEMP']);
   $stmt->bindParam(':CNPJ', $dados['CNPJ']);
   $stmt->bindParam(':ENDEMP', $dados['ENDEMP']);
   $stmt->bindParam(':EMAILCONTATO', $dados['EMAILCONTATO']);
   $stmt->bindParam(':EMAILNOREPLY', $dados['EMAILNOREPLY']);
   $stmt->bindParam(':PWNOREPLY', $dados['PWNOREPLY']);
   $stmt->bindParam(':USRADMIN', $dados['USRADMIN']);
   $stmt->bindParam(':PWADMIN', $dados['PWADMIN']);
   $stmt->bindParam(':LNKFACEBOOK', $dados['LNKFACEBOOK']);
   $stmt->bindParam(':LNKTWITTER', $dados['LNKTWITTER']);
   $stmt->bindParam(':LNKINSTAGRAM', $dados['LNKINSTAGRAM']);
   $stmt->bindParam(':LNKYOUTUBE', $dados['LNKYOUTUBE']);
   $stmt->bindParam(':LNKWHATSAPP', $dados['LNKWHATSAPP']);
   $stmt->bindParam(':TXTTERMOS', $dados['TXTTERMOS']);
   $stmt->bindParam(':VLRDSCGRMVDA', $dados['VLRDSCGRMVDA']);
   $stmt->bindParam(':QTDDIASIMPLE', $dados['QTDDIASIMPLE']);
   $stmt->bindParam(':PERDSCSIMPLE', $dados['PERDSCSIMPLE']);
   $stmt->bindParam(':QTDDIACLASSIC', $dados['QTDDIACLASSIC']);
   $stmt->bindParam(':PERDSCCLASSIC', $dados['PERDSCCLASSIC']);
   $stmt->bindParam(':QTDDIASTANDARD', $dados['QTDDIASTANDARD']);
   $stmt->bindParam(':PERDSCSTANDARD', $dados['PERDSCSTANDARD']);
   $stmt->bindParam(':QTDDIAPREMIUM', $dados['QTDDIAPREMIUM']);
   $stmt->bindParam(':PERDSCPREMIUM', $dados['PERDSCPREMIUM']);
   $stmt->bindParam(':TPOLMT_SEG', $dados['TPOLMT_SEG']);
   $stmt->bindParam(':MAXKBIMGPER', $dados['MAXKBIMGPER']);
   $stmt->bindParam(':MAXKBIMGDOC', $dados['MAXKBIMGDOC']);
   $stmt->bindParam(':MAXKBIMGEND', $dados['MAXKBIMGEND']);

   // Executa com verificação
   if (!$stmt->execute()) {
      http_response_code(500);
      echo json_encode(['mensagem' => 'Erro ao atualizar dados da empresa no banco de dados.']);
      exit;
   }

   // Sucesso
   http_response_code(200);
   echo json_encode(['status' => 'ok', 'mensagem' => 'Dados da empresa atualizados com sucesso!']);
} catch (PDOException $e) {
   http_response_code(500);
   echo json_encode(['mensagem' => 'Erro no banco de dados: ' . $e->getMessage()]);
} catch (Exception $e) {
   http_response_code(500);
   echo json_encode(['mensagem' => 'Erro inesperado: ' . $e->getMessage()]);
}
