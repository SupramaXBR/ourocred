<?php

include_once('../uses/conexao.php');
include_once('../uses/funcoes.php');
require '../uses/phpmailer/PHPMailerAutoload.php';

// Recebe os dados da requisição
$resposta = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    // Se houver erro na decodificação do JSON
    echo json_encode(['mensagem' => 'Erro no JSON recebido.']);
    exit;
}

error_log(print_r($resposta, true));

// Verifica se os dados foram recebidos
if (isset($resposta['idecli'], $resposta['codcli'], $resposta['cpfcli'])) {

    // Dados recebidos
    $idecli = $resposta['idecli'];
    $codcli = gerarNovoCodigoCliente();
    $cpfcli = $resposta['cpfcli'];
    $rgcli  = $resposta['rgcli'];
    $nomcli = $resposta['nomcli'];
    $dtansc = $resposta['dtansc'];
    $maecli = $resposta['maecli'];
    $numtel = $resposta['numtel'];
    $email  = $resposta['email'];
    $cepcli = $resposta['cepcli'];
    $endcli = $resposta['endcli'];
    $numcsa = $resposta['numcsa'];
    $cplend = $resposta['cplend'];
    $baicli = $resposta['baicli'];
    $ufdcli = $resposta['ufdcli'];
    $muncli = $resposta['muncli'];
    $imgPadrao = '';
    $stactaatv = 'S';
    $stacmfeml = 'N';
    $statrm = $resposta['statrm'];
    $senha = $resposta['senha'];  // Senha
    $dtains = date('Y-m-d');  // Data de inserção (hoje)
    $dtaalt = date('Y-m-d');  // Data de alteração (hoje)

    // convertendo variaveis para o padrão correto de uppercase
    $nomcli =  ucwords(mb_strtolower($nomcli, 'UTF-8'));
    $maecli =  ucwords(mb_strtolower($maecli, 'UTF-8'));
    $endcli =  ucwords(mb_strtolower($endcli, 'UTF-8'));
    $cplend =  ucwords(mb_strtolower($cplend, 'UTF-8'));


    // Criptografando a senha com MD5
    $md5pw = md5($senha); 

    // **Verificação do CPF duplicado** antes de tentar inserir
    try {
        $sqlCPFCheck = "SELECT COUNT(*) FROM clientes WHERE CPFCLI = :cpfcli";
        $stmtCPFCheck = $pdo->prepare($sqlCPFCheck);
        $stmtCPFCheck->bindParam(':cpfcli', $cpfcli, PDO::PARAM_STR);
        $stmtCPFCheck->execute();
        
        // Se o CPF já existir na tabela
        $cpfExists = $stmtCPFCheck->fetchColumn();
        if ($cpfExists > 0) {
            // Retorna erro se o CPF já estiver cadastrado
            $response = ['mensagem' => 'CPF já cadastrado!'];
            header('Content-Type: application/json', true, 400);
            echo json_encode($response);
            exit;
        }        

        // Buscar o CODMUNIBGE na tabela municipio com base no UFD e MUNCLI
        $sqlMunicipio = "SELECT CODMUNIBGE FROM municipio WHERE id = :id";
        $stmtMunicipio = $pdo->prepare($sqlMunicipio);
        $stmtMunicipio->bindParam(':id', $muncli, PDO::PARAM_STR);
        $stmtMunicipio->execute();

        // Verifica se encontrou o município
        if ($stmtMunicipio->rowCount() > 0) {
            // Pega o CODMUNIBGE encontrado
            $municipio = $stmtMunicipio->fetch(PDO::FETCH_ASSOC);
            $codmunibge = $municipio['CODMUNIBGE'];
        } else {
            // Se não encontrar, retorna erro
            $response = ['mensagem' => 'Município não encontrado para o estado e cidade fornecidos.'];
            header('Content-Type: application/json', true, 400);
            echo json_encode($response);
            exit;
        }

        // Prepara a query para inserir no banco de dados
        $sql = "INSERT INTO clientes (IDECLI, CODCLI, CPFCLI, RGCLI, NOMCLI, DTANSC, MAECLI, NUMTEL, EMAIL, CEPCLI, ENDCLI, NUMCSA, CPLEND, BAICLI, UFDCLI, CODMUNIBGE, MUNCLI, MD5PW, IMG64, STACTAATV, STACMFEML, STATRM, DTAINS, DTAALT)
                VALUES (:idecli, :codcli, :cpfcli, :rgcli, :nomcli, :dtansc, :maecli, :numtel, :email, :cepcli, :endcli, :numcsa, :cplend, :baicli, :ufdcli, :codmunibge, :muncli, :md5pw, :img64, :stactaatv, :stacmfeml, :statrm, :dtains, :dtaalt)";
        
        $stmt = $pdo->prepare($sql);

        // Bind dos parâmetros
        $stmt->bindParam(':idecli', $idecli, PDO::PARAM_STR);
        $stmt->bindParam(':codcli', $codcli, PDO::PARAM_INT);
        $stmt->bindParam(':cpfcli', $cpfcli, PDO::PARAM_STR);
        $stmt->bindParam(':rgcli', $rgcli, PDO::PARAM_STR);
        $stmt->bindParam(':nomcli', $nomcli, PDO::PARAM_STR);
        $stmt->bindParam(':dtansc', $dtansc, PDO::PARAM_STR);        
        $stmt->bindParam(':maecli', $maecli, PDO::PARAM_STR);
        $stmt->bindParam(':numtel', $numtel, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':cepcli', $cepcli, PDO::PARAM_STR);
        $stmt->bindParam(':endcli', $endcli, PDO::PARAM_STR);
        $stmt->bindParam(':numcsa', $numcsa, PDO::PARAM_STR);
        $stmt->bindParam(':cplend', $cplend, PDO::PARAM_STR);
        $stmt->bindParam(':baicli', $baicli, PDO::PARAM_STR);
        $stmt->bindParam(':ufdcli', $ufdcli, PDO::PARAM_STR);
        $stmt->bindParam(':codmunibge', $codmunibge, PDO::PARAM_INT);
        $stmt->bindParam(':muncli', $muncli, PDO::PARAM_STR);
        $stmt->bindParam(':md5pw', $md5pw, PDO::PARAM_STR);
        $stmt->bindParam(':img64', $imgPadrao, PDO::PARAM_STR);
        $stmt->bindParam(':stactaatv', $stactaatv, PDO::PARAM_STR);
        $stmt->bindParam(':stacmfeml', $stacmfeml, PDO::PARAM_STR);
        $stmt->bindParam(':statrm', $statrm, PDO::PARAM_STR);
        $stmt->bindParam(':dtains', $dtains, PDO::PARAM_STR);
        $stmt->bindParam(':dtaalt', $dtaalt, PDO::PARAM_STR);


        //rotina de envio de email da localweb -- inicio
        $emailusr = $email;

        $emailserver = retornarCampoEmpresa(1,'EMAILNOREPLY'); 
        $senhaserver = retornarCampoEmpresa(1,'PWNOREPLY'); 

        // Instância do PHPMailer
        $mail = new PHPMailer(true);

        // Configurações de envio
        //$mail->SMTPDebug = 3;                 // Modo debug (opcional para testes)
        $mail->isSMTP();                        // Usar SMTP
        $mail->Host = 'email-ssl.com.br';      // Servidor SMTP
        $mail->SMTPAuth = true;                // Autenticação habilitada
        $mail->Username = $emailserver;        // E-mail remetente
        $mail->Password = $senhaserver;        // Senha do remetente
        $mail->Port = 587;                     // Porta SMTP (587 ou 465)
        $mail->CharSet = 'UTF-8';              // <-- ESSENCIAL PARA ACENTOS

        $mail->setFrom($emailserver, 'OuroCred - Confirme seu Cadastro');
        $mail->addAddress($email, $nomcli); // Destinatário
        $mail->isHTML(true);
        $mail->Subject = 'OuroCred - Confirme seu Cadastro';

        $mail->Body    = '<!DOCTYPE html>
                            <html lang="pt-BR">
                            <head>
                                <meta charset="UTF-8">
                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                <title>Confirmação de Cadastro</title>
                                <style>
                                    body {
                                        font-family: Arial, sans-serif;
                                        background-color: #f9f9f9;
                                        margin: 0;
                                        padding: 0;
                                    }
                                    .email-container {
                                        max-width: 600px;
                                        margin: 20px auto;
                                        background-color: #ffffff;
                                        border: 1px solid #ddd;
                                        border-radius: 8px;
                                        overflow: hidden;
                                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                                    }
                                    .email-header {
                                        background-color: #0066ff;
                                        color: #ffffff;
                                        padding: 20px;
                                        text-align: center;
                                    }
                                    .email-header img {
                                        max-height: 50px;
                                        margin-bottom: 10px;
                                    }
                                    .email-body {
                                        padding: 20px;
                                        color: #333333;
                                    }
                                    .email-body h1 {
                                        font-size: 20px;
                                        margin-bottom: 10px;
                                    }
                                    .email-body p {
                                        font-size: 16px;
                                        line-height: 1.5;
                                    }
                                    .email-footer {
                                        text-align: center;
                                        margin: 20px 0;
                                    }
                                    .btn-confirm {
                                        display: inline-block;
                                        padding: 10px 20px;
                                        font-size: 16px;
                                        color: #ffffff;
                                        background-color: #0066ff;
                                        text-decoration: none;
                                        border-radius: 5px;
                                    }
                                    .btn-confirm:hover {
                                        background-color: #0066ff;
                                    }
                                </style>
                            </head>
                            <body>
                                <table class="email-container">
                                    <tr>
                                        <td class="email-header">
                                            <img src="https://ourocreddtvm.com.br/imagens/emaillogo.png" alt="Logo da OuroCred">
                                            <h2>Bem-vindo à OuroCred!</h2>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="email-body">
                                            <h1>Olá, '.$nomcli.' !</h1>
                                            <p>Obrigado por se cadastrar na OuroCred. Para completar seu cadastro e aproveitar todos os nossos serviços, é necessário confirmar o seu e-mail.</p>
                                            <div class="email-footer">
                                                <a href="https://ourocreddtvm.com.br/confirm.php?ide='.$idecli.'&hash='.$md5pw.'" style="color: white;" class="btn-confirm">Confirmar Cadastro</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center; padding: 10px; font-size: 12px; color: #888;">
                                            <p>OuroCred DTVM LTDA<br>Várzea Grande, Mato Grosso</p>
                                            <p>Se você não realizou este cadastro, por favor, ignore este e-mail.</p>
                                        </td>
                                    </tr>
                                </table>
                            </body>
                        </html>'; 
             

        $sql_cpl = "INSERT INTO clientes_cpl (IDECLI, TPODOC, IMG64DOC, IMG64CPREND, STAAPV, DTAINS, DTAALT) 
        VALUES (:idecli, '', '', '', 'N', :dtains, :dtaalt)";

        $stmt_cpl = $pdo->prepare($sql_cpl);

        $stmt_cpl->bindParam(':idecli', $idecli, PDO::PARAM_STR);
        $stmt_cpl->bindParam(':dtains', $dtains);
        $stmt_cpl->bindParam(':dtaalt', $dtaalt);

        //inserção do primeiro registro de saldo para este cliente
        $sql_saldo = "INSERT INTO clientes_saldo (IDEMOV, IDECLI, DTAMOV, TPOMOV, DCRMOV, VLRBSECLC, STAMOV, USRINS, carteira, saldo_reais, saldo_simple, saldo_classic, saldo_standard, saldo_premium)  
                      VALUES (:idemov, :idecli, :dtamov, :tpomov, :dcrmov, :vlrbseclc, :stamov,  :usrins, :carteira, :saldo_reais, :saldo_simple, :saldo_classic, :saldo_standard, :saldo_premium)";
        
        $stmt_saldo = $pdo->prepare($sql_saldo);

        $idemov          = gerarIDEMOV();
        $tpomov          = 'Ajuste';
        $dcrmov          = 'INSERT AUTOMATICO - PRIMEIRO REGISTRO';
        $vlrbseclc       = number_format(RetornarValorGrama(), 2);
        $stamov          = 'A';
        $saldo_reais     = 0;
        $saldo_simple    = 0;
        $saldo_classic   = 0;
        $saldo_standard  = 0;
        $saldo_premium   = 0;
        $carteira        = 'Reais';
        $usrins          = 'Admin';

        $stmt_saldo->bindParam(':idemov', $idemov, PDO::PARAM_STR);
        $stmt_saldo->bindParam(':idecli', $idecli, PDO::PARAM_STR);
        $stmt_saldo->bindParam(':dtamov', $dtains);
        $stmt_saldo->bindParam(':tpomov', $tpomov, PDO::PARAM_STR);
        $stmt_saldo->bindParam(':dcrmov', $dcrmov, PDO::PARAM_STR);
        $stmt_saldo->bindParam(':vlrbseclc', $vlrbseclc);
        $stmt_saldo->bindParam(':stamov', $stamov, PDO::PARAM_STR);
        $stmt_saldo->bindParam(':usrins', $usrins, PDO::PARAM_STR);        
        $stmt_saldo->bindParam(':carteira', $carteira, PDO::PARAM_STR);        
        $stmt_saldo->bindParam(':saldo_reais', $saldo_reais);
        $stmt_saldo->bindParam(':saldo_simple', $saldo_simple);
        $stmt_saldo->bindParam(':saldo_classic', $saldo_classic);
        $stmt_saldo->bindParam(':saldo_standard', $saldo_standard);
        $stmt_saldo->bindParam(':saldo_premium', $saldo_premium);
       

        //inserção do registro de conta bancaria
        $sql_bco = "INSERT INTO clientes_bco (IDECLI, NOMTTL, CPFTTL, CODBCO, NUMAGC, NUMCTA, TPOCTA, STAACTPIX, STAACTCTA, DTAALT, DTAINS)  
                      VALUES (:idecli, :nomttl, :cpfttl, :codbco, :numagc, :numcta, :tpocta, :staactpix, :staactcta, :dtaalt, :dtains)";
                
        $stmt_bco = $pdo->prepare($sql_bco);

        $codBco = '';
        $numAgc = '';
        $numCta = '';
        $tpocta = '';
        $staActPix = 'N';
        $staActCta = 'N';

        $stmt_bco->bindParam(':idecli', $idecli, PDO::PARAM_STR);
        $stmt_bco->bindParam(':nomttl', $nomcli, PDO::PARAM_STR);
        $stmt_bco->bindParam(':cpfttl', $cpfcli, PDO::PARAM_STR);
        $stmt_bco->bindParam(':codbco', $codBco, PDO::PARAM_INT);
        $stmt_bco->bindParam(':numagc', $numAgc, PDO::PARAM_STR);
        $stmt_bco->bindParam(':numcta', $numCta, PDO::PARAM_STR);
        $stmt_bco->bindParam(':tpocta', $tpocta, PDO::PARAM_STR);
        $stmt_bco->bindParam(':staactpix', $staActPix, PDO::PARAM_STR);
        $stmt_bco->bindParam(':staactcta', $staActCta, PDO::PARAM_STR);
        $stmt_bco->bindParam(':dtaalt', $dtaalt);
        $stmt_bco->bindParam(':dtains', $dtains);

        // Inserção do registro inicial na tabela clientes_cfg
        $sql_cfg = "INSERT INTO clientes_cfg (
                                IDECLI,
                                STAACSPERFIL, MTVNEGACSPERFIL,
                                STAACSCOMPRA, MTVNEGACSCOMPRA,
                                STAACSVENDA, MTVNEGACSVENDA,
                                STAACSDEPOSITAR, MTVNEGACSDEPOSITAR,
                                STAACSSACAR, MTVNEGACSSACAR,
                                STAACSHISTORICO, MTVNEGACSHISTORICO,
                                STAACSSAC, MTVNEGACSSAC
                    ) VALUES (
                                :idecli,
                                :staacsperfil, :mtvnegacsperfil,
                                :staacscompra, :mtvnegacscompra,
                                :staacsvenda, :mtvnegacsvenda,
                                :staacsdepositar, :mtvnegacsdepositar,
                                :staacssacar, :mtvnegacssacar,
                                :staacshistorico, :mtvnegacshistorico,
                                :staacssac, :mtvnegacssac
        )";

        $stmt_cfg = $pdo->prepare($sql_cfg);

        // Variáveis iniciais
        $staPadrao = 'S';
        $mtvPadrao = '';

        $stmt_cfg->bindParam(':idecli', $idecli, PDO::PARAM_STR);
        $stmt_cfg->bindParam(':staacsperfil', $staPadrao, PDO::PARAM_STR);
        $stmt_cfg->bindParam(':mtvnegacsperfil', $mtvPadrao, PDO::PARAM_STR);
        $stmt_cfg->bindParam(':staacscompra', $staPadrao, PDO::PARAM_STR);
        $stmt_cfg->bindParam(':mtvnegacscompra', $mtvPadrao, PDO::PARAM_STR);
        $stmt_cfg->bindParam(':staacsvenda', $staPadrao, PDO::PARAM_STR);
        $stmt_cfg->bindParam(':mtvnegacsvenda', $mtvPadrao, PDO::PARAM_STR);
        $stmt_cfg->bindParam(':staacsdepositar', $staPadrao, PDO::PARAM_STR);
        $stmt_cfg->bindParam(':mtvnegacsdepositar', $mtvPadrao, PDO::PARAM_STR);
        $stmt_cfg->bindParam(':staacssacar', $staPadrao, PDO::PARAM_STR);
        $stmt_cfg->bindParam(':mtvnegacssacar', $mtvPadrao, PDO::PARAM_STR);
        $stmt_cfg->bindParam(':staacshistorico', $staPadrao, PDO::PARAM_STR);
        $stmt_cfg->bindParam(':mtvnegacshistorico', $mtvPadrao, PDO::PARAM_STR);
        $stmt_cfg->bindParam(':staacssac', $staPadrao, PDO::PARAM_STR);
        $stmt_cfg->bindParam(':mtvnegacssac', $mtvPadrao, PDO::PARAM_STR);        

        // executar a gravação no DB
        if (!$stmt->execute()) {
            $response = ['mensagem' => 'Erro ao inserir dados [cliente]: ' . $e->getMessage()];
            header('Content-Type: application/json', true, 500);
            echo json_encode($response);
            exit();
        }
        // executar a gravação no DB
        if (!$stmt_cpl->execute()) {
            $response = ['mensagem' => 'Erro ao inserir dados [Complemento de Cliente]: ' . $e->getMessage()];
            header('Content-Type: application/json', true, 500);
            echo json_encode($response);
            exit();
        }        
        // executar a gravação no DB
        if (!$stmt_saldo->execute()) {
            $response = ['mensagem' => 'Erro ao inserir dados [Saldo]: ' . $e->getMessage()];
            header('Content-Type: application/json', true, 500);
            echo json_encode($response);
            exit();
        }            
        // executar a gravação no DB
        if (!$stmt_bco->execute()) {
            $response = ['mensagem' => 'Erro ao inserir dados [Banco]: ' . $e->getMessage()];
            header('Content-Type: application/json', true, 500);
            echo json_encode($response);
            exit();
        }
        if (!$stmt_cfg->execute()) {
            $response = ['mensagem' => 'Erro ao inserir dados [cliente]: ' . $e->getMessage()];
            header('Content-Type: application/json', true, 500);
            echo json_encode($response);
            exit();
        }


        // enviando o email de confirmação de criação de conta
        if(!$mail->send()) {
            $msgsmtp = $mail->ErrorInfo;
        } else {
            $msgsmtp = 'sucesso';
        }        
        
        // Resposta de sucesso ao front-end;
        $response = ['mensagem' => 'Dados inseridos com sucesso!',
                     'msgsmtp'  => $msgsmtp];

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
