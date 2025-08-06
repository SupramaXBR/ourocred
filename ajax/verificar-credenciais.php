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

error_log(print_r($resposta, true));

// Verifica se os dados foram recebidos
if (isset($resposta['cpf'], $resposta['senha'])) {

    $cpf = $resposta['cpf'];
    $senha = $resposta['senha'];
    $md5pw = md5($senha);

    try {
        // Consulta ao banco de dados para verificar o CPF
        $query = $pdo->prepare("SELECT MD5PW, STACTAATV FROM clientes WHERE CPFCLI = :cpf");
        $query->bindParam(':cpf', $cpf, PDO::PARAM_STR);
        $query->execute();

        if ($query->rowCount() > 0) {
            $dadosCliente = $query->fetch(PDO::FETCH_ASSOC);
            if ($dadosCliente['MD5PW'] === $md5pw) {
                // Senha correta
                if ($dadosCliente['STACTAATV'] != 'S') {
                    $response = ['mensagem' => 'NAO-CONFIRMADA'];
                } else {
                    $response = ['mensagem' => 'ACEITO'];
                }
            } else {
                // Senha incorreta
                $response = ['mensagem' => 'SENHA-INCORRETA'];
            }
        } else {
            // CPF inexistente
            $response = ['mensagem' => 'CPF-INEXISTENTE'];
        }

        // Retorna a resposta
        header('Content-Type: application/json');
        echo json_encode($response);

    } catch (PDOException $e) {
        // Tratamento de erro do banco de dados
        $response = ['mensagem' => 'Erro ao consultar o banco de dados: ' . $e->getMessage()];
        header('Content-Type: application/json', true, 500);
        echo json_encode($response);
    } catch (Exception $e) {
        // Tratamento de erro genérico
        $response = ['mensagem' => 'Erro inesperado: ' . $e->getMessage()];
        header('Content-Type: application/json', true, 500);
        echo json_encode($response);
    }

} else {
    echo json_encode(['mensagem' => 'Dados incompletos.']);
    exit;
}

?>
