<?php
// Recebe os dados da requisição
$resposta = json_decode(file_get_contents('php://input'), true);

// Verifica se os dados foram recebidos
if (isset($resposta['codigo'], $resposta['nome'], $resposta['email'])) {
    // Aqui você pode processar os dados (ex: salvar no banco de dados)

    // Exemplo de resposta
    $response = [
        'mensagem' => 'Dados recebidos com sucesso!',
        'codigo' => $resposta['codigo'],
        'nome' => $resposta['nome'],
        'email' => $resposta['email']
    ];
    
    // Define o cabeçalho para resposta JSON
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Resposta de erro se os dados não forem válidos
    header('Content-Type: application/json', true, 400);
    echo json_encode(['mensagem' => 'Dados inválidos!']);
}
?>