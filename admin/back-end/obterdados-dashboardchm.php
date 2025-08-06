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
            clientes_chm.NUMPTC, 
            clientes.NOMCLI, 
            clientes_chm.DCRCHM, 
            clientes_chm.DTAINS, 
            clientes_chm.STACHM
        FROM clientes_chm 
        LEFT JOIN clientes ON clientes.IDECLI = clientes_chm.IDECLI
        ORDER BY clientes_chm.DTAINS DESC
    ";

   $stmt = $pdo->prepare($sql);
   $stmt->execute();
   $chamados = $stmt->fetchAll(PDO::FETCH_ASSOC);

   // Aplica a função global primeiroUltimoNome
   foreach ($chamados as &$chamado) {
      if (!empty($chamado['NOMCLI'])) {
         $chamado['NOMCLI'] = primeiroUltimoNome($chamado['NOMCLI']);
      }
   }

   echo json_encode(['chamados' => $chamados]);
} catch (PDOException $e) {
   http_response_code(500);
   echo json_encode(['mensagem' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
