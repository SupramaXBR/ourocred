<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");

include_once('../../uses/conexao.php');
include_once('../../uses/funcoes.php');

if (!$pdo) {
    http_response_code(500);
    die(json_encode(['mensagem' => 'Erro de conexão com o banco de dados.']));
}
//referer
$dominiosPermitidos = [
    'www.ourocreddtvm.com.br',
    'ourocreddtvm.com.br',
    '192.168.18.88' // Ambiente de teste (LAMP)
];

// Verifica se o Referer foi enviado
if (!empty($_SERVER['HTTP_REFERER'])) {
    $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);

    // Se o domínio do referer não estiver na lista, bloqueia o acesso
    if (!in_array($referer, $dominiosPermitidos)) {
        error_log("Acesso negado! Origem inválida: $referer");
        die("Acesso negado!");
    }
} else {   
    die("Atenção: Nenhum Referer detectado!");
}

// Lê o JSON recebido
$jsonData = file_get_contents('php://input');

$inputJSON = file_get_contents('php://input');

// Verifica se há dados na requisição
if (!$jsonData) {
    echo json_encode(['mensagem' => 'Nenhum dado recebido na requisição.']);
    exit;
}

$tempoLimite = retornaTempoLimite(1); // definido na tabela empresa
// Verifica tempo de inatividade
$tempoInativo = time() - $_SESSION['cliente']['ultimo_acesso'];
if ($tempoInativo > $tempoLimite) {
    session_unset();
    session_destroy();
    echo json_encode(['mensagem' => 'Sessão expirada por inatividade, volte e faça o login novamente.']);
    header('Content-Type: application/json', true, 400);
    exit;
}


// Decodifica o JSON
$resposta = json_decode($jsonData, true);

// Verifica se a decodificação foi bem-sucedida
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'mensagem' => 'Erro no JSON recebido: ' . json_last_error_msg(),
        'jsonRecebido' => $jsonData  // Exibe o JSON para debug
    ]);
    exit;
}

// Verifica se os dados foram recebidos corretamente e se o token é válido
if (isset($resposta['IDECLI'], $resposta['NUMPTC'], $resposta['TOKEN'], $_SESSION['token']) && $_SESSION['token'] === $resposta['TOKEN']) {

    $vsIdeCli = $resposta['IDECLI'];
    $vsNumPtc = $resposta['NUMPTC'];
    $vsDcrChm = $resposta['DCRCHM'];
    $vsTxtChm = $resposta['TXTCHM'];
    $vsImgChm = $resposta['ARQCHM'];
    $vsUsrIns = 'User';
    $vsUsrAlt = 'User';

    try {
        $sql = "INSERT INTO clientes_chm (
                    IDECLI, NUMPTC, STACHM, DCRCHM, TXTCHM, IMG64CHM, USRALT, USRINS, DTAINS, DTAALT
                ) VALUES (
                    :idecli, :numptc, :stachm, :dcrchm, :txtchm, :img64chm, :usralt, :usrins, NOW(), NOW()
                )";

        $stmt = $pdo->prepare($sql);

        $vsStaChm = 'A'; // Status A = Aberto (ou outro padrão que você preferir)

        $stmt->bindParam(':idecli',    $vsIdeCli, PDO::PARAM_STR);
        $stmt->bindParam(':numptc',    $vsNumPtc, PDO::PARAM_STR);
        $stmt->bindParam(':stachm',    $vsStaChm, PDO::PARAM_STR);
        $stmt->bindParam(':dcrchm',    $vsDcrChm, PDO::PARAM_STR);
        $stmt->bindParam(':txtchm',    $vsTxtChm, PDO::PARAM_STR);
        $stmt->bindParam(':img64chm',  $vsImgChm, PDO::PARAM_STR);
        $stmt->bindParam(':usralt',    $vsUsrAlt, PDO::PARAM_STR);
        $stmt->bindParam(':usrins',    $vsUsrIns, PDO::PARAM_STR);

        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(['mensagem' => 'Erro ao inserir o chamado no banco de dados']);
            exit;
        }

        http_response_code(200);
        echo json_encode(['mensagem' => 'Chamado aberto com sucesso!']);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['mensagem' => 'Erro no banco de dados: ' . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['mensagem' => 'Erro inesperado: ' . $e->getMessage()]);
    }

} else {
    http_response_code(401);
    echo json_encode(['mensagem' => 'Acesso negado: Token inválido ou ausente!']);
}

?>
