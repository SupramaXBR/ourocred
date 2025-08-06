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

// Valida sessão do admin
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

try {
   $sql = "
        SELECT 
            clientes_cpl.IDECLI,
            clientes.NOMCLI,
            clientes_cpl.TPODOC,
            LEFT(clientes_cpl.IMG64DOC, 128) AS IMG64DOC,
            LEFT(clientes_cpl.IMG64CPREND, 128) AS IMG64CPREND,
            clientes_cpl.STAAPV,
            clientes_cpl.DTAINS
        FROM clientes_cpl
        LEFT JOIN clientes ON clientes_cpl.IDECLI = clientes.IDECLI
        ORDER BY clientes_cpl.DTAINS DESC
    ";

   $stmt = $pdo->prepare($sql);
   $stmt->execute();
   $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

   foreach ($documentos as &$doc) {
      // Nome reduzido
      if (!empty($doc['NOMCLI'])) {
         $doc['NOMCLI'] = primeiroUltimoNome($doc['NOMCLI']);
      }

      // Substituir conteúdo base64 por tipo identificado
      $doc['IMG64DOC'] = tipoArquivoBase64($doc['IMG64DOC']);
      $doc['IMG64CPREND'] = tipoArquivoBase64($doc['IMG64CPREND']);
   }

   echo json_encode(['documentos' => $documentos]);
} catch (PDOException $e) {
   http_response_code(500);
   echo json_encode(['mensagem' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
