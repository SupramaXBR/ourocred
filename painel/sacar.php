<?php
// Validar o token
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
   <link rel="stylesheet" href="css/sacar.css"> <!-- css da pagina -->
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
   $pagina = 'sacar';
   $acesso = obterStatusDeAcesso($cliente['IDECLI'], $pagina);
   if (!$acesso || $acesso['STATUS'] == 'N') {
      $urlVoltar = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
      echo "<script>alert('Acesso bloqueado à página Solicitar Saque; Motivo: {$acesso['MOTIVO']}');</script>";
      echo '<meta http-equiv="refresh" content="0;url=' . $urlVoltar . '">';
      exit;
   }


   if (verificarEmailVerificado($cliente['IDECLI'])  == 'N') {
      $urlVoltar = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
      echo "<script>alert('Acesso bloqueado à página Solicitar Saque; e-Mail não verificado, por favor verifique o e-Mail Cadastrado em Editar Perfil');</script>";
      echo '<meta http-equiv="refresh" content="0;url=' . $urlVoltar . '">';
      exit;
   }

   if (verificarStatusDocumentos($cliente['IDECLI'])  == 'A') {
      $urlVoltar = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
      echo "<script>alert('Acesso bloqueado à página Solicitar Saque; Documentos Não verificados, por favor envie os documentos em Editar Perfil');</script>";
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

$valorMinimo = (float) str_replace(',', '.', obterValorGramaVendaComDesconto('1', 'Simple'));

$dadosBancarios = obterContaBancariaCliente($cliente['IDECLI']);

$dadosBancarios['CPFTTL'] = mascararCPF($dadosBancarios['CPFTTL']);

$nomeTitular     = $dadosBancarios['NOMTTL']     ?? '';
$cpfTitular      = $dadosBancarios['CPFTTL']     ?? '';
$instituicao     = $dadosBancarios['CODBCO']     ?? '';
$agencia         = $dadosBancarios['NUMAGC']     ?? '';
$contaBancaria   = $dadosBancarios['NUMCTA']     ?? '';
$tipoConta       = $dadosBancarios['TPOCTA']     ?? '';
$chavePix        = $dadosBancarios['STAACTPIX'] === 'S' ? $dadosBancarios['CPFTTL'] : ''; // Exemplo usando o próprio CPF como chave se ativado

$saquesCliente = obterSaquesClientes($cliente['IDECLI']);

?>

<body class="bg-light">
   <div class="page-wrapper d-flex flex-column min-vh-100">
      <div class="container-fluid flex-grow-1">
         <div class="row">
            <!-- SIDEBAR - visível apenas em telas XL (≥1200px) -->
            <nav class="col-xl-2 d-none d-xl-block bg-nav-painel sidebar p-3 min-vh-100 border-end position-fixed sidebar-xl">
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
            <!-- NAVBAR com OFFCANVAS - visível em telas menores que XL -->
            <nav class="d-xl-none fixed-top navbar-offcanvas-custom auto-hide-on-overlap">
               <div class="d-flex justify-content-between align-items-center p-2 bg-nav-painel border-bottom">
                  <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNav" aria-controls="offcanvasNav">
                     <i class="bi bi-list"></i>
                  </button>
                  <div class="offcanvas-header">
                     <h5 class="offcanvas-title cor_ouro" id="offcanvasNavLabel">
                        <img src="../imagens/favicon.ico" alt="OuroCred" width="30"> OuroCred
                     </h5>
                  </div>
                  <img src="<?php echo $cliente['IMG64'] ?>" alt="Perfil" class="img-lil-circle-perfil mobile">
               </div>
            </nav>

            <!-- OFFCANVAS -->
            <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNav" aria-labelledby="offcanvasNavLabel">
               <div class="offcanvas-header">
                  <h5 class="offcanvas-title" id="offcanvasNavLabel">
                     <img src="../imagens/favicon.ico" alt="OuroCred" width="30"> OuroCred
                  </h5>
                  <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
               </div>
               <div class="offcanvas-body">
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

            <main class="col-md-10 ms-sm-auto px-md-4 d-flex flex-column align-items-center mt-3">
               <div class="container-fluid">
                  <div class="card wizard-card shadow-sm border border-secondary-subtle rounded-4">
                     <div class="card-header bg-primary text-white text-center py-4">
                        <div class="d-flex flex-column align-items-center">
                           <h2 class="titulo-card mb-1">Solicitação de saque em conta</h2>
                           <small id="smallDcrAbaAberta" class="subtitulo-card">Solicite um saque para sua Conta Bancaria</small>
                        </div>
                     </div>

                     <div class="card-body pt-3"> <!-- Reduzido o padding superior -->
                        <ul class="nav nav-tabs nav-tabs-wizard justify-content-center mb-0" id="tabMenu"> <!-- Removido espaço extra -->
                           <li class="nav-item">
                              <a class="nav-link active" data-bs-toggle="tab" href="#aba1">Solicitações de Saque</a>
                           </li>
                           <li class="nav-item">
                              <a class="nav-link" data-bs-toggle="tab" href="#aba2">Solicitações de Saque Executadas/Em Analise</a>
                           </li>
                        </ul>

                        <div class="tab-content pt-2"> <!-- Reduzido espaçamento superior -->
                           <!-- ABA 1 -->
                           <div class="tab-pane fade show active" id="aba1">
                              <div class="tab-pane fade show active" id="aba1">
                                 <div class="row g-3">
                                    <div class="col-md-6">
                                       <label for="valorSaque" class="form-label">Valor do Saque (R$)</label>
                                       <input type="text" step="0.01" min="1.00" class="form-control" id="valorSaque" required>
                                    </div>
                                    <div class="col-md-6">
                                       <label for="tipoSaque" class="form-label">Tipo de Saque</label>
                                       <select class="form-select" id="tipoSaque" required onchange="exibirDestinoSaque()">
                                          <option value="PIX" selected>PIX</option>
                                          <option value="TED">TED</option>
                                       </select>
                                    </div>
                                 </div>
                                 <br>
                                 <div id="divPIX" style="display: block;">
                                    <h3 class="text-primary">Destino</h1>

                                       <label for="chavePix" class="form-label">Chave PIX</label>
                                       <input type="text" id="chavePix" class="form-control" value="<?= htmlspecialchars($chavePix) ?>" readonly>
                                 </div>
                                 <div id="divTED" style="display: none;">
                                    <h3 class="text-primary">Destino</h1>
                                       <div class="row g-3">
                                          <div class="col-md-6">
                                             <label for="cpfTitular" class="form-label">CPF do Titular</label>
                                             <input type="text" id="cpfTitular" class="form-control" value="<?= htmlspecialchars($cpfTitular) ?>" readonly>
                                          </div>
                                          <div class="col-md-6">
                                             <label for="nomeTitular" class="form-label">Nome do Titular</label>
                                             <input type="text" id="nomeTitular" class="form-control" value="<?= htmlspecialchars($nomeTitular) ?>" readonly>
                                          </div>
                                          <div class="col-md-6">
                                             <label for="instituicao" class="form-label">Instituição Financeira</label>
                                             <input type="text" id="instituicao" class="form-control" value="<?= htmlspecialchars($instituicao) ?>" readonly>
                                          </div>
                                          <div class="col-md-6">
                                             <label for="agencia" class="form-label">Agência</label>
                                             <input type="text" id="agencia" class="form-control" value="<?= htmlspecialchars($agencia) ?>" readonly>
                                          </div>
                                          <div class="col-md-6">
                                             <label for="contaBancaria" class="form-label">Número da Conta Bancária - Dígito</label>
                                             <input type="text" id="contaBancaria" class="form-control" value="<?= htmlspecialchars($contaBancaria) ?>" readonly>
                                          </div>
                                          <div class="col-md-6">
                                             <label for="tipoConta" class="form-label">Tipo de Conta</label>
                                             <input type="text" id="tipoConta" class="form-control" value="<?= htmlspecialchars($tipoConta) ?>" readonly>
                                          </div>
                                       </div>
                                 </div>
                                 <br>
                                 <div class="mt-4 text-center">
                                    <button type="button" id="btnSolicitarSaque" class="btn btn-primary w-100" onclick="solicitarSaque()">
                                       Solicitar Saque
                                       <span id="spinnerbtnSolicitarSaque" class="loaderbtn-sm" style="display: none;"></span>
                                    </button>
                                 </div>
                              </div>
                           </div>
                           <!-- ABA 2 -->
                           <div class="tab-pane fade show" id="aba2">

                              <!-- Solicitações de Saque Abertas -->
                              <div class="mt-2">
                                 <h6 class="fw-bold text-primary mb-2 border-bottom pb-1 small">Em Analise</h6>
                                 <div class="table-responsive">
                                    <table class="table table-bordered table-sm align-middle">
                                       <thead class="table-primary small">
                                          <tr class="align-middle">
                                             <th class="py-1">Data</th>
                                             <th class="py-1">ID Movimentação</th>
                                             <th class="py-1">Tipo</th>
                                             <th class="py-1 text-end">Valor</th>
                                             <th class="py-1 text-center">Ação</th>
                                          </tr>
                                       </thead>
                                       <tbody>
                                          <?php $temFinalizados = false; ?>
                                          <?php foreach ($saquesCliente as $saque): ?>
                                             <?php if ($saque['STASAQ'] === 'A'): ?>
                                                <tr class="text-primary small">
                                                   <td class="py-1"><?= date('d/m/Y H:i', strtotime($saque['DTAINS'])) ?></td>
                                                   <td class="py-1"><?= htmlspecialchars($saque['IDEMOV']) ?></td>
                                                   <td class="py-1"><?= htmlspecialchars($saque['TPOSAQ']) ?></td>
                                                   <td class="py-1 text-end">R$ <?= number_format(abs($saque['VLRSAQ']), 2, ',', '.') ?></td>
                                                   <td class="py-1 text-center">
                                                      <button class="btn btn-sm btn-danger" id="btnCancelarSaque_<?= $saque['CODREG'] ?>" onclick="cancelarSaque(<?= $saque['CODREG'] ?>, <?= $saque['IDEMOV'] ?>)">
                                                         Cancelar
                                                         <span id="spinnerbtnCancelarSaque_<?= $saque['CODREG'] ?>" class="loaderbtn-sm" style="display: none;"></span>
                                                      </button>
                                                   </td>
                                                </tr>
                                             <?php elseif ($saque['STASAQ'] === 'F'): ?>
                                                <?php $temFinalizados = true; ?>
                                             <?php endif; ?>
                                          <?php endforeach; ?>
                                       </tbody>
                                    </table>
                                 </div>
                              </div>

                              <?php if ($temFinalizados): ?>
                                 <!-- Solicitações de Saque Finalizadas -->
                                 <div class="mt-4 pt-3 border-top">
                                    <h6 class="fw-bold text-success mb-2 border-bottom pb-1 small">Executadas</h6>
                                    <div class="table-responsive">
                                       <table class="table table-bordered table-sm align-middle">
                                          <thead class="table-success small">
                                             <tr class="align-middle">
                                                <th class="py-1">Data</th>
                                                <th class="py-1">ID Movimentação</th>
                                                <th class="py-1">Tipo</th>
                                                <th class="py-1 text-end">Valor</th>
                                                <th class="py-1 text-center">Comprovante</th> <!-- ✅ Nova coluna -->
                                                <th class="py-1 text-center">Status</th>
                                             </tr>
                                          </thead>
                                          <tbody>
                                             <?php foreach ($saquesCliente as $saque): ?>
                                                <?php if ($saque['STASAQ'] === 'F'): ?>
                                                   <tr class="text-success small">
                                                      <td class="py-1"><?= date('d/m/Y H:i', strtotime($saque['DTAINS'])) ?></td>
                                                      <td class="py-1"><?= htmlspecialchars($saque['IDEMOV']) ?></td>
                                                      <td class="py-1"><?= htmlspecialchars($saque['TPOSAQ']) ?></td>
                                                      <td class="py-1 text-end">R$ <?= number_format(abs($saque['VLRSAQ']), 2, ',', '.') ?></td>

                                                      <td class="py-1 text-center">
                                                         <?php if (!empty($saque['IMG64CPR'])): ?>
                                                            <?php
                                                            // Detectar se é PDF
                                                            if (str_starts_with($saque['IMG64CPR'], 'data:application/pdf')) {
                                                               // É PDF base64 → link direto com atributo download
                                                               echo '<a href="' . $saque['IMG64CPR'] . '" download="Comprovante_' . $saque['IDEMOV'] . '.pdf">
                                                                        <i class="bi bi-file-earmark-pdf-fill text-danger" title="Download do Comprovante PDF"></i>
                                                                     </a>';
                                                            } else {
                                                               // É imagem base64 (se futuramente suportar imagem também)
                                                               echo '<i class="bi bi-image-fill text-primary" role="button" title="Ver imagem"
                                                                        onclick="abrirModalArquivo(`' . $saque['IMG64CPR'] . '`)"></i>';
                                                            }
                                                            ?>
                                                         <?php else: ?>
                                                            <i class="bi bi-file-earmark text-muted"></i>
                                                         <?php endif; ?>
                                                      </td>

                                                      <td class="py-1 text-center">
                                                         <span class="badge bg-success">Finalizado</span>
                                                      </td>
                                                   </tr>
                                                <?php endif; ?>
                                             <?php endforeach; ?>
                                          </tbody>
                                       </table>
                                    </div>
                                 </div>
                              <?php endif; ?>





                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </main>
         </div>
      </div>
      <?php echo footerPainel(); ?>
   </div>
</body>
<script>
   document.addEventListener("DOMContentLoaded", function() {
      const smallDescricao = document.getElementById("smallDcrAbaAberta");
      const tabs = document.querySelectorAll(".nav-link");

      tabs.forEach(tab => {
         tab.addEventListener("click", function() {
            if (tab.getAttribute("href") === "#aba1") {
               smallDescricao.textContent = "Faça uma solicitação de saque para sua conta bancaria";
            } else if (tab.getAttribute("href") === "#aba2") {
               smallDescricao.textContent = "Visualize solicitações de saque Em Analise e Executadas";
            }
         });
      });
   });

   document.addEventListener("DOMContentLoaded", function() {
      const navbar = document.querySelector('.auto-hide-on-overlap');
      const observerTarget = document.querySelector('.wizard-card');

      if (navbar && observerTarget) {
         const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
               if (entry.isIntersecting) {
                  navbar.classList.add('sticky-hidden');
               } else {
                  navbar.classList.remove('sticky-hidden');
               }
            });
         }, {
            rootMargin: '-1px 0px 0px 0px',
            threshold: 0
         });
         observer.observe(observerTarget);
      }
   });


   document.addEventListener("DOMContentLoaded", function() {
      const campoValor = document.getElementById("valorSaque");

      campoValor.addEventListener("input", function(e) {
         let valor = e.target.value;

         // Remove tudo que não for número
         valor = valor.replace(/\D/g, '');

         // Se o valor estiver vazio, não faz nada
         if (valor === '' || isNaN(valor)) {
            campoValor.value = '';
            return;
         }

         // Divide por 100 (para representar centavos)
         let valorFloat = parseFloat(valor) / 100;

         // Evita NaN e formata corretamente
         if (!isNaN(valorFloat)) {
            campoValor.value = valorFloat.toLocaleString('pt-BR', {
               minimumFractionDigits: 2,
               maximumFractionDigits: 2
            });
         } else {
            campoValor.value = '';
         }
      });
   });

   function solicitarSaque() {
      bloquearBotao("btnSolicitarSaque", "spinnerbtnSolicitarSaque");
      document.getElementById("btnSolicitarSaque").disabled = true;

      const vsIdeCli = "<?php echo $cliente['IDECLI']; ?>";
      const vsVlrSaqBr = document.getElementById("valorSaque").value;
      const vsTipoSaq = document.getElementById("tipoSaque").value;
      const vsToken = "<?php echo $token ?>";
      const valorMinimo = parseFloat("<?= number_format($valorMinimo, 2, '.', '') ?>");

      // Conversão do valor formatado para número puro
      const vsVlrSaq = parseFloat(vsVlrSaqBr.replace(/\./g, '').replace(',', '.'));

      // Validação de valor mínimo
      if (isNaN(vsVlrSaq) || vsVlrSaq < valorMinimo) {
         alert(`O valor mínimo para saque é R$ ${valorMinimo.toFixed(2).replace('.', ',')}`);
         desbloquearBotao("btnSolicitarSaque", "spinnerbtnSolicitarSaque");
         return;
      }

      const ajaxdados = {
         IDECLI: vsIdeCli,
         VLRSAQ: vsVlrSaq,
         TPOSAQ: vsTipoSaq,
         TOKEN: vsToken
      };

      fetch('back-end/inserirsaldo-saque.php', {
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
            alert(resposta.mensagem || 'Solicitação enviada com sucesso!');
            carregarConteudo('sacar.php');
         })
         .catch(error => {
            console.error('Erro:', error);
            alert(`Erro: ${error.message}`);
            if (error.message.includes('Sessão expirada')) {
               window.location.href = '../index.php';
            }
         })
         .finally(() => {
            desbloquearBotao("btnSolicitarSaque", "spinnerbtnSolicitarSaque");
         });
   }

   function exibirDestinoSaque() {
      const tipoSaque = document.getElementById("tipoSaque").value;
      const divPIX = document.getElementById("divPIX");
      const divTED = document.getElementById("divTED");

      if (tipoSaque === "PIX") {
         divPIX.style.display = "block";
         divTED.style.display = "none";
      } else if (tipoSaque === "TED") {
         divPIX.style.display = "none";
         divTED.style.display = "block";
      }
   }

   function baixarComprovantePDF(base64, nomeArquivo) {
      // Remove cabeçalho caso exista
      const base64Data = base64.replace(/^data:application\/pdf;base64,/, '');
      const byteCharacters = atob(base64Data);
      const byteNumbers = new Array(byteCharacters.length);

      for (let i = 0; i < byteCharacters.length; i++) {
         byteNumbers[i] = byteCharacters.charCodeAt(i);
      }

      const byteArray = new Uint8Array(byteNumbers);
      const blob = new Blob([byteArray], {
         type: 'application/pdf'
      });

      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = nomeArquivo;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
   }

   function cancelarSaque(CODREG, IDEMOV) {
      if (!confirm("Tem certeza que deseja cancelar esta solicitação de saque?")) return;

      const botaoId = 'btnCancelarSaque_' + CODREG;
      const spinnerId = 'spinnerbtnCancelarSaque_' + CODREG;

      bloquearBotao(botaoId, spinnerId);

      const dadosCancelamento = {
         CODREG: CODREG,
         IDEMOV: IDEMOV, // ✅ Enviando o IDEMOV junto
         TOKEN: "<?php echo $_SESSION['token']; ?>"
      };

      fetch('back-end/atualizardados-cancelarsaq.php', {
            method: 'POST',
            headers: {
               'Content-Type': 'application/json'
            },
            body: JSON.stringify(dadosCancelamento)
         })
         .then(response => response.json()
            .then(data => {
               if (!response.ok) {
                  throw new Error(data.mensagem || 'Erro desconhecido ao cancelar saque');
               }
               desbloquearBotao(botaoId, spinnerId);
               return data;
            })
         )
         .then(resposta => {
            alert(resposta.mensagem || "Solicitação de saque cancelada com sucesso!");
            desbloquearBotao(botaoId, spinnerId);
            carregarConteudo('sacar.php'); // Recarrega conteúdo ou página
         })
         .catch(error => {
            console.error('Erro:', error);
            alert(`Erro ao cancelar saque: ${error.message}`);
            desbloquearBotao(botaoId, spinnerId);

            if (error.message.includes('Sessão expirada')) {
               window.location.href = '../index.php';
            }
         });
   }
</script>


</html>