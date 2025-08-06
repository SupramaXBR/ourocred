<?php

header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");
// Inclua seu arquivo de conexão e funções
require_once '../../uses/conexao.php';
require_once '../../uses/funcoes.php';

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

// Recebe o JSON enviado via AJAX
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['usuario'], $input['senha'])) {
    echo json_encode(['status' => 'error', 'message' => 'Dados incompletos!']);
    exit;
}

$usuario = $input['usuario'];
$senha = md5($input['senha']);

// Supondo que $vsCodEmp esteja definido (defina como necessário no contexto da sua aplicação)
$vsCodEmp = 1; // exemplo

$credenciais = obterCredenciaisAdminEmpresa($vsCodEmp);

if ($credenciais) {
    if ($usuario === $credenciais['USRADMIN'] && $senha === $credenciais['PWADMIN']) {
        $_SESSION['admin'] = $usuario;
        echo json_encode(['status' => 'success', 'redirect' => 'redirect.php']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Usuário ou senha incorretos']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Empresa não encontrada ou configuração inválida']);
}

?>