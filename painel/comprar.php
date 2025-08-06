<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
   <meta name="referrer" content="strict-origin-when-cross-origin"> <!-- referrer -->
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
   <!-- Script da Responsividade offcanva -->
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
         desbloquearBotao("btnComprarSimple", "spinnerbtnComprarSimple");
         desbloquearBotao("btnComprarClassic", "spinnerbtnComprarClassic");
         desbloquearBotao("btnComprarStandard", "spinnerbtnComprarStandard");
         desbloquearBotao("btnComprarPremium", "spinnerbtnComprarPremium");
      }

      function formatacaoBR(valor) {
         return valor.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
      }

      function converterParaNumero(valor) {
         return parseFloat(valor.replace(/\./g, '').replace(',', '.'));
      }
   </script>
   <link rel="stylesheet" href="css/comprar.css"> <!-- css da pagina -->
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
   $pagina = 'compra';
   $acesso = obterStatusDeAcesso($cliente['IDECLI'], $pagina);
   if (!$acesso || $acesso['STATUS'] == 'N') {
      $urlVoltar = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
      echo "<script>alert('Acesso bloqueado à página Comprar Ouro; Motivo: {$acesso['MOTIVO']}');</script>";
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
$valorMinimo = number_format(RetornarValorGrama(), 2, '.', '');

// rotina da foto de perfil
if ($cliente['IMG64'] == '') {
   $cliente['IMG64'] = ImagemPadrao(1);
}

// Tempo de Desbloqueio da Carteira Classic
$dataAtual = date('Y-m-d H:i:s');
$qtdDiasBloqueio = retornarQtddiaClassic('1');
$dataDesbloqueioClassic = date('d/m/Y H:i:s', strtotime($dataAtual . " +{$qtdDiasBloqueio} days"));

// Tempo de Desbloqueio da Carteira Standard
$dataAtual = date('Y-m-d H:i:s');
$qtdDiasBloqueio = retornarQtddiaStandard('1');
$dataDesbloqueioStandard = date('d/m/Y H:i:s', strtotime($dataAtual . " +{$qtdDiasBloqueio} days"));

// Tempo de Desbloqueio da Carteira Premium
$dataAtual = date('Y-m-d H:i:s');
$qtdDiasBloqueio = retornarQtddiaPremium('1');
$dataDesbloqueioPremium = date('d/m/Y H:i:s', strtotime($dataAtual . " +{$qtdDiasBloqueio} days"));
?>

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
                           <i class="bi bi-minecart"></i> <b> Comprar Ouro </b>
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
                           <i class="bi bi-minecart cor-texto-saldo icone-wallet"></i>
                           <span class="ms-1 cor-texto-saldo text-right">
                              R$ <?php echo number_format(RetornarValorGrama(), 2); ?> x 1g
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

                  <div id="carteiraSimple" style="display: block;">
                     <div class="plan-card d-flex align-items-center p-2 h-100">
                        <div class="plano simple text-center px-3 py-2 h-100 d-flex flex-column justify-content-center">
                           <h5><i class="bi bi-wallet-fill"></i> Simple</h5>
                           <p class="mb-0">
                              <?php echo number_format(obterSaldoSimple($cliente['IDECLI']), 4, ',', '.'); ?>g
                           </p>
                        </div>
                        <div class="ms-3 flex-grow-1 d-flex flex-column h-100 gap-2">

                           <div class="input-group">
                              <span class="input-group-text">g</span>
                              <input type="number" id="simpleGInput" class="form-control" value="1.00" step="0.1">
                           </div>
                           <div class="input-group">
                              <span class="input-group-text">R$</span>
                              <input type="text" id="simpleRSInput" class="form-control" readonly>
                           </div>
                           <button class="btn btn-primary w-100 mt-auto" id="btnComprarSimple" onclick="comprarOuro('Simple')">
                              Comprar
                              <span id="spinnerbtnComprarSimple" class="loaderbtn-sm" style="display: none;"></span>
                           </button>
                        </div>
                     </div>
                     <br>
                  </div>

                  <div id="carteiraClassic" style="display: none;">
                     <div class="plan-card d-flex align-items-center p-2 h-100">
                        <div class="plano classic text-center px-3 py-2 h-100 d-flex flex-column justify-content-center">
                           <h5><i class="bi bi-wallet-fill"></i> Classic</h5>
                           <p class="mb-0">
                              <?php echo number_format(obterSaldoclassic($cliente['IDECLI']), 4, ',', '.'); ?>g
                           </p>
                        </div>
                        <div class="ms-3 flex-grow-1 d-flex flex-column h-100 gap-2">
                           <div class="input-group">
                              <span class="input-group-text">g</span>
                              <input type="number" id="classicGInput" class="form-control" value="1.00" step="0.1">
                           </div>
                           <div class="input-group">
                              <span class="input-group-text">R$</span>
                              <input type="text" id="classicRSInput" class="form-control" readonly>
                           </div>
                           <button class="btn btn-primary w-100 mt-auto" id="btnComprarClassic" onclick="comprarOuro('Classic')">
                              Comprar
                              <span id="spinnerbtnComprarClassic" class="loaderbtn-sm" style="display: none;"></span>
                           </button>
                        </div>
                     </div>
                     <br>
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
                           <div class="input-group">
                              <span class="input-group-text">g</span>
                              <input type="number" id="standardGInput" class="form-control" value="1.00" step="0.1">
                           </div>
                           <div class="input-group">
                              <span class="input-group-text">R$</span>
                              <input type="text" id="standardRSInput" class="form-control" readonly>
                           </div>
                           <button class="btn btn-primary w-100 mt-auto" id="btnComprarStandard" onclick="comprarOuro('Standard')">
                              Comprar
                              <span id="spinnerbtnComprarStandard" class="loaderbtn-sm" style="display: none;"></span>
                           </button>
                        </div>
                     </div>
                     <br>
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
                           <div class="input-group">
                              <span class="input-group-text">g</span>
                              <input type="number" id="premiumGInput" class="form-control" value="1.00" step="0.1">
                           </div>
                           <div class="input-group">
                              <span class="input-group-text">R$</span>
                              <input type="text" id="premiumRSInput" class="form-control" readonly>
                           </div>
                           <button class="btn btn-primary w-100 mt-auto" id="btnComprarPremium" onclick="comprarOuro('Premium')">
                              Comprar
                              <span id="spinnerbtnComprarPremium" class="loaderbtn-sm" style="display: none;"></span>
                           </button>
                        </div>
                     </div>
                     <br>
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
   // testes
   document.addEventListener("DOMContentLoaded", function() {
      // Obtém o valor da grama via PHP
      const vfVlrGrm = Number("<?php echo RetornarValorGrama(); ?>");

      if (isNaN(vfVlrGrm) || vfVlrGrm <= 0) {
         console.error("Erro: RetornarValorGrama() retornou um valor inválido.");
         return;
      }

      // Lista de planos para aplicar a funcionalidade
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

               // Se o resultado for negativo, define como 0,00
               if (resultado < 0) {
                  resultado = 0.00;
               }

               inputRS.value = formatacaoBR(resultado.toFixed(2).replace(".", ",")); // Formata para exibição PT-BR
            });
            // Dispara o evento uma vez para inicializar o valor correto
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


   function comprarOuro(carteira) {
      let ajaxdados = {};

      if (carteira == "Simple") {
         const inputG = document.getElementById("simpleGInput");
         const inputRS = document.getElementById("simpleRSInput");

         if (!confirm("Tem certeza que deseja Comprar " + inputG.value + "g de Ouro por R$:" + inputRS.value + " para a Carteira Simple?")) return;

         bloquearBotao("btnComprarSimple", "spinnerbtnComprarSimple");


         if (!inputG || !inputRS) {
            console.error("Erro: Campo(s) não encontrado(s)!");
            return;
         }

         // Agora é seguro acessar .value
         const vsInputG = inputG.value;
         const vSInputRS = inputRS.value;

         const vfQtdGrm = parseFloat(vsInputG.replace(',', '.'));
         const vfVlrPgo = converterParaNumero(vSInputRS);

         if (vfQtdGrm <= 0) {
            alert("Digite uma quantidade a ser comprada");
            return;
         }

         if (vfVlrPgo <= 0) {
            alert("Digite um valor a ser pago");
            return;
         }

         //identificação
         const vsIdeCli = "<?php echo $cliente['IDECLI']; ?>";
         const vsTpoMov = "Compra";
         const vsDcrMov = "Compra para a Carteira " + carteira + " -> " + vfQtdGrm + 'g';
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
         const inputG = document.getElementById("classicGInput");
         const inputRS = document.getElementById("classicRSInput");

         if (!confirm("Tem certeza que deseja Comprar " + inputG.value +
               "g de Ouro por R$:" + inputRS.value +
               " para a Carteira Classic? Lembrando que sua Carteira Classic Ficará Bloqueada para venda até " + "<?php echo $dataDesbloqueioClassic; ?>")) return;

         bloquearBotao("btnComprarClassic", "spinnerbtnComprarClassic");

         if (!inputG || !inputRS) {
            console.error("Erro: Campo(s) não encontrado(s)!");
            return;
         }

         // Agora é seguro acessar .value
         const vsInputG = inputG.value;
         const vSInputRS = inputRS.value;

         const vfQtdGrm = parseFloat(vsInputG.replace(',', '.'));
         const vfVlrPgo = converterParaNumero(vSInputRS);

         if (vfQtdGrm <= 0) {
            alert("Digite uma quantidade a ser comprada");
            return;
         }

         if (vfVlrPgo <= 0) {
            alert("Digite um valor a ser pago");
            return;
         }

         //identificação
         const vsIdeCli = "<?php echo $cliente['IDECLI']; ?>";
         const vsTpoMov = "Compra";
         const vsDcrMov = "Compra para a Carteira " + carteira + " -> " + vfQtdGrm + 'g';
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

         const inputG = document.getElementById("standardGInput");
         const inputRS = document.getElementById("standardRSInput");

         if (!confirm("Tem certeza que deseja Comprar " + inputG.value +
               "g de Ouro por R$:" + inputRS.value +
               " para a Carteira Standard? Lembrando que sua Carteira Standard Ficará Bloqueada para venda até " + "<?php echo $dataDesbloqueioStandard; ?>")) return;

         bloquearBotao("btnComprarStandard", "spinnerbtnComprarStandard");

         if (!inputG || !inputRS) {
            console.error("Erro: Campo(s) não encontrado(s)!");
            return;
         }

         // Agora é seguro acessar .value
         const vsInputG = inputG.value;
         const vSInputRS = inputRS.value;

         const vfQtdGrm = parseFloat(vsInputG.replace(',', '.'));
         const vfVlrPgo = converterParaNumero(vSInputRS);

         if (vfQtdGrm <= 0) {
            alert("Digite uma quantidade a ser comprada");
            return;
         }

         if (vfVlrPgo <= 0) {
            alert("Digite um valor a ser pago");
            return;
         }

         //identificação
         const vsIdeCli = "<?php echo $cliente['IDECLI']; ?>";
         const vsTpoMov = "Compra";
         const vsDcrMov = "Compra para a Carteira " + carteira + " -> " + vfQtdGrm + 'g';
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

         const inputG = document.getElementById("premiumGInput");
         const inputRS = document.getElementById("premiumRSInput");

         if (!confirm("Tem certeza que deseja Comprar " + inputG.value +
               "g de Ouro por R$:" + inputRS.value +
               " para a Carteira Premium? Lembrando que sua Carteira Premium Ficará Bloqueada para venda até " + "<?php echo $dataDesbloqueioPremium; ?>")) return;

         bloquearBotao("btnComprarPremium", "spinnerbtnComprarPremium");

         if (!inputG || !inputRS) {
            console.error("Erro: Campo(s) não encontrado(s)!");
            return;
         }

         // Agora é seguro acessar .value
         const vsInputG = inputG.value;
         const vSInputRS = inputRS.value;

         const vfQtdGrm = parseFloat(vsInputG.replace(',', '.'));
         const vfVlrPgo = converterParaNumero(vSInputRS);

         if (vfQtdGrm <= 0) {
            alert("Digite uma quantidade a ser comprada");
            return;
         }

         if (vfVlrPgo <= 0) {
            alert("Digite um valor a ser pago");
            return;
         }

         //identificação
         const vsIdeCli = "<?php echo $cliente['IDECLI']; ?>";
         const vsTpoMov = "Compra";
         const vsDcrMov = "Compra para a Carteira " + carteira + " -> " + vfQtdGrm + 'g';
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
      //apos os if elseif        
      fetch('back-end/inserirsaldo-comprarouro.php', {
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
            alert(resposta.mensagem || 'Saldo Adicionado com sucesso!');
            carregarConteudo('comprar.php');
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

   // funções de front-end

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
   }

   document.addEventListener("DOMContentLoaded", function() {
      document.getElementById("carteiraSimple").style.display = "block";
      document.getElementById("carteiraClassic").style.display = "none";
      document.getElementById("carteiraStandard").style.display = "none";
      document.getElementById("carteiraPremium").style.display = "none";
   });


   function verificarValorMinimo() {
      let valorMinimo = <?php echo $valorMinimo; ?>;
      let depositoInput = document.getElementById("depositoInput");

      // Converte o valor digitado para formato numérico
      let deposito = depositoInput.value.replace(/\./g, '').replace(',', '.');
      let valor = parseFloat(deposito);

      // Verifica se o valor é menor que o mínimo
      if (isNaN(valor) || valor < valorMinimo) {
         alert(`Valor mínimo: R$ ${formatarValor(valorMinimo)}`);
         depositoInput.value = formatarValor(valorMinimo);
         depositoInput.focus();
      }
   }

   function formatarValor(valor) {
      return parseFloat(valor).toFixed(2).replace('.', ',');
   }
</script>
<html>