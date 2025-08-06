<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Clientes </title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
   <!-- meus codigos -->
   <link href="css/clientes.css" rel="stylesheet">
   <script src="components/scriptpadrao.js"></script>
</head>

<script>
   let vsToken = "<?php echo $_SESSION['admin']['token']; ?>";
</script>

<body class="bg-light">
   <?php

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

   $clientes = obterListaSimplesClientesStatus();

   ?>
   <div class="container-fluid">
      <div class="row">
         <!-- Inclusão da NAVBAR -->
         <?php include 'components/navbar.php'; ?>

         <!-- Conteúdo principal da Dashboard -->
         <main class="col-lg-10 offset-lg-2 px-4 mt-4">
            <h3 class="mb-4">Lista de Clientes</h3>

            <!-- 🔎 Campo de Busca -->
            <div class="mb-3 d-flex flex-column flex-md-row align-items-md-center gap-2">
               <label for="buscarCpf" class="form-label mb-0">Buscar Cliente (CPF):</label>
               <input type="text" class="form-control w-100 w-md-auto" id="buscarCpf" placeholder="Digite o CPF" onblur="formatarCPF(this)">
               <button class="btn btn-primary" onclick="buscarCliente()">Buscar</button>
            </div>

            <!-- 🗂️ Card com Relatório -->
            <div class="card">
               <div class="card-body p-3">
                  <div class="table-responsive">
                     <table class="table table-bordered table-hover align-middle text-center">
                        <thead class="table-light">
                           <tr>
                              <th>Conta</th>
                              <th>CPF</th>
                              <th>Nome</th>
                              <th>Conta Ativa</th>
                              <th>e-Mail Verificado</th>
                              <th>Documentos</th>
                              <th>Ação</th>
                           </tr>
                        </thead>
                        <tbody>
                           <?php foreach ($clientes as $cliente): ?>
                              <tr>
                                 <td><?= htmlspecialchars($cliente['IDECLI']) ?></td>
                                 <td><?= htmlspecialchars($cliente['CPFCLI']) ?></td>
                                 <td><?= htmlspecialchars($cliente['NOMCLI']) ?></td>
                                 <td><?= $cliente['STACTAATV'] === 'S' ? 'Sim' : 'Não' ?></td>
                                 <td><?= $cliente['STACMFEML'] === 'S' ? 'Sim' : 'Não' ?></td>
                                 <td>
                                    <?php
                                    echo match ($cliente['STAAPV']) {
                                       'A' => 'Aguardando',
                                       'V' => 'Verificado',
                                       default => 'Indefinido'
                                    };
                                    ?>
                                 </td>
                                 <td>
                                    <button class="btn btn-sm btn-primary" id="btnDetalhes_<?= $cliente['IDECLI'] ?>"
                                       onclick="detalhesCliente('<?= $cliente['IDECLI'] ?>')">
                                       Detalhes
                                       <span class="loaderbtn-sm ms-1" id="spinner_<?= $cliente['IDECLI'] ?>" style="display: none;"></span>
                                    </button>
                                 </td>
                              </tr>
                           <?php endforeach; ?>
                        </tbody>
                     </table>
                  </div>
               </div>
            </div>
         </main>
         <!-- Modal Detalhes Cliente -->
         <div class="modal fade" id="modalDetalhesCliente" tabindex="-1" aria-labelledby="modalDetalhesClienteLabel" aria-hidden="true" style="display: none;">
            <div class="modal-dialog modal-lg modal-dialog-centered">
               <div class="modal-content">
                  <div class="modal-header">
                     <h5 class="modal-title" id="modalDetalhesClienteLabel">Detalhes do Cliente</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                  </div>
                  <div class="modal-body">
                     <div class="row align-items-center">
                        <!-- Coluna Esquerda: Imagem -->
                        <div class="col-md-3 text-center mb-3 mb-md-0">
                           <img id="imgPerfilCliente" src="" class="foto-perfil-cliente border border-secondary" alt="Imagem de Perfil">
                        </div>

                        <!-- Coluna Central: Info Cliente -->
                        <div class="col-md-6">
                           <h5 id="nomeCliente"></h5>
                           <p class="mb-1"><strong>Conta:</strong> <span id="contaCliente"></span></p>
                           <p class="mb-1"><strong>CPF:</strong> <span id="cpfCliente"></span></p>
                           <p class="mb-1"><strong>Cliente Desde:</strong> <span id="clienteDesde"></span></p>
                           <p class="mb-1"><strong>Docs e Comprovantes:</strong></p>
                           <div class="d-flex gap-3 mt-2" id="iconesDocsCliente"></div>
                        </div>

                        <!-- Coluna Direita: Botões (ATUALIZADO) -->
                        <div class="col-md-3 text-center d-flex flex-column gap-2" id="botoesClienteWrapper">
                           <button class="btn btn-primary btn-sm" id="btnEnderecoCliente" onclick="abrirModalEnderecoCliente(JSON.parse(sessionStorage.getItem('clienteData')))">Endereço</button>
                           <button class="btn btn-primary btn-sm" id="btnContaCliente" onclick="abrirModalContaCliente(JSON.parse(sessionStorage.getItem('clienteData')))">Conta Bancária</button>
                           <button class="btn btn-primary btn-sm" id="btnDadosPessoaisCliente" onclick="abrirModalDadosPessoais(JSON.parse(sessionStorage.getItem('clienteData')))">Dados Pessoais</button>
                           <button class="btn btn-primary btn-sm" id="btnHistoricoCliente" onclick="exibirHistoricoTransacoes(document.getElementById('contaCliente').innerText)">Histórico de Transações</button>

                           <!-- Aqui vai ser injetado dinamicamente o botão de ativar/desativar -->
                           <div id="btnToggleContaWrapper"></div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>

         <!-- Modal Histórico de Transações -->
         <div class="modal fade" id="modalHistoricoTransacoes" tabindex="-1" aria-labelledby="modalHistoricoTransacoesLabel" aria-hidden="true" style="display: none;">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
               <div class="modal-content">
                  <div class="modal-header">
                     <h5 class="modal-title" id="modalHistoricoTransacoesLabel">Histórico de Transações</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                  </div>
                  <div class="modal-body">
                     <div class="table-responsive">
                        <table class="table table-bordered table-sm text-center align-middle" id="tabelaTransacoesCliente">
                           <thead class="table-light">
                              <tr>
                                 <th>Data/Hora</th>
                                 <th>Descrição</th>
                                 <th>Valor (g)</th>
                                 <th>Saldo R$</th>
                                 <th>Simple</th>
                                 <th>Classic</th>
                                 <th>Standard</th>
                                 <th>Premium</th>
                              </tr>
                           </thead>
                           <tbody id="transacoesCliente"></tbody>
                           <tfoot class="fw-bold bg-white" id="totaisTransacoes"></tfoot>
                        </table>
                     </div>
                  </div>
               </div>
            </div>
         </div>

         <!-- Modal Dados Pessoais -->
         <div class="modal fade" id="modalDadosPessoaisCliente" tabindex="-1" aria-labelledby="modalDadosPessoaisLabel" aria-hidden="true" style="display: none;">
            <div class="modal-dialog modal-lg">
               <div class="modal-content">
                  <div class="modal-header">
                     <h5 class="modal-title" id="modalDadosPessoaisLabel">Dados Pessoais do Cliente</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                  </div>
                  <div class="modal-body">
                     <form class="row g-3">
                        <div class="col-md-6">
                           <label for="inputNomeCliente" class="form-label">Nome Completo</label>
                           <input type="text" class="form-control" id="inputNomeCliente" readonly>
                        </div>
                        <div class="col-md-6">
                           <label for="inputMaeCliente" class="form-label">Nome da Mãe</label>
                           <input type="text" class="form-control" id="inputMaeCliente" readonly>
                        </div>

                        <div class="col-md-4">
                           <label for="inputCPFCliente" class="form-label">CPF</label>
                           <input type="text" class="form-control" id="inputCPFCliente" readonly>
                        </div>
                        <div class="col-md-4">
                           <label for="inputRGCliente" class="form-label">RG</label>
                           <input type="text" class="form-control" id="inputRGCliente" readonly>
                        </div>
                        <div class="col-md-4">
                           <label for="inputTelefoneCliente" class="form-label">Telefone</label>
                           <input type="text" class="form-control" id="inputTelefoneCliente" readonly>
                        </div>

                        <div class="col-12">
                           <label for="inputEmailCliente" class="form-label">E-mail</label>
                           <input type="email" class="form-control" id="inputEmailCliente" readonly>
                        </div>
                     </form>
                  </div>
               </div>
            </div>
         </div>

         <!-- Modal Endereço do Cliente -->
         <div class="modal fade" id="modalEnderecoCliente" tabindex="-1" aria-labelledby="modalEnderecoClienteLabel" aria-hidden="true" style="display: none;">
            <div class="modal-dialog modal-lg">
               <div class="modal-content">
                  <div class="modal-header">
                     <h5 class="modal-title" id="modalEnderecoClienteLabel">Endereço do Cliente</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                  </div>
                  <div class="modal-body">
                     <form class="row g-3">
                        <div class="col-md-4">
                           <label for="inputCEPCliente" class="form-label">CEP</label>
                           <input type="text" class="form-control" id="inputCEPCliente" readonly>
                        </div>
                        <div class="col-md-8">
                           <label for="inputEnderecoCliente" class="form-label">Endereço</label>
                           <input type="text" class="form-control" id="inputEnderecoCliente" readonly>
                        </div>

                        <div class="col-md-4">
                           <label for="inputNumeroCliente" class="form-label">Número</label>
                           <input type="text" class="form-control" id="inputNumeroCliente" readonly>
                        </div>
                        <div class="col-md-8">
                           <label for="inputComplementoCliente" class="form-label">Complemento</label>
                           <input type="text" class="form-control" id="inputComplementoCliente" readonly>
                        </div>

                        <div class="col-md-6">
                           <label for="inputBairroCliente" class="form-label">Bairro</label>
                           <input type="text" class="form-control" id="inputBairroCliente" readonly>
                        </div>
                        <div class="col-md-4">
                           <label for="inputCidadeCliente" class="form-label">Cidade</label>
                           <input type="text" class="form-control" id="inputCidadeCliente" readonly>
                        </div>
                        <div class="col-md-2">
                           <label for="inputEstadoCliente" class="form-label">UF</label>
                           <input type="text" class="form-control" id="inputEstadoCliente" readonly>
                        </div>
                     </form>
                  </div>
               </div>
            </div>
         </div>

         <!-- Modal Conta Bancária do Cliente -->
         <div class="modal fade" id="modalContaCliente" tabindex="-1" aria-labelledby="modalContaClienteLabel" aria-hidden="true" style="display: none;">
            <div class="modal-dialog modal-lg">
               <div class="modal-content">
                  <div class="modal-header">
                     <h5 class="modal-title" id="modalContaClienteLabel">Conta Bancária do Cliente</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                  </div>
                  <div class="modal-body">
                     <form class="row g-3">
                        <div class="col-md-6">
                           <label for="inputNomeTitular" class="form-label">Nome do Titular</label>
                           <input type="text" class="form-control" id="inputNomeTitular" readonly>
                        </div>
                        <div class="col-md-6">
                           <label for="inputCPFTitular" class="form-label">CPF do Titular</label>
                           <input type="text" class="form-control" id="inputCPFTitular" readonly>
                        </div>

                        <div class="col-md-6">
                           <label for="inputBancoCliente" class="form-label">Banco</label>
                           <input type="text" class="form-control" id="inputBancoCliente" readonly>
                        </div>
                        <div class="col-md-3">
                           <label for="inputAgenciaCliente" class="form-label">Agência</label>
                           <input type="text" class="form-control" id="inputAgenciaCliente" readonly>
                        </div>
                        <div class="col-md-3">
                           <label for="inputContaCliente" class="form-label">Conta</label>
                           <input type="text" class="form-control" id="inputContaCliente" readonly>
                        </div>

                        <div class="col-md-6">
                           <label for="inputTipoConta" class="form-label">Tipo de Conta</label>
                           <input type="text" class="form-control" id="inputTipoConta" readonly>
                        </div>

                        <div class="col-md-3">
                           <label for="inputPixAtivo" class="form-label">Aceitou Termo PIX</label>
                           <input type="text" class="form-control" id="inputPixAtivo" readonly>
                        </div>
                        <div class="col-md-3">
                           <label for="inputContaAtiva" class="form-label">Aceitou Termo TED/DOC</label>
                           <input type="text" class="form-control" id="inputContaAtiva" readonly>
                        </div>
                     </form>
                  </div>
               </div>
            </div>
         </div>

         <!-- Modal Visualização de Documento -->
         <div class="modal fade" id="modalVisualizarDocumento" tabindex="-1" aria-labelledby="modalVisualizarDocumentoLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
               <div class="modal-content">
                  <div class="modal-header">
                     <h5 class="modal-title" id="modalVisualizarDocumentoLabel">Visualizar Documento</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                  </div>
                  <div class="modal-body text-center">
                     <img id="imagemDocumento" src="" alt="Documento" class="img-fluid rounded shadow">
                  </div>
               </div>
            </div>
         </div>

      </div>
   </div>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
   <script>
      function detalhesCliente(idecli) {
         const botaoId = `btnDetalhes_${idecli}`;
         const spinnerId = `spinner_${idecli}`;
         bloquearBotao(botaoId, spinnerId);

         fetch("back-end/obterdados-clientes.php", {
               method: "POST",
               headers: {
                  "Content-Type": "application/json"
               },
               body: JSON.stringify({
                  IDECLI: idecli,
                  TOKEN: vsToken
               })
            })
            .then(response => response.json())
            .then(data => {
               desbloquearBotao(botaoId, spinnerId);

               if (data.cliente) {
                  const cliente = data.cliente;
                  sessionStorage.setItem("clienteData", JSON.stringify(cliente));

                  document.getElementById('imgPerfilCliente').src = cliente.IMG64;
                  document.getElementById('nomeCliente').innerText = cliente.NOMCLI;
                  document.getElementById('contaCliente').innerText = cliente.IDECLI;
                  document.getElementById('cpfCliente').innerText = cliente.CPFCLI;
                  document.getElementById('clienteDesde').innerText = cliente.DTAINS;

                  // DOC/IMG Preview
                  const isPdfDoc = verificarPDF(cliente.IMG64DOC);
                  const isPdfComp = verificarPDF(cliente.IMG64CPREND);
                  const icones = `
                              <a href="#" onclick="abrirDocumento('${cliente.IMG64DOC}', 'Documento do Cliente')" title="Ver Documento">
                                 ${isPdfDoc ? '<i class="bi bi-file-earmark-pdf-fill fs-4 text-danger"></i>' : '<i class="bi bi-image-fill fs-4 text-primary"></i>'}
                              </a>
                              <a href="#" onclick="abrirDocumento('${cliente.IMG64CPREND}', 'Comprovante de Endereço')" title="Ver Comprovante">
                                 ${isPdfComp ? '<i class="bi bi-file-earmark-pdf-fill fs-4 text-danger"></i>' : '<i class="bi bi-image-fill fs-4 text-primary"></i>'}
                              </a>`;
                  document.getElementById('iconesDocsCliente').innerHTML = icones;

                  // 🔁 Botão Ativar / Desativar Conta
                  const wrapper = document.getElementById("btnToggleContaWrapper");
                  if (cliente.STACTAATV === "S") {
                     wrapper.innerHTML = `
                                          <button class="btn btn-danger btn-sm w-100" onclick="ctaAtvDstv('${cliente.IDECLI}', 'S')">
                                             Desativar Conta
                                          </button>`;
                  } else {
                     wrapper.innerHTML = `
                                          <button class="btn btn-success btn-sm w-100" onclick="ctaAtvDstv('${cliente.IDECLI}', 'N')">
                                             Ativar Conta
                                          </button>`;
                  }
                  // Mostra o modal
                  const modal = new bootstrap.Modal(document.getElementById('modalDetalhesCliente'));
                  modal.show();
               } else {
                  alert("⚠️ Cliente não encontrado.");
               }
            })
            .catch(err => {
               desbloquearBotao(botaoId, spinnerId);
               console.error(err);
               alert("❌ Erro ao buscar dados do cliente.");
            });
      }

      function verificarPDF(base64String) {
         if (!base64String || typeof base64String !== 'string') return false;

         const base64Data = base64String.includes(',') ? base64String.split(',')[1] : base64String;
         const binary = atob(base64Data.slice(0, 16)); // Limita leitura para segurança/performance

         if (binary.startsWith('%PDF')) return true;
         if (binary.startsWith('\xFF\xD8\xFF')) return false; // JPEG
         if (binary.startsWith('\x89PNG')) return false; // PNG

         return false;
      }

      function exibirHistoricoTransacoes(idecli) {
         const modalDetalhes = bootstrap.Modal.getInstance(document.getElementById('modalDetalhesCliente'));
         if (modalDetalhes) modalDetalhes.hide();

         fetch("back-end/obterdados-movimentacoes.php", {
               method: "POST",
               headers: {
                  "Content-Type": "application/json"
               },
               body: JSON.stringify({
                  IDECLI: idecli,
                  TOKEN: vsToken
               })
            })
            .then(res => res.json())
            .then(data => {
               const corpo = document.getElementById('transacoesCliente');
               const totais = document.getElementById('totaisTransacoes');
               corpo.innerHTML = "";
               totais.innerHTML = "";

               let totalReais = 0,
                  totalSimple = 0,
                  totalClassic = 0,
                  totalStandard = 0,
                  totalPremium = 0;

               data.transacoes.forEach(saldo => {
                  const cor = {
                     'Entrada': 'text-primary',
                     'Compra': 'text-success',
                     'Venda': 'text-danger',
                     'Saida': 'text-warning',
                     'Ajuste': 'text-dark'
                  } [saldo.TPOMOV] || '';

                  corpo.innerHTML += `
                                    <tr class="${cor}">
                                       <td>${saldo.DTAMOV}</td>
                                       <td>${saldo.DCRMOV}</td>
                                       <td>${parseFloat(saldo.VLRBSECLC).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                                       <td>${parseFloat(saldo.saldo_reais).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                                       <td>${parseFloat(saldo.saldo_simple).toFixed(4)}</td>
                                       <td>${parseFloat(saldo.saldo_classic).toFixed(4)}</td>
                                       <td>${parseFloat(saldo.saldo_standard).toFixed(4)}</td>
                                       <td>${parseFloat(saldo.saldo_premium).toFixed(4)}</td>
                                    </tr>`;

                  totalReais += parseFloat(saldo.saldo_reais);
                  totalSimple += parseFloat(saldo.saldo_simple);
                  totalClassic += parseFloat(saldo.saldo_classic);
                  totalStandard += parseFloat(saldo.saldo_standard);
                  totalPremium += parseFloat(saldo.saldo_premium);
               });

               totais.innerHTML = `
                                    <tr>
                                       <td colspan="3"></td>
                                       <td>${totalReais.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                                       <td>${totalSimple.toFixed(4)}</td>
                                       <td>${totalClassic.toFixed(4)}</td>
                                       <td>${totalStandard.toFixed(4)}</td>
                                       <td>${totalPremium.toFixed(4)}</td>
                                    </tr>`;

               const modal = new bootstrap.Modal(document.getElementById('modalHistoricoTransacoes'));
               modal.show();
            })
            .catch(err => {
               console.error("Erro ao carregar transações:", err);
               alert("Erro ao buscar histórico de transações do cliente.");
            });
      }

      // Aciona o modal Dados Pessoais com preenchimento
      function abrirModalDadosPessoais(cliente) {
         // Oculta o modal anterior
         const modalAnterior = bootstrap.Modal.getInstance(document.getElementById('modalDetalhesCliente'));
         if (modalAnterior) modalAnterior.hide();

         // Preenche os campos do modal de dados pessoais
         document.getElementById('inputNomeCliente').value = cliente.NOMCLI;
         document.getElementById('inputMaeCliente').value = cliente.MAECLI;
         document.getElementById('inputCPFCliente').value = cliente.CPFCLI;
         document.getElementById('inputRGCliente').value = cliente.RGCLI;
         document.getElementById('inputTelefoneCliente').value = cliente.NUMTEL;
         document.getElementById('inputEmailCliente').value = cliente.EMAIL;

         // Exibe o modal
         const modal = new bootstrap.Modal(document.getElementById('modalDadosPessoaisCliente'));
         modal.show();
      }

      function abrirModalEnderecoCliente(cliente) {
         const modalAnterior = bootstrap.Modal.getInstance(document.getElementById('modalDetalhesCliente'));
         if (modalAnterior) modalAnterior.hide();

         document.getElementById('inputCEPCliente').value = cliente.CEPCLI;
         document.getElementById('inputEnderecoCliente').value = cliente.ENDCLI;
         document.getElementById('inputNumeroCliente').value = cliente.NUMCSA;
         document.getElementById('inputComplementoCliente').value = cliente.CPLEND;
         document.getElementById('inputBairroCliente').value = cliente.BAICLI;
         document.getElementById('inputCidadeCliente').value = cliente.NOMMUN;
         document.getElementById('inputEstadoCliente').value = cliente.UFDCLI;

         const modal = new bootstrap.Modal(document.getElementById('modalEnderecoCliente'));
         modal.show();
      }

      function abrirModalContaCliente(cliente) {
         const modalAnterior = bootstrap.Modal.getInstance(document.getElementById('modalDetalhesCliente'));
         if (modalAnterior) modalAnterior.hide();

         document.getElementById('inputNomeTitular').value = cliente.NOMTTL;
         document.getElementById('inputCPFTitular').value = cliente.CPFTTL;
         document.getElementById('inputBancoCliente').value = `[${cliente.CODBCO}] - ${cliente.DCRBCO}`;
         document.getElementById('inputAgenciaCliente').value = cliente.NUMAGC;
         document.getElementById('inputContaCliente').value = cliente.NUMCTA;
         document.getElementById('inputTipoConta').value = cliente.TPOCTA;
         document.getElementById('inputPixAtivo').value = cliente.STAACTPIX === 'S' ? 'Sim' : 'Não';
         document.getElementById('inputContaAtiva').value = cliente.STAACTCTA === 'S' ? 'Sim' : 'Não';

         const modal = new bootstrap.Modal(document.getElementById('modalContaCliente'));
         modal.show();
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


      function formatarCPF(input) {
         let valor = input.value.replace(/\D/g, ''); // remove tudo que não for número

         if (valor.length === 11) {
            input.value = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
         }
      }

      function abrirDocumento(base64, titulo = "Documento") {
         if (verificarPDF(base64)) {
            // Criar link temporário para download
            const a = document.createElement("a");
            a.href = base64;
            a.download = `${titulo}.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
         } else {
            // Exibir imagem no modal
            document.getElementById("imagemDocumento").src = base64;
            document.getElementById("modalVisualizarDocumentoLabel").innerText = titulo;
            const modal = new bootstrap.Modal(document.getElementById("modalVisualizarDocumento"));
            modal.show();
         }
      }

      function ctaAtvDstv(idecli, statusAtual) {
         const acao = statusAtual === 'S' ? 'desativar' : 'ativar';
         if (!confirm(`Deseja realmente ${acao} esta conta?`)) return;

         alert(`🔄 Executaria função para ${acao} conta ${idecli}`);

         // Aqui você pode posteriormente implementar:
         // - fetch para back-end
         // - tratamento da resposta
         // - reabrir modal ou atualizar status
      }

      function ctaAtvDstv(idecli, statusAtual) {
         const novoStatus = statusAtual === 'S' ? 'N' : 'S';
         const acaoTexto = novoStatus === 'S' ? 'ativar' : 'desativar';

         if (!confirm(`Deseja realmente ${acaoTexto.toUpperCase()} esta conta?`)) {
            return;
         }

         const botaoId = 'btnAtvDstvContaCliente';
         const spinnerId = 'spinnerBtnAtvDstvContaCliente';

         bloquearBotao(botaoId, spinnerId);

         fetch("back-end/atualizardados-atvdstvcliente.php", {
               method: "POST",
               headers: {
                  "Content-Type": "application/json"
               },
               body: JSON.stringify({
                  IDECLI: idecli,
                  STACTAATV: novoStatus,
                  TOKEN: vsToken
               })
            })
            .then(res => res.json())
            .then(res => {
               alert(res.mensagem || "Status atualizado com sucesso.");
               carregarConteudo(window.location.pathname); // 🔄 Atualiza a página atual
            })
            .catch(err => {
               console.error("Erro ao atualizar status:", err);
               alert("❌ Falha ao atualizar o status.");
            })
            .finally(() => {
               desbloquearBotao(botaoId, spinnerId);
            });
      }
   </script>

</html>