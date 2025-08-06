<?php
require_once '../../uses/conexao.php';
require_once '../../uses/funcoes.php'; // onde está a função obterValorGramaVendaComDesconto()

header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");

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


// Validação e filtragem segura
/* 
$carteira = filter_input(INPUT_POST, 'carteira', FILTER_SANITIZE_STRING);
$codemp = filter_input(INPUT_POST, 'codemp', FILTER_VALIDATE_INT);
*/

//corigindo erro do servidor localweb referente ao php 8.1 para o php 8.3

$carteira = isset($_POST['carteira']) ? trim(strip_tags($_POST['carteira'])) : null;
$codemp = filter_input(INPUT_POST, 'codemp', FILTER_VALIDATE_INT);

$response = [
   'success' => false,
   'valor' => '0,00',
   'mensagem' => ''
];

if ($carteira && $codemp !== false && $codemp !== null) {
   try {
      $valor = obterValorGramaVendaComDesconto($codemp, $carteira);
      $response['success'] = true;
      $response['valor'] = $valor;
   } catch (Exception $e) {
      $response['mensagem'] = "Erro ao calcular valor: " . $e->getMessage();
   }
} else {
   $response['mensagem'] = "Parâmetros inválidos.";
}

echo json_encode($response);
