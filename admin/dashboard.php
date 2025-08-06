<!DOCTYPE html>
<html lang="pt-br">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Dashboard Administrativo</title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <!-- meus codigos -->
   <link href="css/dashboard.css" rel="stylesheet">
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
   ?>
   <div class="container-fluid">
      <div class="row">
         <!-- Inclusão da NAVBAR -->
         <?php include 'components/navbar.php'; ?>

         <!-- Conteúdo principal da Dashboard -->
         <main class="col-lg-10 offset-lg-2 px-4 mt-4">
            <h3 class="mb-4">Bem-vindo ao Painel Administrativo</h3>


            <div class="row g-4">
               <div class="col-md-4">
                  <div class="card h-100 text-center">
                     <div class="card-body">
                        <i class="bi bi-chat-dots-fill fs-1 text-primary"></i>
                        <h5 class="mt-3">Gerencie Chamados</h5>
                        <p>Responda e acompanhe os chamados dos clientes com eficiência.</p>
                        <div id="grafico-chamados" class="my-3">
                           <canvas id="chamadosChart" style="height: 120px; width: 100%; max-width: 220px; margin: 0 auto;"></canvas>
                        </div>

                        <div class="scroll-area" id="listaChamadosAbertos">
                           <!-- Itens da lista serão injetados aqui via JS -->
                        </div>
                     </div>
                  </div>
               </div>


               <div class="col-md-4">
                  <div class="card h-100 text-center">
                     <div class="card-body">
                        <i class="bi bi-cash-coin fs-1 text-success"></i>
                        <h5 class="mt-3">Controle de Saques</h5>
                        <p>Avalie solicitações de saque rapidamente e com segurança.</p>

                        <div id="grafico-saques" class="my-3">
                           <canvas id="saquesChart" style="height: 120px; width: 100%; max-width: 220px; margin: 0 auto;"></canvas>
                        </div>

                        <div class="scroll-area" id="listaSaquesAbertos">
                           <!-- Lista de saques abertos será injetada aqui -->
                        </div>
                     </div>
                  </div>
               </div>


               <div class="col-md-4">
                  <div class="card h-100 text-center">
                     <div class="card-body">
                        <i class="bi bi-folder-check fs-1 text-warning"></i>
                        <h5 class="mt-3">Verificar Documentos</h5>
                        <p>Valide documentos dos clientes e agilize a aprovação.</p>

                        <div id="grafico-documentos" class="my-3">
                           <canvas id="documentosChart" style="height: 120px; width: 100%; max-width: 220px; margin: 0 auto;"></canvas>
                        </div>

                        <div class="scroll-area" id="listaDocumentosPendentes">
                           <!-- Lista de documentos pendentes será injetada aqui via JS -->
                        </div>
                     </div>
                  </div>
               </div>


            </div>
         </main>
      </div>
   </div>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
   <!-- Chart.js CDN (coloque no <head> ou antes do script abaixo) -->
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

   <script>
      document.addEventListener("DOMContentLoaded", function() {
         const graficoCanvas = document.getElementById('chamadosChart');
         const listaContainer = document.getElementById('listaChamadosAbertos');
         let chartInstance = null;

         async function carregarDadosChamados() {
            try {
               const response = await fetch('back-end/obterdados-dashboardchm.php');
               const json = await response.json();
               const chamados = json.chamados;

               if (!chamados) return;

               // 🧮 Contagem para o gráfico
               let aberto = 0,
                  fechado = 0,
                  cancelado = 0;
               let chamadosAbertos = [];

               chamados.forEach(chamado => {
                  switch (chamado.STACHM) {
                     case 'A':
                        aberto++;
                        chamadosAbertos.push(chamado);
                        break;
                     case 'F':
                        fechado++;
                        break;
                     case 'C':
                        cancelado++;
                        break;
                  }
               });

               // 🎯 Atualiza gráfico
               atualizarGrafico(aberto, fechado, cancelado);

               // 🧾 Atualiza lista de chamados abertos
               montarListaChamados(chamadosAbertos);

            } catch (error) {
               console.error("Erro ao buscar dados dos chamados:", error);
            }
         }

         function atualizarGrafico(aberto, fechado, cancelado) {
            const data = {
               labels: ['Abertos', 'Fechados', 'Cancelados'],
               datasets: [{
                  data: [aberto, fechado, cancelado],
                  backgroundColor: ['#0d6efd', '#198754', '#dc3545'],
                  hoverOffset: 8
               }]
            };

            const options = {
               responsive: true,
               cutout: '70%',
               plugins: {
                  legend: {
                     display: true,
                     position: 'bottom'
                  }
               }
            };

            if (chartInstance) {
               chartInstance.data = data;
               chartInstance.update();
            } else {
               chartInstance = new Chart(graficoCanvas, {
                  type: 'doughnut',
                  data,
                  options
               });
            }
         }

         function montarListaChamados(lista) {
            listaContainer.innerHTML = '';

            if (lista.length === 0) {
               listaContainer.innerHTML = `<div class="text-muted small">Nenhum chamado aberto encontrado.</div>`;
               return;
            }

            lista.forEach(ch => {
               const item = document.createElement('div');
               item.className = 'list-group-item d-flex flex-column align-items-start';

               item.innerHTML = `
        <div class="w-100 d-flex justify-content-between">
          <span class="fw-bold text-primary">#${ch.NUMPTC}</span>
          <span class="badge badge-aberto">Aberto</span>
        </div>
        <div class="small text-muted">${ch.NOMCLI} - ${new Date(ch.DTAINS).toLocaleString()}</div>
        <div class="text-dark">${ch.DCRCHM}</div>
      `;
               listaContainer.appendChild(item);
            });
         }

         // 🚀 Primeira carga + atualização a cada 30 segundos
         carregarDadosChamados();
         setInterval(carregarDadosChamados, 30000);
      });

      document.addEventListener("DOMContentLoaded", function() {
         const saquesCanvas = document.getElementById('saquesChart');
         const listaSaquesContainer = document.getElementById('listaSaquesAbertos');
         let saquesChartInstance = null;

         async function carregarDadosSaques() {
            try {
               const response = await fetch('back-end/obterdados-dashboardsaq.php');
               const json = await response.json();
               const saques = json.saques;

               if (!saques) return;

               let aberto = 0,
                  fechado = 0,
                  cancelado = 0;
               let saquesAbertos = [];

               saques.forEach(saque => {
                  switch (saque.STASAQ) {
                     case 'A':
                        aberto++;
                        saquesAbertos.push(saque);
                        break;
                     case 'F':
                        fechado++;
                        break;
                     case 'C':
                        cancelado++;
                        break;
                  }
               });

               atualizarGraficoSaques(aberto, fechado, cancelado);
               montarListaSaques(saquesAbertos);

            } catch (error) {
               console.error("Erro ao buscar dados dos saques:", error);
            }
         }

         function atualizarGraficoSaques(aberto, fechado, cancelado) {
            const data = {
               labels: ['Abertos', 'Finalizados', 'Cancelados'],
               datasets: [{
                  data: [aberto, fechado, cancelado],
                  backgroundColor: ['#0d6efd', '#198754', '#dc3545'],
                  hoverOffset: 8
               }]
            };

            const options = {
               responsive: true,
               cutout: '70%',
               plugins: {
                  legend: {
                     display: true,
                     position: 'bottom'
                  }
               }
            };

            if (saquesChartInstance) {
               saquesChartInstance.data = data;
               saquesChartInstance.update();
            } else {
               saquesChartInstance = new Chart(saquesCanvas, {
                  type: 'doughnut',
                  data,
                  options
               });
            }
         }

         function montarListaSaques(lista) {
            listaSaquesContainer.innerHTML = '';

            if (lista.length === 0) {
               listaSaquesContainer.innerHTML = `<div class="text-muted small">Nenhum saque em aberto.</div>`;
               return;
            }

            lista.forEach(saq => {
               const item = document.createElement('div');
               item.className = 'list-group-item d-flex flex-column align-items-start';

               item.innerHTML = `
            <div class="w-100 d-flex justify-content-between">
               <span class="fw-bold text-success">#${saq.IDEMOV}</span>
               <span class="badge badge-aberto">Aberto</span>
            </div>
            <div class="small text-muted">${saq.NOMCLI} - ${saq.TPOSAQ} - R$ ${parseFloat(saq.VLRSAQ).toFixed(2)}</div>
         `;
               listaSaquesContainer.appendChild(item);
            });
         }

         // 🚀 Primeira carga + atualização a cada 30s
         carregarDadosSaques();
         setInterval(carregarDadosSaques, 30000);
      });

      document.addEventListener("DOMContentLoaded", function() {
         const documentosCanvas = document.getElementById('documentosChart');
         const listaDocumentosContainer = document.getElementById('listaDocumentosPendentes');
         let documentosChartInstance = null;

         async function carregarDadosDocumentos() {
            try {
               const response = await fetch('back-end/obterdados-dashboarddocs.php');
               const json = await response.json();
               const documentos = json.documentos;

               if (!documentos) return;

               let aguardando = 0,
                  verificado = 0;
               let docsPendentes = [];

               documentos.forEach(doc => {
                  if (doc.STAAPV === 'A' || doc.STAAPV === 'N') {
                     aguardando++;
                     docsPendentes.push(doc);
                  } else if (doc.STAAPV === 'V') {
                     verificado++;
                  }
               });

               atualizarGraficoDocumentos(aguardando, verificado);
               montarListaDocumentos(docsPendentes);

            } catch (error) {
               console.error("Erro ao buscar dados dos documentos:", error);
            }
         }

         function atualizarGraficoDocumentos(aguardando, verificado) {
            const data = {
               labels: ['Aguardando', 'Verificado'],
               datasets: [{
                  data: [aguardando, verificado],
                  backgroundColor: ['#ffc107', '#198754'],
                  hoverOffset: 8
               }]
            };

            const options = {
               responsive: true,
               cutout: '70%',
               plugins: {
                  legend: {
                     display: true,
                     position: 'bottom'
                  }
               }
            };

            if (documentosChartInstance) {
               documentosChartInstance.data = data;
               documentosChartInstance.update();
            } else {
               documentosChartInstance = new Chart(documentosCanvas, {
                  type: 'doughnut',
                  data,
                  options
               });
            }
         }

         function montarListaDocumentos(lista) {
            listaDocumentosContainer.innerHTML = '';

            if (lista.length === 0) {
               listaDocumentosContainer.innerHTML = `<div class="text-muted small">Nenhum documento pendente.</div>`;
               return;
            }

            lista.forEach(doc => {
               const item = document.createElement('div');
               item.className = 'list-group-item d-flex flex-column align-items-start';

               // Escolhe ícones de acordo com tipo
               const iconeDoc = tipoIcone(doc.IMG64DOC);
               const iconeComp = tipoIcone(doc.IMG64CPREND);

               item.innerHTML = `
         <div class="w-100 d-flex justify-content-between">
            <span class="fw-bold text-warning">${doc.NOMCLI}</span>
            <span class="badge bg-warning text-dark">Aguardando</span>
         </div>
         <div class="small text-muted">${doc.TPODOC} - Doc: ${iconeDoc} / Comp: ${iconeComp}</div>
      `;

               listaDocumentosContainer.appendChild(item);
            });
         }

         // Função auxiliar para retornar o ícone correto
         function tipoIcone(tipo) {
            switch (tipo) {
               case 'IMG':
                  return `<i class="bi bi-image text-secondary"></i>`;
               case 'PDF':
                  return `<i class="bi bi-file-earmark-pdf text-danger"></i>`;
               case 'NADA':
                  return `<i class="bi bi-file-earmark-excel text-muted"></i>`;
               default:
                  return `<i class="bi bi-question-circle text-warning"></i>`;
            }
         }

         // 🚀 Primeira carga + atualização a cada 30 segundos
         carregarDadosDocumentos();
         setInterval(carregarDadosDocumentos, 30000);
      });
   </script>


</body>

</html>