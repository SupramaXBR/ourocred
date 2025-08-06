<!DOCTYPE html>
<html lang="pt-br">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Dashboard de Chamados</title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <link href="css/chamados.css" rel="stylesheet">
   <script src="components/scriptpadrao.js"></script>
</head>
<?php
session_start();
include_once "../uses/conexao.php";
include_once "../uses/funcoes.php";

// Define o código da empresa (ajuste conforme sua lógica)
$codemp = 1;

// 1. Verifica se sessão admin existe
if (!isset($_SESSION['admin'])) {
   session_destroy();
   header("Location: ../index.php");
   exit;
}

// 2. Verifica tempo de sessão
$tempoLimite = retornaTempoLimite($codemp);
$ultimoAcesso = $_SESSION['admin']['ultimo_acesso'] ?? 0;
if ((time() - $ultimoAcesso) > $tempoLimite) {
   session_destroy();
   header("Location: ../index.php");
   exit;
}

// 3. Verifica token
if (!isset($_SESSION['admin']['token']) || $_SESSION['admin']['token'] !== ($_SESSION['token'] ?? '')) {
   session_destroy();
   header("Location: ../index.php");
   exit;
}

// 4. Valida credenciais direto do banco
$credenciais = obterCredenciaisAdminEmpresa($codemp);
if (
   !$credenciais ||
   $_SESSION['admin']['usuario'] !== $credenciais['USRADMIN'] ||
   md5($_SESSION['admin']['senha']) !== $credenciais['PWADMIN']
) {

   session_destroy();
   header("Location: ../index.php");
   exit;
}

// 5. Atualiza timestamp de último acesso
$_SESSION['admin']['ultimo_acesso'] = time();

$total        = obterNumeroChamados('T');
$abertos      = obterNumeroChamados('A');
$respondidos  = obterNumeroChamados('F');
$cancelados   = obterNumeroChamados('C');

$chamadosCliente = obterChamadosCliente();
$temFechados = false;
foreach ($chamadosCliente as $chm) {
   if ($chm['STACHM'] == 'F') {
      $temFechados = true;
      break;
   }
}
?>

<body class="bg-light">
   <div class="container-fluid">
      <div class="row">

         <!-- Inclusão da NAVBAR -->
         <?php include 'components/navbar.php'; ?>

         <!-- Conteúdo principal -->
         <main class="col-md-10 ms-sm-auto px-md-4 mt-3">
            <div class="container-fluid">
               <div class="row g-3">
                  <!-- Cards resumo -->
                  <div class="col-sm-6 col-md-4">
                     <div class="card text-center">
                        <div class="card-body">
                           <h6>Total Chamados</h6>
                           <h3 class="text-primary"><?php echo $total; ?></h3>
                        </div>
                     </div>
                  </div>
                  <div class="col-sm-6 col-md-4">
                     <div class="card text-center">
                        <div class="card-body">
                           <h6>Resolvidos</h6>
                           <h3 class="text-success"><?php echo $respondidos; ?></h3>
                        </div>
                     </div>
                  </div>
                  <div class="col-sm-6 col-md-4">
                     <div class="card text-center">
                        <div class="card-body">
                           <h6>Pendentes</h6>
                           <h3 class="text-warning"><?php echo $abertos; ?></h3>
                        </div>
                     </div>
                  </div>

                  <!-- Gráficos -->
                  <div class="col-lg-6">
                     <div class="card h-100">
                        <div class="card-body d-flex flex-column justify-content-between">
                           <h6>Chamados</h6>
                           <div class="chart-wrapper flex-grow-1">
                              <canvas id="chartSetores"></canvas>
                           </div>
                        </div>
                     </div>
                  </div>

                  <div class="col-lg-6">
                     <div class="card h-100">
                        <div class="card-body d-flex flex-column justify-content-between">
                           <h6>Status dos Chamados</h6>
                           <div class="chart-wrapper flex-grow-1">
                              <canvas id="chartStatus"></canvas>
                           </div>
                        </div>
                     </div>
                  </div>

                  <!-- Lista de chamados com scroll vertical -->
                  <div class="col-lg-12">
                     <div class="card">
                        <div class="card-body">
                           <div class="scroll-area table-responsive-sm">
                              <h6 class="text-primary small fw-bold mb-2">Chamados Abertos</h6>
                              <table class="table table-bordered table-sm align-middle mb-3">
                                 <thead class="table-primary small">
                                    <tr class="align-middle">
                                       <th class="py-1 coluna-data">Data</th>
                                       <th class="py-1">Protocolo</th>
                                       <th class="py-1">Descrição</th>
                                       <th class="py-1 text-center">Arquivo</th>
                                       <th class="py-1 text-center coluna-status">Status</th>
                                       <th class="py-1 text-center">Ação</th>
                                    </tr>
                                 </thead>
                                 <tbody>
                                    <?php foreach ($chamadosCliente as $chamado): ?>
                                       <?php if ($chamado['STACHM'] == 'A'): ?>
                                          <tr class="text-primary small">
                                             <td class="py-1 coluna-data"><?= date('d/m/Y H:i', strtotime($chamado['DTAINS'])) ?></td>
                                             <td class="py-1"><?= $chamado['NUMPTC'] ?></td>
                                             <td class="py-1">
                                                <span class="text-decoration-underline text-primary" role="button" onclick="abrirModalDetalhes(`<?= htmlspecialchars($chamado['DCRCHM']) ?>`, `<?= htmlspecialchars($chamado['TXTCHM']) ?>`, `<?= htmlspecialchars($chamado['NOMCLI']) ?>`, `<?= htmlspecialchars($chamado['CPFCLI']) ?>`)">
                                                   <?= htmlspecialchars('[' . primeiroUltimoNome($chamado['NOMCLI']) .  '] - ' . $chamado['DCRCHM']) ?>
                                                </span>
                                             </td>
                                             <td class="text-center">
                                                <?php if (!empty($chamado['IMG64CHM'])): ?>
                                                   <?php if (str_starts_with($chamado['IMG64CHM'], 'data:application/pdf')): ?>
                                                      <a href="<?= $chamado['IMG64CHM'] ?>" download="PDF_<?= $chamado['NUMPTC'] ?>.pdf">
                                                         <i class="bi bi-file-earmark-pdf-fill text-danger" title="Download do PDF"></i>
                                                      </a>
                                                   <?php else: ?>
                                                      <i class="bi bi-image-fill text-primary" role="button" title="Ver imagem" onclick="abrirModalArquivo(`<?= $chamado['IMG64CHM'] ?>`)"></i>
                                                   <?php endif; ?>
                                                <?php else: ?>
                                                   <i class="bi bi-file-earmark text-muted"></i>
                                                <?php endif; ?>
                                             </td>
                                             <td class="py-1 text-center coluna-status">
                                                <span class="badge bg-primary">Aberto</span>
                                             </td>
                                             <td class="py-1 text-center">
                                                <button class="btn btn-sm btn-success" id="btnResponderChamado-<?= $chamado['CODREG'] ?>" onclick="responderChamado(<?= $chamado['CODREG'] ?>, '<?= $chamado['NUMPTC'] ?>', '<?= $chamado['IDECLI'] ?>')">
                                                   Responder
                                                   <span id="spinnerbtnResponderChamado-<?= $chamado['CODREG'] ?>" class="loaderbtn-sm" style="display: none;"></span>
                                                </button>
                                                <button class="btn btn-sm btn-danger" id="btnCancelarChamado-<?= $chamado['CODREG'] ?>" onclick="cancelarChamado(<?= $chamado['CODREG'] ?>)">
                                                   Cancelar
                                                   <span id="spinnerbtnCancelarChamado-<?= $chamado['CODREG'] ?>" class="loaderbtn-sm" style="display: none;"></span>
                                                </button>
                                             </td>
                                          </tr>
                                       <?php endif; ?>
                                    <?php endforeach; ?>
                                 </tbody>
                              </table>

                              <?php if ($temFechados): ?>
                                 <h6 class="text-success small fw-bold mt-3 mb-2 border-top pt-2">Chamados Respondidos</h6>
                                 <table class="table table-bordered table-sm align-middle">
                                    <thead class="table-success small">
                                       <tr class="align-middle">
                                          <th class="py-1 coluna-data">Data</th>
                                          <th class="py-1">Protocolo</th>
                                          <th class="py-1">Descrição</th>
                                          <th class="py-1 text-center">Arquivo</th>
                                          <th class="py-1 text-center coluna-status">Status</th>
                                          <th class="py-1 text-center">Visualizar</th>
                                       </tr>
                                    </thead>
                                    <tbody>
                                       <?php foreach ($chamadosCliente as $chamado): ?>
                                          <?php if ($chamado['STACHM'] == 'F'): ?>
                                             <tr class="text-success small">
                                                <td class="py-1 coluna-data"><?= date('d/m/Y H:i', strtotime($chamado['DTAINS'])) ?></td>
                                                <td class="py-1"><?= $chamado['NUMPTC'] ?></td>
                                                <td class="py-1">
                                                   <span class="text-decoration-underline text-primary" role="button" onclick="abrirModalDetalhes(`<?= htmlspecialchars($chamado['DCRCHM']) ?>`, `<?= htmlspecialchars($chamado['TXTCHM']) ?>`, `<?= htmlspecialchars($chamado['NOMCLI']) ?>`, `<?= htmlspecialchars($chamado['CPFCLI']) ?>`)">
                                                      <?= htmlspecialchars('[' . primeiroUltimoNome($chamado['NOMCLI']) .  '] - ' . $chamado['DCRCHM']) ?>
                                                   </span>
                                                </td>
                                                <td class="text-center">
                                                   <?php if (!empty($chamado['IMG64CHM'])): ?>
                                                      <?php if (str_starts_with($chamado['IMG64CHM'], 'data:application/pdf')): ?>
                                                         <a href="<?= $chamado['IMG64CHM'] ?>" download="PDF_<?= $chamado['NUMPTC'] ?>.pdf">
                                                            <i class="bi bi-file-earmark-pdf-fill text-danger" title="Download do PDF"></i>
                                                         </a>
                                                      <?php else: ?>
                                                         <i class="bi bi-image-fill text-primary" role="button" title="Ver imagem" onclick="abrirModalArquivo(`<?= $chamado['IMG64CHM'] ?>`)"></i>
                                                      <?php endif; ?>
                                                   <?php else: ?>
                                                      <i class="bi bi-file-earmark text-muted"></i>
                                                   <?php endif; ?>
                                                </td>
                                                <td class="py-1 text-center coluna-status">
                                                   <span class="badge bg-success">Fechado</span>
                                                </td>
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
                              <?php endif; ?>
                           </div>
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
                        <label id="modalLabelNomCli" class="form-label">Cliente:</label>
                        <label id="modalLabelCpfCli" class="form-label">Cpf:</label>
                     </div>
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


         <!-- Modal Responder Chamado -->
         <div class="modal fade" id="modalRespChamado" tabindex="-1" aria-labelledby="modalRespChamadoLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
               <div class="modal-content rounded-4">
                  <div class="modal-header">
                     <h5 class="modal-title fw-bold" id="modalRespChamadoLabel">Responder Chamado</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                  </div>
                  <div class="modal-body">
                     <input type="hidden" id="modalResp-codreg">
                     <input type="hidden" id="modalResp-idecli">
                     <div class="row g-3">
                        <div class="col-lg-4">
                           <label for="modalResp-inputProtocolo" class="form-label">Protocolo</label>
                           <input type="text" id="modalResp-inputProtocolo" class="form-control bg-light text-muted" readonly>
                        </div>
                        <div class="col-lg-8">
                           <label for="modalResp-inputDescricao" class="form-label">Descrição</label>
                           <input type="text" id="modalResp-inputDescricao" class="form-control">
                        </div>
                        <div class="col-12">
                           <label for="modalResp-textareaResposta" class="form-label">Mensagem da Resposta</label>
                           <textarea id="modalResp-textareaResposta" class="form-control" rows="8" style="max-height: 300px; overflow-y: auto;"></textarea>
                        </div>
                        <div class="col-12">
                           <label for="inputArquivo" class="form-label">Anexar Arquivo (Imagem ou PDF)</label>
                           <input class="form-control" type="file" id="inputArquivo" accept=".jpg, .jpeg, .png, .pdf">
                        </div>
                        <div class="col-12 form-check mt-2">
                           <input class="form-check-input" type="checkbox" id="checkboxEmail">
                           <label class="form-check-label" for="checkboxEmail">Enviar cópia via e-Mail</label>
                        </div>
                     </div>
                  </div>
                  <div class="modal-footer">
                     <button type="button" class="btn btn-primary w-100" id="btnEnviarRespChamado" onclick="enviarRespostaChamadoForm()">
                        Responder Chamado
                        <span id="spinnerRespChamado" class="loaderbtn-sm" style="display: none;"></span>
                     </button>
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
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <script>
      const ctxSetores = document.getElementById('chartSetores').getContext('2d');
      new Chart(ctxSetores, {
         type: 'doughnut',
         data: {
            labels: ['Abertos', 'Respondidos', 'Cancelados'],
            datasets: [{
               data: [<?php echo $abertos ?>, <?php echo $respondidos ?>, <?php echo $cancelados ?>],
               backgroundColor: ['#0d6efd', '#198754', '#dc3545']
            }]
         },
         options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
               legend: {
                  position: 'bottom'
               }
            }
         }
      });

      const ctxStatus = document.getElementById('chartStatus').getContext('2d');
      new Chart(ctxStatus, {
         type: 'bar',
         data: {
            labels: ['Abertos', 'Respondidos', 'Cancelados'],
            datasets: [{
               label: 'Total',
               data: [<?php echo $abertos ?>, <?php echo $respondidos ?>, <?php echo $cancelados ?>],
               backgroundColor: ['#0d6efd', '#198754', '#dc3545']
            }]
         },
         options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
               legend: {
                  display: false
               }
            },
            scales: {
               x: {
                  beginAtZero: true
               }
            }
         }
      });

      function abrirModalDetalhes(descricao, mensagem, cliente, cpf) {
         document.getElementById('modalInputDescricao').value = descricao;
         document.getElementById('modalTextareaMensagem').value = mensagem;

         // Negrito no label, valor normal
         document.getElementById('modalLabelNomCli').innerHTML = "<strong>Cliente:</strong> " + cliente;
         document.getElementById('modalLabelCpfCli').innerHTML = "<strong>CPF:</strong> " + cpf;

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

      function cancelarChamado(CODREG) {
         if (!confirm("Tem certeza que deseja cancelar este chamado?")) return;
         bloquearBotao('btnCancelarChamado-' + CODREG, 'spinnerbtnCancelarChamado-' + CODREG);

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
               desbloquearBotao('btnCancelarChamado-' + CODREG, 'spinnerbtnCancelarChamado-' + CODREG);
               carregarConteudo('chamados.php'); // Recarrega página para atualizar a lista
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

      function responderChamado(codreg, numptc, idecli) {
         // Preenche os campos do modal
         document.getElementById('modalResp-codreg').value = codreg;
         document.getElementById('modalResp-idecli').value = idecli;
         document.getElementById('modalResp-inputProtocolo').value = numptc;
         document.getElementById('modalResp-inputDescricao').value = '';
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

      function enviarRespostaChamadoForm() {
         const codreg = document.getElementById('modalResp-codreg').value;
         const idecli = document.getElementById('modalResp-idecli').value;
         const numptc = document.getElementById('modalResp-inputProtocolo').value;
         const dcrchm = document.getElementById('modalResp-inputDescricao').value;
         const txtchm = document.getElementById('modalResp-textareaResposta').value;
         const enviarEmail = document.getElementById('checkboxEmail').checked;
         const fileInput = document.getElementById('inputArquivo');
         const file = fileInput.files[0];

         bloquearBotao('btnEnviarRespChamado', 'spinnerRespChamado');

         if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
               const base64File = e.target.result;
               enviarRespostaChamadoPayload(codreg, idecli, numptc, dcrchm, txtchm, base64File, enviarEmail);
            };
            reader.readAsDataURL(file);
         } else {
            enviarRespostaChamadoPayload(codreg, idecli, numptc, dcrchm, txtchm, null, enviarEmail);
         }
      }

      function enviarRespostaChamadoPayload(codreg, idecli, numptc, dcrchm, txtchm, base64, enviarEmail) {
         const payload = {
            CODREG: codreg,
            IDECLI: idecli,
            NUMPTC: numptc,
            DCRCHM: dcrchm,
            TXTCHM: txtchm,
            IMG64CHM: base64,
            TOKEN: "<?php echo $_SESSION['token']; ?>",
            ENVIAR_EMAIL: enviarEmail
         };

         fetch('back-end/inserirdados-chmresposta.php', {
               method: 'POST',
               headers: {
                  'Content-Type': 'application/json'
               },
               body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(response => {
               if (response.mensagem) {
                  alert(response.mensagem);
                  const modal = bootstrap.Modal.getInstance(document.getElementById('modalRespChamado'));
                  modal.hide();
                  carregarConteudo('chamados.php');
               } else {
                  alert('Erro inesperado. Nenhuma mensagem retornada.');
               }
            })
            .catch(err => {
               console.error('Erro:', err);
               alert('Erro ao enviar resposta: ' + err.message);
            })
            .finally(() => {
               desbloquearBotao('btnEnviarRespChamado', 'spinnerRespChamado');
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
</body>

</html>