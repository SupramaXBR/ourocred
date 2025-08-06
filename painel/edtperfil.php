<?php
// Validar o token
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
   <meta name="referrer" content="strict-origin-when-cross-origin"> <!-- referrer -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <link rel="stylesheet" href="../uses/estilo.css">
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <link rel="stylesheet" href="css/edtperfil.css"> <!-- css da pagina -->
   <script>
      function carregarConteudo(pagina) {
         // Redireciona diretamente via GET
         window.location.href = pagina;
      }
   </script>
</head>
<?php
// Verifica se a sessão do cliente e o token estão corretos

include_once "../uses/components.php";
include_once "../uses/funcoes.php";
include_once "../uses/conexao.php";

if (
   !isset($_SESSION['cliente']) ||
   !isset($_SESSION['token']) ||
   !isset($_SESSION['cliente']['token']) ||
   $_SESSION['token'] !== $_SESSION['cliente']['token']
) {
   session_destroy();
   header("Location: ../index.php");
   exit;
}

$tempoLimite = retornaTempoLimite(1); // definido na tabela empresa

// Verifica tempo de inatividade
$tempoInativo = time() - $_SESSION['cliente']['ultimo_acesso'];
if ($tempoInativo > $tempoLimite) {
   session_unset();
   session_destroy();
   header("Location: timeout.php");
   exit;
}

// Atualiza timestamp do último acesso
$_SESSION['cliente']['ultimo_acesso'] = time();

echo head('../uses/estilo.css', '../imagens/favicon.ico');

// Carrega os dados do cliente da sessão
$cliente_session = $_SESSION['cliente'];

// (Opcional, reforço de segurança) Revalida no banco se o cliente ainda existe e está ativo
try {
   $sql = "SELECT IDECLI, CPFCLI, MD5PW, STACTAATV FROM clientes WHERE CPFCLI = :cpfcli";
   $stmt = $pdo->prepare($sql);
   $stmt->bindParam(':cpfcli', $cliente_session['CPFCLI'], PDO::PARAM_STR);
   $stmt->execute();
   $clienteVerificado = $stmt->fetch(PDO::FETCH_ASSOC);

   if (
      !$clienteVerificado ||
      $clienteVerificado['MD5PW'] !== $cliente_session['MD5PW'] ||
      $clienteVerificado['STACTAATV'] !== 'S'
   ) {
      session_destroy();
      header("Location: ../index.php");
      exit;
   }

   // Carrega os dados do cliente do db - dados frescos
   $cliente = obterDadosCliente($_SESSION['cliente']['IDECLI'], $_SESSION['cliente']['CODCLI']);
   if ($cliente == null) {
      session_destroy();
      header("Location: ../index.php");
      exit;
   }

   // Verificando o acesso
   $pagina = 'perfil';
   $acesso = obterStatusDeAcesso($cliente['IDECLI'], $pagina);
   if (!$acesso || $acesso['STATUS'] == 'N') {
      $urlVoltar = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
      echo "<script>alert('Acesso bloqueado à página Editar Perfil; Motivo: {$acesso['MOTIVO']}');</script>";
      echo '<meta http-equiv="refresh" content="0;url=' . $urlVoltar . '">';
      exit;
   }

   // buscar as informações da tabela bancos
   try {
      $sql_bco = "SELECT CODBCO, DCRBCO                        
                              FROM bancos";


      $stmt_bco = $pdo->prepare($sql_bco);
      $stmt_bco->execute();

      // Pega todos os resultados como um array associativo
      $bancos = $stmt_bco->fetchAll(PDO::FETCH_ASSOC);
   } catch (PDOException $e) {

      echo "Erro ao buscar a tabela de bancos. " . $e->getMessage();
      echo '<meta http-equiv="refresh" content="10;url=../index.php">';
      exit();
   }

   //buscar as informações da tabela clientes_cpl    
   try {
      $sql_cpl = "SELECT TPODOC, IMG64DOC, IMG64CPREND, STAAPV 
                FROM clientes_cpl
                WHERE IDECLI = :idecli";


      $stmt_cpl = $pdo->prepare($sql_cpl);
      $stmt_cpl->bindParam(':idecli', $cliente['IDECLI'], PDO::PARAM_STR);
      $stmt_cpl->execute();

      // Pega todos os resultados como um array associativo
      $cliente_cpl = $stmt_cpl->fetch(PDO::FETCH_ASSOC);
   } catch (PDOException $e) {

      echo "Erro ao buscar a tabela complemento de clientes. " . $e->getMessage();
      echo '<meta http-equiv="refresh" content="10;url=../index.php">';
      exit();
   }

   //buscar as informações da tabela clientes_bco
   try {
      $sql_ctabco = "SELECT NOMTTL, CPFTTL, CODBCO, NUMAGC, NUMCTA, TPOCTA, STAACTCTA, STAACTPIX, DTAALT, DTAINS 
                FROM clientes_bco
                WHERE IDECLI = :idecli";

      $stmt_ctabco = $pdo->prepare($sql_ctabco);
      $stmt_ctabco->bindParam(':idecli', $cliente['IDECLI'], PDO::PARAM_STR);
      $stmt_ctabco->execute();

      // Pega todos os resultados como um array associativo
      $cliente_bco = $stmt_ctabco->fetch(PDO::FETCH_ASSOC);
   } catch (PDOException $e) {

      echo "Erro ao buscar a tabela banco de clientes. " . $e->getMessage();
      echo '<meta http-equiv="refresh" content="10;url=../index.php">';
      exit();
   }
} catch (PDOException $e) {
   session_destroy();
   header("Location: ../index.php");
   exit;
}

$token = $_SESSION['token'];

$cssLblStaCmfEml = '';
$lblStaCmfEml = '';
$vsReadOnlyEml = '';
$vsBtnDisabled = '';

if (verificarEmailVerificado($cliente['IDECLI'])  == 'S') {
   $cssLblStaCmfEml = 'cor-texto-verde';
   $lblStaCmfEml = 'Conta de e-Mail Verificada.';
   $vsReadOnlyEml = 'readonly';
   $vsBtnDisabled = 'disabled';
} else {
   $cssLblStaCmfEml = 'cor-texto-vermelho';
   $lblStaCmfEml = 'Conta de e-Mail Não Verificada.';
}

$cssLblStaCmfDoc = '';
$lblStaCmfDoc = '';

if ($cliente_cpl['STAAPV']  == 'S') {
   $cssLblStaCmfDoc = 'cor-texto-verde';
   $lblStaCmfDoc = 'Documentos Aprovados';
} elseif ($cliente_cpl['STAAPV']  == 'N') {
   $cssLblStaCmfDoc = 'cor-texto-vermelho';
   $lblStaCmfDoc = 'Documentos Nao Enviados';
} elseif ($cliente_cpl['STAAPV']  == 'A') {
   $cssLblStaCmfDoc = 'cor-texto-azul';
   $lblStaCmfDoc = 'Aguardando Aprovação dos Documentos';
}


if ($cliente_cpl['IMG64DOC'] == '') {
   $cliente_cpl['IMG64DOC'] = ImagemPadrao(1);
}

if ($cliente_cpl['IMG64CPREND'] == '') {
   $cliente_cpl['IMG64CPREND'] = ImagemPadrao(1);
}

// pdf

$imgpadraopdf = ImagemPadrao(3);

// manter o conteúdo real
$imgRealDoc = $cliente_cpl['IMG64DOC'];
$imgRealEnd = $cliente_cpl['IMG64CPREND'];

// para visualização no <img>
$imgPreviewDoc = verificarPDF($imgRealDoc) ? $imgpadraopdf : $imgRealDoc;
$imgPreviewEnd = verificarPDF($imgRealEnd) ? $imgpadraopdf : $imgRealEnd;


if ($cliente['IMG64'] == '') {
   $cliente['IMG64'] = ImagemPadrao(1);
}



$vsCpfCliMask = mascararCPF($cliente['CPFCLI']);
$vsCpfTtlMask = mascararCPF($cliente_bco['CPFTTL']);
$vsNumTelMask = mascararTelefone($cliente['NUMTEL']);

// rotina da foto de perfil
if ($cliente['IMG64'] == '') {
   $cliente['IMG64'] = ImagemPadrao(1);
}
?>

<script>
   let ImgFtoBase64String = "<?php echo $cliente['IMG64']; ?>";
   let ImgDocBase64String = "<?php echo $cliente_cpl['IMG64DOC']; ?>";
   let ImgEndBase64String = "<?php echo $cliente_cpl['IMG64CPREND']; ?>";
   let imgPadraoPDF = "<?= $imgpadraopdf ?>";

   function bloquearBotao(botaoId, spinnerId) {
      let botao = document.getElementById(botaoId);
      let spinner = document.getElementById(spinnerId);

      if (botao && spinner) {
         botao.disabled = true; // Bloqueia o botão
         spinner.style.display = 'inline-block'; // Exibe o spinner
      }
   }

   function desbloquearBotao(botaoId, spinnerId) {
      let botao = document.getElementById(botaoId);
      let spinner = document.getElementById(spinnerId);

      if (botao && spinner) {
         botao.disabled = false; // Desbloqueia o botão
         spinner.style.display = 'none'; // Oculta o spinner
      }
   }
</script>

<body class="bg-light">
   <div class="container-fluid">
      <div class="row">
         <!-- Sidebar -->
         <!-- Sidebar para desktop (visível em telas md e maiores) -->
         <nav class="col-md-2 d-none d-md-block bg-nav-painel sidebar p-3 min-vh-100 border-end position-fixed">
            <div class="text-center mt-3">
               <h4 class="cor_ouro">
                  <img src="../imagens/favicon.ico" alt="OuroCred" width="30"> OuroCred
               </h4>
            </div>
            <hr>
            <div class="d-flex flex-column align-items-center">
               <img src="<?php echo $cliente['IMG64'] ?>" class="img-lil-circle-perfil desktop" />
               <small><b><?php echo primeiroUltimoNome($cliente['NOMCLI']) ?></b></small>
               <small>Conta: <?php echo $cliente['IDECLI'] ?></small>
               <small><i class="bi bi-cash-coin cor-texto-saldo icone-wallet"></i> <?php echo number_format(obterSaldoReais($cliente['IDECLI']), 2, ',', '.'); ?></small>
               <small><i class="bi bi-wallet-fill cor-texto-simple icone-wallet"></i> <?php echo number_format(obterSaldoSimple($cliente['IDECLI']), 4, ',', '.'); ?>g</small>
               <small><i class="bi bi-wallet-fill cor-texto-classic icone-wallet"></i> <?php echo number_format(obterSaldoClassic($cliente['IDECLI']), 4, ',', '.'); ?>g</small>
               <small><i class="bi bi-wallet-fill cor-texto-standard icone-wallet"></i> <?php echo number_format(obterSaldoStandard($cliente['IDECLI']), 4, ',', '.'); ?>g</small>
               <small><i class="bi bi-wallet-fill cor-texto-premium icone-wallet"></i> <?php echo number_format(obterSaldoPremium($cliente['IDECLI']), 4, ',', '.'); ?>g</small>
            </div>
            <br>
            <ul class="nav flex-column">
               <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('perfil.php')"><i class="bi bi-house"></i> Inicio</button></li>
               <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('comprar.php')"><i class="bi bi-minecart"></i> Comprar Ouro</button></li>
               <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('vender.php')"><i class="bi bi-minecart-loaded"></i> Vender Ouro</button></li>
               <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('depositar.php')"><i class="bi bi-arrow-up-square"></i> Depositar</button></li>
               <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('sacar.php')"><i class="bi bi-arrow-down-square"></i> Sacar</button></li>
               <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('historico.php')"><i class="bi bi-clock-history"></i> His. Transações</button></li>
               <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('sac.php')"><i class="bi bi-headset"></i> Suporte</button></li>
               <li class="nav-item"><button class="btn btn-danger w-100 mb-2" onclick="carregarConteudo('sair.php')"><i class="bi bi-box-arrow-left"></i> Sair</button></li>
            </ul>
         </nav>

         <!-- Cabeçalho para dispositivos móveis -->
         <nav class="d-md-none">
            <div class="d-flex justify-content-between align-items-center p-2 bg-nav-painel border-bottom">
               <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNav" aria-controls="offcanvasNav">
                  <i class="bi bi-list"></i>
               </button>
               <div class="offcanvas-header">
                  <h5 class="offcanvas-title cor_ouro" id="offcanvasNavLabel">
                     <img src="../imagens/favicon.ico" alt="OuroCred" width="30"> OuroCred
                  </h5>
               </div>
               <!-- Imagem reduzida para o cabeçalho -->
               <img src="<?php echo $cliente['IMG64'] ?>" alt="Perfil" class="img-lil-circle-perfil mobile">
            </div>
         </nav>

         <!-- Offcanvas: menu lateral para dispositivos móveis -->
         <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNav" aria-labelledby="offcanvasNavLabel">
            <div class="offcanvas-header">
               <h5 class="offcanvas-title" id="offcanvasNavLabel">
                  <img src="../imagens/favicon.ico" alt="OuroCred" width="30"> OuroCred
               </h5>
               <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
            </div>
            <div class="offcanvas-body">
               <!-- Bloco de informações adicionais exibido ao abrir o menu -->
               <div class="d-flex flex-column align-items-center mb-3">
                  <img src="<?php echo $cliente['IMG64'] ?>" alt="Perfil" class="img-lil-circle-perfil" style="width: 60px; height: 60px;" />
                  <small><b><?php echo primeiroUltimoNome($cliente['NOMCLI']) ?></b></small>
                  <small>Conta: <?php echo $cliente['IDECLI'] ?></small>
                  <small><i class="bi bi-cash-coin cor-texto-saldo icone-wallet"></i> <?php echo number_format(obterSaldoReais($cliente['IDECLI']), 2, ',', '.'); ?></small>
                  <small><i class="bi bi-wallet-fill cor-texto-simple icone-wallet"></i> <?php echo number_format(obterSaldoSimple($cliente['IDECLI']), 4, ',', '.'); ?>g</small>
                  <small><i class="bi bi-wallet-fill cor-texto-classic icone-wallet"></i> <?php echo number_format(obterSaldoClassic($cliente['IDECLI']), 4, ',', '.'); ?>g</small>
                  <small><i class="bi bi-wallet-fill cor-texto-standard icone-wallet"></i> <?php echo number_format(obterSaldoStandard($cliente['IDECLI']), 4, ',', '.'); ?>g</small>
                  <small><i class="bi bi-wallet-fill cor-texto-premium icone-wallet"></i> <?php echo number_format(obterSaldoPremium($cliente['IDECLI']), 4, ',', '.'); ?>g</small>
               </div>
               <ul class="nav flex-column">
                  <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('perfil.php');" data-bs-dismiss="offcanvas"><i class="bi bi-house"></i> Inicio</button></li>
                  <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('comprar.php');" data-bs-dismiss="offcanvas"><i class="bi bi-minecart"></i> Comprar Ouro</button></li>
                  <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('vender.php');" data-bs-dismiss="offcanvas"><i class="bi bi-minecart-loaded"></i> Vender Ouro</button></li>
                  <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('depositar.php');" data-bs-dismiss="offcanvas"><i class="bi bi-arrow-up-square"></i> Depositar</button></li>
                  <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('sacar.php');" data-bs-dismiss="offcanvas"><i class="bi bi-arrow-down-square"></i> Sacar</button></li>
                  <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('historico.php');" data-bs-dismiss="offcanvas"><i class="bi bi-clock-history"></i> His. Transações</button></li>
                  <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('sac.php');" data-bs-dismiss="offcanvas"><i class="bi bi-headset"></i> Suporte</button></li>
                  <li class="nav-item"><button class="btn btn-danger w-100 mb-2" onclick="carregarConteudo('sair.php');" data-bs-dismiss="offcanvas"><i class="bi bi-box-arrow-left"></i> Sair</button></li>
               </ul>
            </div>
         </div>

         <!-- Conteúdo principal -->
         <main class="col-md-10 ms-sm-auto px-md-4 d-flex flex-column align-items-center mt-3">
            <div class="container-fluid">
               <div class="card">
                  <div class="card-header bg-primary text-white text-center">
                     <div class="d-flex flex-column align-items-center">
                        <h2>Dados do Cliente: <?php echo $cliente['IDECLI']; ?></h1>
                     </div>
                     <!-- PROGRESSBAR DO BOOTSRAP FUNCIONANDO -->
                     <?php
                     $camposVazios = contarCamposVazios_clientes($cliente['IDECLI']);
                     if ($camposVazios == -1) {
                        echo '<p class="text-danger">Houve um erro ao buscar os campos</p>';
                     } else {
                        $totalCampos = 16;
                        $percentual = (($totalCampos - $camposVazios) / $totalCampos) * 100;

                        // Definição de cores dinâmica
                        if ($percentual == 100) {
                           $csscor = 'bg-success'; // Verde quando 100%
                        } elseif ($percentual >= 60) {
                           $csscor = 'bg-warning'; // Amarelo entre 50% e 99%
                        } else {
                           $csscor = 'bg-danger'; // Vermelho abaixo de 50%
                        }
                     ?>
                        <div class="progress" style="height: 9px;">
                           <div class="progress-bar <?php echo $csscor; ?>" role="progressbar"
                              style="width: <?php echo $percentual; ?>%"
                              aria-valuenow="<?php echo $percentual; ?>"
                              aria-valuemin="0" aria-valuemax="100">
                              <?php echo round($percentual); ?>%
                           </div>
                        </div>
                     <?php
                     }
                     ?>
                     <!-- FINAL DO PROGRESSBAR -->
                  </div>

                  <div class="card-body">
                     <!-- Abas (Responsivas) -->
                     <ul class="nav nav-tabs justify-content-center" id="tabMenu">
                        <li class="nav-item">
                           <a class="nav-link active" data-bs-toggle="tab" href="#aba1">Dados Pessoais</a>
                        </li>
                        <li class="nav-item">
                           <a class="nav-link" data-bs-toggle="tab" href="#aba2">Endereço</a>
                        </li>
                        <li class="nav-item">
                           <a class="nav-link" data-bs-toggle="tab" href="#aba3">Foto e Documentos</a>
                        </li>
                        <li class="nav-item">
                           <a class="nav-link" data-bs-toggle="tab" href="#aba4">Conta Bancaria</a>
                        </li>
                     </ul>

                     <!-- CONTEUDO TOTAL DO WIZARD ABA -->
                     <div class="tab-content mt-3">

                        <!-- Conteúdo da 1ª Aba  -->
                        <div class="tab-pane fade show active" id="aba1">
                           <div class="form-group cpf-alterar-senha">
                              <label for="inputcpf">CPF</label>
                              <div class="d-flex row">
                                 <div class="col-md-9">
                                    <input type="text" class="form-control" id="inputcpf" placeholder="Digite seu CPF"
                                       value="<?php echo $vsCpfCliMask ?>" maxlength="14"
                                       onblur="vaziovalidarcpf('inputcpf' , 'errorcpf' , 'errorcpf1')" readonly>
                                    <small id="errorcpf" class="form-text text-danger" style="display: none;">CPF inválido</small>
                                    <small id="errorcpf1" class="form-text text-danger" style="display: none;">* Campo Obrigatório</small>
                                 </div>
                                 <div class="col-md-3">
                                    <button type="button" id="btnAlterarSenha" class="btn btn-primary btn-block"
                                       onclick="enviarEmailAlterarSenha()">
                                       Alterar Senha
                                       <span id="spinnerbtnAlterarSenha" class="loaderbtn-sm" style="display: none;"></span>
                                    </button>
                                 </div>
                              </div>
                           </div>
                           <div class="form-group">
                              <label for="rginput">RG</label>
                              <input type="text" class="form-control" id="rginput" placeholder="Digite seu RG sem traços" value="<?php echo $cliente['RGCLI'] ?>" onblur="inputvazio('rginput' , 'rgerror')" readonly>
                              <small id="rgerror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
                           </div>
                           <div class="form-group">
                              <label for="nomeinput">Nome</label>
                              <input type="text" class="form-control" id="nomeinput" placeholder="Digite seu nome completo" value="<?php echo $cliente['NOMCLI'] ?>" onblur="inputvazio('nomeinput' , 'nomeerror')" readonly>
                              <small id="nomeerror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
                           </div>

                           <div class="form-group data-nascimento">
                              <label for="dataNascimento" class="form-label">Data de Nascimento</label>
                              <div class="d-flex justify-content-between">
                                 <div class="col-md-3">
                                    <input id="diaNsc" class="form-control form-control-sm" type="text" value="<?php echo converterDataDropDown(1, $cliente['DTANSC']) ?>" placeholder="Dia Nascimento" readonly>
                                 </div>
                                 <div class="col-md-3">
                                    <input id="mesNsc" class="form-control form-control-sm" type="text" value="<?php echo converterDataDropDown(2, $cliente['DTANSC']) ?>" placeholder="Dia Nascimento" readonly>
                                 </div>
                                 <div class="col-md-3">
                                    <input id="anoNsc" class="form-control form-control-sm" type="text" value="<?php echo converterDataDropDown(3, $cliente['DTANSC']) ?>" placeholder="Dia Nascimento" readonly>
                                 </div>
                              </div>
                           </div>

                           <div class="form-group">
                              <label for="nommaeinput">Nome da Mãe</label>
                              <input type="text" class="form-control" id="nommaeinput" placeholder="Digite o nome completo da sua mãe" value="<?php echo $cliente['MAECLI'] ?>" onblur="inputvazio('nommaeinput' , 'nommaeerror')" readonly>
                              <small id="nommaeerror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
                           </div>

                           <div class="form-group email-confirmacao">
                              <label for="emailinput">e-Mail <i class="<?php echo $cssLblStaCmfEml; ?>"> <?php echo $lblStaCmfEml; ?> </i></label>
                              <div class="d-flex row">
                                 <div class="col-md-9">
                                    <input type="text" class="form-control" id="emailinput" placeholder="Digite seu e-Mail"
                                       value="<?php echo $cliente['EMAIL'] ?>" maxlength="64"
                                       onblur="valirdarvazioemail('emailinput' , 'emailerror' , 'emailinval')" <?php echo $vsReadOnlyEml ?>>
                                    <small id="emailerror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
                                    <small id="emailinval" class="form-text text-danger" style="display: none;">* e-Mail Invalido</small>
                                 </div>
                                 <div class="col-md-3">
                                    <button type="button" id="btnVerificarEmail" class="btn btn-primary btn-block"
                                       onclick="enviarEmailVerificar()" <?php echo $vsBtnDisabled ?>>
                                       Verificar e-Mail
                                       <span id="spinnerbtnVerificarEmail" class="loaderbtn-sm" style="display: none;"></span>
                                    </button>
                                 </div>
                              </div>
                           </div>

                           <div class="form-group">
                              <label for="numtelinput">Telefone</label>
                              <input type="text" class="form-control" id="nuntelinput" placeholder="Digite seu Numero de Telefone" value="<?php echo $vsNumTelMask ?>" onblur="inputvazio('numtelinput' , 'numtelerror')">
                              <small id="numtelerror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
                           </div>
                        </div>

                        <!-- Conteúdo da 2ª Aba  -->
                        <div class="tab-pane fade" id="aba2">
                           <div class="form-group">
                              <label for="cep">CEP</label>
                              <input type="text" class="form-control" id="cep" placeholder="Digite o CEP da sua rua" value="<?php echo $cliente['CEPCLI'] ?>" onblur="vaziobuscarcep('cep')">
                              <div id="mensagemErroCep" class="mensagem-erro"></div>
                              <div id="loader-cep" class="loader" style="display: none;"></div>
                           </div>
                           <div class="form-group">
                              <label for="endereco">Endereço</label>
                              <input type="text" class="form-control" id="endereco" placeholder="Digite seu endereço (sem o número)" onblur="inputvazio('endereco' , 'enderror')">
                              <small id="enderror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
                           </div>
                           <div class="form-group">
                              <label for="numcsainput">Numero</label>
                              <input type="text" class="form-control" id="numcsainput" placeholder="Digite o numero da sua casa" value="<?php echo $cliente['NUMCSA'] ?>" onblur="inputvazio('numcsainput' , 'numcsaerror')">
                              <small id="numcsaerror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
                           </div>
                           <div class="form-group">
                              <label for="exampleFormControlInput1">Complemento</label>
                              <input type="text" class="form-control" id="cplinput" placeholder="Digite o complemento do seu endereço" value="<?php echo $cliente['CPLEND'] ?>">
                           </div>
                           <div class="form-group">
                              <label for="bairro">Bairro</label>
                              <input type="text" class="form-control" id="bairro" placeholder="Digite o nome do seu bairro" onblur="inputvazio('bairro' , 'baierror')">
                              <small id="baierror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
                           </div>
                           <div class="form-group">
                              <label for="estadosBrasileiros">Estados da Federação</label>
                              <select class="form-control" id="estadosBrasileiros" onchange="carregarCidades()">
                                 <option value="">Selecione o Estado</option>
                                 <option value="AC">Acre (AC)</option>
                                 <option value="AL">Alagoas (AL)</option>
                                 <option value="AP">Amapá (AP)</option>
                                 <option value="AM">Amazonas (AM)</option>
                                 <option value="BA">Bahia (BA)</option>
                                 <option value="CE">Ceará (CE)</option>
                                 <option value="DF">Distrito Federal (DF)</option>
                                 <option value="ES">Espírito Santo (ES)</option>
                                 <option value="GO">Goiás (GO)</option>
                                 <option value="MA">Maranhão (MA)</option>
                                 <option value="MT">Mato Grosso (MT)</option>
                                 <option value="MS">Mato Grosso do Sul (MS)</option>
                                 <option value="MG">Minas Gerais (MG)</option>
                                 <option value="PA">Pará (PA)</option>
                                 <option value="PB">Paraíba (PB)</option>
                                 <option value="PR">Paraná (PR)</option>
                                 <option value="PE">Pernambuco (PE)</option>
                                 <option value="PI">Piauí (PI)</option>
                                 <option value="RJ">Rio de Janeiro (RJ)</option>
                                 <option value="RN">Rio Grande do Norte (RN)</option>
                                 <option value="RS">Rio Grande do Sul (RS)</option>
                                 <option value="RO">Rondônia (RO)</option>
                                 <option value="RR">Roraima (RR)</option>
                                 <option value="SC">Santa Catarina (SC)</option>
                                 <option value="SP">São Paulo (SP)</option>
                                 <option value="SE">Sergipe (SE)</option>
                                 <option value="TO">Tocantins (TO)</option>
                              </select>
                              <small id="ufdeerror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
                           </div>
                           <div class="form-group">
                              <label for="cidade">Cidade</label>
                              <select class="form-control" id="cidade" onblur="inputvazio('cidade' , 'cdderror')">
                                 <option value="">Selecione a Cidade</option>
                              </select>
                              <small id="cdderror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
                           </div>
                        </div>

                        <!-- Conteúdo da 3ª Aba  -->
                        <div class="tab-pane fade" id="aba3">
                           <div class="form-group">
                              <label for="tpoDoc">Tipo de Documento <i class="<?php echo $cssLblStaCmfDoc; ?>"> <?php echo $lblStaCmfDoc; ?> </i> </label>
                              <select class="form-control" id="tpoDoc">
                                 <option value="">Selecione o Tipo do Documento</option>
                                 <option value="RG">Registro Geral (RG)</option>
                                 <option value="CNH">Carteira Naciona de Habilitação (CNH)</option>
                              </select>
                              <small id="cdderror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
                           </div>
                           <div class="form-group">
                              <div class="d-flex justify-content-between">
                                 <div class="col-md-3">
                                    <label for="lblImagemDoc" class="form-label">Foto do Documento</label>
                                    <!-- Input de arquivo oculto -->
                                    <input type="file" id="inputImagemDoc" accept=".jpg, .jpeg, .png, .pdf">
                                    <!-- Label que age como botão -->
                                    <label for="inputImagemDoc" class="btn-upload">Selecionar Documento</label>
                                 </div>
                                 <div class="col-md-3">
                                    <label for="lblImagemEnd" class="form-label">Comprovante de Endereço</label>
                                    <!-- Input de arquivo oculto -->
                                    <input type="file" id="inputImagemEnd" accept=".jpg, .jpeg, .png, .pdf">
                                    <!-- Label que age como botão -->
                                    <label for="inputImagemEnd" class="btn-upload">Selecionar Documento</label>
                                 </div>
                                 <div class="col-md-3">
                                    <label for="fotoperfil" class="form-label">Selecionar Foto de Perfil</label>
                                    <!-- Input de arquivo oculto -->
                                    <input type="file" id="inputImagem" accept=".jpg, .jpeg, .png">
                                    <!-- Label que age como botão -->
                                    <label for="inputImagem" class="btn-upload">Selecionar Foto </label>
                                 </div>
                              </div>
                           </div>
                           <div class="form-group">
                              <div class="d-flex justify-content-between">
                                 <div class="col-md-3">
                                    <div class="d-flex flex-column align-items-center">
                                       <img id="imgDoc-show" src="<?= $imgPreviewDoc ?>" class="img-mid-square" />
                                    </div>
                                 </div>
                                 <div class="col-md-3">
                                    <div class="d-flex flex-column align-items-center">
                                       <img id="imgEnd-show" src="<?= $imgPreviewEnd ?>" class="img-mid-square" />
                                    </div>
                                 </div>
                                 <div class="col-md-3">
                                    <div class="d-flex flex-column align-items-center">
                                       <img id="imgFto-show" src="<?php echo $cliente['IMG64'] ?>" class="img-mid-square" />
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>

                        <!-- Conteúdo da 4ª Aba  -->
                        <div class="tab-pane fade" id="aba4">
                           <div class="form-group">
                              <div class="row g-0">
                                 <div class="col-md-4">
                                    <label for="cpfTitCtaBcoInput" class="form-label">CPF do Titular</label>
                                    <input type="text" class="form-control" id="cpfTitCtaBcoInput" value="<?php echo $vsCpfTtlMask ?>" readonly>
                                 </div>
                                 <div class="col-md-8">
                                    <label for="nomTitCtaBcoInput" class="form-label">Nome do Titular</label>
                                    <input type="text" class="form-control" id="nomTitCtaBcoInput" value="<?php echo $cliente_bco['NOMTTL'] ?>" readonly>
                                 </div>
                              </div>

                              <div class="form-group">
                                 <div class="row g-0">
                                    <div class="col-md-12">
                                       <label for="bancosSelect" class="form-label">Instituição Financeira</label>
                                       <select class="form-control" id="bancosSelect" name="bancosSelect">
                                          <!-- Opção fixa no topo -->
                                          <option value="">Selecione a Instituição Financeira</option>
                                          <?php
                                          // Obtém o banco do cliente (se existir)
                                          $codBancoCliente = isset($cliente_bco['CODBCO']) ? $cliente_bco['CODBCO'] : '';

                                          // Percorre todos os bancos disponíveis na tabela 'bancos'
                                          foreach ($bancos as $banco) {
                                             $codBanco = $banco['CODBCO'];
                                             $descBanco = $banco['DCRBCO'];

                                             // Define a opção como selecionada apenas se for o banco do cliente
                                             $selected = ($codBancoCliente === $codBanco) ? 'selected' : '';

                                             echo '<option value="' . $codBanco . '" ' . $selected . '> ' . $codBanco . ' - ' . $descBanco . ' </option>';
                                          }
                                          ?>
                                       </select>
                                    </div>
                                 </div>
                              </div>

                              <div class="form-group">
                                 <div class="row g-0">
                                    <div class="col-md-3">
                                       <label for="numAgnCtaBcoInput" class="form-label">Agencia</label>
                                       <input type="text" class="form-control" id="numAgnCtaBcoInput" placeholder="Digite sua Agencia Bancaria" value="<?php echo $cliente_bco['NUMAGC'] ?>">
                                    </div>
                                    <div class="col-md-5">
                                       <label for="numTitCtaBcoInput" class="form-label">Numero da Conta Bancaria - Digito</label>
                                       <input type="text" class="form-control" id="numTitCtaBcoInput" placeholder="Digite o Numero da sua Conta Bancaria (Com o Digito)" value="<?php echo $cliente_bco['NUMCTA'] ?>">
                                    </div>
                                    <div class="col-md-4">
                                       <label for="tpoCtaSelect" class="form-label">Tipo de Conta</label>
                                       <select class="form-control" id="tpoCtaSelect" name="tpoCtaSelect">
                                          <option value="" <?= (empty($cliente_bco['TPOCTA']) ? 'selected' : '') ?>>Selecione o Tipo de Conta</option>
                                          <option value="Corrente" <?= ($cliente_bco['TPOCTA'] === 'Corrente' ? 'selected' : '') ?>>Conta Corrente</option>
                                          <option value="Poupança" <?= ($cliente_bco['TPOCTA'] === 'Poupança' ? 'selected' : '') ?>>Conta Poupança</option>
                                       </select>
                                    </div>
                                 </div>
                              </div>

                              <div class="form-group">
                                 <div class="row g-0">
                                    <div class="col-md-12">
                                       <div class="form-check ms-2">
                                          <input type="checkbox" class="form-check-input" id="chkCta">
                                          <label class="form-check-label" for="chkCta"> Eu aceito que a OuroCred só fará Transferencias Bancarias para conta relacionada ao CPF do Cliente</label>
                                       </div>
                                    </div>
                                 </div>
                              </div>

                              <div class="form-group">
                                 <div class="row g-0">
                                    <div class="col-md-12">
                                       <div class="form-check ms-2">
                                          <input type="checkbox" class="form-check-input" id="chkPix">
                                          <label class="form-check-label" for="chkPix"> Eu aceito que a OuroCred só enviará PIX para a chave cadastrada no CPF do Cliente.</label>
                                       </div>
                                    </div>
                                 </div>
                              </div>

                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <!-- Botões de Navegação -->
               <div class="d-flex justify-content-between mt-4 btn-container">
                  <button class="btn btn-secondary" id="btnAnterior">← Anterior</button>
                  <button class="btn btn-primary" id="btnProximo">Próximo →</button>
               </div>
               <br>
               <div class="form-group">
                  <button type="button" id="btnAtualizarDados" class="btn btn-primary btn-lg btn-block" onclick="alterarConta()">Atualizar Dados <span id="spinnerBtnAtualizarDados" class="loaderbtn-sm" style="display: none;"></span> </button>
               </div>
            </div>
         </main>
         <?php
         echo footerPainel(); // invocando <footer>
         ?>
      </div>
   </div>
</body>
<script>
   /* ========================================
       ===== FUNCAO ATUALIZAR DADOS AJAX ======
       ========================================  */

   function alterarConta() {

      bloquearBotao("btnAtualizarDados", "spinnerBtnAtualizarDados");


      //Capturar o IDECLI
      const vsIdeCli = "<?php echo $cliente['IDECLI'] ?>";

      //variavel do token de validação
      const vsToken = "<?php echo $token; ?>";

      //variaveis da tabela clientes
      const vsNumTel = limparNumero(document.getElementById('nuntelinput').value);
      const vsEmlCli = document.getElementById('emailinput').value;
      const vsImg64 = ImgFtoBase64String;

      //variaves sobre endereço, tabela clientes
      const vsCepCli = document.getElementById('cep').value;
      const vsEndCli = document.getElementById('endereco').value;
      const vsNumCsa = document.getElementById('numcsainput').value;
      const vsCplEnd = document.getElementById('cplinput').value;
      const vsBaiCli = document.getElementById('bairro').value;
      const vsUfdCli = document.getElementById('estadosBrasileiros').value;
      const vsIdeMun = document.getElementById('cidade').value;

      //variaveis da tabela clientes_cpl
      const vsTpoDoc = document.getElementById('tpoDoc').value;
      const vsImg64Doc = ImgDocBase64String;
      const vsImg64CprEnd = ImgEndBase64String;

      //varaveis da tabela clientes_bco
      const vsCpfTtl = "<?php echo $cliente_bco['CPFTTL'] ?>";
      const vsNomTtl = document.getElementById('nomTitCtaBcoInput').value;
      const vsCodBco = document.getElementById('bancosSelect').value;
      const vsNumAgc = document.getElementById('numAgnCtaBcoInput').value;
      const vsNumCta = document.getElementById('numTitCtaBcoInput').value;
      const vsTpoCta = document.getElementById('tpoCtaSelect').value;

      let vsStaActCta = '';
      let vsStaActPix = '';

      //Variaveis do Checkbox PIX e TED Tabela clientes_bco -- Status Aceita Conta
      if (document.getElementById('chkCta').checked == true) {
         vsStaActCta = 'S';
      } else {
         vsStaActCta = 'N';
      }

      //Variaveis do Checkbox PIX e TED Tabela clientes_bco  --  Status Aceita Pix
      if (document.getElementById('chkPix').checked == true) {
         vsStaActPix = 'S';
      } else {
         vsStaActPix = 'N';
      }

      // Verificar Vazio para o endereço;
      if (!verificarVazio('cep')) {
         desbloquearBotao("btnAtualizarDados", "spinnerBtnAtualizarDados");
         return;
      }
      if (!verificarVazio('endereço')) {
         desbloquearBotao("btnAtualizarDados", "spinnerBtnAtualizarDados");
         return;
      }
      if (!verificarVazio('numcsainput')) {
         desbloquearBotao("btnAtualizarDados", "spinnerBtnAtualizarDados");
         return;
      }
      if (!verificarVazio('cplinput')) {
         desbloquearBotao("btnAtualizarDados", "spinnerBtnAtualizarDados");
         return;
      }
      if (!verificarVazio('bairro')) {
         desbloquearBotao("btnAtualizarDados", "spinnerBtnAtualizarDados");
         return;
      }
      if (!verificarVazio('estadosBrasileiros')) {
         desbloquearBotao("btnAtualizarDados", "spinnerBtnAtualizarDados");
         return;
      }
      if (!verificarVazio('cidade')) {
         desbloquearBotao("btnAtualizarDados", "spinnerBtnAtualizarDados");
         return;
      }

      const ajaxdados = {
         //token
         TOKEN: vsToken,

         //Clientes
         IDECLI: vsIdeCli,
         NUMTEL: vsNumTel,
         EMLCLI: vsEmlCli,

         //Clientes Endereço
         CEPCLI: vsCepCli,
         ENDCLI: vsEndCli,
         NUMCSA: vsNumCsa,
         CPLEND: vsCplEnd,
         BAICLI: vsBaiCli,
         UFDCLI: vsUfdCli,
         IDEMUN: vsIdeMun,

         //Clientes_cpl
         TPODOC: vsTpoDoc,

         //Clientes_bco
         CPFTTL: vsCpfTtl,
         NOMTTL: vsNomTtl,
         CODBCO: vsCodBco,
         NUMAGC: vsNumAgc,
         NUMCTA: vsNumCta,
         TPOCTA: vsTpoCta,
         STAACTCTA: vsStaActCta,
         STAACTPIX: vsStaActPix,

         //imagens BASE64
         IMG64: vsImg64, //Clientes
         IMG64DOC: vsImg64Doc, //Clientes_cpl
         IMG64CPREND: vsImg64CprEnd //Clientes_cpl
      };

      fetch('back-end/atualizardados-clientes.php', {
            method: 'POST',
            headers: {
               'Content-Type': 'application/json'
            },
            body: JSON.stringify(ajaxdados)
         })
         .then(response => response.json()
            .then(data => {
               if (!response.ok) {
                  throw new Error(data.mensagem || 'Erro desconhecido');
               }
               return data;
            })
         )
         .then(resposta => {
            alert(resposta.mensagem || 'Informações do Cliente atualizadas com sucesso!');
            carregarConteudo('perfil.php');
            // Oculta o spinner do botão após o sucesso
            desbloquearBotao("btnAtualizarDados", "spinnerBtnAtualizarDados");
         })
         .catch(error => {
            console.error('Erro:', error);
            alert(`Erro: ${error.message}`);
            desbloquearBotao("btnAtualizarDados", "spinnerBtnAtualizarDados");
            if (error.message.includes('Sessão expirada')) {
               window.location.href = '../index.php';
            }
         });

      //desbloquearBotao("btnAtualizarDados","spinnerBtnAtualizarDados");        

   }

   /* ==============================================
      ===== FUNCAO ENVIAR EMAIL ALTERAR SENHA ======
      ==============================================  */


   // Função chamada no onclick do botão "Recuperar Acesso"
   function enviarEmailAlterarSenha() {

      bloquearBotao("btnAlterarSenha", "spinnerbtnAlterarSenha");

      const vsCpf = "<?php echo $cliente['CPFCLI'] ?>";

      const ajaxdados = {
         cpfcli: vsCpf
      };

      fetch('../ajax/recuperar-senha.php', {
            method: 'POST',
            headers: {
               'Content-Type': 'application/json'
            },
            body: JSON.stringify(ajaxdados)
         })
         .then(response => {
            if (!response.ok) {
               return response.json().then(err => {
                  // Lança o erro com a mensagem recebida
                  throw new Error(err.mensagem || 'Erro desconhecido');
               });
            }

            //desaparecer spinner
            desbloquearBotao("btnAlterarSenha", "spinnerbtnAlterarSenha");

            return response.json();
         })
         .then(resposta => {
            //Pega a mensagem do JSON e exibe no elemento 'resultado'
            const staenv = resposta.staenv;
            if (staenv == 'S') {
               alert('Uma mensagem foi enviada para seu email, para continuarmos com a alteração da senha')
            }

            //desaparecer spinner
            desbloquearBotao("btnAlterarSenha", "spinnerbtnAlterarSenha");
         })
         .catch(error => {
            alert(`Erro: ${error.message}`);
            //desaparecer spinner
            desbloquearBotao("btnAlterarSenha", "spinnerbtnAlterarSenha");
         });
   }


   /// =============================
   /// =============================
   /// =============================



   // Pegando os valores das variáveis PHP e convertendo para JavaScript
   var chkCtaStatus = "<?php echo isset($cliente_bco['STAACTCTA']) ? $cliente_bco['STAACTCTA'] : 'N'; ?>";
   var chkPixStatus = "<?php echo isset($cliente_bco['STAACTPIX']) ? $cliente_bco['STAACTPIX'] : 'N'; ?>";

   // Se o valor for "S", marcar o checkbox; se for "N" ou qualquer outro, desmarcar
   document.getElementById("chkCta").checked = (chkCtaStatus.trim() === "S");
   document.getElementById("chkPix").checked = (chkPixStatus.trim() === "S");

   // Suponha que o valor da variável seja passado via PHP para um input hidden
   var tpoDocCliente = "<?php echo $cliente_cpl['TPODOC']; ?>";

   // Define o valor do select se a variável estiver preenchida
   if (tpoDocCliente) {
      document.getElementById("tpoDoc").value = tpoDocCliente;
   }

   ///////

   document.addEventListener("DOMContentLoaded", function() {
      const tabs = document.querySelectorAll('.nav-link');
      let currentIndex = 0;

      function atualizarAba(index) {
         if (index >= 0 && index < tabs.length) {
            document.querySelector('.nav-link.active').classList.remove("active");
            document.querySelector('.tab-pane.show.active').classList.remove("show", "active");

            currentIndex = index;

            tabs[currentIndex].classList.add("active");
            document.querySelector(tabs[currentIndex].getAttribute("href")).classList.add("show", "active");
         }
      }

      tabs.forEach((tab, index) => {
         tab.addEventListener("click", function() {
            currentIndex = index;
         });
      });

      document.getElementById("btnAnterior").addEventListener("click", function() {
         if (currentIndex > 0) atualizarAba(currentIndex - 1);
      });

      document.getElementById("btnProximo").addEventListener("click", function() {
         if (currentIndex < tabs.length - 1) atualizarAba(currentIndex + 1);
      });
   });

   document.getElementById("inputImagem").addEventListener("change", function(event) {
      const file = event.target.files[0];
      if (file) {
         const reader = new FileReader();

         reader.onload = function(e) {
            ImgFtoBase64String = e.target.result;
            //console.log("Imagem Foto convertida para Base64:", ImgFtoBase64String); // Apenas para teste
            document.getElementById("imgFto-show").src = ImgFtoBase64String;
         };

         reader.readAsDataURL(file);
      }
   });

   document.getElementById("inputImagemDoc").addEventListener("change", function(event) {
      const file = event.target.files[0];

      if (file) {
         const reader = new FileReader();

         reader.onload = function(e) {
            const base64String = e.target.result;

            // Detecta PDF pela assinatura Base64 (startsWith data:application/pdf)
            if (base64String.startsWith("data:application/pdf")) {
               document.getElementById("imgDoc-show").src = imgPadraoPDF;
               ImgDocBase64String = base64String; // Mantém base64 real para envio posterior se necessário
            } else {
               document.getElementById("imgDoc-show").src = base64String;
               ImgDocBase64String = base64String;
            }
         };

         reader.readAsDataURL(file);
      }
   });

   document.getElementById("inputImagemEnd").addEventListener("change", function(event) {
      const file = event.target.files[0];

      if (file) {
         const reader = new FileReader();

         reader.onload = function(e) {
            const base64String = e.target.result;

            // Detecta PDF pelo prefixo Base64
            if (base64String.startsWith("data:application/pdf")) {
               document.getElementById("imgEnd-show").src = imgPadraoPDF;
               ImgEndBase64String = base64String; // Guarda base64 real, se necessário enviar depois
            } else {
               document.getElementById("imgEnd-show").src = base64String;
               ImgEndBase64String = base64String;
            }
         };

         reader.readAsDataURL(file);
      }
   });



   ////Importado de ../criarconta.php

   function vaziobuscarcep(cepinput) {
      if (document.getElementById(cepinput).value !== '') {
         buscarDadosCep();
      } else {
         desbloquearCampos();
         document.getElementById('endereco').value = '';
         document.getElementById('bairro').value = '';
         document.getElementById('estadosBrasileiros').value = '';
         document.getElementById('cidade').value = '';
      }
   }

   function desbloquearCampos() {
      const ids = ['endereco', 'bairro', 'cidade', 'estadosBrasileiros'];
      ids.forEach(id => {
         const elemento = document.getElementById(id);
         elemento.readOnly = false;
         elemento.disabled = false;
         elemento.classList.remove('input-preenchido'); // Remove a classe de fundo cinza claro
      });
   }

   function limparCampos() {
      document.getElementById('endereco').value = "";
      document.getElementById('bairro').value = "";
      document.getElementById('cidade').value = "";
      document.getElementById('estadosBrasileiros').value = "";
   }

   function inputvazio(inputid, errorid) {
      const varinput = document.getElementById(inputid);
      const varerror = document.getElementById(errorid);

      if (varinput !== null) {
         if (varinput.value === '') {
            varerror.style.display = "block"; // Exibe mensagem de erro
         } else {
            varerror.style.display = "none"; // Oculta mensagem de erro
         }
      }
   }

   // Função para formatar o CEP
   function formatarCep(cep) {
      return cep.replace(/(\d{5})(\d{3})/, "$1-$2");
   }

   // Função para tornar o campo somente leitura e mudar o fundo para cinza claro
   function bloquearInput(id) {
      const elemento = document.getElementById(id);
      elemento.readOnly = true; // Funciona apenas para inputs
      elemento.disabled = true; // Funciona para inputs e selects
      elemento.classList.add('input-preenchido'); // Adiciona uma classe para o fundo cinza claro
   }

   function buscarDadosCep() {
      let cep = document.getElementById('cep').value;
      let mensagemErro = document.getElementById('mensagemErroCep');

      // Limpa mensagem de erro e campos antes de buscar o CEP
      mensagemErro.innerText = "";
      limparCampos();

      // Verifica se o CEP tem pelo menos 8 dígitos
      if (cep.length >= 8) {
         //fazer spinner aparecer
         const spinner = document.getElementById('loader-cep');
         spinner.style.display = "block";

         // Faz a requisição para o PHP busca_cep.php
         fetch('../uses/busca_cep.php', {
               method: 'POST',
               headers: {
                  'Content-Type': 'application/json'
               },
               body: JSON.stringify({
                  cep: cep
               })
            })
            .then(response => response.json())
            .then(data => {
               spinner.style.display = "none";
               if (!data.logradouro) {
                  // Exibe mensagem de erro se o CEP for inexistente
                  mensagemErro.innerText = "CEP inexistente";
                  desbloquearCampos(); // Desbloqueia os campos se o CEP não existir
               } else {
                  // Preenche os campos com os dados retornados pela API
                  document.getElementById('endereco').value = data.logradouro;
                  document.getElementById('bairro').value = data.bairro;
                  document.getElementById('estadosBrasileiros').value = data.uf;

                  // Limpa o select de cidade antes de carregar novas opções
                  const cidadeSelect = document.getElementById('cidade');
                  cidadeSelect.innerHTML = '<option value="">Selecione a Cidade</option>'; // Opcional, adiciona uma opção padrão

                  // Faz a requisição para carregar as cidades do estado
                  fetch(`../uses/cidades.php?estado=${data.uf}&online=n`)
                     .then(response => response.json())
                     .then(cidades => {
                        cidades.forEach(cidade => {
                           const option = document.createElement('option');
                           option.value = cidade.Id; // ID da cidade do DB
                           option.text = cidade.NOMMUN; // Nome da cidade
                           cidadeSelect.appendChild(option);
                        });
                        // Se a cidade retornada for válida, selecione-a
                        const cidadeEncontrada = cidades.find(c => c.NOMMUN.toLowerCase() === data.localidade.toLowerCase());
                        if (cidadeEncontrada) {
                           cidadeSelect.value = cidadeEncontrada.Id; // Define o valor do select como o ID da cidade encontrada
                        }
                     })
                     .catch(error => console.error('Erro ao carregar as cidades:', error));

                  // Torna os campos somente leitura e muda o fundo para cinza claro
                  bloquearInput('endereco');
                  bloquearInput('bairro');
                  bloquearInput('estadosBrasileiros');
                  bloquearInput('cidade');

                  // limpando msg de erro
                  document.getElementById('enderror').style.display = "none";
                  document.getElementById('baierror').style.display = "none";
                  document.getElementById('cdderror').style.display = "none";
                  document.getElementById('numcsaerror').style.display = "none";
                  //

                  // Formata o CEP para o padrão 78000-000
                  document.getElementById('cep').value = formatarCep(cep);
               }
            })
            .catch(error => {
               spinner.style.display = "none";
               console.error('Erro:', error);
               mensagemErro.innerText = "Erro ao buscar o CEP";
               desbloquearCampos(); // Desbloqueia os campos em caso de erro na requisição
            });
      }
   }

   // Função JavaScript para Carregar as Cidades
   function carregarCidades() {
      const estado = document.getElementById('estadosBrasileiros').value;
      const cidadeSelect = document.getElementById('cidade');

      if (estado) {
         fetch(`../uses/cidades.php?estado=${estado}&online=n`)
            .then(response => response.json())
            .then(cidades => {
               cidadeSelect.innerHTML = '<option value="">Selecione a Cidade</option>';
               cidades.forEach(cidade => {
                  const option = document.createElement('option');
                  option.value = cidade.Id; // ID da cidade do DB
                  option.text = cidade.NOMMUN; // Nome da cidade
                  cidadeSelect.appendChild(option);
               });
            })
            .catch(error => console.error('Erro ao carregar as cidades:', error));
      } else {
         cidadeSelect.innerHTML = '<option value="">Selecione a Cidade</option>';
      }
   }

   window.onload = function() {
      vaziobuscarcep('cep');
   };

   function limparNumero(telefone) {
      return telefone.replace(/\D/g, ''); // Remove tudo que não for número
   }

   function verificarVazio(idDoElemento) {
      let elemento = document.getElementById(idDoElemento);
      let dcrElemento = "";

      if (idDoElemento == "cplinput") {
         dcrElemento = 'Complemento do Endereço';
      } else if (idDoElemento == "numcsainput") {
         dcrElemento = 'Numero da Casa';
      } else if (idDoElemento == "nuntelinput") {
         dcrElemento = 'Numero de Telefone';
      } else {
         dcrElemento = idDoElemento;
      }

      if (elemento) {
         if ((elemento.tagName === "SELECT" && (elemento.value === "" || elemento.value === "0")) ||
            (elemento.tagName === "INPUT" && elemento.value.trim() === "")) {
            alert(`Por favor, preencha o campo ${dcrElemento}.`);
            return false; // Para a execução se estiver vazio
         }
      }
      return true; // Continua se estiver preenchido
   }

   function validaremail(emailinput, emailerror) {
      const emailField = document.getElementById(emailinput);
      const errorElement = document.getElementById(emailerror);
      const email = emailField.value;

      // Verifica se o e-mail contém "@" e se há um "." após o "@"
      const isValidEmail = email.includes('@') && email.indexOf('.', email.indexOf('@')) > email.indexOf('@');

      if (!isValidEmail) {
         errorElement.style.display = "block"; // Exibe mensagem de erro
      } else {
         errorElement.style.display = "none"; // Oculta mensagem de erro
      }
   }

   function valirdarvazioemail(einputid, eerrorid, emailinval) {
      inputvazio(einputid, eerrorid);
      validaremail(einputid, emailinval);
   }

   function enviarEmailVerificar() {

      bloquearBotao("btnVerificarEmail", "spinnerbtnVerificarEmail");
      document.getElementById("btnVerificarEmail").disable = true;

      //antes de executar a inserção no db, deve-se confirmar o pagamento na api
      const vsIdeCli = "<?php echo $cliente['IDECLI']; ?>";
      const vsEmlCli = document.getElementById('emailinput').value;
      const vsNomCli = "<?php echo primeiroUltimoNome($cliente['NOMCLI']); ?>";
      const vsMd5Pw = "<?php echo $cliente['MD5PW'] ?>";
      const vsToken = "<?php echo $token ?>";

      const ajaxdados = {
         IDECLI: vsIdeCli,
         EMLCLI: vsEmlCli,
         NOMCLI: vsNomCli,
         MD5PW: vsMd5Pw,
         TOKEN: vsToken
      }

      fetch('back-end/enviar-emailverificacao.php', {
            method: 'POST',
            headers: {
               'Content-Type': 'application/json'
            },
            body: JSON.stringify(ajaxdados)
         })
         .then(response => response.json()
            .then(data => {
               if (!response.ok) {
                  throw new Error(data.mensagem || 'Erro desconhecido');
               }
               return data;
            })
         )
         .then(resposta => {
            alert(resposta.mensagem || 'Email de Confirmação Enviado com Sucesso!');
            desbloquearBotao("btnVerificarEmail", "spinnerbtnVerificarEmail");
            carregarConteudo('edtperfil.php');
            // Oculta o spinner do botão após o sucesso

         })
         .catch(error => {
            console.error('Erro:', error);
            alert(`Erro: ${error.message}`);
            // Oculta o spinner mesmo em caso de erro
            spinnerbtn.style.display = 'none';
         });
   }

   window.onload = function() {
      // Preservar os valores reais vindos do PHP
      ImgDocBase64String = "<?= $imgRealDoc ?>";
      ImgEndBase64String = "<?= $imgRealEnd ?>";
      ImgFtoBase64String = "<?= $cliente['IMG64'] ?>";

      // Carrega preview automático do endereço via CEP
      vaziobuscarcep('cep');
   };
</script>

</html>