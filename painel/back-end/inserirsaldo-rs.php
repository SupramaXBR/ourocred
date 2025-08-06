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

// Verifica se os dados foram recebidos
if (isset($resposta['IDECLI'], $resposta['TOKEN'], $_SESSION['token']) && $_SESSION['token'] === $resposta['TOKEN']) {

    // clientes
    $vsIdeMov = gerarIDEMOV();
    $vsIdeCli = $resposta['IDECLI'];
    $vsVlrDpt = $resposta['VLRDPT'];
    $vsTpoMov = $resposta['TPOMOV'];
    $vsDcrMov = $resposta['DCRMOV'];

    //token da resposta
    $token = $resposta['TOKEN'];

    $vsVlrBseClc      = (float) number_format(RetornarValorGrama(), 2);
    $vsStaMov         = 'A';
    $vfSaldo_Reais    = formatarNumeroBanco($resposta['VLRDPT']);
    $vfSaldo_Simple   = 0.00;
    $vfSaldo_Classic  = 0.00;
    $vfSaldo_Standard = 0.00;
    $vfSaldo_Premium  = 0.00;
    $vsCarteira       = 'Reais';
    $vsUsrIns         = 'User';

    // Atualização dos dados do cliente
    try {       

        // Prepara a query para inserir no banco de dados
        $sql = "INSERT INTO clientes_saldo (
                    IDEMOV, IDECLI, DTAMOV, TPOMOV, DCRMOV, VLRBSECLC, STAMOV, USRINS, carteira, saldo_reais, saldo_simple, saldo_classic, saldo_standard, saldo_premium
                ) VALUES (
                    :idemov, :idecli, NOW(), :tpomov, :dcrmov, :vlrbseclc, :stamov, :usrins, :carteira ,:saldo_reais, :saldo_simple, :saldo_classic, :saldo_standard, :saldo_premium
                )";
        
        $stmt = $pdo->prepare($sql);
       
        $stmt->bindParam(':idemov',         $vsIdeMov, PDO::PARAM_STR);
        $stmt->bindParam(':idecli',         $vsIdeCli, PDO::PARAM_STR);
        $stmt->bindParam(':tpomov',         $vsTpoMov, PDO::PARAM_STR);
        $stmt->bindParam(':dcrmov',         $vsDcrMov, PDO::PARAM_STR);
        $stmt->bindParam(':vlrbseclc',      $vsVlrBseClc, PDO::PARAM_STR);
        $stmt->bindParam(':stamov',         $vsStaMov, PDO::PARAM_STR);        
        $stmt->bindParam(':usrins',         $vsUsrIns, PDO::PARAM_STR);                
        $stmt->bindParam(':carteira',       $vsCarteira, PDO::PARAM_STR);                
        $stmt->bindParam(':saldo_reais',    $vfSaldo_Reais, PDO::PARAM_STR);
        $stmt->bindParam(':saldo_simple',   $vfSaldo_Simple, PDO::PARAM_STR);
        $stmt->bindParam(':saldo_classic',  $vfSaldo_Classic, PDO::PARAM_STR);
        $stmt->bindParam(':saldo_standard', $vfSaldo_Standard, PDO::PARAM_STR);
        $stmt->bindParam(':saldo_premium',  $vfSaldo_Premium, PDO::PARAM_STR);

        $sql_LogSaldo = "INSERT INTO log_saldo (IDECLI, IDEMOV, TOKEN, IPUSUARIO, NAVUSUARIO, REFERER) 
                         VALUES (:idecli, :idemov, :token, :ipusuario, :navusuario, :referer)";  

        $stmt_LogSaldo = $pdo->prepare($sql_LogSaldo);

        // Bind dos parâmetros
        $stmt_LogSaldo->bindParam(':idecli', $vsIdeCli, PDO::PARAM_STR);
        $stmt_LogSaldo->bindParam(':idemov', $vsIdeMov, PDO::PARAM_STR);        
        $stmt_LogSaldo->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt_LogSaldo->bindValue(":ipusuario", $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);
        $stmt_LogSaldo->bindValue(":navusuario", $_SERVER['HTTP_USER_AGENT'], PDO::PARAM_STR);
        $stmt_LogSaldo->bindValue(":referer", $_SERVER['HTTP_REFERER'], PDO::PARAM_STR);

        if (!$stmt_LogSaldo->execute()) {
            header('Content-Type: application/json', true, 500);
            echo json_encode(['mensagem' => 'Erro ao atualizar o registro de [Log Saldo]']);
            exit;
        }

        if (!$stmt->execute()) {
            header('Content-Type: application/json', true, 500);
            echo json_encode(['mensagem' => 'Erro ao atualizar o registro de [Saldo Clientes]']);
            exit;
        }

        // Resposta de sucesso ao front-end;
        $response = ['mensagem' => 'Saldo Adicionado com sucesso!'];
        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (PDOException $e) {
        // Erro na inserção
        $response = ['mensagem' => 'Erro ao inserir dados: ' . $e->getMessage()];
        header('Content-Type: application/json', true, 500);
        echo json_encode($response);
    } catch (Exception $e) {
        // Tratamento de erros genéricos
        $response = ['mensagem' => 'Erro inesperado: ' . $e->getMessage()];
        header('Content-Type: application/json', true, 500);
        echo json_encode($response);
    }

} else {
    // Tratamento de erros genéricos
    $response = ['mensagem' => 'Acesso negado: Token inválido ou ausente!'];
    header('Content-Type: application/json', true, 500);
    echo json_encode($response);    
}

?>
