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
   <link rel="stylesheet" href="css/sac.css"> <!-- css da pagina -->
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
   $pagina = 'sac';
   $acesso = obterStatusDeAcesso($cliente['IDECLI'], $pagina);
   if (!$acesso || $acesso['STATUS'] == 'N') {
      $urlVoltar = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
      echo "<script>alert('Acesso bloqueado à página Abrir Chamado ao Suporte; Motivo: {$acesso['MOTIVO']}');</script>";
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

$protocolo = gerarProtocoloChamado();

$chamadosCliente = obterChamadosCliente($cliente['IDECLI']);
$temFechados = false;

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
                           <h2 class="titulo-card mb-1">Serviço de Atendimento ao Cliente</h2>
                           <small id="smallDcrAbaAberta" class="subtitulo-card">Abra um chamado para o suporte</small>
                        </div>
                     </div>

                     <div class="card-body pt-3"> <!-- Reduzido o padding superior -->
                        <ul class="nav nav-tabs nav-tabs-wizard justify-content-center mb-0" id="tabMenu"> <!-- Removido espaço extra -->
                           <li class="nav-item">
                              <a class="nav-link active" data-bs-toggle="tab" href="#aba1">Abrir Chamado</a>
                           </li>
                           <li class="nav-item">
                              <a class="nav-link" data-bs-toggle="tab" href="#aba2">Chamados Abertos/Solucionados</a>
                           </li>
                        </ul>

                        <div class="tab-content pt-2"> <!-- Reduzido espaçamento superior -->
                           <!-- ABA 1 -->
                           <div class="tab-pane fade show active" id="aba1">
                              <div class="row mb-3">
                                 <div class="col-md-6">
                                    <label for="inputProtocolo" class="form-label">Protocolo</label>
                                    <input type="text" class="form-control bg-light text-muted" id="inputProtocolo" value="<?php echo $protocolo ?>" readonly>
                                 </div>
                              </div>
                              <div class="mb-2">
                                 <label for="inputDescricao" class="form-label">Descrição do Título do Chamado</label>
                                 <input type="text" class="form-control" id="inputDescricao" placeholder="Digite um resumo do chamado">
                                 <small id="alertDescricao" class="text-danger" style="display: none;">⚠ Este campo é obrigatório.</small>
                              </div>
                              <div class="mb-2">
                                 <label for="inputMensagem" class="form-label">Detalhes do Chamado</label>
                                 <textarea class="form-control" id="inputMensagem" rows="5" placeholder="Descreva aqui sua dúvida, sugestão ou problema com detalhes"></textarea>
                                 <small id="alertMensagem" class="text-danger" style="display: none;">⚠ Este campo é obrigatório.</small>
                              </div>
                              <div class="mb-3">
                                 <label for="inputArquivo" class="form-label">Anexar Arquivo (Imagem ou PDF)</label>
                                 <input class="form-control" type="file" id="inputArquivo" accept=".jpg, .jpeg, .png, .pdf">
                              </div>
                              <div class="mb-2">
                                 <button type="submit" class="btn btn-primary btn-lg w-100" id="btnCriarChamado" onclick="abrirChamado()">
                                    Abrir Chamado
                                    <span id="spinnerbtnCriarChamado" class="loaderbtn-sm" style="display: none;"></span>
                                 </button>
                              </div>
                           </div>

                           <!-- ABA 2 -->
                           <div class="tab-pane fade show" id="aba2">
                              <!-- Chamados Abertos -->
                              <div class="mt-2">
                                 <h6 class="fw-bold text-primary mb-2 border-bottom pb-1 small">Chamados Abertos</h6>
                                 <div class="table-responsive">
                                    <table class="table table-bordered table-sm align-middle">
                                       <thead class="table-primary small">
                                          <tr class="align-middle">
                                             <th class="py-1">Data</th>
                                             <th class="py-1">Protocolo</th>
                                             <th class="py-1">Descrição</th>
                                             <th class="py-1 text-center">Arquivo</th>
                                             <th class="py-1 text-center">Status</th>
                                             <th class="py-1 text-center">Ação</th>
                                          </tr>
                                       </thead>
                                       <tbody>
                                          <?php foreach ($chamadosCliente as $chamado): ?>
                                             <?php if ($chamado['STACHM'] == 'A'): ?>
                                                <tr class="text-primary small">
                                                   <td class="py-1"><?= date('d/m/Y H:i', strtotime($chamado['DTAINS'])) ?></td>
                                                   <td class="py-1"><?= $chamado['NUMPTC'] ?></td>
                                                   <td class="py-1">
                                                      <span class="text-decoration-underline text-primary" role="button" onclick="abrirModalDetalhes(`<?= htmlspecialchars($chamado['DCRCHM']) ?>`, `<?= htmlspecialchars($chamado['TXTCHM']) ?>`)">
                                                         <?= htmlspecialchars($chamado['DCRCHM']) ?>
                                                      </span>
                                                   </td>
                                                   <td class="text-center">
                                                      <?php if (!empty($chamado['IMG64CHM'])): ?>
                                                         <?php
                                                         // Detectar se é PDF ou imagem
                                                         if (str_starts_with($chamado['IMG64CHM'], 'data:application/pdf')) {
                                                            // É PDF
                                                            echo '<a href="' . $chamado['IMG64CHM'] . '" download="PDF_' . $chamado['NUMPTC'] . '.pdf">
                                                                     <i class="bi bi-file-earmark-pdf-fill text-danger" title="Download do PDF"></i>
                                                                  </a>';
                                                         } else {
                                                            // É imagem base64
                                                            echo '<i class="bi bi-image-fill text-primary" role="button" title="Ver imagem"
                                                                    onclick="abrirModalArquivo(`' . $chamado['IMG64CHM'] . '`)"></i>';
                                                         }
                                                         ?>
                                                      <?php else: ?>
                                                         <i class="bi bi-file-earmark text-muted"></i>
                                                      <?php endif; ?>
                                                   </td>
                                                   <td class="py-1 text-center"><span class="badge bg-primary">Aberto</span></td>
                                                   <td class="py-1 text-center">
                                                      <button class="btn btn-sm btn-danger" id="btnCancelarChamado" onclick="cancelarChamado(<?= $chamado['CODREG'] ?>)">
                                                         Cancelar
                                                         <span id="spinnerbtnCancelarChamado" class="loaderbtn-sm" style="display: none;"></span>
                                                      </button>
                                                   </td>
                                                </tr>
                                             <?php endif; ?>
                                             <?php if ($chamado['STACHM'] == 'F') $temFechados = true; ?>
                                          <?php endforeach; ?>
                                       </tbody>
                                    </table>
                                 </div>
                              </div>

                              <?php if ($temFechados): ?>
                                 <!-- Chamados Fechados -->
                                 <div class="mt-4 pt-3 border-top">
                                    <h6 class="fw-bold text-success mb-2 border-bottom pb-1 small">Chamados Fechados</h6>
                                    <div class="table-responsive">
                                       <table class="table table-bordered table-sm align-middle">
                                          <thead class="table-success small">
                                             <tr class="align-middle">
                                                <th class="py-1">Data</th>
                                                <th class="py-1">Protocolo</th>
                                                <th class="py-1">Descrição</th>
                                                <th class="py-1 text-center">Arquivo</th>
                                                <th class="py-1 text-center">Status</th>
                                                <th class="py-1 text-center">Vis. Resposta</th>
                                             </tr>
                                          </thead>
                                          <tbody>
                                             <?php foreach ($chamadosCliente as $chamado): ?>
                                                <?php if ($chamado['STACHM'] == 'F'): ?>
                                                   <tr class="text-success small">
                                                      <td class="py-1"><?= date('d/m/Y H:i', strtotime($chamado['DTAINS'])) ?></td>
                                                      <td class="py-1"><?= $chamado['NUMPTC'] ?></td>
                                                      <td class="py-1">
                                                         <span class="text-decoration-underline text-primary" role="button" onclick="abrirModalDetalhes(`<?= htmlspecialchars($chamado['DCRCHM']) ?>`, `<?= htmlspecialchars($chamado['TXTCHM']) ?>`)">
                                                            <?= htmlspecialchars($chamado['DCRCHM']) ?>
                                                         </span>
                                                      </td>
                                                      <td class="text-center">
                                                         <?php if (!empty($chamado['IMG64CHM'])): ?>
                                                            <?php
                                                            // Detectar se é PDF ou imagem
                                                            if (str_starts_with($chamado['IMG64CHM'], 'data:application/pdf')) {
                                                               // É PDF
                                                               echo '<a href="' . $chamado['IMG64CHM'] . '" download="PDF_' . $chamado['NUMPTC'] . '.pdf">
                                                                     <i class="bi bi-file-earmark-pdf-fill text-danger" title="Download do PDF"></i>
                                                                  </a>';
                                                            } else {
                                                               // É imagem base64
                                                               echo '<i class="bi bi-image-fill text-primary" role="button" title="Ver imagem"
                                                                    onclick="abrirModalArquivo(`' . $chamado['IMG64CHM'] . '`)"></i>';
                                                            }
                                                            ?>
                                                         <?php else: ?>
                                                            <i class="bi bi-file-earmark text-muted"></i>
                                                         <?php endif; ?>
                                                      </td>
                                                      <td class="py-1 text-center"><span class="badge bg-success">Respondido</span></td>
                                                      <td class="py-1 text-center">
                                                         <button class="btn btn-outline-success btn-sm" title="Visualizar Resposta" onclick="visualizarRespostaChamado(<?= $chamado['CODREG'] ?>)">
                                                            <i class="bi bi-eye-fill"></i>
                                                         </button>
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


            <!-- Modal Detalhes do Chamado -->
            <div class="modal fade" id="modalDetalhesChamado" tabindex="-1" aria-labelledby="modalDetalhesChamadoLabel" aria-hidden="true">
               <div class="modal-dialog modal-lg modal-dialog-centered">
                  <div class="modal-content rounded-4">
                     <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalDetalhesChamadoLabel">Detalhes do Chamado</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                     </div>
                     <div class="modal-body">
                        <div class="mb-3">
                           <label for="modalInputDescricao" class="form-label">Descrição</label>
                           <input type="text" id="modalInputDescricao" class="form-control bg-light text-muted" readonly>
                        </div>
                        <div class="mb-3">
                           <label for="modalTextareaMensagem" class="form-label">Mensagem Completa</label>
                           <textarea id="modalTextareaMensagem" class="form-control bg-light text-muted" rows="10" readonly style="max-height: 300px; overflow-y: auto;"></textarea>
                        </div>
                     </div>
                     <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                     </div>
                  </div>
               </div>
            </div>

            <!-- Modal Visualização de Arquivo -->
            <div class="modal fade" id="modalVisualizarArquivo" tabindex="-1" aria-labelledby="modalVisualizarArquivoLabel" aria-hidden="true">
               <div class="modal-dialog modal-lg modal-dialog-centered">
                  <div class="modal-content rounded-4">
                     <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalVisualizarArquivoLabel">Arquivo Anexado</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                     </div>
                     <div class="modal-body text-center">
                        <img id="imgVisualizarArquivo" src="" alt="Arquivo Anexado" class="img-fluid rounded-3" style="max-height: 500px;">
                     </div>
                     <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                     </div>
                  </div>
               </div>
            </div>


            <!-- Modal Visualizar Resposta Chamado -->
            <div class="modal fade" id="modalVisualRespChamado" tabindex="-1" aria-labelledby="modalVisualRespChamadoLabel" aria-hidden="true">
               <div class="modal-dialog modal-lg modal-dialog-centered">
                  <div class="modal-content rounded-4">
                     <div class="modal-header">
                        <h5 class="modal-title fw-bold">Visualizar Resposta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                     </div>
                     <div class="modal-body">
                        <div class="row g-3">
                           <div class="col-lg-4">
                              <label class="form-label">Protocolo</label>
                              <input type="text" id="modalVisualResp-inputProtocolo" class="form-control bg-light text-muted" readonly>
                           </div>
                           <div class="col-lg-8">
                              <label class="form-label">Descrição</label>
                              <input type="text" id="modalVisualResp-inputDescricao" class="form-control bg-light text-muted" readonly>
                           </div>
                           <div class="col-12">
                              <label class="form-label">Mensagem da Resposta</label>
                              <textarea id="modalVisualResp-textareaResposta" class="form-control bg-light text-muted" rows="8" readonly style="max-height:300px;overflow-y:auto;"></textarea>
                           </div>
                           <div class="col-12" id="modalVisualResp-anexoContainer" style="display: none;">
                              <label class="form-label d-block">Arquivo Anexo</label>
                              <button id="modalVisualResp-btnAnexo" type="button" class="btn btn-outline-secondary btn-sm" onclick="baixarAnexoRespostaChamado()">
                                 <i id="modalVisualResp-iconAnexo" class="bi"></i> <span id="modalVisualResp-labelAnexo"></span>
                              </button>
                           </div>
                        </div>
                     </div>
                     <div class="modal-footer">
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Fechar</button>
                     </div>
                  </div>
               </div>
            </div>


         </div>
      </div>
      <?php echo footerPainel(); ?>
   </div>
</body>

<script>
   function abrirChamado() {
      // Limpa alertas anteriores
      document.getElementById("alertDescricao").style.display = "none";
      document.getElementById("alertMensagem").style.display = "none";

      const vsDcrChm = document.getElementById("inputDescricao").value.trim();
      const vsTxtChm = document.getElementById("inputMensagem").value.trim();

      let erro = false;

      // Validação da Descrição
      if (vsDcrChm === "") {
         document.getElementById("alertDescricao").style.display = "block";
         erro = true;
      }

      // Validação da Mensagem
      if (vsTxtChm === "") {
         document.getElementById("alertMensagem").style.display = "block";
         erro = true;
      }

      if (erro) {
         return; // Interrompe envio se houver erro
      }

      // Prossegue normalmente com envio AJAX
      bloquearBotao("btnCriarChamado", "spinnerbtnCriarChamado");

      const vsIdeCli = "<?php echo $cliente['IDECLI']; ?>";
      const vsNumPtc = "<?php echo $protocolo; ?>";
      const vsToken = "<?php echo $token ?>";
      const inputFile = document.getElementById("inputArquivo");
      const arquivo = inputFile.files[0];

      if (arquivo) {
         const reader = new FileReader();
         reader.onload = function(e) {
            const base64Data = e.target.result;
            enviarChamadoAjax(vsIdeCli, vsNumPtc, vsDcrChm, vsTxtChm, base64Data, vsToken);
         };
         reader.readAsDataURL(arquivo);
      } else {
         enviarChamadoAjax(vsIdeCli, vsNumPtc, vsDcrChm, vsTxtChm, '', vsToken);
      }
   }

   function enviarChamadoAjax(vsIdeCli, vsNumPtc, vsDcrChm, vsTxtChm, base64Arquivo, vsToken) {
      const ajaxdados = {
         IDECLI: vsIdeCli,
         NUMPTC: vsNumPtc,
         DCRCHM: vsDcrChm,
         TXTCHM: vsTxtChm,
         ARQCHM: base64Arquivo,
         TOKEN: vsToken
      };

      fetch('back-end/inserirdados-chamados.php', {
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
               desbloquearBotao("btnCriarChamado", "spinnerbtnCriarChamado");
               return data;
            })
         )
         .then(resposta => {
            alert(resposta.mensagem || 'Chamado aberto com sucesso!');
            carregarConteudo('sac.php');
         })
         .catch(error => {
            console.error('Erro:', error);
            alert(`Erro: ${error.message}`);
            desbloquearBotao("btnCriarChamado", "spinnerbtnCriarChamado");
            if (error.message.includes('Sessão expirada')) {
               window.location.href = '../index.php';
            }
         });
   }

   function cancelarChamado(CODREG) {
      if (!confirm("Tem certeza que deseja cancelar este chamado?")) return;
      bloquearBotao('btnCancelarChamado', 'spinnerbtnCancelarChamado');

      const dadosCancelamento = {
         CODREG: CODREG,
         TOKEN: "<?php echo $_SESSION['token']; ?>"
      };

      fetch('back-end/atualizardados-cancelarchm.php', {
            method: 'POST',
            headers: {
               'Content-Type': 'application/json'
            },
            body: JSON.stringify(dadosCancelamento)
         })
         .then(response => response.json()
            .then(data => {
               if (!response.ok) {
                  throw new Error(data.mensagem || 'Erro desconhecido ao cancelar chamado');
               }
               desbloquearBotao('btnCancelarChamado', 'spinnerbtnCancelarChamado');
               return data;
            })
         )
         .then(resposta => {
            alert(resposta.mensagem || "Chamado cancelado com sucesso!");
            desbloquearBotao('btnCancelarChamado', 'spinnerbtnCancelarChamado');
            carregarConteudo('sac.php'); // Recarrega página para atualizar a lista
         })
         .catch(error => {
            console.error('Erro:', error);
            alert(`Erro ao cancelar chamado: ${error.message}`);
            desbloquearBotao('btnCancelarChamado', 'spinnerbtnCancelarChamado');
            if (error.message.includes('Sessão expirada')) {
               window.location.href = '../index.php';
            }
         });
   }


   document.addEventListener("DOMContentLoaded", function() {
      const smallDescricao = document.getElementById("smallDcrAbaAberta");
      const tabs = document.querySelectorAll(".nav-link");

      tabs.forEach(tab => {
         tab.addEventListener("click", function() {
            if (tab.getAttribute("href") === "#aba1") {
               smallDescricao.textContent = "Abra um chamado para o suporte";
            } else if (tab.getAttribute("href") === "#aba2") {
               smallDescricao.textContent = "Visualize chamados abertos e já solucionados";
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

   function abrirModalDetalhes(descricao, mensagem) {
      document.getElementById('modalInputDescricao').value = descricao;
      document.getElementById('modalTextareaMensagem').value = mensagem;
      const modal = new bootstrap.Modal(document.getElementById('modalDetalhesChamado'));
      modal.show();
   }

   function abrirModalArquivo(base64img) {
      if (base64img && base64img.startsWith("data:image")) {
         document.getElementById('imgVisualizarArquivo').src = base64img;
         const modal = new bootstrap.Modal(document.getElementById('modalVisualizarArquivo'));
         modal.show();
      } else {
         alert("Arquivo inválido ou não é uma imagem.");
      }
   }

   function responderChamado(codreg, numptc = '', descricao = '') {
      // Preenche os campos do modal
      document.getElementById('modalResp-codreg').value = codreg;
      document.getElementById('modalResp-inputProtocolo').value = numptc;
      document.getElementById('modalResp-inputDescricao').value = descricao;
      document.getElementById('modalResp-textareaResposta').value = '';

      // Limpa campos extras
      document.getElementById('inputArquivo').value = '';
      document.getElementById('checkboxEmail').checked = false;

      // Abre o modal
      const modalResp = new bootstrap.Modal(document.getElementById('modalRespChamado'));
      modalResp.show();
   }

   function visualizarRespostaChamado(codreg) {
      fetch('back-end/obterdados-respchm.php', {
            method: 'POST',
            headers: {
               'Content-Type': 'application/json'
            },
            body: JSON.stringify({
               CODREG: codreg
            })
         })
         .then(res => res.json())
         .then(data => {
            if (data && data.resposta) {
               document.getElementById('modalVisualResp-inputProtocolo').value = data.resposta.NUMPTC;
               document.getElementById('modalVisualResp-inputDescricao').value = data.resposta.DCRCHM;
               document.getElementById('modalVisualResp-textareaResposta').value = data.resposta.TXTCHM;

               // Verifica e trata arquivo anexo
               if (data.resposta.IMG64CHM && data.resposta.IMG64CHM.trim() !== "") {
                  const isPDF = data.resposta.IMG64CHM.startsWith('data:application/pdf');
                  const icon = isPDF ? 'bi-file-earmark-pdf-fill text-danger' : 'bi-image-fill text-primary';
                  const label = isPDF ? '[PDF]' : '[IMG]';

                  document.getElementById('modalVisualResp-iconAnexo').className = `bi ${icon}`;
                  document.getElementById('modalVisualResp-labelAnexo').innerText = label;
                  document.getElementById('modalVisualResp-btnAnexo').dataset.base64 = data.resposta.IMG64CHM;
                  document.getElementById('modalVisualResp-anexoContainer').style.display = 'block';
               } else {
                  document.getElementById('modalVisualResp-anexoContainer').style.display = 'none';
               }

               const modal = new bootstrap.Modal(document.getElementById('modalVisualRespChamado'));
               modal.show();
            } else {
               alert('Resposta não encontrada para este chamado.');
            }
         })
         .catch(err => {
            console.error(err);
            alert('Erro ao buscar dados da resposta.');
         });
   }

   function baixarAnexoRespostaChamado() {
      const base64 = document.getElementById('modalVisualResp-btnAnexo').dataset.base64;
      const numptc = document.getElementById('modalVisualResp-inputProtocolo').value;

      if (base64 && (base64.startsWith("data:image") || base64.startsWith("data:application/pdf"))) {
         const link = document.createElement('a');
         link.href = base64;

         // Define nome de arquivo com extensão e prefixo apropriado
         let prefixo = "";
         let extensao = "";

         if (base64.startsWith("data:image")) {
            prefixo = "IMG_";
            extensao = "jpg"; // padrão genérico; se quiser detectar png/jpeg, dá pra refinar mais
         } else if (base64.startsWith("data:application/pdf")) {
            prefixo = "PDF_";
            extensao = "pdf";
         }

         link.download = `${prefixo}${numptc}.${extensao}`;

         // Dispara o clique no link virtual
         document.body.appendChild(link);
         link.click();
         document.body.removeChild(link);
      } else {
         alert("Arquivo inválido ou não suportado.");
      }
   }
</script>

</html>