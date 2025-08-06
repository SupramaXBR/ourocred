<?php
// Inclui o arquivo de conexão
include_once 'conexao.php';
require_once __DIR__ . "/request.php";


function corrigirTexto($texto)
{
   // Verifica se o texto não está em UTF-8
   if (mb_detect_encoding($texto, 'UTF-8', true) === false) {
      // Converte o texto para UTF-8 assumindo que está em ISO-8859-1
      $texto = mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
   }
   return $texto;
}

function RetornarValorGrama(): float
{
   $peso_onca = 31.1034768; // Peso de 1 onça troy em gramas

   // Obter cotação do Ouro (XAU-USD)
   $requestOuro = new Request();
   $requestOuro->setUrl("https://economia.awesomeapi.com.br/last/XAU-USD?token=85d4e2aa9a21f8b0cece488a26fc1588e9a0e1ab57deb3110625c12558b05da3");
   $dadosOuro = $requestOuro->requestApi();

   if (!is_array($dadosOuro) || !isset($dadosOuro["XAUUSD"]["high"])) {
      error_log("RetornarValorGrama(): Falha ao obter dados do ouro.");
      return 0.00;
   }
   $valorOncaUSD = (float) $dadosOuro["XAUUSD"]["high"];

   // Obter cotação do Dólar (USD-BRL)
   $requestDollar = new Request();
   $requestDollar->setUrl("https://economia.awesomeapi.com.br/last/USD-BRL?token=85d4e2aa9a21f8b0cece488a26fc1588e9a0e1ab57deb3110625c12558b05da3");
   $dadosDollar = $requestDollar->requestApi();

   if (!is_array($dadosDollar) || !isset($dadosDollar["USDBRL"]["high"])) {
      error_log("RetornarValorGrama(): Falha ao obter dados do dólar.");
      return 0.00;
   }
   $valorDollarBRL = (float) $dadosDollar["USDBRL"]["high"];

   // Calcular o valor da grama em Reais
   $valorGramaUSD = $valorOncaUSD / $peso_onca;
   $valorGramaReal = $valorGramaUSD * $valorDollarBRL;

   return $valorGramaReal;
}

function converterParaJson($array)
{
   // Converte o array para UTF-8 usando mb_convert_encoding
   array_walk_recursive($array, function (&$item) {
      if (is_string($item)) {
         $item = mb_convert_encoding($item, 'UTF-8', 'ISO-8859-1');
      }
   });

   // Converte o array para JSON
   $json = json_encode($array);

   // Verifica se a conversão foi bem-sucedida
   if ($json === false) {
      return "Erro ao converter para JSON: " . json_last_error_msg();
   }

   return $json;
}

function retornarCampoEmpresa($codemp, $nomedocampo)
{

   global $pdo;
   try {
      // Monta a query dinâmica com parâmetros preparados
      $query = "SELECT $nomedocampo FROM empresa WHERE codemp = :codemp";
      $stmt = $pdo->prepare($query);

      // Vincula o parâmetro do código da empresa
      $stmt->bindParam(':codemp', $codemp, PDO::PARAM_INT);

      // Executa a consulta
      $stmt->execute();

      // Busca o resultado
      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      // Retorna o valor do campo se existir, ou null caso não exista
      return $result[$nomedocampo] ?? null;
   } catch (PDOException $e) {
      // Lida com erros de execução
      error_log('Erro ao buscar campo da empresa: ' . $e->getMessage());
      return null;
   }
}

function mascararCPF($cpf)
{
   // Verifica se o CPF está no formato correto (xxx.xxx.xxx-xx)
   if (!preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $cpf)) {
      return "CPF inválido";
   }

   // Aplica a máscara, substituindo os caracteres centrais
   return substr($cpf, 0, 1) . '**.***.***-' . substr($cpf, -2);
}

function formatarDataBR($data)
{
   // Verifica se a data está no formato correto (AAAA-MM-DD)
   if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
      return "Data inválida";
   }

   // Converte para o formato DD/MM/AAAA
   return date("d/m/Y", strtotime($data));
}

function ImagemPadrao($codreg)
{
   global $pdo; // Usa a conexão do conexao.php

   try {
      $stmt = $pdo->prepare("SELECT imgsemfoto FROM img_padrao WHERE codreg = :codreg LIMIT 1");
      $stmt->bindParam(':codreg', $codreg, PDO::PARAM_INT);
      $stmt->execute();
      $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

      return $resultado ? $resultado['imgsemfoto'] : null;
   } catch (PDOException $e) {
      return null; // Retorna null em caso de erro
   }
}

function calcularPorcentagem($x, $y)
{
   if ($y == 0) {
      return "Erro: O segundo parâmetro (Y) não pode ser zero.";
   }

   $porcentagem = ($x / $y) * 100;
   return round($porcentagem, 2) . "%"; // Arredonda para 2 casas decimais
}

function contarCamposVazios_clientes($idecli)
{
   global $pdo; // Usa a conexão do conexao.php

   try {
      // Seleciona os campos das tabelas clientes_cpl e clientes_bco
      $sql = "SELECT TPODOC, IMG64DOC, IMG64CPREND FROM clientes_cpl WHERE IDECLI = :idecli";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':idecli', $idecli);
      $stmt->execute();
      $registro_cpl = $stmt->fetch(PDO::FETCH_ASSOC);

      $sql = "SELECT CODBCO, NUMAGC, NUMCTA, TPOCTA, STAACTPIX, STAACTCTA FROM clientes_bco WHERE IDECLI = :idecli";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':idecli', $idecli);
      $stmt->execute();
      $registro_bco = $stmt->fetch(PDO::FETCH_ASSOC);

      // Se não encontrar nenhum registro em ambas as tabelas, retorna -1
      if (!$registro_cpl && !$registro_bco) {
         return -1;
      }

      // Conta os campos vazios das duas tabelas
      $camposVazios = 0;

      if ($registro_cpl) {
         foreach ($registro_cpl as $valor) {
            if (empty($valor)) {
               $camposVazios++;
            }
         }
      }

      if ($registro_bco) {
         foreach ($registro_bco as $campo => $valor) {
            // Se for STAACTPIX ou STAACTCTA e estiver 'N', conta como vazio
            if (empty($valor) || (($campo === 'STAACTPIX' || $campo === 'STAACTCTA') && $valor === 'N')) {
               $camposVazios++;
            }
         }
      }

      return $camposVazios;
   } catch (PDOException $e) {
      return "Erro: " . $e->getMessage();
   }
}


function primeiroUltimoNome($nome)
{
   $partes = explode(" ", trim($nome)); // Divide o nome em partes
   $primeiro = $partes[0]; // Primeiro nome
   $ultimo = end($partes); // Último nome
   return "$primeiro $ultimo";
}

//obter saldos
function obterSaldoReais($idecli)
{
   global $pdo;
   $sql = "SELECT SUM(saldo_reais) AS saldo FROM clientes_saldo WHERE IDECLI = :idecli AND STAMOV <> 'N'";
   $stmt = $pdo->prepare($sql);
   $stmt->bindParam(':idecli', $idecli);
   $stmt->execute();
   return $stmt->fetch(PDO::FETCH_ASSOC)['saldo'] ?? 0;
}

function obterSaldoSimple($idecli)
{
   global $pdo;
   $sql = "SELECT SUM(saldo_simple) AS saldo FROM clientes_saldo WHERE IDECLI = :idecli AND STAMOV <> 'N'";
   $stmt = $pdo->prepare($sql);
   $stmt->bindParam(':idecli', $idecli);
   $stmt->execute();
   return $stmt->fetch(PDO::FETCH_ASSOC)['saldo'] ?? 0;
}

function obterSaldoClassic($idecli)
{
   global $pdo;
   $sql = "SELECT SUM(saldo_classic) AS saldo FROM clientes_saldo WHERE IDECLI = :idecli AND STAMOV <> 'N'";
   $stmt = $pdo->prepare($sql);
   $stmt->bindParam(':idecli', $idecli);
   $stmt->execute();
   return $stmt->fetch(PDO::FETCH_ASSOC)['saldo'] ?? 0;
}

function obterSaldoStandard($idecli)
{
   global $pdo;
   $sql = "SELECT SUM(saldo_standard) AS saldo FROM clientes_saldo WHERE IDECLI = :idecli AND STAMOV <> 'N'";
   $stmt = $pdo->prepare($sql);
   $stmt->bindParam(':idecli', $idecli);
   $stmt->execute();
   return $stmt->fetch(PDO::FETCH_ASSOC)['saldo'] ?? 0;
}

function obterSaldoPremium($idecli)
{
   global $pdo;
   $sql = "SELECT SUM(saldo_premium) AS saldo FROM clientes_saldo WHERE IDECLI = :idecli AND STAMOV <> 'N'";
   $stmt = $pdo->prepare($sql);
   $stmt->bindParam(':idecli', $idecli);
   $stmt->execute();
   return $stmt->fetch(PDO::FETCH_ASSOC)['saldo'] ?? 0;
}
////////

function converterDataDropDown($indice, $data)
{
   // Converte a string para um objeto DateTime
   $dataObj = DateTime::createFromFormat('Y-m-d', $data);

   if (!$dataObj) {
      return "Data inválida";
   }

   // Array de meses em português
   $meses = [
      1 => 'Janeiro',
      2 => 'Fevereiro',
      3 => 'Março',
      4 => 'Abril',
      5 => 'Maio',
      6 => 'Junho',
      7 => 'Julho',
      8 => 'Agosto',
      9 => 'Setembro',
      10 => 'Outubro',
      11 => 'Novembro',
      12 => 'Dezembro'
   ];

   switch ($indice) {
      case 1:
         return $dataObj->format('d'); // Retorna o dia (ex: 31)
      case 2:
         return $meses[(int)$dataObj->format('m')]; // Retorna o nome do mês (ex: Maio)
      case 3:
         return $dataObj->format('Y'); // Retorna o ano (ex: 1992)
      default:
         return "Índice inválido";
   }
}

function verificarEmailVerificado($idecli)
{
   global $pdo; // Usa a conexão já incluída no arquivo funções.php

   // Prepara a consulta para obter o status da confirmação do e-mail
   $sql = "SELECT STACMFEML FROM clientes WHERE IDECLI = :idecli";
   $stmt = $pdo->prepare($sql);
   $stmt->bindParam(':idecli', $idecli, PDO::PARAM_STR);
   $stmt->execute();

   // Obtém o resultado
   $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

   // Verifica se encontrou o cliente e retorna S ou N
   if ($resultado) {
      return $resultado['STACMFEML']; // Retorna S ou N conforme o banco de dados
   } else {
      return null; // Retorna null se o cliente não for encontrado
   }
}

function obterStatusAprovacao($idecli)
{
   global $pdo;

   $sql = "SELECT STAAPV FROM clientes_cpl WHERE IDECLI = :idecli LIMIT 1";
   $stmt = $pdo->prepare($sql);
   $stmt->bindParam(':idecli', $idecli, PDO::PARAM_INT);
   $stmt->execute();
   $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

   return ($resultado && $resultado['STAAPV'] === 'A') ? 'Documentos Aguardando Aprovação' : '';
}

function mascararTelefone($numero)
{
   // Remove qualquer caractere que não seja número
   $numero = preg_replace('/\D/', '', $numero);

   // Verifica se tem DDD + número
   if (strlen($numero) === 11) {
      return "(" . substr($numero, 0, 2) . ") " . substr($numero, 2, 5) . "-" . substr($numero, 7);
   } elseif (strlen($numero) === 10) {
      return "(" . substr($numero, 0, 2) . ") " . substr($numero, 2, 4) . "-" . substr($numero, 6);
   } else {
      return "Número inválido";
   }
}

function formatarNumeroBanco($valor)
{
   return (float) str_replace(',', '.', str_replace('.', '', $valor));
}

function obterValorDescGramaVendida()
{
   global $pdo;

   try {
      $sql = "SELECT VLRDSCGRMVDA FROM empresa WHERE CODEMP = 1";
      $stmt = $pdo->prepare($sql);
      $stmt->execute();

      $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
      return $resultado ? $resultado['VLRDSCGRMVDA'] : null;
   } catch (PDOException $e) {
      die("Erro ao obter valor: " . $e->getMessage());
   }
}

function gerarNovoCodigoCliente()
{
   global $pdo;  // Declarar $pdo como global para usá-la dentro da função

   // Consulta para obter o maior valor atual de CODCLI
   $sql = "SELECT MAX(CODCLI) AS max_codcli FROM clientes";
   $stmt = $pdo->prepare($sql);
   $stmt->execute();

   // Pega o valor máximo de CODCLI ou define como 0 se não houver registros
   $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
   $novoCodigo = $resultado['max_codcli'] ? $resultado['max_codcli'] + 1 : 1;

   return $novoCodigo;
}

function gerarIDEMOV($tamanho = 12)
{
   // Gera um código aleatório numérico com 12 caracteres
   $codigo = '';

   for ($i = 0; $i < $tamanho; $i++) {
      $codigo .= rand(0, 9);  // Adiciona um número aleatório de 0 a 9
   }

   return $codigo;
}

function inserirLogLogin()
{
   global $pdo;
   try {
      $sql = "INSERT INTO log_login (TOKEN, IPUSUARIO, NAVUSUARIO, REFERER) 
                    VALUES (:token, :ipusuario, :navusuario, :referer)";
      $stmt = $pdo->prepare($sql);

      // Utiliza bindValue para vincular imediatamente os valores
      $stmt->bindValue(":token", $_SESSION['token'], PDO::PARAM_STR);
      $stmt->bindValue(":ipusuario", $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);
      $stmt->bindValue(":navusuario", $_SERVER['HTTP_USER_AGENT'], PDO::PARAM_STR);
      $stmt->bindValue(":referer", $_SERVER['HTTP_REFERER'], PDO::PARAM_STR);

      // Executa a query e verifica se houve erro
      if (!$stmt->execute()) {
         $errorInfo = $stmt->errorInfo();
         error_log("Erro ao executar query: " . $errorInfo[2]);
         exit("Erro ao executar query: " . $errorInfo[2]);
      }

      // Recupera o código gerado pelo autoincremento
      $codreg = $pdo->lastInsertId();
      return $codreg;
   } catch (PDOException $e) {
      error_log("Erro ao inserir log: " . $e->getMessage());
      exit("Erro ao inserir log: " . $e->getMessage());
   }
}

function atualizarLogLogin($vsCodReg, $vsIdeCli, $vsStaAcs)
{
   global $pdo;
   try {
      $sql = "UPDATE log_login SET IDECLI = :idecli, STAACS = :staacs WHERE CODREG = :codreg";
      $stmt = $pdo->prepare($sql);

      // Vincula os parâmetros com os valores fornecidos
      $stmt->bindParam(":idecli", $vsIdeCli);
      $stmt->bindParam(":staacs", $vsStaAcs);
      $stmt->bindParam(":codreg", $vsCodReg);

      // Executa a query e verifica se houve erro
      if (!$stmt->execute()) {
         $errorInfo = $stmt->errorInfo();
         error_log("Erro ao executar query: " . $errorInfo[2]);
         exit("Erro ao executar query: " . $errorInfo[2]);
      }

      return true;
   } catch (PDOException $e) {
      // Lida com erros de execução
      error_log('Erro ao atualizar log: ' . $e->getMessage());
      return false;
   }
}

// funcoes retorna informações dos planos/carteiras
function retornarQtddiaClassic($codemp)
{
   global $pdo;
   try {
      $sql = "SELECT QTDDIACLASSIC FROM empresa WHERE CODEMP = :codemp";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':codemp', $codemp, PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return isset($result['QTDDIACLASSIC']) ? $result['QTDDIACLASSIC'] : null;
   } catch (PDOException $e) {
      error_log("Erro ao buscar QTDDIACLASSIC: " . $e->getMessage());
      return null;
   }
}

function retornarPerdscClassic($codemp)
{
   global $pdo;
   try {
      $sql = "SELECT PERDSCCLASSIC FROM empresa WHERE CODEMP = :codemp";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':codemp', $codemp, PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return isset($result['PERDSCCLASSIC']) ? $result['PERDSCCLASSIC'] : null;
   } catch (PDOException $e) {
      error_log("Erro ao buscar PERDSCCLASSIC: " . $e->getMessage());
      return null;
   }
}

function retornarQtddiaStandard($codemp)
{
   global $pdo;
   try {
      $sql = "SELECT QTDDIASTANDARD FROM empresa WHERE CODEMP = :codemp";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':codemp', $codemp, PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return isset($result['QTDDIASTANDARD']) ? $result['QTDDIASTANDARD'] : null;
   } catch (PDOException $e) {
      error_log("Erro ao buscar QTDDIASTANDARD: " . $e->getMessage());
      return null;
   }
}

function retornarPerdscStandard($codemp)
{
   global $pdo;
   try {
      $sql = "SELECT PERDSCSTANDARD FROM empresa WHERE CODEMP = :codemp";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':codemp', $codemp, PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return isset($result['PERDSCSTANDARD']) ? $result['PERDSCSTANDARD'] : null;
   } catch (PDOException $e) {
      error_log("Erro ao buscar PERDSCSTANDARD: " . $e->getMessage());
      return null;
   }
}

function retornarQtddiaPremium($codemp)
{
   global $pdo;
   try {
      $sql = "SELECT QTDDIAPREMIUM FROM empresa WHERE CODEMP = :codemp";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':codemp', $codemp, PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return isset($result['QTDDIAPREMIUM']) ? $result['QTDDIAPREMIUM'] : null;
   } catch (PDOException $e) {
      error_log("Erro ao buscar QTDDIAPREMIUM: " . $e->getMessage());
      return null;
   }
}

function retornarPerdscPremium($codemp)
{
   global $pdo;
   try {
      $sql = "SELECT PERDSCPREMIUM FROM empresa WHERE CODEMP = :codemp";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':codemp', $codemp, PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return isset($result['PERDSCPREMIUM']) ? $result['PERDSCPREMIUM'] : null;
   } catch (PDOException $e) {
      error_log("Erro ao buscar PERDSCPREMIUM: " . $e->getMessage());
      return null;
   }
}

function retornaTempoLimite($codemp)
{
   global $pdo;

   try {
      $sql = "SELECT TPOLMT_SEG FROM empresa WHERE CODEMP = :codemp LIMIT 1";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':codemp', $codemp, PDO::PARAM_INT);
      $stmt->execute();

      $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($resultado && isset($resultado['TPOLMT_SEG'])) {
         return (int)$resultado['TPOLMT_SEG'];
      } else {
         // Se não encontrar, retorna um valor padrão (ex: 300 segundos)
         return 300;
      }
   } catch (PDOException $e) {
      // Em caso de erro, retorna também um valor padrão
      return 300;
   }
}

function retornarDataUltimaCompra($idecli, $carteira)
{
   global $pdo;

   try {
      $sql = "SELECT MAX(DTAMOV) AS ultima_data 
                    FROM clientes_saldo 
                    WHERE IDECLI = :idecli 
                      AND carteira = :carteira 
                      AND TPOMOV = 'Compra'";

      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':idecli', $idecli, PDO::PARAM_STR);
      $stmt->bindParam(':carteira', $carteira, PDO::PARAM_STR);
      $stmt->execute();

      $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($resultado && isset($resultado['ultima_data']) && !empty($resultado['ultima_data'])) {
         return $resultado['ultima_data'];
      } else {
         return null; // Nenhuma compra encontrada
      }
   } catch (PDOException $e) {
      // Em caso de erro, retorna null
      return null;
   }
}

function obterValorGramaVendaComDesconto($codemp, $carteira)
{
   $valorGrama = RetornarValorGrama();
   $valorDescontoBase = obterValorDescGramaVendida();

   switch ($carteira) {
      case 'Standard':
         $percentual = retornarPerdscStandard($codemp);
         break;
      case 'Classic':
         $percentual = retornarPerdscClassic($codemp);
         break;
      case 'Premium':
         $percentual = retornarPerdscPremium($codemp);
         break;
      case 'Simple':
      default:
         $percentual = 0;
         break;
   }

   if ($percentual > 0) {
      $valorDescontoFinal = $valorDescontoBase - ($valorDescontoBase * ($percentual / 100));
   } else {
      $valorDescontoFinal = $valorDescontoBase;
   }

   $valorVenda = $valorGrama - $valorDescontoFinal;

   return number_format($valorVenda, 2, ',', '.');
}

function obterTamanhoImagemBase64($base64)
{
   // Remove prefixo "data:image/...;base64," se houver
   if (strpos($base64, ',') !== false) {
      $base64 = explode(',', $base64)[1];
   }

   // Calcula o tamanho em bytes
   $tamanhoBytes = (int)(strlen($base64) * 3 / 4);

   // Converte para KB com 2 casas decimais
   $tamanhoKB = round($tamanhoBytes / 1024, 2);

   return $tamanhoKB;
}

function obterMaxkbImgper($codemp)
{
   global $pdo;
   try {
      $sql = "SELECT MAXKBIMGPER FROM empresa WHERE CODEMP = :codemp";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':codemp', $codemp, PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return isset($result['MAXKBIMGPER']) ? (int)$result['MAXKBIMGPER'] : null;
   } catch (PDOException $e) {
      error_log("Erro ao buscar MAXKBIMGPER: " . $e->getMessage());
      return null;
   }
}

function obterMaxkbImgdoc($codemp)
{
   global $pdo;
   try {
      $sql = "SELECT MAXKBIMGDOC FROM empresa WHERE CODEMP = :codemp";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':codemp', $codemp, PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return isset($result['MAXKBIMGDOC']) ? (int)$result['MAXKBIMGDOC'] : null;
   } catch (PDOException $e) {
      error_log("Erro ao buscar MAXKBIMGDOC: " . $e->getMessage());
      return null;
   }
}

function obterMaxkbImgend($codemp)
{
   global $pdo;
   try {
      $sql = "SELECT MAXKBIMGEND FROM empresa WHERE CODEMP = :codemp";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':codemp', $codemp, PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return isset($result['MAXKBIMGEND']) ? (int)$result['MAXKBIMGEND'] : null;
   } catch (PDOException $e) {
      error_log("Erro ao buscar MAXKBIMGEND: " . $e->getMessage());
      return null;
   }
}

function atualizaEmailCliente($idecli, $email)
{
   global $pdo;
   try {
      $sql = "UPDATE clientes SET EMAIL = :email WHERE IDECLI = :idecli";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':email', $email, PDO::PARAM_STR);
      $stmt->bindParam(':idecli', $idecli, PDO::PARAM_INT);
      return $stmt->execute();
   } catch (PDOException $e) {
      error_log("Erro ao atualizar EMAIL do cliente: " . $e->getMessage());
      return false;
   }
}

function obterDadosCliente($idecli, $codcli)
{
   global $pdo;

   $sql = "SELECT * FROM clientes WHERE IDECLI = :idecli AND CODCLI = :codcli LIMIT 1";
   $stmt = $pdo->prepare($sql);
   $stmt->bindParam(':idecli', $idecli);
   $stmt->bindParam(':codcli', $codcli);
   $stmt->execute();

   if ($stmt->rowCount() > 0) {
      return $stmt->fetch(PDO::FETCH_ASSOC); // Retorna array associativo com todos os campos
   } else {
      return null; // Caso nenhum registro seja encontrado
   }
}

function gerarProtocoloChamado()
{
   // Data atual no formato AAMMDD
   $data = date('ymd');

   // Número aleatório de 4 dígitos (com zeros à esquerda se necessário)
   $numeroAleatorio = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

   // Protocolo final com 10 dígitos
   return $data . $numeroAleatorio;
}

function obterChamadosCliente($idecli = null)
{
   global $pdo;

   try {
      // Se vier vazio (consulta geral)
      if (empty($idecli)) {
         $sql = "SELECT 
                     clientes_chm.CODREG,
                     clientes_chm.IDECLI,
                     clientes_chm.NUMPTC,
                     clientes_chm.STACHM,
                     clientes_chm.DCRCHM,
                     clientes_chm.TXTCHM,
                     clientes_chm.IMG64CHM,
                     clientes_chm.USRALT,
                     clientes_chm.USRINS,
                     clientes_chm.DTAINS,
                     clientes_chm.DTAALT,
                     clientes.NOMCLI,
                     clientes.CPFCLI
                 FROM clientes_chm
                 LEFT JOIN clientes ON clientes.IDECLI = clientes_chm.IDECLI
                 WHERE clientes_chm.STACHM != 'C'
                 ORDER BY clientes_chm.DTAINS DESC";
         $stmt = $pdo->prepare($sql);

         // Se for um filtro por status
      } elseif (in_array($idecli, ['A', 'F', 'C'])) {
         $sql = "SELECT 
                     clientes_chm.CODREG,
                     clientes_chm.IDECLI,
                     clientes_chm.NUMPTC,
                     clientes_chm.STACHM,
                     clientes_chm.DCRCHM,
                     clientes_chm.TXTCHM,
                     clientes_chm.IMG64CHM,
                     clientes_chm.USRALT,
                     clientes_chm.USRINS,
                     clientes_chm.DTAINS,
                     clientes_chm.DTAALT,
                     clientes.NOMCLI,
                     clientes.CPFCLI
                 FROM clientes_chm
                 LEFT JOIN clientes ON clientes.IDECLI = clientes_chm.IDECLI
                 WHERE clientes_chm.STACHM = :status
                 ORDER BY clientes_chm.DTAINS DESC";
         $stmt = $pdo->prepare($sql);
         $stmt->bindParam(':status', $idecli, PDO::PARAM_STR);

         // Se for uma IDECLI específica
      } elseif (strlen($idecli) >= 9 && strlen($idecli) <= 10) {
         $sql = "SELECT 
                     clientes_chm.CODREG,
                     clientes_chm.IDECLI,
                     clientes_chm.NUMPTC,
                     clientes_chm.STACHM,
                     clientes_chm.DCRCHM,
                     clientes_chm.TXTCHM,
                     clientes_chm.IMG64CHM,
                     clientes_chm.USRALT,
                     clientes_chm.USRINS,
                     clientes_chm.DTAINS,
                     clientes_chm.DTAALT,
                     clientes.NOMCLI,
                     clientes.CPFCLI
                 FROM clientes_chm
                 LEFT JOIN clientes ON clientes.IDECLI = clientes_chm.IDECLI
                 WHERE clientes_chm.IDECLI = :idecli
                 ORDER BY clientes_chm.DTAINS DESC";
         $stmt = $pdo->prepare($sql);
         $stmt->bindParam(':idecli', $idecli, PDO::PARAM_STR);
      } else {
         return [];
      }

      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
   } catch (PDOException $e) {
      // log opcional: error_log($e->getMessage());
      return [];
   }
}



function obterStatusDeAcesso($idecli, $pagina)
{
   global $pdo;

   // Definindo campos com base na página solicitada
   $campos = [
      'perfil' => ['STAACSPERFIL', 'MTVNEGACSPERFIL'],
      'compra' => ['STAACSCOMPRA', 'MTVNEGACSCOMPRA'],
      'venda' => ['STAACSVENDA', 'MTVNEGACSVENDA'],
      'depositar' => ['STAACSDEPOSITAR', 'MTVNEGACSDEPOSITAR'],
      'sacar' => ['STAACSSACAR', 'MTVNEGACSSACAR'],
      'historico' => ['STAACSHISTORICO', 'MTVNEGACSHISTORICO'],
      'sac' => ['STAACSSAC', 'MTVNEGACSSAC']
   ];

   if (!isset($campos[$pagina])) {
      return ['STATUS' => 'N', 'MOTIVO' => 'Página não encontrada'];
   }

   [$campoStatus, $campoMotivo] = $campos[$pagina];

   $sql = "SELECT $campoStatus AS STATUS, $campoMotivo AS MOTIVO FROM clientes_cfg WHERE IDECLI = :idecli";
   $stmt = $pdo->prepare($sql);
   $stmt->bindParam(':idecli', $idecli, PDO::PARAM_STR);
   $stmt->execute();

   return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obterCredenciaisAdminEmpresa($vsCodEmp)
{
   global $pdo;
   $stmt = $pdo->prepare("SELECT USRADMIN, PWADMIN FROM empresa WHERE CODEMP = :codemp");
   $stmt->bindParam(':codemp', $vsCodEmp, PDO::PARAM_INT);
   $stmt->execute();
   return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obterNumeroChamados($status)
{
   global $pdo;

   try {
      if ($status === 'T') {
         $sql = "SELECT COUNT(*) AS total FROM clientes_chm";
         $stmt = $pdo->prepare($sql);
      } else {
         $sql = "SELECT COUNT(*) AS total FROM clientes_chm WHERE STACHM = :status";
         $stmt = $pdo->prepare($sql);
         $stmt->bindParam(':status', $status, PDO::PARAM_STR);
      }

      $stmt->execute();
      $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
      return (int)$resultado['total'];
   } catch (PDOException $e) {
      return 0;
   }
}

function obterEmailCliente($idecli)
{
   global $pdo;
   try {
      $sql = "SELECT EMAIL FROM clientes WHERE IDECLI = :idecli LIMIT 1";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':idecli', $idecli, PDO::PARAM_STR);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return $result['EMAIL'] ?? null;
   } catch (PDOException $e) {
      return null;
   }
}

function obterRespostaChamado($codreg)
{
   global $pdo;
   try {
      $sql = "SELECT * FROM clientes_resp WHERE CODREG = :codreg LIMIT 1";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':codreg', $codreg, PDO::PARAM_INT);
      $stmt->execute();
      return $stmt->fetch(PDO::FETCH_ASSOC);
   } catch (PDOException $e) {
      error_log("Erro ao buscar resposta do chamado: " . $e->getMessage());
      return null;
   }
}

function obterNomeCliente($idecli)
{
   global $pdo;
   try {
      $sql = "SELECT NOMCLI FROM clientes WHERE IDECLI = :idecli LIMIT 1";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':idecli', $idecli, PDO::PARAM_STR);
      $stmt->execute();
      $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
      return $resultado ? $resultado['NOMCLI'] : null;
   } catch (PDOException $e) {
      error_log("Erro ao obter nome do cliente: " . $e->getMessage());
      return null;
   }
}

function obterDescChmPai($numptc)
{
   global $pdo;
   try {
      $sql = "SELECT DCRCHM FROM clientes_chm WHERE NUMPTC = :numptc LIMIT 1";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':numptc', $numptc, PDO::PARAM_STR);
      $stmt->execute();
      $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
      return $resultado ? $resultado['DCRCHM'] : null;
   } catch (PDOException $e) {
      error_log("Erro ao obter descrição do chamado pai: " . $e->getMessage());
      return null;
   }
}

// Função para obter dados da tabela empresa
function obterDadosEmpresa($codemp)
{
   global $pdo;
   $stmt = $pdo->prepare("SELECT * FROM empresa WHERE CODEMP = :codemp LIMIT 1");
   $stmt->bindParam(':codemp', $codemp, PDO::PARAM_INT);
   $stmt->execute();
   return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obterPWADMIN($codemp)
{
   global $pdo;

   try {
      $sql = "SELECT PWADMIN FROM empresa WHERE CODEMP = :codemp LIMIT 1";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':codemp', $codemp, PDO::PARAM_INT);
      $stmt->execute();

      $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($resultado && isset($resultado['PWADMIN'])) {
         return $resultado['PWADMIN'];
      } else {
         return null; // Empresa não encontrada ou sem valor
      }
   } catch (PDOException $e) {
      error_log("Erro ao obter PWADMIN: " . $e->getMessage());
      return null;
   }
}

function validarSessaoAdmin($codemp)
{
   $credenciais = obterCredenciaisAdminEmpresa($codemp);
   if (!$credenciais) return false;

   $sessUsuario = $_SESSION['admin']['usuario'];
   $sessSenha   = $_SESSION['admin']['senha'];

   return (
      $sessUsuario === $credenciais['USRADMIN'] &&
      $sessSenha === $credenciais['PWADMIN']
   );
}

function obterContaBancariaCliente($idecli)
{
   global $pdo;

   $sql = "SELECT NOMTTL, CPFTTL, CODBCO, NUMAGC, NUMCTA, TPOCTA, STAACTPIX, STAACTCTA 
           FROM clientes_bco 
           WHERE IDECLI = :idecli 
           LIMIT 1";

   $stmt = $pdo->prepare($sql);
   $stmt->bindParam(':idecli', $idecli, PDO::PARAM_STR);
   $stmt->execute();

   $dados = $stmt->fetch(PDO::FETCH_ASSOC);

   return $dados ?: null; // retorna null se não encontrar
}


function obterSaquesClientes($idecli)
{
   global $pdo;

   $sql = "SELECT * FROM clientes_saque 
           WHERE IDECLI = :idecli 
           AND STASAQ IN ('A', 'F') 
           ORDER BY DTAINS DESC";

   $stmt = $pdo->prepare($sql);
   $stmt->bindParam(':idecli', $idecli, PDO::PARAM_STR);
   $stmt->execute();

   $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

   return $dados ?: []; // retorna array vazio se nenhum saque encontrado
}

function verificarPDF($arquivo)
{
   // Remove header se existir (ex: data:application/pdf;base64,...)
   if (strpos($arquivo, ',') !== false) {
      $arquivo = explode(',', $arquivo, 2)[1];
   }

   $binary = base64_decode($arquivo, true);

   if ($binary === false) {
      return false; // não é base64 válido, assumimos não ser PDF
   }

   // Verifica assinatura PDF
   if (substr($binary, 0, 4) === '%PDF') {
      return true;
   }

   // Verifica JPEG
   if (substr($binary, 0, 3) === "\xFF\xD8\xFF") {
      return false;
   }

   // Verifica PNG
   if (substr($binary, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
      return false;
   }

   return false; // não identificado, tratamos como imagem
}

function obterClientesComDocumentosAguardandoAprovacao()
{
   global $pdo; // Usa a conexão PDO global

   $sql = "
      SELECT 
         clientes.IDECLI, 
         clientes.NOMCLI, 
         clientes.CPFCLI, 
         clientes.IMG64, 
         clientes_cpl.TPODOC, 
         clientes_cpl.IMG64DOC, 
         clientes_cpl.IMG64CPREND, 
         clientes_cpl.STAAPV
      FROM clientes
      LEFT JOIN clientes_cpl ON clientes.IDECLI = clientes_cpl.IDECLI
      WHERE clientes_cpl.STAAPV = 'A'
   ";

   $stmt = $pdo->prepare($sql);
   $stmt->execute();

   $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

   // Corrige IDECLI para string preservando zeros à esquerda
   foreach ($clientes as &$cliente) {
      $cliente['IDECLI'] = str_pad($cliente['IDECLI'], 9, '0', STR_PAD_LEFT);
   }
   return $clientes;
}

function verificarStatusDocumentos($idecli)
{
   global $pdo; // Usa a conexão PDO global

   try {
      $sql = "SELECT STAAPV FROM clientes_cpl WHERE IDECLI = :idecli LIMIT 1";
      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':idecli', $idecli, PDO::PARAM_STR);
      $stmt->execute();

      $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

      return $resultado ? $resultado['STAAPV'] : null;
   } catch (PDOException $e) {
      error_log("Erro ao buscar status do documento: " . $e->getMessage());
      return null;
   }
}

function obterListaSimplesClientesStatus()
{
   global $pdo;

   $sql = "SELECT 
               clientes.IDECLI, 
               clientes.CPFCLI, 
               clientes.NOMCLI, 
               clientes.STACTAATV, 
               clientes.STACMFEML, 
               clientes_cpl.STAAPV 
           FROM clientes 
           LEFT JOIN clientes_cpl 
           ON clientes.IDECLI = clientes_cpl.IDECLI";

   $stmt = $pdo->prepare($sql);
   $stmt->execute();

   $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

   return $dados ?: []; // retorna array vazio se nenhum cliente encontrado
}

function obterSaquesAbertos()
{
   global $pdo;

   $sql = "SELECT 
               clientes_saque.IDECLI, 
               clientes_saque.IDEMOV, 
               clientes.NOMCLI, 
               clientes.CPFCLI,
               clientes_saque.TPOSAQ, 
               clientes_saque.VLRSAQ 
           FROM clientes_saque
           LEFT JOIN clientes ON clientes.IDECLI = clientes_saque.IDECLI
           WHERE clientes_saque.STASAQ = 'A'";

   $stmt = $pdo->prepare($sql);
   $stmt->execute();

   $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

   return $dados ?: []; // Retorna array vazio se nenhum resultado
}

function tipoArquivoBase64(?string $base64): string
{
   if (empty($base64)) {
      return 'NADA';
   }

   if (str_starts_with($base64, 'data:application/pdf')) {
      return 'PDF';
   }

   if (
      str_starts_with($base64, 'data:image/png') ||
      str_starts_with($base64, 'data:image/jpeg') ||
      str_starts_with($base64, 'data:image/jpg')
   ) {
      return 'IMG';
   }

   return 'OUTRO';
}

function obterTotalComprasPorCarteira($idecli, $carteira, $mes, $ano)
{
   global $pdo;

   $colunasPermitidas = [
      'simple'   => 'saldo_simple',
      'classic'  => 'saldo_classic',
      'standard' => 'saldo_standard',
      'premium'  => 'saldo_premium'
   ];

   $carteira = strtolower($carteira);
   if (!isset($colunasPermitidas[$carteira])) {
      return ['QTD' => 0, 'TOTAL' => 0.00];
   }

   $colunaSaldo = $colunasPermitidas[$carteira];

   $sql = "
       SELECT 
           SUM(S.$colunaSaldo) AS QTD,
           SUM(S.$colunaSaldo * S.VLRBSECLC) AS TOTAL
       FROM clientes_saldo S
       WHERE S.IDECLI = :idecli
         AND S.STAMOV = 'A'
         AND S.TPOMOV = 'Compra'
         AND MONTH(S.DTAMOV) = :mes
         AND YEAR(S.DTAMOV) = :ano
   ";

   $stmt = $pdo->prepare($sql);
   $stmt->execute([
      ':idecli' => $idecli,
      ':mes'    => $mes,
      ':ano'    => $ano
   ]);

   $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

   return [
      'QTD'   => $resultado['QTD']   ?? 0,
      'TOTAL' => $resultado['TOTAL'] ?? 0.00
   ];
}


function obterTotalVendasPorCarteira($idecli, $carteira, $mes, $ano)
{
   global $pdo;

   $colunasPermitidas = [
      'simple'   => 'saldo_simple',
      'classic'  => 'saldo_classic',
      'standard' => 'saldo_standard',
      'premium'  => 'saldo_premium'
   ];

   $carteira = strtolower($carteira);
   if (!isset($colunasPermitidas[$carteira])) {
      return ['QTD' => 0, 'TOTAL' => 0.00];
   }

   $colunaSaldo = $colunasPermitidas[$carteira];

   $sql = "
       SELECT 
           SUM(S.$colunaSaldo * -1) AS QTD,
           SUM(S.$colunaSaldo * S.VLRBSECLC * -1) AS TOTAL
       FROM clientes_saldo S
       WHERE S.IDECLI = :idecli
         AND S.STAMOV = 'A'
         AND S.TPOMOV = 'Venda'
         AND MONTH(S.DTAMOV) = :mes
         AND YEAR(S.DTAMOV) = :ano
   ";

   $stmt = $pdo->prepare($sql);
   $stmt->execute([
      ':idecli' => $idecli,
      ':mes'    => $mes,
      ':ano'    => $ano
   ]);

   $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

   return [
      'QTD'   => $resultado['QTD']   ?? 0,
      'TOTAL' => $resultado['TOTAL'] ?? 0.00
   ];
}

function obterHistoricoUltimos6Meses($idecli)
{
   $carteiras = ['Simple', 'Classic', 'Standard', 'Premium'];
   $dados = [];

   $agora = new DateTime();

   // Percorre os últimos 6 meses (incluindo o mês atual)
   for ($i = 0; $i < 6; $i++) {
      $mesAtual = (int)$agora->format('m');
      $anoAtual = (int)$agora->format('Y');
      $mesAnoFormatado = str_pad($mesAtual, 2, '0', STR_PAD_LEFT) . '/' . $anoAtual;

      foreach ($carteiras as $carteira) {
         $compras = obterTotalComprasPorCarteira($idecli, $carteira, $mesAtual, $anoAtual);
         $vendas  = obterTotalVendasPorCarteira($idecli, $carteira, $mesAtual, $anoAtual);

         $dados[$carteira][$mesAnoFormatado] = [
            'compras' => $compras,
            'vendas'  => $vendas
         ];
      }

      // Subtrai 1 mês
      $agora->modify('-1 month');
   }

   return $dados;
}
