<?php
    header('Content-Type: application/json');

    // Recebe o CEP enviado pelo JavaScript
    $cep = json_decode(file_get_contents('php://input'), true)['cep'];

    // Chama a API de CEP (usando ViaCEP)
    $response = file_get_contents("https://viacep.com.br/ws/{$cep}/json/");

    // Retorna os dados para o JavaScript
    echo $response;

?>