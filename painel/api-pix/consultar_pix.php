<?php

// Garante que o script está sendo acessado via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
   http_response_code(405); // Método não permitido
   echo json_encode(['error' => 'Método de requisição não permitido.']);
   exit();
}

$autoload = realpath(__DIR__ . '/vendor/autoload.php');
if (!file_exists($autoload)) {
   http_response_code(500); // Erro interno do servidor
   echo json_encode(['error' => "Autoload file not found or on path `$autoload`."]);
   exit();
}
require_once $autoload;

use Efi\Exception\EfiException;
use Efi\EfiPay;

// Define o cabeçalho de resposta como JSON
header('Content-Type: application/json');

// Lê o corpo da requisição bruta (raw input)
$inputJSON = file_get_contents('php://input');
// Decodifica o JSON para um array associativo PHP
$requestData = json_decode($inputJSON, true);

// Verifica se os dados foram decodificados corretamente e se o txid está presente
if (json_last_error() !== JSON_ERROR_NONE || !isset($requestData['txid'])) {
   http_response_code(400); // Requisição inválida
   echo json_encode(['error' => 'Dados JSON inválidos ou "txid" ausente na requisição.']);
   exit();
}

$txid = $requestData['txid'];

// Lê o arquivo json com suas credenciais
$file = file_get_contents(__DIR__ . '/credentials.json');
$options = json_decode($file, true);

try {
   $api = EfiPay::getInstance($options);

   // Parâmetros para consultar a cobrança Pix pelo txid
   $params = [
      'txid' => $txid
   ];

   // Consulta a cobrança Pix na API da Efí
   $pixConsult = $api->pixDetailCharge($params);

   // A API da Efí retorna a estrutura da cobrança.
   // Se a cobrança foi paga, o array 'pix' estará presente e conterá os detalhes do pagamento.
   // O status da cobrança ('ATIVA', 'CONCLUIDA', 'EXPIRADA', etc.) está em $pixConsult['status'].

   $return = [
      "code" => 200,
      "message" => "Consulta de Pix realizada com sucesso!",
      "status" => $pixConsult['status'], // Status da cobrança (ATIVA, CONCLUIDA, EXPIRADA, etc.)
      "txid" => $pixConsult['txid'], // O txid da cobrança
      "valor" => $pixConsult['valor']['original'] // O valor original da cobrança
   ];

   // Se o Pix foi pago, 'pix' será um array contendo os dados do pagamento (incluindo endToEndId)
   if (isset($pixConsult['pix']) && !empty($pixConsult['pix'])) {
      // Para uma cobrança única, o array 'pix' geralmente contém apenas um elemento
      $return['endToEndId'] = $pixConsult['pix'][0]['endToEndId'] ?? null;
      $return['infoPagador'] = $pixConsult['pix'][0]['infoPagador'] ?? null;
   }

   // Retorna o JSON de sucesso
   echo json_encode($return);
} catch (EfiException $e) {
   // Captura erros específicos da Efí
   http_response_code($e->code); // Usa o código HTTP da exceção da Efí
   echo json_encode([
      'error' => 'Erro na API da Efí.',
      'code' => $e->code,
      'message' => $e->error,
      'description' => $e->errorDescription
   ]);
} catch (Exception $e) {
   // Captura outras exceções gerais
   http_response_code(500); // Erro interno do servidor
   echo json_encode([
      'error' => 'Um erro inesperado ocorreu.',
      'message' => $e->getMessage()
   ]);
}
