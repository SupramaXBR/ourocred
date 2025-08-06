<?php
// Validar o token
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
   <!-- Script da Responsividade offcanva -->
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

   <link rel="stylesheet" href="../uses/estilo.css">

   <style>
      body {
         font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
         background-color: #f4f7f9;
         color: #333;
      }

      .container {
         max-width: 900px;
         margin: 0 auto;

         padding: 25px;
         border-radius: 8px;
         box-shadow: 0 4px L(0, 0, 0, 0.05);
      }

      h1,
      h2 {
         color: #2c3e50;
         text-align: center;
         border-bottom: 2px solid #ecf0f1;
         padding-bottom: 10px;
      }

      .label {
         font-weight: bold;
         color: #3498db;
      }

      /* Div para controlar o tamanho do gráfico */
      .chart-container {
         position: relative;
         height: 40vh;
         /* 40% da altura da tela */
         width: 100%;
         /* 100% da largura do container pai */
      }
   </style>
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<link rel="stylesheet" href="css/home.css"> <!-- css da pagina -->
<?php
// Verifica se a sessão do cliente e o token estão corretos

include_once "../uses/components.php";
include_once "../uses/funcoes.php";
include_once "../uses/conexao.php";

$valorDesconto = obterValorDescGramaVendida();

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
} catch (PDOException $e) {
   session_destroy();
   header("Location: ../index.php");
   exit;
}

$token = $_SESSION['token'];

// Se imagem estiver vazia, define padrão
if (empty($cliente['IMG64'])) {
   $cliente['IMG64'] = ImagemPadrao(1);
}

$historico = obterHistoricoUltimos6Meses($cliente['IDECLI']);

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
               <li class="nav-item"><button class="btn btn-primary w-100 mb-2" onclick="carregarConteudo('perfil.php')"><i class="bi bi bi-person-circle"></i> Perfil</button></li>
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
         <main class="col-md-10 ms-sm-auto px-md-4 d-flex flex-column align-items-center mt-3">
            <div class="container mb-5">
               <div class="row g-3">

                  <!-- Cards de saldo -->
                  <div class="col-md-2 col-sm-6">
                     <div class="card text-center shadow-sm p-3">
                        <i class="bi bi-cash-coin cor-texto-saldo fs-4"></i>
                        <div class="fw-bold">R$ <?php echo number_format(obterSaldoReais($cliente['IDECLI']), 2, ',', '.'); ?></div>
                        <small class="text-muted">Saldo Reais</small>
                     </div>
                  </div>

                  <div class="col-md-2 col-sm-6">
                     <div class="card text-center shadow-sm p-3">
                        <i class="bi bi-wallet-fill cor-texto-simple fs-4"></i>
                        <div class="fw-bold"><?php echo number_format(obterSaldoSimple($cliente['IDECLI']), 4, ',', '.'); ?>g</div>
                        <small class="text-muted">Carteira Simple</small>
                     </div>
                  </div>

                  <div class="col-md-2 col-sm-6">
                     <div class="card text-center shadow-sm p-3">
                        <i class="bi bi-wallet-fill cor-texto-classic fs-4"></i>
                        <div class="fw-bold"><?php echo number_format(obterSaldoClassic($cliente['IDECLI']), 4, ',', '.'); ?>g</div>
                        <small class="text-muted">Carteira Classic</small>
                     </div>
                  </div>

                  <div class="col-md-2 col-sm-6">
                     <div class="card text-center shadow-sm p-3">
                        <i class="bi bi-wallet-fill cor-texto-standard fs-4"></i>
                        <div class="fw-bold"><?php echo number_format(obterSaldoStandard($cliente['IDECLI']), 4, ',', '.'); ?>g</div>
                        <small class="text-muted">Carteira Standard</small>
                     </div>
                  </div>

                  <div class="col-md-2 col-sm-6">
                     <div class="card text-center shadow-sm p-3">
                        <i class="bi bi-wallet-fill cor-texto-premium fs-4"></i>
                        <div class="fw-bold"><?php echo number_format(obterSaldoPremium($cliente['IDECLI']), 4, ',', '.'); ?>g</div>
                        <small class="text-muted">Carteira Premium</small>
                     </div>
                  </div>

               </div>

               <!-- Gráficos -->
               <div class="row mt-4 g-4">
                  <div class="col-md-10">
                     <div class="card shadow-sm p-3">
                        <div class="container">
                           <!-- Wizard Tabs -->
                           <ul class="nav nav-tabs mb-3" id="graficoTabs" role="tablist">
                              <?php
                              $carteiras = ['Simple', 'Classic', 'Standard', 'Premium'];
                              foreach ($carteiras as $index => $carteira) {
                                 $ativo = $index === 0 ? 'active' : '';
                                 $id = strtolower($carteira);
                                 $iconeClass = "cor-texto-$id";
                                 echo "<li class='nav-item' role='presentation'>
                                          <button class='nav-link $ativo' id='tab-$id' data-bs-toggle='tab' data-bs-target='#grafico-$id' type='button' role='tab'>
                                             <i class='bi bi-wallet-fill $iconeClass fs-4'></i> $carteira
                                          </button>
                                       </li>";
                              }
                              ?>
                           </ul>

                           <!-- Wizard Content -->
                           <div class="tab-content" id="graficoTabsContent">
                              <?php foreach ($carteiras as $index => $carteira):
                                 $ativo = $index === 0 ? 'show active' : '';
                                 $id = strtolower($carteira);
                              ?>
                                 <div class="tab-pane fade <?= $ativo ?>" id="grafico-<?= $id ?>" role="tabpanel">
                                    <div class="chart-container">
                                       <canvas id="chart-<?= $id ?>"></canvas>
                                    </div>
                                 </div>
                              <?php endforeach; ?>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </main>
         <footer class="footer-custom" id="footerPainel">
            <div class="container">
               <div class="row align-items-center">
                  <div class="col-md-2 d-none d-md-block">
                  </div>
                  <div class="col-md-6 text-center text-md-start">
                     <p class="texto-grande"><?php echo retornarCampoEmpresa(1, 'NOMEMP'); ?> Todos os direitos reservados.</p>
                  </div>
                  <div class="col-md-4 text-center text-md-end">
                     <a class="link-branco" href="<?php echo retornarCampoEmpresa(1, 'LNKFACEBOOK');  ?>"><i class="bi bi-facebook icone-grande"></i></a>
                     <a class="link-branco" href="<?php echo retornarCampoEmpresa(1, 'LNKTWITTER');   ?>"><i class="bi bi-twitter-x icone-grande"></i></a>
                     <a class="link-branco" href="<?php echo retornarCampoEmpresa(1, 'LNKINSTAGRAM'); ?>"><i class="bi bi-instagram icone-grande"></i></a>
                     <a class="link-branco" href="<?php echo retornarCampoEmpresa(1, 'LNKYOUTUBE');   ?>"><i class="bi bi-youtube icone-grande"></i></a>
                     <a class="link-branco" href="<?php echo retornarCampoEmpresa(1, 'LNKWHATSAPP');  ?>"><i class="bi bi-whatsapp icone-grande"></i></a>
                  </div>
               </div>
            </div>
         </footer>
      </div>
   </div>
   <!-- Chart.js Script -->
   <script src="js/home.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <script>
      <?php foreach ($carteiras as $carteira):
         $id = strtolower($carteira);
         $dados = $historico[$carteira] ?? [];

         // Organiza labels e valores
         $labels = array_reverse(array_keys($dados)); // Mais antigos primeiro
         $compras = [];
         $vendas = [];

         foreach (array_reverse($dados) as $mes) {
            $compras[] = round($mes['compras']['TOTAL'] ?? 0, 2);
            $vendas[]  = round($mes['vendas']['TOTAL'] ?? 0, 2);
         }
      ?>
         const ctx_<?= $id ?> = document.getElementById('chart-<?= $id ?>').getContext('2d');
         new Chart(ctx_<?= $id ?>, {
            type: 'bar',
            data: {
               labels: <?= json_encode($labels) ?>,
               datasets: [{
                     label: 'Compras (R$)',
                     backgroundColor: '#FFC107',
                     borderColor: '#FFC107',
                     borderWidth: 1,
                     data: <?= json_encode($compras) ?>
                  },
                  {
                     label: 'Vendas (R$)',
                     backgroundColor: '#0f5132',
                     borderColor: '#0f5132',
                     borderWidth: 1,
                     data: <?= json_encode($vendas) ?>
                  }
               ]
            },
            options: {
               responsive: true,
               scales: {
                  y: {
                     beginAtZero: true,
                     title: {
                        display: true,
                        text: 'Valor em R$'
                     }
                  }
               },
               plugins: {
                  legend: {
                     position: 'top'
                  },
                  title: {
                     display: true,
                     text: 'Histórico de Compras e Vendas - <?= $carteira ?>'
                  }
               }
            }
         });
      <?php endforeach; ?>
   </script>
</body>

</html>