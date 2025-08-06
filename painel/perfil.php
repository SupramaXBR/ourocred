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
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<link rel="stylesheet" href="css/perfil.css"> <!-- css da pagina -->
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
            <div class="container-fluid">
               <!-- Tabela de Perfil -->
               <div class="table-responsive mb-3">
                  <table class="table table-bordered text-center align-middle">
                     <tr>
                        <td colspan="5">
                           <img src="<?php echo $cliente['IMG64'] ?>" class="img-circle-perfil" alt="Perfil" />
                        </td>
                     </tr>
                     <tr>
                        <td colspan="2" class="text-end w-50"><b class="titulo-cinza">Investidor:</b></td>
                        <td colspan="2" class="text-start w-50"><?php echo $cliente['NOMCLI'] ?></td>
                     </tr>
                     <tr>
                        <td colspan="2" class="text-end"><b class="titulo-cinza">CPF:</b></td>
                        <td colspan="2" class="text-start"><?php echo mascararCPF($cliente['CPFCLI']) ?></td>
                     </tr>
                     <tr>
                        <td colspan="2" class="text-end"><b class="titulo-cinza">Conta Nº:</b></td>
                        <td colspan="2" class="text-start"><?php echo $cliente['IDECLI'] ?></td>
                     </tr>
                     <tr>
                        <td colspan="2" class="text-end"><b class="titulo-cinza">Cliente Desde:</b></td>
                        <td colspan="2" class="text-start"><?php echo formatarDataBR($cliente['DTAINS']) ?></td>
                     </tr>
                     <tr>
                        <td colspan="2" class="text-end"><b class="titulo-cinza">Status da Conta:</b></td>
                        <td colspan="2" class="text-start">
                           <form id="frm_edtcad" method="post" action="edtperfil.php">
                              <input type="hidden" name="idecli" value="<?php echo $cliente['IDECLI']; ?>">
                              <input type="hidden" name="loginpw" value="<?php echo $senha; ?>">
                              <input type="hidden" name="token" value="<?php echo $token; ?>">
                              <button type="submit" class="btn btn-primary btn-sm">Editar Perfil</button>
                              <?php
                              $camposVazios = contarCamposVazios_clientes($cliente['IDECLI']);
                              if ($camposVazios == -1) {
                                 echo 'Houve um Erro ao buscar os campos';
                              } else {
                                 echo '<progress id="progressBar" value="' . (16 - $camposVazios) . '" max="16"></progress>';
                                 $csscor = ($camposVazios == 0) ? 'cor-texto-verde' : 'cor-texto-vermelho';
                                 echo '<i class="' . $csscor . '"> ' . calcularPorcentagem((16 - $camposVazios), 16) . ' Concluido </i>';
                                 if (obterStatusAprovacao($cliente['IDECLI']) !== '') {
                                    echo ' | <i class="cor-texto-amarelo">' . obterStatusAprovacao($cliente['IDECLI']) . '</i>';
                                 }
                              }
                              ?>
                           </form>
                        </td>
                     </tr>
                  </table>
               </div>

               <!-- Tabela de Saldos -->
               <div class="table-responsive mb-3 carteiras">
                  <table class="table table-bordered text-center align-middle">
                     <tr>
                        <td colspan="5" class="table-primary"><b>CARTEIRAS</b></td>
                     </tr>
                     <tr>
                        <td>
                           <div class="card bg-success text-white">
                              <div class="card-body">
                                 <h5 class="card-title"><i class="bi bi-cash-coin"></i> Saldo</h5>
                                 <p class="card-text">R$: <?php echo number_format(obterSaldoReais($cliente['IDECLI']), 2, ',', '.'); ?></p>
                              </div>
                           </div>
                        </td>
                        <td>
                           <div class="card text-white" style="background-color: #cd7f32;">
                              <div class="card-body">
                                 <h5 class="card-title"><i class="bi bi-wallet-fill"></i> Simple</h5>
                                 <p class="card-text"><?php echo number_format(obterSaldoSimple($cliente['IDECLI']), 4, ',', '.'); ?>g</p>
                              </div>
                           </div>
                        </td>
                        <td>
                           <div class="card text-white" style="background-color: #C0C0C0;">
                              <div class="card-body">
                                 <h5 class="card-title"><i class="bi bi-wallet-fill"></i> Classic</h5>
                                 <p class="card-text"><?php echo number_format(obterSaldoclassic($cliente['IDECLI']), 4, ',', '.'); ?>g</p>
                              </div>
                           </div>
                        </td>
                        <td>
                           <div class="card bg-dark text-white">
                              <div class="card-body">
                                 <h5 class="card-title"><i class="bi bi-wallet-fill"></i> Standard</h5>
                                 <p class="card-text"><?php echo number_format(obterSaldoStandard($cliente['IDECLI']), 4, ',', '.'); ?>g</p>
                              </div>
                           </div>
                        </td>
                        <td>
                           <div class="card text-white" style="background-color: #FFD700;">
                              <div class="card-body">
                                 <h5 class="card-title"><i class="bi bi-wallet-fill"></i> Premium</h5>
                                 <p class="card-text"><?php echo number_format(obterSaldoPremium($cliente['IDECLI']), 4, ',', '.'); ?>g</p>
                              </div>
                           </div>
                        </td>
                     </tr>
                  </table>
               </div>

               <!-- Tabela de Cotação do Ouro -->
               <div class="table-responsive">
                  <table class="table table-bordered text-center align-middle">
                     <tr>
                        <td colspan="5" class="table-primary"><b>COTAÇÃO ATUAL DO OURO</b></td>
                     </tr>
                     <tr>
                        <td colspan="2">
                           <div class="card border-warning">
                              <div class="card-body">
                                 <h5 class="card-title text-warning"><i class="bi bi-minecart"></i> Compra</h5>
                                 <p class="card-text">R$ <?php echo number_format(RetornarValorGrama(), 2) ?> X 1g</p>
                              </div>
                           </div>
                        </td>
                        <td></td>
                        <td colspan="2">
                           <div class="card border-danger">
                              <div class="card-body">
                                 <h5 class="card-title text-danger"><i class="bi bi-minecart-loaded"></i> Venda</h5>
                                 <p class="card-text">R$ <?php echo number_format((RetornarValorGrama() - obterValorDescGramaVendida()), 2) ?> X 1g</p>
                              </div>
                           </div>
                        </td>
                     </tr>
                  </table>
               </div>
            </div>
         </main>
         <?php
         echo footerPainel(); // invocando <footer>
         ?>
      </div>
   </div>
</body>
<script src="js/perfil.js"></script>

</html>