<!DOCTYPE html>
<html lang="pt-br">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Solicitações de Saque</title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
   <!-- meus codigos -->
   <link href="css/saques.css" rel="stylesheet">
   <script src="components/scriptpadrao.js"></script>
</head>

<body class="bg-light">
   <?php
   session_start();
   include_once "../uses/conexao.php";
   include_once "../uses/funcoes.php";
   $codemp = 1;
   if (!isset($_SESSION['admin'])) {
      session_destroy();
      header("Location: ../index.php");
      exit;
   }
   $tempoLimite = retornaTempoLimite($codemp);
   $ultimoAcesso = $_SESSION['admin']['ultimo_acesso'] ?? 0;
   if ((time() - $ultimoAcesso) > $tempoLimite) {
      session_destroy();
      header("Location: ../index.php");
      exit;
   }
   if (!isset($_SESSION['admin']['token']) || $_SESSION['admin']['token'] !== ($_SESSION['token'] ?? '')) {
      session_destroy();
      header("Location: ../index.php");
      exit;
   }
   $credenciais = obterCredenciaisAdminEmpresa($codemp);
   if (!$credenciais || $_SESSION['admin']['usuario'] !== $credenciais['USRADMIN'] || md5($_SESSION['admin']['senha']) !== $credenciais['PWADMIN']) {
      session_destroy();
      header("Location: ../index.php");
      exit;
   }
   $_SESSION['admin']['ultimo_acesso'] = time();

   $saques = obterSaquesAbertos();

   ?>
   <div class="container-fluid">
      <div class="row">
         <!-- Inclusão da NAVBAR -->
         <?php include 'components/navbar.php'; ?>

         <!-- Conteúdo principal da Dashboard -->
         <main class="col-lg-10 offset-lg-2 px-4 mt-4">
            <h3 class="mb-4">Solicitações de Saque</h3>
            <div class="row g-4">
               <div class="col-md-12">
                  <div class="card h-100 text-center">
                     <div class="card-body">


                        <!-- 🔎 Campo de Busca -->
                        <div class="mb-3 d-flex flex-column flex-md-row align-items-md-center gap-2">
                           <label for="buscarCpf" class="form-label mb-0">Buscar Cliente (CPF):</label>
                           <input type="text" class="form-control w-100 w-md-auto" id="buscarCpf" placeholder="Digite o CPF" onblur="formatarCPF(this)">
                           <button class="btn btn-primary" onclick="buscarCliente()">Buscar</button>
                        </div>

                        <!-- 📋 Tabela de Solicitações de Saque -->
                        <div class="table-responsive scroll-area">
                           <table class="table table-hover table-bordered align-middle bg-white">
                              <thead class="table-light">
                                 <tr>
                                    <th>Conta</th>
                                    <th>CPF</th>
                                    <th>Movimentação</th>
                                    <th>Cliente</th>
                                    <th>Tipo de Saque</th>
                                    <th>Valor</th>
                                    <th>Ação</th>
                                 </tr>
                              </thead>
                              <tbody>
                                 <?php if (count($saques) > 0): ?>
                                    <?php foreach ($saques as $saque): ?>
                                       <tr data-cpf="<?= htmlspecialchars($saque['CPFCLI']) ?>">
                                          <td><?= htmlspecialchars($saque['IDECLI']) ?></td>
                                          <td><?= htmlspecialchars($saque['CPFCLI']) ?></td>
                                          <td><?= htmlspecialchars($saque['IDEMOV']) ?></td>
                                          <td><?= htmlspecialchars($saque['NOMCLI']) ?></td>
                                          <td><?= htmlspecialchars($saque['TPOSAQ']) ?></td>
                                          <td>R$ <?= number_format(abs($saque['VLRSAQ']), 2, ',', '.') ?></td>
                                          <td class="d-flex gap-2 justify-content-center">
                                             <button class="btn btn-sm btn-success"
                                                id="btnPagar_<?= $saque['IDEMOV'] ?>"
                                                onclick="pagarSaque('<?= $saque['IDEMOV'] ?>')">
                                                Pagar
                                                <span class="loaderbtn-sm ms-1" id="spinner_<?= $saque['IDEMOV'] ?>" style="display: none;"></span>
                                             </button>
                                             <button class="btn btn-sm btn-danger"
                                                id="btnNegar_<?= $saque['IDEMOV'] ?>"
                                                onclick="negarSaque('<?= $saque['IDEMOV'] ?>')">
                                                Negar
                                                <span class="loaderbtn-sm ms-1" id="spinnerNegar_<?= $saque['IDEMOV'] ?>" style="display: none;"></span>
                                             </button>
                                          </td>
                                       </tr>
                                    <?php endforeach; ?>
                                 <?php else: ?>
                                    <tr>
                                       <td colspan="7" class="text-center">Nenhuma solicitação de saque em aberto.</td>
                                    </tr>
                                 <?php endif; ?>
                              </tbody>
                           </table>
                        </div>
                     </div>
                  </div>
               </div>

               <!-- Modal de Pagamento -->
               <div class="modal fade" id="modalPagarSaque" tabindex="-1" aria-labelledby="modalPagarSaqueLabel" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-lg">
                     <div class="modal-content">
                        <div class="modal-header">
                           <h5 class="modal-title" id="modalPagarSaqueLabel">Processar Pagamento</h5>
                           <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                           <!-- Bloco de informações principais -->
                           <div class="row mb-3">
                              <div class="col-md-4"><strong>Conta:</strong> <span id="modalIDECLI"></span></div>
                              <div class="col-md-4"><strong>Mov:</strong> <span id="modalIDEMOV"></span></div>
                              <div class="col-md-4"><strong>Valor:</strong> R$ <span id="modalVLRSAQ"></span></div>
                           </div>
                           <div class="row mb-3">
                              <div class="col-md-12"><strong>Cliente:</strong> <span id="modalNOMCLI"></span></div>
                           </div>
                           <!-- Upload de comprovante + Cópia via e-mail -->
                           <div class="row mb-4">
                              <div class="col-md-12 mb-2">
                                 <label for="comprovantePdf" class="form-label">Comprovante (PDF):</label>
                                 <input type="file" class="form-control" id="comprovantePdf" accept=".pdf">
                              </div>
                              <div class="col-md-12 form-check">
                                 <input class="form-check-input" type="checkbox" id="chkEmailCopia">
                                 <label class="form-check-label" for="chkEmailCopia">
                                    Cópia via e-Mail
                                 </label>
                              </div>
                           </div>
                           <!-- Botão de ação -->
                           <div class="row">
                              <div class="col-12">
                                 <button class="btn btn-success w-100" id="btnConcluirSaque" onclick="concluirSaque()">
                                    Pagar
                                    <span class="loaderbtn-sm ms-1" id="spinnerConcluirSaque" style="display: none;"></span>
                                 </button>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>

         </main>
      </div>
   </div>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
   <script>
      function pagarSaque(idemov) {
         const btnId = 'btnPagar_' + idemov;
         const spinnerId = 'spinner_' + idemov;
         bloquearBotao(btnId, spinnerId);

         // Obter linha referente
         const linha = document.querySelector(`button[id='${btnId}']`).closest("tr");
         const idecli = linha.children[0].textContent.trim();
         const cpfcli = linha.getAttribute("data-cpf");
         const nomcli = linha.children[3].textContent.trim();
         const vlrsaq = linha.children[5].textContent.trim();

         // Preenche o modal
         document.getElementById('modalIDECLI').textContent = idecli;
         document.getElementById('modalIDEMOV').textContent = idemov;
         document.getElementById('modalVLRSAQ').textContent = vlrsaq;
         document.getElementById('modalNOMCLI').textContent = nomcli;

         // Exibe o modal
         const modal = new bootstrap.Modal(document.getElementById('modalPagarSaque'));
         modal.show();

         desbloquearBotao(btnId, spinnerId);
      }

      function concluirSaque() {
         const btnId = 'btnConcluirSaque';
         const spinnerId = 'spinnerConcluirSaque';

         const fileInput = document.getElementById('comprovantePdf');
         const file = fileInput.files[0];

         if (!file) {
            alert("Por favor, selecione um arquivo PDF como comprovante.");
            return;
         }

         if (file.type !== 'application/pdf') {
            alert("O arquivo selecionado deve ser um PDF.");
            return;
         }

         bloquearBotao(btnId, spinnerId);

         const reader = new FileReader();
         reader.onload = function(e) {
            const base64Pdf = e.target.result; // mantém o cabeçalho intacto
            const IDEMOV = document.getElementById('modalIDEMOV').textContent;
            const IDECLI = document.getElementById('modalIDECLI').textContent;
            const enviaEmail = document.getElementById('chkEmailCopia').checked ? 'S' : 'N';

            const payload = {
               IDEMOV: IDEMOV,
               IDECLI: IDECLI,
               IMG64CPR: base64Pdf,
               STAMAIL: enviaEmail,
               TOKEN: '<?= $_SESSION['admin']['token'] ?>'
            };

            fetch('back-end/atualizardados-saqueapv.php', {
                  method: 'POST',
                  headers: {
                     'Content-Type': 'application/json'
                  },
                  body: JSON.stringify(payload)
               })
               .then(response => response.json())
               .then(data => {
                  if (data.mensagem) {
                     alert(data.mensagem);
                  } else {
                     alert("Resposta inesperada do servidor.");
                  }
                  carregarConteudo('saques.php');
               })
               .catch(err => {
                  console.error("Erro:", err);
                  alert("Erro ao enviar requisição.");
                  desbloquearBotao(btnId, spinnerId);
               });
         };

         reader.readAsDataURL(file); // dispara conversão para base64
      }

      function buscarCliente() {
         const input = document.getElementById("buscarCpf");
         const filtro = input.value.trim();
         const linhas = document.querySelectorAll("tbody tr");

         if (!filtro) {
            linhas.forEach(linha => linha.style.display = "");
            return;
         }

         linhas.forEach(linha => {
            const cpf = linha.children[1].textContent || "";
            linha.style.display = cpf.includes(filtro) ? "" : "none";
         });
      }

      function negarSaque(idemov) {
         const confirmar = confirm(`Tem certeza que deseja negar o saque da movimentação ${idemov}?`);
         if (!confirmar) return;

         const btnId = 'btnNegar_' + idemov;
         const spinnerId = 'spinnerNegar_' + idemov;

         bloquearBotao(btnId, spinnerId);

         // Aqui estamos pegando os dados diretamente da linha da tabela
         const linha = document.querySelector(`button[id='${btnId}']`).closest("tr");
         const idecli = linha.children[0].textContent.trim();

         const payload = {
            IDEMOV: idemov,
            IDECLI: idecli,
            TOKEN: '<?= $_SESSION['admin']['token'] ?>'
         };

         fetch('back-end/atualizardados-saqueneg.php', {
               method: 'POST',
               headers: {
                  'Content-Type': 'application/json'
               },
               body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
               if (data.mensagem) {
                  alert(data.mensagem);
               } else {
                  alert("Resposta inesperada do servidor.");
               }
               carregarConteudo('saques.php');
            })
            .catch(err => {
               console.error("Erro:", err);
               alert("Erro ao enviar requisição.");
               desbloquearBotao(btnId, spinnerId);
            });
      }
   </script>
</body>

</html>