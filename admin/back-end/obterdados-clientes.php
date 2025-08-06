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

$input = file_get_contents("php://input");
$resposta = json_decode($input, true);

if (!isset($resposta['IDECLI']) || !isset($resposta['TOKEN'])) {
   http_response_code(400);
   echo json_encode(['mensagem' => 'Parâmetros ausentes.']);
   exit;
}

if (!isset($_SESSION['admin']['token']) || $_SESSION['admin']['token'] !== $resposta['TOKEN']) {
   http_response_code(403);
   echo json_encode(['mensagem' => 'Token inválido ou sessão comprometida.']);
   exit;
}

$idecli = $resposta['IDECLI'];

try {
   $sql = "SELECT 
                clientes.IDECLI, 
                clientes.CPFCLI, 
                clientes.RGCLI, 
                clientes.NOMCLI, 
                clientes.MAECLI, 
                clientes.NUMTEL, 
                clientes.EMAIL, 
                clientes.CEPCLI, 
                clientes.ENDCLI,
                clientes.NUMCSA, 
                clientes.CPLEND, 
                clientes.BAICLI, 
                clientes.UFDCLI, 
                municipio.NOMMUN, 
                clientes.IMG64, 
                clientes.STACTAATV,
                clientes.DTAINS, 
                clientes_cpl.IMG64DOC, 
                clientes_cpl.IMG64CPREND,
                clientes_bco.NOMTTL,
                clientes_bco.CPFTTL,
                clientes_bco.CODBCO,
                bancos.DCRBCO,
                clientes_bco.NUMAGC,
                clientes_bco.NUMCTA,
                clientes_bco.TPOCTA,
                clientes_bco.STAACTPIX,
                clientes_bco.STAACTCTA
            FROM clientes 
            LEFT JOIN clientes_cpl ON clientes.IDECLI = clientes_cpl.IDECLI
            LEFT JOIN municipio ON clientes.CODMUNIBGE = municipio.CODMUNIBGE
            LEFT JOIN clientes_bco ON clientes.IDECLI = clientes_bco.IDECLI
            LEFT JOIN bancos ON clientes_bco.CODBCO = bancos.CODBCO
            WHERE clientes.IDECLI = :idecli
            LIMIT 1";

   $stmt = $pdo->prepare($sql);
   $stmt->bindParam(':idecli', $idecli, PDO::PARAM_STR);
   $stmt->execute();

   $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

   if ($cliente) {
      echo json_encode(['cliente' => $cliente]);
   } else {
      echo json_encode(['mensagem' => 'Cliente não encontrado.']);
   }
} catch (PDOException $e) {
   http_response_code(500);
   echo json_encode(['mensagem' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
