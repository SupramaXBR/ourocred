<!DOCTYPE html>
<html lang="pt-br">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Relatórios</title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
   <link href="css/relatorios.css" rel="stylesheet">
   <script src="components/scriptpadrao.js"></script>
   <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
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
         <?php include 'components/navbar.php'; ?>

         <main class="col-lg-10 offset-lg-2 px-4 mt-4">
            <h3 class="mb-4">Relatórios Disponíveis</h3>
            <div class="row g-4">
               <!-- Rentabilidade por Cliente [Compra/Venda] -->
               <div class="col-md-6">
                  <div class="card h-100 text-start">
                     <div class="card-body">
                        <h5 class="mb-4 fw-bold text-primary d-flex align-items-center gap-2">
                           <i class="bi bi-graph-up-arrow fs-5"></i> Rentabilidade por Cliente [Compra/Venda]
                        </h5>

                        <!-- CPF -->
                        <div class="mb-3">
                           <div class="input-group">
                              <span class="input-group-text">CPF</span>
                              <input type="text" id="inputCpf" class="form-control" placeholder="Digite o CPF"
                                 onblur="formatarCPF(this)">
                           </div>
                        </div>

                        <!-- Período -->
                        <div class="row mb-3">
                           <div class="col-md-6">
                              <div class="input-group">
                                 <span class="input-group-text">De</span>
                                 <input type="date" id="inputDataInicio" class="form-control" value="2025-01-01">
                              </div>
                           </div>
                           <div class="col-md-6">
                              <div class="input-group">
                                 <span class="input-group-text">Até</span>
                                 <input type="date" id="inputDataFim" class="form-control" value="2049-12-31">
                              </div>
                           </div>
                        </div>

                        <!-- Botão -->
                        <div class="text-end">
                           <button id="btnBuscarRelatorio" class="btn btn-primary position-relative w-100" onclick="mostrarRelatorioRentabilidade()">
                              <span id="spinnerRelatorio" class="loaderbtn-sm me-2 d-none"></span>
                              <i class="bi bi-search"></i> Buscar Rentabilidade
                           </button>
                        </div>

                        <!-- Observação -->
                        <div class="text-muted small mt-2">
                           Deixe o CPF em branco para exibir a rentabilidade de todos os clientes.
                        </div>
                     </div>
                  </div>
               </div>

               <!-- Rentabilidade por Cliente [Entrada/Saida] -->
               <div class="col-md-6">
                  <div class="card h-100 text-start">
                     <div class="card-body">
                        <h5 class="mb-4 fw-bold text-primary d-flex align-items-center gap-2">
                           <i class="bi bi-graph-up-arrow fs-5"></i> Rentabilidade por Cliente [Entrada/Saida]
                        </h5>

                        <!-- CPF -->
                        <div class="mb-3">
                           <div class="input-group">
                              <span class="input-group-text">CPF</span>
                              <input type="text" id="inputCpf" class="form-control" placeholder="Digite o CPF"
                                 onblur="formatarCPF(this)">
                           </div>
                        </div>

                        <!-- Período -->
                        <div class="row mb-3">
                           <div class="col-md-6">
                              <div class="input-group">
                                 <span class="input-group-text">De</span>
                                 <input type="date" id="inputDataInicio" class="form-control" value="2025-01-01">
                              </div>
                           </div>
                           <div class="col-md-6">
                              <div class="input-group">
                                 <span class="input-group-text">Até</span>
                                 <input type="date" id="inputDataFim" class="form-control" value="2049-12-31">
                              </div>
                           </div>
                        </div>

                        <!-- Botão -->
                        <div class="text-end">
                           <button id="btnBuscarRelatorioCapital" class="btn btn-primary position-relative w-100" onclick="mostrarRelatorioRentabilidadeCapital()">
                              <span id="spinnerRelatorioCapital" class="loaderbtn-sm me-2 d-none"></span>
                              <i class="bi bi-search"></i> Buscar Rentabilidade
                           </button>
                        </div>

                        <!-- Observação -->
                        <div class="text-muted small mt-2">
                           Deixe o CPF em branco para exibir a rentabilidade de todos os clientes.
                        </div>
                     </div>
                  </div>
               </div>


            </div>
         </main>
         <!-- Modais -->
         <?php include 'components/modais-relatorios.php'; ?>
      </div>
   </div>

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
   <!-- Script específico para relatório de rentabilidade -->
   <script src="components/js-relatorios/rentabilidade.js"></script>
   <script src="components/js-relatorios/rentabilidadecapital.js"></script>

   <script>
      // Formata o CPF no formato ###.###.###-##
      function formatarCPF(campo) {
         let valor = campo.value.replace(/\D/g, '');
         if (valor === '') return;

         if (valor.length === 11) {
            campo.value = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
         }
      }

      // Formata data/hora de YYYY-MM-DD HH:MM para DD/MM/YYYY HH:MM
      function formatarDataBR(dataIso) {
         const data = new Date(dataIso);
         const dia = String(data.getDate()).padStart(2, '0');
         const mes = String(data.getMonth() + 1).padStart(2, '0');
         const ano = data.getFullYear();
         const hora = String(data.getHours()).padStart(2, '0');
         const minuto = String(data.getMinutes()).padStart(2, '0');
         return `${dia}/${mes}/${ano} ${hora}:${minuto}`;
      }
   </script>
</body>

</html>