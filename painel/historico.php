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

      function formatacaoBR(valor) {
         return valor.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
      }

      function converterParaNumero(valor) {
         return parseFloat(valor.replace(/\./g, '').replace(',', '.'));
      }
   </script>
   <style>
      /* css da navbar */
      @media (max-width: 1030px) {
         .nav .btn {
            font-size: 0.8rem;
            padding: 0.4rem 0.5rem;
         }

         .nav .btn i {
            font-size: 0.9rem;
         }
      }

      .titulo-responsivo {
         font-size: 24px;
         /* Tamanho padrão em telas maiores */
         margin-bottom: 0.5rem;
         /* Adicione outras propriedades conforme necessário */
      }

      @media (max-width: 767.98px) {
         .titulo-responsivo {
            font-size: 18px;
            /* Tamanho reduzido em telas menores */
         }
      }

      /* Para telas maiores (por exemplo, desktop) */
      .main-responsive {
         width: 85%;
         margin-left: 15%;
      }

      /* Para telas menores (mobile/tablet) */
      @media (max-width: 991.98px) {
         .main-responsive {
            width: 100%;
            margin-left: 0;
         }
      }

      html,
      body {
         height: 100%;
         margin: 0;
      }

      /* Wrapper que envolve todo o conteúdo */
      .wrapper {
         display: flex;
         flex-direction: column;
         min-height: 100%;
      }

      /* Área de conteúdo que expande para ocupar o espaço disponível */
      .content {
         flex: 1;
      }

      /* Remove o gutter (espaçamento) entre as colunas */
      .no-gutters>.col,
      .no-gutters>[class*="col-"] {
         padding-right: 0;
         padding-left: 0;
      }

      /* Cabeçalho para telas médias e maiores */
      .header-row {
         display: flex;
         flex-wrap: nowrap;
         align-items: center;
         background: #f8f9fa;
         border-bottom: 1px solid #dee2e6;
         margin-bottom: 0;
      }

      .header-row>div {
         overflow: hidden;
         text-overflow: ellipsis;
         white-space: nowrap;
         padding: 4px;
      }

      /* Distribuição em porcentagem para o cabeçalho */
      .header-datamov {
         flex: 0 0 14%;
         max-width: 14%;
      }

      .header-desc {
         flex: 0 0 30%;
         max-width: 30%;
      }

      .header-valor {
         flex: 0 0 7%;
         max-width: 7%;
      }

      .header-saldo {
         flex: 0 0 9%;
         max-width: 9%;
      }

      .header-simple {
         flex: 0 0 10%;
         max-width: 10%;
      }

      .header-classic {
         flex: 0 0 10%;
         max-width: 10%;
      }

      .header-standard {
         flex: 0 0 10%;
         max-width: 10%;
      }

      .header-premium {
         flex: 0 0 10%;
         max-width: 10%;
      }

      /* Alinha à direita os campos de valores no cabeçalho */
      .header-valor,
      .header-saldo,
      .header-simple,
      .header-classic,
      .header-standard,
      .header-premium {
         text-align: right;
      }

      /* Linha de dados para telas médias e maiores */
      .data-row-md {
         display: flex;
         flex-wrap: nowrap;
         align-items: center;
         border-bottom: 1px solid #dee2e6;
         margin-bottom: 0;
      }

      .data-row-md>div {
         overflow: hidden;
         text-overflow: ellipsis;
         white-space: nowrap;
         padding: 4px;
      }

      .data-datamov {
         flex: 0 0 14%;
         max-width: 14%;
      }

      .data-desc {
         flex: 0 0 30%;
         max-width: 30%;
      }

      .data-valor {
         flex: 0 0 7%;
         max-width: 7%;
      }

      .data-saldo {
         flex: 0 0 9%;
         max-width: 9%;
      }

      .data-simple {
         flex: 0 0 10%;
         max-width: 10%;
      }

      .data-classic {
         flex: 0 0 10%;
         max-width: 10%;
      }

      .data-standard {
         flex: 0 0 10%;
         max-width: 10%;
      }

      .data-premium {
         flex: 0 0 10%;
         max-width: 10%;
      }

      /* Alinha à direita os campos de valores nas linhas de dados */
      .data-valor,
      .data-saldo,
      .data-simple,
      .data-classic,
      .data-standard,
      .data-premium {
         text-align: right;
      }

      /* Layout para telas pequenas: itens empilhados com padding reduzido */
      @media (max-width: 767.98px) {
         .data-row .col {
            border-bottom: 1px solid #ddd;
            padding: 4px;
         }

         .data-row .col:last-child {
            border-bottom: none;
         }
      }

      .plan-card {
         display: none !important;
      }

      .custom-container {
         max-width: 500px;
         /* Define um limite máximo */
         width: 100%;
      }

      .saldoReais {
         background: #28a745;
         color: white;
         text-align: center;
      }

      .saldo {
         background: #28a745;
         color: white;
         padding: 20px;
         text-align: center;
         font-size: 20px;
         border-radius: 8px;
         margin-bottom: 20px;
      }

      .plano {
         padding: 15px;
         border-radius: 8px;
         color: white;
         min-width: 150px;
         min-height: 125px;
         display: flex;
         flex-direction: column;
         justify-content: center;
         align-items: center;
      }

      .simple {
         background: #b87333;
      }

      .classic {
         background: #a9a9a9;
      }

      .standard {
         background: #333;
      }

      .premium {
         background: #ffd700;
         color: black;
      }

      .plan-card {
         padding: 15px;
         border: 1px solid #ddd;
         border-radius: 8px;
      }

      /* css da responsividade do footer */
      .footer-custom {
         background-color: #3271a5;
         color: white;
         padding: 2rem 0;
      }

      .link-branco {
         color: white;
         text-decoration: none;
         margin-left: 10px;
         font-size: 1.5rem;
      }

      .texto-grande {
         font-size: 1.25rem;
      }

      @media (max-width: 992px) {
         .texto-grande {
            font-size: 1rem;
         }

         .link-branco {
            font-size: 1.25rem;
            margin-left: 5px;
         }
      }

      @media (max-width: 576px) {
         .texto-grande {
            font-size: 0.875rem;
         }

         .link-branco {
            font-size: 1rem;
            margin-left: 3px;
         }
      }
   </style>
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
   $pagina = 'historico';
   $acesso = obterStatusDeAcesso($cliente['IDECLI'], $pagina);
   if (!$acesso || $acesso['STATUS'] == 'N') {
      $urlVoltar = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
      echo "<script>alert('Acesso bloqueado à página Historico de Transações; Motivo: {$acesso['MOTIVO']}');</script>";
      echo '<meta http-equiv="refresh" content="0;url=' . $urlVoltar . '">';
      exit;
   }

   // Consulta os registros da tabela clientes_saldo para o cliente informado
   $sqlSaldo = "SELECT 
                            CODREG, IDEMOV, IDECLI, DTAMOV, TPOMOV, DCRMOV, VLRBSECLC, STAMOV,
                            saldo_reais, saldo_simple, saldo_classic, saldo_standard, saldo_premium
                        FROM clientes_saldo
                        WHERE IDECLI = :idecli AND STAMOV <> 'N'
                        ORDER BY DTAMOV ASC";

   $stmtSaldo = $pdo->prepare($sqlSaldo);
   $stmtSaldo->bindParam(':idecli', $cliente['IDECLI'], PDO::PARAM_STR);
   $stmtSaldo->execute();

   // Obtém os registros em forma de array associativo
   $saldoRecords = $stmtSaldo->fetchAll(PDO::FETCH_ASSOC);
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
         <main class="main-responsive">
            <div class="container justify-content-center align-items-center min-vh-100">

               <!-- Linha 1: Título -->
               <div class="row my-2">
                  <div class="col-12 text-center">
                     <h2 class="cor_ouro titulo-responsivo" style="margin-bottom:0.5rem;"><img src="../imagens/favicon.ico" alt="Ourocred" width="60"> OuroCred - Histórico de Transações</h2>
                  </div>
               </div>

               <!-- Cabeçalho para telas médias e maiores -->
               <div class="d-none d-md-flex header-row no-gutters font-weight-bold">
                  <div class="header-datamov">Data/Hora</div>
                  <div class="header-desc">Descrição</div>
                  <div class="header-valor">Valor (g)</div>
                  <div class="header-saldo">Saldo R$</div>
                  <div class="header-simple">Simple</div>
                  <div class="header-classic">Classic</div>
                  <div class="header-standard">Standard</div>
                  <div class="header-premium">Premium</div>
               </div>

               <!-- Cabeçalho para telas pequenas (itens empilhados) -->
               <div class="row d-block d-md-none font-weight-bold bg-light py-1">
                  <div class="col-12"><strong>Data/Hora</strong></div>
                  <div class="col-12"><strong>Descrição</strong></div>
                  <div class="col-12"><strong>Valor (g)</strong></div>
                  <div class="col-12"><strong>Saldo R$</strong></div>
                  <div class="col-12"><strong>Simple</strong></div>
                  <div class="col-12"><strong>Classic</strong></div>
                  <div class="col-12"><strong>Standard</strong></div>
                  <div class="col-12"><strong>Premium</strong></div>
               </div>

               <?php foreach ($saldoRecords as $saldo) {
                  if ($saldo['TPOMOV'] == "Entrada") {
                     $vsStyle = 'style="color: #0d6efd;"';
                  } else if ($saldo['TPOMOV'] == "Compra") {
                     $vsStyle = 'style="color: #198754;"';
                  } else if ($saldo['TPOMOV'] == "Venda") {
                     $vsStyle = 'style="color: #CE2835;"';
                  } else if ($saldo['TPOMOV'] == "Saida") {
                     $vsStyle = 'style="color: #e0c13b;"';
                  } else if ($saldo['TPOMOV'] == "Ajuste") {
                     $vsStyle = 'style="color:rgb(3, 33, 77);"';
                  }

               ?>
                  <!-- Versão para telas médias e maiores -->
                  <div class="d-none d-md-flex data-row-md no-gutters">
                     <div class="data-datamov" <?php echo $vsStyle; ?>><?php echo date('d/m/Y H:i', strtotime($saldo['DTAMOV'])); ?></div>
                     <div class="data-desc" <?php echo $vsStyle; ?>><?php echo $saldo['DCRMOV']; ?></div>
                     <div class="data-valor" <?php echo $vsStyle; ?>><?php echo number_format($saldo['VLRBSECLC'], 2, ',', '.'); ?></div>
                     <div class="data-saldo" <?php echo $vsStyle; ?>><?php echo number_format($saldo['saldo_reais'], 2, ',', '.'); ?></div>
                     <div class="data-simple" <?php echo $vsStyle; ?>><?php echo number_format($saldo['saldo_simple'], 4, ',', '.'); ?></div>
                     <div class="data-classic" <?php echo $vsStyle; ?>><?php echo number_format($saldo['saldo_classic'], 4, ',', '.'); ?></div>
                     <div class="data-standard" <?php echo $vsStyle; ?>><?php echo number_format($saldo['saldo_standard'], 4, ',', '.'); ?></div>
                     <div class="data-premium" <?php echo $vsStyle; ?>><?php echo number_format($saldo['saldo_premium'], 4, ',', '.'); ?></div>
                  </div>

                  <!-- Versão para telas pequenas -->
                  <div class="row d-block d-md-none data-row border-bottom py-1">
                     <div class="col-12" <?php echo $vsStyle; ?>><strong>Data/Hora:</strong> <?php echo date('d/m/Y H:i', strtotime($saldo['DTAMOV'])); ?></div>
                     <div class="col-12" <?php echo $vsStyle; ?>><strong>Descrição:</strong> <?php echo $saldo['DCRMOV']; ?></div>
                     <div class="col-12" <?php echo $vsStyle; ?>><strong>Valor (g):</strong> <?php echo number_format($saldo['VLRBSECLC'], 2, ',', '.'); ?></div>
                     <div class="col-12" <?php echo $vsStyle; ?>><strong>Saldo R$:</strong> <?php echo number_format($saldo['saldo_reais'], 2, ',', '.'); ?></div>
                     <div class="col-12" <?php echo $vsStyle; ?>><strong>Simple:</strong> <?php echo number_format($saldo['saldo_simple'], 4, ',', '.'); ?></div>
                     <div class="col-12" <?php echo $vsStyle; ?>><strong>Classic:</strong> <?php echo number_format($saldo['saldo_classic'], 4, ',', '.'); ?></div>
                     <div class="col-12" <?php echo $vsStyle; ?>><strong>Standard:</strong> <?php echo number_format($saldo['saldo_standard'], 4, ',', '.'); ?></div>
                     <div class="col-12" <?php echo $vsStyle; ?>><strong>Premium:</strong> <?php echo number_format($saldo['saldo_premium'], 4, ',', '.'); ?></div>
                  </div>
               <?php } ?>

               <!-- Cabeçalho para telas médias e maiores -->
               <div class="d-none d-md-flex header-row no-gutters font-weight-bold">
                  <div class="header-datamov"></div>
                  <div class="header-desc"></div>
                  <div class="header-valor"></div>
                  <div class="header-saldo"><?php echo number_format(obterSaldoReais($cliente['IDECLI']), 2, ',', '.'); ?></div>
                  <div class="header-simple"><?php echo number_format(obterSaldoSimple($cliente['IDECLI']), 4, ',', '.'); ?></div>
                  <div class="header-classic"><?php echo number_format(obterSaldoClassic($cliente['IDECLI']), 4, ',', '.'); ?></div>
                  <div class="header-standard"><?php echo number_format(obterSaldoStandard($cliente['IDECLI']), 4, ',', '.'); ?></div>
                  <div class="header-premium"><?php echo number_format(obterSaldoPremium($cliente['IDECLI']), 4, ',', '.'); ?></div>
               </div>

               <!-- Cabeçalho para telas pequenas (itens empilhados) -->
               <div class="row d-block d-md-none font-weight-bold bg-light py-1">
                  <div class="col-12"><strong></strong></div>
                  <div class="col-12"><strong></strong></div>
                  <div class="col-12"><strong></strong></div>
                  <div class="col-12"><strong><?php echo number_format(obterSaldoReais($cliente['IDECLI']), 2, ',', '.'); ?></strong></div>
                  <div class="col-12"><strong><?php echo number_format(obterSaldoSimple($cliente['IDECLI']), 4, ',', '.'); ?></strong></div>
                  <div class="col-12"><strong><?php echo number_format(obterSaldoClassic($cliente['IDECLI']), 4, ',', '.'); ?></strong></div>
                  <div class="col-12"><strong><?php echo number_format(obterSaldoStandard($cliente['IDECLI']), 4, ',', '.'); ?></strong></div>
                  <div class="col-12"><strong><?php echo number_format(obterSaldoPremium($cliente['IDECLI']), 4, ',', '.'); ?></strong></div>
               </div>

            </div>

         </main>

      </div>

   </div>
   <?php
   echo footerPainel(); // invocando <footer>
   ?>
</body>

<html>