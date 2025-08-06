<?php
session_start();
include_once "../uses/conexao.php";
include_once "../uses/funcoes.php";
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Configuração de Acesso do Cliente</title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
   <link href="css/clientescfg.css" rel="stylesheet">
   <script src="components/scriptpadrao.js"></script>
</head>

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

   $imgPadrao = ImagemPadrao(1);

   ?>
   <div class="container-fluid">
      <div class="row">
         <!-- Inclusão da NAVBAR -->
         <?php include 'components/navbar.php'; ?>

         <!-- Conteúdo principal da Dashboard -->
         <main class="col-lg-10 offset-lg-2 px-4 mt-4">
            <div class="container mt-4">
               <!-- CARD PRINCIPAL -->
               <div class="card shadow-lg">
                  <div class="card-body">
                     <h5 class="card-title fw-bold mb-3">Consulta de Acessos de Cliente</h5>

                     <!-- Input CPF + Botão Buscar -->
                     <div class="row g-2 align-items-end">
                        <div class="col-md-10">
                           <label for="cpfBusca" class="form-label">CPF do Cliente <small id="alertaCpfInvalido" class="text-danger d-none">CPF inválido! Verifique e tente novamente.</small> </label>
                           <input type="text" class="form-control" id="cpfBusca" placeholder="Digite o CPF" onblur="validarCpf()">

                        </div>
                        <div class="col-md-2 d-grid">
                           <button class="btn btn-primary" onclick="buscarCliente()">Buscar</button>
                        </div>
                     </div>

                     <!-- CARD DE CONFIGURAÇÕES (inicialmente readonly/desabilitado) -->
                     <div class="card mt-4 border border-primary-subtle shadow-sm" id="cardConfiguracoes" style="pointer-events: none; opacity: 0.6;">
                        <div class="card-body">

                           <!-- Sub-Card de Informações do Cliente -->
                           <div class="card mb-4 border-info">
                              <div class="card-body">
                                 <table class="table table-borderless align-middle m-0">
                                    <tr>
                                       <td rowspan="2" style="width: 80px;">
                                          <img src="<?php echo $imgPadrao ?> " alt="Perfil" id="imgPerfil" class="img-lil-circle-perfil" style="width: 60px; height: 60px;">
                                       </td>
                                       <td><strong>CPF:</strong> <span id="cpfCliente"></span></td>
                                       <td><strong>Conta:</strong> <span id="idecliCliente"></span></td>
                                    </tr>
                                    <tr>
                                       <td colspan="2"><strong>Nome do Cliente:</strong> <span id="nomeCliente"></span></td>
                                    </tr>
                                 </table>
                              </div>
                           </div>

                           <!-- Campos de configurações - Base clientes_cfg -->
                           <div class="row g-3">
                              <div class="col-md-4">
                                 <label for="STAACSPERFIL" class="form-label">Acesso Perfil</label>
                                 <select id="STAACSPERFIL" class="form-select" disabled>
                                    <option value="S">Sim</option>
                                    <option value="N">Não</option>
                                 </select>
                                 <textarea class="form-control mt-1" id="MTVNEGACSPERFIL" placeholder="Motivo da negação" rows="2" disabled></textarea>
                              </div>

                              <div class="col-md-4">
                                 <label for="STAACSCOMPRA" class="form-label">Acesso Compras</label>
                                 <select id="STAACSCOMPRA" class="form-select" disabled>
                                    <option value="S">Sim</option>
                                    <option value="N">Não</option>
                                 </select>
                                 <textarea class="form-control mt-1" id="MTVNEGACSCOMPRA" placeholder="Motivo da negação" rows="2" disabled></textarea>
                              </div>

                              <div class="col-md-4">
                                 <label for="STAACSVENDA" class="form-label">Acesso Vendas</label>
                                 <select id="STAACSVENDA" class="form-select" disabled>
                                    <option value="S">Sim</option>
                                    <option value="N">Não</option>
                                 </select>
                                 <textarea class="form-control mt-1" id="MTVNEGACSVENDA" placeholder="Motivo da negação" rows="2" disabled></textarea>
                              </div>

                              <div class="col-md-4">
                                 <label for="STAACSDEPOSITAR" class="form-label">Acesso Depositar</label>
                                 <select id="STAACSDEPOSITAR" class="form-select" disabled>
                                    <option value="S">Sim</option>
                                    <option value="N">Não</option>
                                 </select>
                                 <textarea class="form-control mt-1" id="MTVNEGACSDEPOSITAR" placeholder="Motivo da negação" rows="2" disabled></textarea>
                              </div>

                              <div class="col-md-4">
                                 <label for="STAACSSACAR" class="form-label">Acesso Sacar</label>
                                 <select id="STAACSSACAR" class="form-select" disabled>
                                    <option value="S">Sim</option>
                                    <option value="N">Não</option>
                                 </select>
                                 <textarea class="form-control mt-1" id="MTVNEGACSSACAR" placeholder="Motivo da negação" rows="2" disabled></textarea>
                              </div>

                              <div class="col-md-4">
                                 <label for="STAACSHISTORICO" class="form-label">Acesso Histórico</label>
                                 <select id="STAACSHISTORICO" class="form-select" disabled>
                                    <option value="S">Sim</option>
                                    <option value="N">Não</option>
                                 </select>
                                 <textarea class="form-control mt-1" id="MTVNEGACSHISTORICO" placeholder="Motivo da negação" rows="2" disabled></textarea>
                              </div>

                              <div class="col-md-4">
                                 <label for="STAACSSAC" class="form-label">Acesso SAC</label>
                                 <select id="STAACSSAC" class="form-select" disabled>
                                    <option value="S">Sim</option>
                                    <option value="N">Não</option>
                                 </select>
                                 <textarea class="form-control mt-1" id="MTVNEGACSSAC" placeholder="Motivo da negação" rows="2" disabled></textarea>
                              </div>
                           </div>

                           <!-- Botão Atualizar -->
                           <div class="mt-4 text-center">
                              <button type="button" id="btnAtualizarConfig" class="btn btn-primary w-100" onclick="atualizarConfiguracoes()">
                                 Atualizar Configurações
                                 <span id="spinnerbtnAtualizarConfig" class="loaderbtn-sm" style="display: none;"></span>
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
      function validarCpf() {
         const inputCpf = document.getElementById("cpfBusca");
         const alertaCpf = document.getElementById("alertaCpfInvalido");

         let cpf = inputCpf.value.replace(/[.\-\s]/g, ""); // Remove formatação

         // Se vazio
         if (cpf === "") {
            alertaCpf.classList.remove("d-none");
            inputCpf.value = "";
            return false;
         }

         // Se não tiver 11 dígitos
         if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
            alertaCpf.classList.remove("d-none");
            inputCpf.value = "";
            return false;
         }

         // Validação dos dígitos verificadores
         let soma = 0;
         for (let i = 0; i < 9; i++) soma += parseInt(cpf[i]) * (10 - i);
         let digito1 = (soma * 10) % 11;
         if (digito1 === 10 || digito1 === 11) digito1 = 0;

         if (digito1 !== parseInt(cpf[9])) {
            alertaCpf.classList.remove("d-none");
            inputCpf.value = "";
            return false;
         }

         soma = 0;
         for (let i = 0; i < 10; i++) soma += parseInt(cpf[i]) * (11 - i);
         let digito2 = (soma * 10) % 11;
         if (digito2 === 10 || digito2 === 11) digito2 = 0;

         if (digito2 !== parseInt(cpf[10])) {
            alertaCpf.classList.remove("d-none");
            inputCpf.value = "";
            return false;
         }

         // CPF válido — formata
         inputCpf.value = cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
         alertaCpf.classList.add("d-none");
         return true;
      }

      function buscarCliente() {
         const inputCpf = document.getElementById("cpfBusca");
         const alertaCpf = document.getElementById("alertaCpfInvalido");

         // Validação do CPF antes de enviar
         if (!validarCpf()) return;

         const cpf = inputCpf.value;

         const dadosEnvio = {
            cpf: cpf
         };

         fetch('back-end/buscardados-clientescfg.php', {
               method: 'POST',
               headers: {
                  'Content-Type': 'application/json'
               },
               body: JSON.stringify(dadosEnvio)
            })
            .then(async response => {
               const text = await response.text();

               try {
                  const data = JSON.parse(text);

                  // Se o backend enviar erro explicitamente
                  if (typeof data.erro !== 'undefined' && data.erro === true) {
                     alertaCpf.textContent = data.mensagem || "Cliente não encontrado.";
                     alertaCpf.classList.remove("d-none");
                     return;
                  }

                  // Oculta alerta de erro
                  alertaCpf.classList.add("d-none");

                  // Preenche dados do cliente
                  document.getElementById("cpfCliente").textContent = data.cpfcli || "";
                  document.getElementById("idecliCliente").textContent = data.idecli || "";
                  document.getElementById("nomeCliente").textContent = data.nomcli || "";
                  document.getElementById("imgPerfil").src = data.img64 || "";

                  // Preenche campos de configuração
                  const camposCfg = [
                     "STAACSPERFIL", "MTVNEGACSPERFIL",
                     "STAACSCOMPRA", "MTVNEGACSCOMPRA",
                     "STAACSVENDA", "MTVNEGACSVENDA",
                     "STAACSDEPOSITAR", "MTVNEGACSDEPOSITAR",
                     "STAACSSACAR", "MTVNEGACSSACAR",
                     "STAACSHISTORICO", "MTVNEGACSHISTORICO",
                     "STAACSSAC", "MTVNEGACSSAC"
                  ];

                  camposCfg.forEach(id => {
                     const el = document.getElementById(id);
                     if (el) {
                        el.value = data[id] || "";
                        el.removeAttribute("disabled");
                     }
                  });

                  // Ativa o botão de atualizar
                  document.getElementById("btnAtualizarConfig").removeAttribute("disabled");

                  // Ativa o card de configurações
                  const cardConfig = document.getElementById("cardConfiguracoes");
                  cardConfig.style.opacity = "1";
                  cardConfig.style.pointerEvents = "auto";

               } catch (e) {
                  console.error("Erro ao interpretar JSON:", e, text);
                  alertaCpf.textContent = "Erro ao interpretar resposta do servidor.";
                  alertaCpf.classList.remove("d-none");
               }
            })
            .catch(error => {
               console.error("Erro ao buscar cliente:", error);
               alertaCpf.textContent = "Erro ao buscar cliente (conexão ou servidor).";
               alertaCpf.classList.remove("d-none");
            });
      }

      function atualizarConfiguracoes() {
         const botaoId = "btnAtualizarConfig";
         const spinnerId = "spinnerbtnAtualizarConfig";

         bloquearBotao(botaoId, spinnerId);

         // Monta os dados a serem enviados
         const dadosEnvio = {
            idecli: document.getElementById("idecliCliente").textContent.trim(),
            cpfcli: document.getElementById("cpfCliente").textContent.trim(),
            nomcli: document.getElementById("nomeCliente").textContent.trim(),
            STAACSPERFIL: document.getElementById("STAACSPERFIL").value,
            MTVNEGACSPERFIL: document.getElementById("MTVNEGACSPERFIL").value,
            STAACSCOMPRA: document.getElementById("STAACSCOMPRA").value,
            MTVNEGACSCOMPRA: document.getElementById("MTVNEGACSCOMPRA").value,
            STAACSVENDA: document.getElementById("STAACSVENDA").value,
            MTVNEGACSVENDA: document.getElementById("MTVNEGACSVENDA").value,
            STAACSDEPOSITAR: document.getElementById("STAACSDEPOSITAR").value,
            MTVNEGACSDEPOSITAR: document.getElementById("MTVNEGACSDEPOSITAR").value,
            STAACSSACAR: document.getElementById("STAACSSACAR").value,
            MTVNEGACSSACAR: document.getElementById("MTVNEGACSSACAR").value,
            STAACSHISTORICO: document.getElementById("STAACSHISTORICO").value,
            MTVNEGACSHISTORICO: document.getElementById("MTVNEGACSHISTORICO").value,
            STAACSSAC: document.getElementById("STAACSSAC").value,
            MTVNEGACSSAC: document.getElementById("MTVNEGACSSAC").value,
            TOKEN: "<?php echo $_SESSION['admin']['token'] ?? ''; ?>"
         };

         fetch("back-end/atualizardados-clientescfg.php", {
               method: "POST",
               headers: {
                  "Content-Type": "application/json"
               },
               body: JSON.stringify(dadosEnvio)
            })
            .then(response => response.json())
            .then(data => {
               if (data.erro) {
                  alert(data.mensagem || "Erro ao atualizar configurações.");
               } else {
                  alert(data.mensagem || "Configurações atualizadas com sucesso!");
                  carregarConteudo("clientescfg.php");
               }
            })
            .catch(error => {
               console.error("Erro na atualização:", error);
               alert("Erro ao atualizar configurações: " + error.message);
            })
            .finally(() => {
               desbloquearBotao(botaoId, spinnerId);
            });
      }
   </script>
</body>

</html>