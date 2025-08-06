<?php

include_once('../uses/conexao.php');
include_once('../uses/funcoes.php');
require '../uses/phpmailer/PHPMailerAutoload.php';

// Recebe os dados da requisição
$resposta = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['mensagem' => 'Erro no JSON recebido.']);
    exit;
}

// Verifica se o CPF foi enviado
if (!isset($resposta['idecli']) || empty($resposta['idecli'])) {
    echo json_encode(['mensagem' => 'Id não recebido']);
    exit;
}

$idecli    = $resposta['idecli'];
$novasenha = $resposta['novasenha'];

try {
    // Gera o hash MD5 da nova senha
    $md5pw = md5($novasenha);

    // Atualiza o campo md5pw na tabela clientes
    $sql = "UPDATE clientes SET md5pw = :md5pw WHERE idecli = :idecli";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':md5pw', $md5pw);
    $stmt->bindParam(':idecli', $idecli);

    if ($stmt->execute()) {
        $mensagem = 'Sucesso';
    } else {
        $mensagem = 'Erro ao atualizar a senha';
    }

    // Resposta em JSON
    $response = [
        'mensagem' => $mensagem,        
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (PDOException $e) {
    // Tratamento de erro na consulta
    $response = [
        'mensagem' => 'Erro ao acessar o banco de dados: ' . $e->getMessage()
    ];
    header('Content-Type: application/json', true, 500);
    echo json_encode($response);
}

?>
