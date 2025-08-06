<?php
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
   <script>
      function carregarConteudo(pagina) {
         // Redireciona diretamente via GET
         window.location.href = pagina;
      }

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

      function desbloquearTodosBotoes() {
         desbloquearBotao("btnVenderSimple", "spinnerbtnVenderSimple");
         desbloquearBotao("btnVenderClassic", "spinnerbtnVenderClassic");
         desbloquearBotao("btnVenderStandard", "spinnerbtnVenderStandard");
         desbloquearBotao("btnVenderPremium", "spinnerbtnVenderPremium");
      }

      function formatacaoBR(valor) {
         return valor.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
      }

      function converterParaNumero(valor) {
         return parseFloat(valor.replace(/\./g, '').replace(',', '.'));
      }

      let vfVlrGrm = 0.0; // valor da grama com desconto (atualizado via AJAX)
   </script>
   <link rel="stylesheet" href="css/vender.css"> <!-- css da pagina -->
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

   // Carrega os dados do cliente da sessão
   $cliente = obterDadosCliente($_SESSION['cliente']['IDECLI'], $_SESSION['cliente']['CODCLI']);
   if ($cliente == null) {
      session_destroy();
      header("Location: ../index.php");
      exit;
   }

   // Verificando o acesso
   $pagina = 'venda';
   $acesso = obterStatusDeAcesso($cliente['IDECLI'], $pagina);
   if (!$acesso || $acesso['STATUS'] == 'N') {
      $urlVoltar = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
      echo "<script>alert('Acesso bloqueado à página Vender Ouro; Motivo: {$acesso['MOTIVO']}');</script>";
      echo '<meta http-equiv="refresh" content="0;url=' . $urlVoltar . '">';
      exit;
   }
} catch (PDOException $e) {
   session_destroy();
   header("Location: ../index.php");
   exit;
}

$token = $_SESSION['token'];



// Obtém o valor mínimo para depósito
$valorMinimo = number_format((RetornarValorGrama() / 10), 2, '.', '');

// rotina da foto de perfil
if ($cliente['IMG64'] == '') {
   $cliente['IMG64'] = ImagemPadrao(1);
}
?>

<body class="bg-light">
   <div class="container-fluid">
      <div class="row">
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
               <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('home.php')"><i class="bi bi-house"></i> Inicio</button></li>
               <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('perfil.php')"><i class="bi bi-person-circle"></i> Perfil</button></li>
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
         <nav class="d-md-none fixed-top">
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
                  <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('home.php');" data-bs-dismiss="offcanvas"><i class="bi bi-house"></i> Inicio</button></li>
                  <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('perfil.php');" data-bs-dismiss="offcanvas"><i class="bi bi-person-circle"></i> Perfil</button></li>
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
         <main class="container mt-4">
            <div class="container d-flex justify-content-center align-items-center min-vh-100">
               <div class="custom-container bg-white p-4 shadow rounded">

                  <div class="titulo-form p-2" style="background-color: #FFD700;">
                     <div class="row">
                        <!-- Primeira linha ocupando toda a largura -->
                        <div class="col-12">
                           <i class="bi bi-minecart"></i> <b> Vender Ouro </b>
                        </div>
                     </div>
                     <div class="row">
                        <div class="col-6 col-md-6 d-flex align-items-center">
                           <i class="bi bi-cash-coin cor-texto-saldo icone-wallet"></i>
                           <span class="ms-1 cor-texto-saldo">
                              <?php echo number_format(obterSaldoReais($cliente['IDECLI']), 2, ',', '.'); ?>
                           </span>
                        </div>
                        <div class="col-6 col-md-6 d-flex align-items-center justify-content-md-end">
                           <i class="bi bi-minecart text-danger icone-wallet"></i>
                           <span id="valorGramaCalculado" class="ms-1 text-danger">
                              R$ <?php echo obterValorGramaVendaComDesconto('1', 'Simple'); ?> x 1g
                           </span>
                        </div>
                     </div>
                  </div>

                  <br>
                  <label for="selectCarteira">Selecione a Carteira</label>
                  <select id="selectCarteira" class="form-select w-100" onchange="exibirCarteira()">
                     <option value="Simple" selected>Carteira Plano Simple</option>
                     <option value="Classic">Carteira Plano Classic</option>
                     <option value="Standard">Carteira Plano Standard</option>
                     <option value="Premium">Carteira Plano Premium</option>
                  </select>
                  <br>

                  <!-- carteiras -->
                  <div id="carteiraSimple" style="display: block;">
                     <div class="plan-card d-flex align-items-center p-2 h-100">
                        <div class="plano simple text-center px-3 py-2 h-100 d-flex flex-column justify-content-center">
                           <h5><i class="bi bi-wallet-fill"></i> Simple</h5>
                           <p class="mb-0">
                              <?php echo number_format(obterSaldoSimple($cliente['IDECLI']), 4, ',', '.'); ?>g
                           </p>
                        </div>
                        <div class="ms-3 flex-grow-1 d-flex flex-column h-100 gap-2">
                           <div class="d-flex justify-content-between align-items-center">
                              <div class="me-auto">
                                 <div class="input-group">
                                    <span class="input-group-text">g</span>
                                    <input type="number" id="simpleGInput" class="form-control" value="1.00" step="0.1">
                                 </div>
                              </div>
                              <div>
                                 <button class="btn btn-primary" onclick="venderTudo('Simple')">Tudo</button>
                              </div>
                           </div>
                           <div class="input-group">
                              <span class="input-group-text">R$</span>
                              <input type="text" id="simpleRSInput" class="form-control" readonly>
                           </div>
                           <button class="btn btn-primary w-100 mt-auto" id="btnVenderSimple" onclick="venderOuro('Simple')">
                              Vender
                              <span id="spinnerbtnVenderSimple" class="loaderbtn-sm" style="display: none;"></span>
                           </button>
                        </div>
                     </div>
                  </div>

                  <div id="carteiraClassic" style="display: none;">
                     <div class="plan-card d-flex align-items-center p-2 h-100">
                        <div class="plano classic text-center px-3 py-2 h-100 d-flex flex-column justify-content-center">
                           <h5><i class="bi bi-wallet-fill"></i> Classic</h5>
                           <p class="mb-0">
                              <?php echo number_format(obterSaldoClassic($cliente['IDECLI']), 4, ',', '.'); ?>g
                           </p>
                        </div>
                        <div class="ms-3 flex-grow-1 d-flex flex-column h-100 gap-2">
                           <div class="d-flex justify-content-between align-items-center">
                              <div class="me-auto">
                                 <div class="input-group">
                                    <span class="input-group-text">g</span>
                                    <input type="number" id="classicGInput" class="form-control" value="1.00" step="0.1">
                                 </div>
                              </div>
                              <div>
                                 <button class="btn btn-primary" onclick="venderTudo('Classic')">Tudo</button>
                              </div>
                           </div>
                           <div class="input-group">
                              <span class="input-group-text">R$</span>
                              <input type="text" id="classicRSInput" class="form-control" readonly>
                           </div>
                           <button class="btn btn-primary w-100 mt-auto" id="btnVenderClassic" onclick="venderOuro('Classic')">
                              Vender
                              <span id="spinnerbtnVenderClassic" class="loaderbtn-sm" style="display: none;"></span>
                           </button>
                        </div>
                     </div>
                  </div>

                  <div id="carteiraStandard" style="display: none;">
                     <div class="plan-card d-flex align-items-center p-2 h-100">
                        <div class="plano standard text-center px-3 py-2 h-100 d-flex flex-column justify-content-center">
                           <h5><i class="bi bi-wallet-fill"></i> Standard</h5>
                           <p class="mb-0">
                              <?php echo number_format(obterSaldoStandard($cliente['IDECLI']), 4, ',', '.'); ?>g
                           </p>
                        </div>
                        <div class="ms-3 flex-grow-1 d-flex flex-column h-100 gap-2">
                           <div class="d-flex justify-content-between align-items-center">
                              <div class="me-auto">
                                 <div class="input-group">
                                    <span class="input-group-text">g</span>
                                    <input type="number" id="standardGInput" class="form-control" value="1.00" step="0.1">
                                 </div>
                              </div>
                              <div>
                                 <button class="btn btn-primary" onclick="venderTudo('Standard')">Tudo</button>
                              </div>
                           </div>
                           <div class="input-group">
                              <span class="input-group-text">R$</span>
                              <input type="text" id="standardRSInput" class="form-control" readonly>
                           </div>
                           <button class="btn btn-primary w-100 mt-auto" id="btnVenderStandard" onclick="venderOuro('Standard')">
                              Vender
                              <span id="spinnerbtnVenderStandard" class="loaderbtn-sm" style="display: none;"></span>
                           </button>
                        </div>
                     </div>
                  </div>

                  <div id="carteiraPremium" style="display: none;">
                     <div class="plan-card d-flex align-items-center p-2 h-100">
                        <div class="plano premium text-center px-3 py-2 h-100 d-flex flex-column justify-content-center">
                           <h5><i class="bi bi-wallet-fill"></i> Premium</h5>
                           <p class="mb-0">
                              <?php echo number_format(obterSaldoPremium($cliente['IDECLI']), 4, ',', '.'); ?>g
                           </p>
                        </div>
                        <div class="ms-3 flex-grow-1 d-flex flex-column h-100 gap-2">
                           <div class="d-flex justify-content-between align-items-center">
                              <div class="me-auto">
                                 <div class="input-group">
                                    <span class="input-group-text">g</span>
                                    <input type="number" id="premiumGInput" class="form-control" value="1.00" step="0.1">
                                 </div>
                              </div>
                              <div>
                                 <button class="btn btn-primary" onclick="venderTudo('Premium')">Tudo</button>
                              </div>
                           </div>
                           <div class="input-group">
                              <span class="input-group-text">R$</span>
                              <input type="text" id="premiumRSInput" class="form-control" readonly>
                           </div>
                           <button class="btn btn-primary w-100 mt-auto" id="btnVenderPremium" onclick="venderOuro('Premium')">
                              Vender
                              <span id="spinnerbtnVenderPremium" class="loaderbtn-sm" style="display: none;"></span>
                           </button>
                        </div>
                     </div>
                  </div>

               </div>
            </div>
         </main>
      </div>
   </div>
   <?php
   echo footerPainel(); // invocando <footer>
   ?>
</body>
<script>
   function venderOuro(carteira) {
      let ajaxdados = {};

      if (carteira == "Simple") {

         bloquearBotao("btnVenderSimple", "spinnerbtnVenderSimple");

         const inputG = document.getElementById("simpleGInput");
         const inputRS = document.getElementById("simpleRSInput");

         if (!inputG || !inputRS) {
            console.error("Erro: Campo(s) não encontrado(s)!");
            return;
         }

         // Agora é seguro acessar .value
         const vsInputG = inputG.value;
         const vSInputRS = inputRS.value;

         const vfQtdGrm = parseFloat(vsInputG.replace(',', '.'));
         const vfVlrPgo = converterParaNumero(vSInputRS);

         //identificação
         const vsIdeCli = "<?php echo $cliente['IDECLI']; ?>";
         const vsTpoMov = "Venda";
         const vsDcrMov = "Venda da Carteira " + carteira + " -> " + vfQtdGrm + 'g';
         const vsCarteira = carteira;

         //token
         const vsToken = "<?php echo $token ?>";

         ajaxdados = {
            IDECLI: vsIdeCli,
            VLRPGO: vfVlrPgo,
            QTDGRM: vfQtdGrm,
            TPOMOV: vsTpoMov,
            DCRMOV: vsDcrMov,
            CARTEIRA: vsCarteira,
            TOKEN: vsToken
         }
      } else if (carteira == "Classic") {

         bloquearBotao("btnVenderClassic", "spinnerbtnVenderClassic");

         const inputG = document.getElementById("classicGInput");
         const inputRS = document.getElementById("classicRSInput");

         if (!inputG || !inputRS) {
            console.error("Erro: Campo(s) não encontrado(s)!");
            return;
         }

         // Agora é seguro acessar .value
         const vsInputG = inputG.value;
         const vSInputRS = inputRS.value;

         const vfQtdGrm = parseFloat(vsInputG.replace(',', '.'));
         const vfVlrPgo = converterParaNumero(vSInputRS);

         //identificação
         const vsIdeCli = "<?php echo $cliente['IDECLI']; ?>";
         const vsTpoMov = "Venda";
         const vsDcrMov = "Venda da Carteira " + carteira + " -> " + vfQtdGrm + 'g';
         const vsCarteira = carteira;

         //token
         const vsToken = "<?php echo $token ?>";

         ajaxdados = {
            IDECLI: vsIdeCli,
            VLRPGO: vfVlrPgo,
            QTDGRM: vfQtdGrm,
            TPOMOV: vsTpoMov,
            DCRMOV: vsDcrMov,
            CARTEIRA: vsCarteira,
            TOKEN: vsToken
         }
      } else if (carteira == "Standard") {

         bloquearBotao("btnVenderStandard", "spinnerbtnVenderStandard");

         const inputG = document.getElementById("standardGInput");
         const inputRS = document.getElementById("standardRSInput");

         if (!inputG || !inputRS) {
            console.error("Erro: Campo(s) não encontrado(s)!");
            return;
         }

         // Agora é seguro acessar .value
         const vsInputG = inputG.value;
         const vSInputRS = inputRS.value;

         const vfQtdGrm = parseFloat(vsInputG.replace(',', '.'));
         const vfVlrPgo = converterParaNumero(vSInputRS);

         //identificação
         const vsIdeCli = "<?php echo $cliente['IDECLI']; ?>";
         const vsTpoMov = "Venda";
         const vsDcrMov = "Venda da Carteira " + carteira + " -> " + vfQtdGrm + 'g';
         const vsCarteira = carteira;

         //token
         const vsToken = "<?php echo $token ?>";

         ajaxdados = {
            IDECLI: vsIdeCli,
            VLRPGO: vfVlrPgo,
            QTDGRM: vfQtdGrm,
            TPOMOV: vsTpoMov,
            DCRMOV: vsDcrMov,
            CARTEIRA: vsCarteira,
            TOKEN: vsToken
         }
      } else if (carteira == "Premium") {

         bloquearBotao("btnVenderPremium", "spinnerbtnVenderPremium");

         const inputG = document.getElementById("premiumGInput");
         const inputRS = document.getElementById("premiumRSInput");

         if (!inputG || !inputRS) {
            console.error("Erro: Campo(s) não encontrado(s)!");
            return;
         }

         // Agora é seguro acessar .value
         const vsInputG = inputG.value;
         const vSInputRS = inputRS.value;

         const vfQtdGrm = parseFloat(vsInputG.replace(',', '.'));
         const vfVlrPgo = converterParaNumero(vSInputRS);

         //identificação
         const vsIdeCli = "<?php echo $cliente['IDECLI']; ?>";
         const vsTpoMov = "Venda";
         const vsDcrMov = "Venda da Carteira " + carteira + " -> " + vfQtdGrm + 'g';
         const vsCarteira = carteira;

         //token
         const vsToken = "<?php echo $token ?>";

         ajaxdados = {
            IDECLI: vsIdeCli,
            VLRPGO: vfVlrPgo,
            QTDGRM: vfQtdGrm,
            TPOMOV: vsTpoMov,
            DCRMOV: vsDcrMov,
            CARTEIRA: vsCarteira,
            TOKEN: vsToken
         }
      }

      fetch('back-end/inserirsaldo-venderouro.php', {
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
               desbloquearTodosBotoes();
               return data;
            })
         )
         .then(resposta => {
            alert(resposta.mensagem || 'Ouro vendido com sucesso!');
            carregarConteudo('vender.php');
            desbloquearTodosBotoes();
         })
         .catch(error => {
            console.error('Erro:', error);
            alert(`Erro: ${error.message}`);
            desbloquearTodosBotoes();
            if (error.message.includes('Sessão expirada')) {
               window.location.href = '../index.php';
            }
         });

   }

   function venderTudo(carteira) {
      // Obtém o valor da grama via PHP
      let vfVlrGrmVda = Number("<?php echo (RetornarValorGrama() - obterValorDescGramaVendida()); ?>");

      if (carteira == "Simple") {
         vlrRSImput = Number("<?php echo obterSaldoSimple($cliente['IDECLI']); ?>") * vfVlrGrmVda;
         document.getElementById("simpleGInput").value = "<?php echo obterSaldoSimple($cliente['IDECLI']); ?>";
         document.getElementById("simpleRSInput").value = vlrRSImput.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
         });
      } else if (carteira == "Classic") {
         vlrRSImput = Number("<?php echo obterSaldoClassic($cliente['IDECLI']); ?>") * vfVlrGrmVda;
         document.getElementById("classicGInput").value = "<?php echo obterSaldoClassic($cliente['IDECLI']); ?>";
         document.getElementById("classicRSInput").value = vlrRSImput.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
         });
      } else if (carteira == "Standard") {
         vlrRSImput = Number("<?php echo obterSaldoStandard($cliente['IDECLI']); ?>") * vfVlrGrmVda;
         document.getElementById("standardGInput").value = "<?php echo obterSaldoStandard($cliente['IDECLI']); ?>";
         document.getElementById("standardRSInput").value = vlrRSImput.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
         });
      } else if (carteira == "Premium") {
         vlrRSImput = Number("<?php echo obterSaldoPremium($cliente['IDECLI']); ?>") * vfVlrGrmVda;
         document.getElementById("premiumGInput").value = "<?php echo obterSaldoPremium($cliente['IDECLI']); ?>";
         document.getElementById("premiumRSInput").value = vlrRSImput.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
         });
      }
   }

   function ocultarCarteiras() {
      document.getElementById("carteiraSimple").style.display = "none";
      document.getElementById("carteiraClassic").style.display = "none";
      document.getElementById("carteiraStandard").style.display = "none";
      document.getElementById("carteiraPremium").style.display = "none";
   }

   function exibirCarteira() {
      ocultarCarteiras(); // Oculta todas primeiro
      let carteiraSelecionada = document.getElementById("selectCarteira").value;
      document.getElementById("carteira" + carteiraSelecionada).style.display = "block";

      const xhr = new XMLHttpRequest();
      xhr.open("POST", "back-end/obter-valorvenda.php", true);
      xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

      xhr.onreadystatechange = function() {
         if (xhr.readyState === 4) {
            if (xhr.status === 200) {
               try {
                  const resposta = JSON.parse(xhr.responseText);
                  if (resposta.success) {
                     // Atualiza valor no HTML
                     document.getElementById("valorGramaCalculado").innerHTML = "R$ " + resposta.valor + " x 1g";

                     // Atualiza valor global da grama (convertendo para número real)
                     vfVlrGrm = parseFloat(resposta.valor.replace(".", "").replace(",", "."));

                     // Recalcula os campos
                     atualizarCamposCalculoGrama();
                  } else {
                     alert("Erro: " + resposta.mensagem);
                  }
               } catch (e) {
                  console.error("Erro ao interpretar JSON: ", e);
                  alert("Erro inesperado ao atualizar valor da grama.");
               }
            } else {
               alert("Erro ao conectar com o servidor.");
            }
         }
      };

      xhr.send("carteira=" + encodeURIComponent(carteiraSelecionada) + "&codemp=1");
      //---

   }

   document.addEventListener("DOMContentLoaded", function() {
      document.getElementById("carteiraSimple").style.display = "block";
      document.getElementById("carteiraClassic").style.display = "none";
      document.getElementById("carteiraStandard").style.display = "none";
      document.getElementById("carteiraPremium").style.display = "none";
   });

   document.addEventListener("DOMContentLoaded", function() {
      // Valor inicial da grama com desconto da carteira 'Simple'
      vfVlrGrm = parseFloat("<?php echo str_replace(",", ".", obterValorGramaVendaComDesconto('1', 'Simple')); ?>");

      if (isNaN(vfVlrGrm) || vfVlrGrm <= 0) {
         console.error("Valor da grama inválido.");
         return;
      }

      // Aplica cálculo ao digitar quantidade de gramas nos inputs
      const plans = ["simple", "classic", "standard", "premium"];

      plans.forEach(plan => {
         const inputG = document.getElementById(`${plan}GInput`);
         const inputRS = document.getElementById(`${plan}RSInput`);

         if (inputG && inputRS) {
            inputG.addEventListener("input", function() {
               let quantidade = inputG.value.replace(",", ".");
               quantidade = parseFloat(quantidade);

               if (isNaN(quantidade) || quantidade < 1) {
                  quantidade = 0.0;
                  inputG.value = "0.0";
               }

               let resultado = vfVlrGrm * quantidade;

               if (resultado < 0) resultado = 0.00;

               inputRS.value = formatacaoBR(resultado.toFixed(2).replace(".", ","));
            });

            // Inicializa com valor atual da grama
            inputG.dispatchEvent(new Event("input"));
         }
      });
   });

   document.addEventListener("DOMContentLoaded", function() {
      const inputsGramas = document.querySelectorAll("input[type='number']");

      inputsGramas.forEach(input => {
         input.addEventListener("input", function() {
            if (parseFloat(this.value) < 1) {
               this.value = 0.0;
            }
         });

         input.addEventListener("blur", function() {
            if (this.value === "" || parseFloat(this.value) < 1) {
               this.value = 0.0;
            }
         });
      });
   });

   function formatarValor(valor) {
      return parseFloat(valor).toFixed(2).replace('.', ',');
   }

   function atualizarCamposCalculoGrama() {
      const plans = ["simple", "classic", "standard", "premium"];
      plans.forEach(plan => {
         const inputG = document.getElementById(`${plan}GInput`);
         if (inputG) {
            inputG.dispatchEvent(new Event("input"));
         }
      });
   }
</script>
<html>