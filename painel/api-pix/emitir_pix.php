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

// --- Início das Modificações ---

// Lê o corpo da requisição bruta (raw input)
$inputJSON = file_get_contents('php://input');
// Decodifica o JSON para um array associativo PHP
$requestData = json_decode($inputJSON, true);

// Verifica se os dados foram decodificados corretamente
if (json_last_error() !== JSON_ERROR_NONE || empty($requestData)) {
   http_response_code(400); // Requisição inválida
   echo json_encode(['error' => 'Dados JSON inválidos ou vazios na requisição.']);
   exit();
}

// Verifica se todos os campos necessários existem nos dados recebidos
$requiredFields = ['expiracao', 'cpf', 'nome_cliente', 'valor', 'descricao'];
foreach ($requiredFields as $field) {
   if (!isset($requestData[$field])) {
      http_response_code(400); // Requisição inválida
      echo json_encode(['error' => "Campo '$field' ausente na requisição."]);
      exit();
   }
}

// Lê o arquivo json com suas credenciais
$file = file_get_contents(__DIR__ . '/credentials.json');
$options = json_decode($file, true);

// Prepara o corpo da requisição para a API da Efí usando os dados do Fetch
$body = [
   "calendario" => [
      "expiracao" => (int) $requestData["expiracao"]
   ],
   "devedor" => [
      "cpf" => $requestData["cpf"],
      "nome" => $requestData["nome_cliente"]
   ],
   "valor" => [
      "original" => $requestData["valor"] // Ex: 0.01
   ],
   "chave" => "ourocreddtvm@gmail.com", // Chave pix da conta Efí do recebedor
   "infoAdicionais" => [
      [
         "nome" => "Produto/Serviço", // Nome do campo string (Nome) ≤ 50 characters
         "valor" => $requestData["descricao"] // Dados do campo string (Valor) ≤ 200 characters
      ]
   ]
];

try {
   $api = EfiPay::getInstance($options);
   $pix = $api->pixCreateImmediateCharge($params = [], $body);

   if (isset($pix['txid'])) { // Usar isset para verificar a existência da chave
      $params = [
         'id' => $pix['loc']['id']
      ];

      // Gera QRCode
      $qrcode = $api->pixGenerateQRCode($params);

      $return = [
         "code" => 200,
         "message" => "Pix gerado com sucesso!",
         "pix" => $pix,
         "qrcode" => $qrcode
      ];

      // Retorna o JSON de sucesso
      echo json_encode($return);
   } else {
      // Se txid não existe, algo deu errado na criação da cobrança
      // Retornar a resposta da Efí para depuração
      http_response_code(400); // Bad Request ou erro da API da Efí
      echo json_encode(['error' => 'Falha ao criar a cobrança Pix.', 'details' => $pix]);
   }
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

// --- Fim das Modificações ---
