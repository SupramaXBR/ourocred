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
error_log("JSON recebido: " . $inputJSON);

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
if (isset($resposta['IDECLI'])) {

    //token
    $vsToken  = $resposta['TOKEN'];
    
    // clientes
    $vsIdeCli = $resposta['IDECLI'];
    $vsNumTel = $resposta['NUMTEL'];
    $vsEmlCli = $resposta['EMLCLI'];
    $vsDtaAlt = date('Y-m-d');  // Data de alteração (hoje)

    // clientes --  Endereço
    $vsCepCli = $resposta['CEPCLI'];
    $vsEndCli = $resposta['ENDCLI'];
    $vsNumCsa = $resposta['NUMCSA'];
    $vsCplEnd = $resposta['CPLEND'];
    $vsBaiCli = $resposta['BAICLI'];
    $vsUfdCli = $resposta['UFDCLI'];
    $vsIdeMun = $resposta['IDEMUN'];

    //clientes_cpl
    $vsTpoDoc = $resposta['TPODOC'];

    //clientes_bco
    $vsCpfTtl = $resposta['CPFTTL'];
    $vsNomTtl = $resposta['NOMTTL'];
    $vsCodBco = $resposta['CODBCO'];
    $vsNumAgc = $resposta['NUMAGC'];
    $vsNumCta = $resposta['NUMCTA'];
    $vsTpoCta = $resposta['TPOCTA'];    
    $vsStaActCta = $resposta['STAACTCTA'];
    $vsStaActPix = $resposta['STAACTPIX'];

    // Imagens BASE64
    $vsImg64       = $resposta['IMG64'];
    $vsImg64Doc    = $resposta['IMG64DOC'];
    $vsImg64CprEnd = $resposta['IMG64CPREND'];


    // verificação do tamanho da imagem
    if (obterTamanhoImagemBase64($vsImg64) > obterMaxkbImgper(1)) {
        header('Content-Type: application/json', true, 400);
        echo json_encode(['mensagem' => 'A imagem de perfil não pode ser maior que ' . obterMaxkbImgper(1) . ' kb',]);
        exit;
    }

    if (obterTamanhoImagemBase64($vsImg64Doc) > obterMaxkbImgdoc(1)) {
        echo json_encode(['mensagem' => 'A foto do documento não pode ser maior que ' . obterMaxkbImgdoc(1) . ' kb']);
        header('Content-Type: application/json', true, 400);
        exit;
    }

    if (obterTamanhoImagemBase64($vsImg64CprEnd) > obterMaxkbImgend(1)) {
        echo json_encode(['mensagem' => 'A foto do comprovante não pode ser maior que ' . obterMaxkbImgend(1) . ' kb']);
        header('Content-Type: application/json', true, 400);
        exit;
    }

    //Verificação para o Status STAATV
    if ($vsImg64Doc == '') {
        $vsStaApv = 'N';
    } else {
        $vsStaApv = 'A';
    }

    $vsEndCli =  ucwords(mb_strtolower($vsEndCli, 'UTF-8'));
    $vsCplEnd =  ucwords(mb_strtolower($vsCplEnd, 'UTF-8'));

    if ($vsToken !== $_SESSION['token']) {
        die('Token Invalido');
    }

    // Atualização dos dados do cliente
    try {
        // Buscar o CODMUNIBGE na tabela municipio com base no UFD e MUNCLI
        $sqlMunicipio = "SELECT CODMUNIBGE FROM municipio WHERE id = :id";
        $stmtMunicipio = $pdo->prepare($sqlMunicipio);
        $stmtMunicipio->bindParam(':id', $vsIdeMun, PDO::PARAM_STR);
        $stmtMunicipio->execute();

        // Verifica se encontrou o município
        if ($stmtMunicipio->rowCount() > 0) {
            // Pega o CODMUNIBGE encontrado
            $municipio  = $stmtMunicipio->fetch(PDO::FETCH_ASSOC);
            $codmunibge = $municipio['CODMUNIBGE'];
        } else {
            // Se não encontrar, retorna erro
            $response = ['mensagem' => 'Município não encontrado para o estado e cidade fornecidos.'];
            header('Content-Type: application/json', true, 400);
            echo json_encode($response);
            exit;
        }

        // Prepara a query para inserir no banco de dados
        $sql = "UPDATE clientes 
                SET EMAIL = :email,
                    NUMTEL = :numtel,
                    CEPCLI = :cepcli,
                    ENDCLI = :endcli,
                    NUMCSA = :numcsa,
                    CPLEND = :cplend,
                    BAICLI = :baicli,
                    UFDCLI = :ufdcli,
                    CODMUNIBGE = :codmunibge,
                    MUNCLI = :muncli,
                    IMG64 = :img64,
                    DTAALT = :dtaalt
                WHERE IDECLI = :idecli;";
        
        $stmt = $pdo->prepare($sql);

        // Bind dos parâmetros
        $stmt->bindParam(':email',  $vsEmlCli, PDO::PARAM_STR);        
        $stmt->bindParam(':numtel', $vsNumTel, PDO::PARAM_STR);
        $stmt->bindParam(':cepcli', $vsCepCli, PDO::PARAM_STR);
        $stmt->bindParam(':endcli', $vsEndCli, PDO::PARAM_STR);
        $stmt->bindParam(':numcsa', $vsNumCsa, PDO::PARAM_STR);
        $stmt->bindParam(':cplend', $vsCplEnd, PDO::PARAM_STR);
        $stmt->bindParam(':baicli', $vsBaiCli, PDO::PARAM_STR);
        $stmt->bindParam(':ufdcli', $vsUfdCli, PDO::PARAM_STR);
        $stmt->bindParam(':codmunibge', $codmunibge, PDO::PARAM_STR);
        $stmt->bindParam(':muncli', $vsIdeMun, PDO::PARAM_STR);
        $stmt->bindParam(':img64',  $vsImg64, PDO::PARAM_STR);
        $stmt->bindParam(':dtaalt', $vsDtaAlt, PDO::PARAM_STR);        
        $stmt->bindParam(':idecli', $vsIdeCli, PDO::PARAM_STR);


        if (!$stmt->execute()) {
            echo json_encode(['mensagem' => 'Erro ao atualizar o registro de Clientes']);
            header('Content-Type: application/json', true, 400);
            exit;
        }

        // Prepara a query para atualizar os dados na tabela clientes_cpl
        $sql_cpl = "UPDATE clientes_cpl 
                    SET 
                        TPODOC = :tpodoc,
                        IMG64DOC = :img64doc,
                        IMG64CPREND = :img64cprend,
                        STAAPV = :staapv
                        WHERE IDECLI = :idecli;";

        $stmt_cpl = $pdo->prepare($sql_cpl);

        // Bind dos parâmetros
        $stmt_cpl->bindParam(':tpodoc', $vsTpoDoc, PDO::PARAM_STR);
        $stmt_cpl->bindParam(':img64doc', $vsImg64Doc, PDO::PARAM_STR);
        $stmt_cpl->bindParam(':img64cprend', $vsImg64CprEnd, PDO::PARAM_STR);
        $stmt_cpl->bindParam(':staapv', $vsStaApv, PDO::PARAM_STR);        
        $stmt_cpl->bindParam(':idecli', $vsIdeCli, PDO::PARAM_STR);

        if (!$stmt_cpl->execute()) {
            echo json_encode(['mensagem' => 'Erro ao atualizar o registro de Complemento de Clientes']);
            header('Content-Type: application/json', true, 400);
            exit;
        }

        // Prepara a query para atualizar os dados na tabela clientes_bco
        $sql_bco = "UPDATE clientes_bco 
                    SET 
                        CPFTTL = :cpfttl,
                        NOMTTL = :nomttl,
                        CODBCO = :codbco,
                        NUMAGC = :numagc,
                        NUMCTA = :numcta,
                        TPOCTA = :tpocta,
                        STAACTCTA = :staactcta,
                        STAACTPIX = :staactpix
                    WHERE IDECLI = :idecli;";

        $stmt_bco = $pdo->prepare($sql_bco);

        // Bind dos parâmetros
        $stmt_bco->bindParam(':cpfttl', $vsCpfTtl, PDO::PARAM_STR);
        $stmt_bco->bindParam(':nomttl', $vsNomTtl, PDO::PARAM_STR);
        $stmt_bco->bindParam(':codbco', $vsCodBco, PDO::PARAM_STR);
        $stmt_bco->bindParam(':numagc', $vsNumAgc, PDO::PARAM_STR);
        $stmt_bco->bindParam(':numcta', $vsNumCta, PDO::PARAM_STR);
        $stmt_bco->bindParam(':tpocta', $vsTpoCta, PDO::PARAM_STR);        
        $stmt_bco->bindParam(':staactcta', $vsStaActCta, PDO::PARAM_STR);
        $stmt_bco->bindParam(':staactpix', $vsStaActPix, PDO::PARAM_STR);
        $stmt_bco->bindParam(':idecli', $vsIdeCli, PDO::PARAM_STR);

        if (!$stmt_bco->execute()) {
            echo json_encode(['mensagem' => 'Erro ao atualizar o registro de Clientes_BCO']);
            header('Content-Type: application/json', true, 400);
            exit;
        }
        
        // Resposta de sucesso ao front-end;
        $response = ['mensagem' => 'Informações Atualizadas com sucesso!'];

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
}

?>
