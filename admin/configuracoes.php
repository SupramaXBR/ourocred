<!DOCTYPE html>
<html lang="pt-br">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Configurações da Empresa</title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <link href="css/configuracoes.css" rel="stylesheet">
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
$dadosEmpresa = obterDadosEmpresa($codemp);
?>

<body class="bg-light">
   <div class="container-fluid">
      <div class="row">
         <!-- Inclusão da NAVBAR -->
         <?php include 'components/navbar.php'; ?>

         <main class="col-lg-10 offset-lg-2 px-4 mt-4">
            <h3 class="mb-4">Configurações da Empresa</h3>

            <ul class="nav nav-tabs mb-3" id="configTabs" role="tablist">
               <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabEmpresa">Empresa</button></li>
               <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabEmails">Emails/Admin</button></li>
               <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabRedes">Redes Sociais</button></li>
               <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabTermos">Termos de Uso</button></li>
               <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabVariaveis">Variáveis de Valores</button></li>
               <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSistema">Sistema</button></li>
            </ul>
            <div class="tab-content bg-white p-3 rounded shadow-sm config-wizard">
               <div class="tab-pane fade show active" id="tabEmpresa">
                  <div class="row g-3">
                     <div class="col-md-6">
                        <label class="form-label">Nome da Empresa</label>
                        <input type="text" name="NOMEMP" id="NOMEMP" class="form-control" value="<?= htmlspecialchars($dadosEmpresa['NOMEMP']) ?>">
                     </div>
                     <div class="col-md-6">
                        <label class="form-label">CNPJ</label>
                        <input type="text" name="CNPJ" id="CNPJ" class="form-control" value="<?= htmlspecialchars($dadosEmpresa['CNPJ']) ?>">
                     </div>
                     <div class="col-12">
                        <label class="form-label">Endereço</label>
                        <input type="text" name="ENDEMP" id="ENDEMP" class="form-control" value="<?= htmlspecialchars($dadosEmpresa['ENDEMP']) ?>">
                     </div>
                  </div>
               </div>

               <div class="tab-pane fade" id="tabEmails">
                  <div class="row g-3">
                     <div class="col-md-6">
                        <label class="form-label">Email No-Reply</label>
                        <input type="email" name="EMAILNOREPLY" id="EMAILNOREPLY" class="form-control" value="<?= htmlspecialchars($dadosEmpresa['EMAILNOREPLY']) ?>">
                     </div>
                     <div class="col-md-6">
                        <label class="form-label">Senha Email No-Reply</label>
                        <input type="password" name="PWNOREPLY" id="PWNOREPLY" class="form-control" value="<?= htmlspecialchars($dadosEmpresa['PWNOREPLY']) ?>">
                     </div>
                     <div class="col-md-6">
                        <label class="form-label">Usuário Admin</label>
                        <input type="text" name="USRADMIN" id="USRADMIN" class="form-control" value="<?= htmlspecialchars($dadosEmpresa['USRADMIN']) ?>">
                     </div>
                     <div class="col-md-6">
                        <label class="form-label">Senha Admin</label>
                        <input type="password" name="PWADMIN" id="PWADMIN" class="form-control" value="<?= htmlspecialchars($dadosEmpresa['PWADMIN']) ?>">
                     </div>
                     <div class="col-md-6">
                        <label class="form-label">Email Contato</label>
                        <input type="email" name="EMAILCONTATO" id="EMAILCONTATO" class="form-control" value="<?= htmlspecialchars($dadosEmpresa['EMAILCONTATO']) ?>">
                     </div>
                  </div>
               </div>

               <div class="tab-pane fade" id="tabRedes">
                  <div class="row g-3">
                     <div class="col-md-6">
                        <label class="form-label">Facebook</label>
                        <input type="url" name="LNKFACEBOOK" id="LNKFACEBOOK" class="form-control" value="<?= htmlspecialchars($dadosEmpresa['LNKFACEBOOK']) ?>">
                     </div>
                     <div class="col-md-6">
                        <label class="form-label">Twitter</label>
                        <input type="url" name="LNKTWITTER" id="LNKTWITTER" class="form-control" value="<?= htmlspecialchars($dadosEmpresa['LNKTWITTER']) ?>">
                     </div>
                     <div class="col-md-6">
                        <label class="form-label">Instagram</label>
                        <input type="url" name="LNKINSTAGRAM" id="LNKINSTAGRAM" class="form-control" value="<?= htmlspecialchars($dadosEmpresa['LNKINSTAGRAM']) ?>">
                     </div>
                     <div class="col-md-6">
                        <label class="form-label">YouTube</label>
                        <input type="url" name="LNKYOUTUBE" id="LNKYOUTUBE" class="form-control" value="<?= htmlspecialchars($dadosEmpresa['LNKYOUTUBE']) ?>">
                     </div>
                     <div class="col-md-6">
                        <label class="form-label">WhatsApp</label>
                        <input type="url" name="LNKWHATSAPP" id="LNKWHATSAPP" class="form-control" value="<?= htmlspecialchars($dadosEmpresa['LNKWHATSAPP']) ?>">
                     </div>
                  </div>
               </div>

               <div class="tab-pane fade" id="tabTermos">
                  <label class="form-label">Termos de Uso [HTML]</label>
                  <textarea name="TXTTERMOS" id="TXTTERMOS" rows="10" class="form-control"><?= htmlspecialchars($dadosEmpresa['TXTTERMOS']) ?></textarea>
               </div>

               <div class="tab-pane fade" id="tabVariaveis">
                  <div class="row g-3">
                     <div class="col-md-4">
                        <label class="form-label">Vlr Dif Grama</label>
                        <input type="number" step="0.01" name="VLRDSCGRMVDA" id="VLRDSCGRMVDA" class="form-control" value="<?= $dadosEmpresa['VLRDSCGRMVDA'] ?>">
                     </div>

                     <?php $planos = ["SIMPLE", "CLASSIC", "STANDARD", "PREMIUM"];
                     foreach ($planos as $p): ?>
                        <div class="col-md-4">
                           <label class="form-label">Dias <?= $p ?></label>
                           <input type="number" name="QTDDIA<?= $p ?>" id="QTDDIA<?= $p ?>" class="form-control" value="<?= $dadosEmpresa['QTDDIA' . $p] ?>">
                        </div>
                        <div class="col-md-4">
                           <label class="form-label">% Desconto <?= $p ?></label>
                           <input type="number" step="0.01" name="PERDSC<?= $p ?>" id="PERDSC<?= $p ?>" class="form-control" value="<?= $dadosEmpresa['PERDSC' . $p] ?>">
                        </div>
                     <?php endforeach; ?>
                  </div>
               </div>

               <div class="tab-pane fade" id="tabSistema">
                  <div class="row g-3">
                     <div class="col-md-4">
                        <label class="form-label">Tempo Limite Sessão (seg)</label>
                        <input type="number" name="TPOLMT_SEG" id="TPOLMT_SEG" class="form-control" value="<?= $dadosEmpresa['TPOLMT_SEG'] ?>">
                     </div>
                     <div class="col-md-4">
                        <label class="form-label">KB Img Perfil</label>
                        <input type="number" name="MAXKBIMGPER" id="MAXKBIMGPER" class="form-control" value="<?= $dadosEmpresa['MAXKBIMGPER'] ?>">
                     </div>
                     <div class="col-md-4">
                        <label class="form-label">KB Img Documento</label>
                        <input type="number" name="MAXKBIMGDOC" id="MAXKBIMGDOC" class="form-control" value="<?= $dadosEmpresa['MAXKBIMGDOC'] ?>">
                     </div>
                     <div class="col-md-4">
                        <label class="form-label">KB Img Endereço</label>
                        <input type="number" name="MAXKBIMGEND" id="MAXKBIMGEND" class="form-control" value="<?= $dadosEmpresa['MAXKBIMGEND'] ?>">
                     </div>
                  </div>
               </div>

            </div>
            <div class="form-group">
               <button type="button" id="btnAtualizarEmpresa" class="btn btn-primary btn-lg w-100" onclick="atualizarEmpresa()">
                  Salvar Configurações
                  <span id="spinnerAtualizarEmpresa" class="loaderbtn-sm" style="display: none;"></span>
               </button>
            </div>
         </main>
      </div>
   </div>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

   <script>
      function atualizarEmpresa() {
         const botaoId = "btnAtualizarEmpresa";
         const spinnerId = "spinnerAtualizarEmpresa";

         // Bloqueia botão e mostra spinner
         bloquearBotao(botaoId, spinnerId);

         // CAPTURA DOS CAMPOS DA MAIN DA configuracao.php
         const vsNomEmp = document.getElementById('NOMEMP').value;
         const vsCnpj = document.getElementById('CNPJ').value;
         const vsEndEmp = document.getElementById('ENDEMP').value;
         const vsEmailContato = document.getElementById('EMAILCONTATO').value;
         const vsEmailNoReply = document.getElementById('EMAILNOREPLY').value;
         const vsPwNoReply = document.getElementById('PWNOREPLY').value;
         const vsUsrAdmin = document.getElementById('USRADMIN').value;
         const vsPwAdmin = document.getElementById('PWADMIN').value;
         const vsFacebook = document.getElementById('LNKFACEBOOK').value;
         const vsTwitter = document.getElementById('LNKTWITTER').value;
         const vsInstagram = document.getElementById('LNKINSTAGRAM').value;
         const vsYoutube = document.getElementById('LNKYOUTUBE').value;
         const vsWhatsapp = document.getElementById('LNKWHATSAPP').value;
         const vsTxtTermos = document.getElementById('TXTTERMOS').value;
         const vsVlrGrama = document.getElementById('VLRDSCGRMVDA').value;

         const vsQtdDiaSimple = document.getElementById('QTDDIASIMPLE').value;
         const vsPerDscSimple = document.getElementById('PERDSCSIMPLE').value;
         const vsQtdDiaClassic = document.getElementById('QTDDIACLASSIC').value;
         const vsPerDscClassic = document.getElementById('PERDSCCLASSIC').value;
         const vsQtdDiaStandard = document.getElementById('QTDDIASTANDARD').value;
         const vsPerDscStandard = document.getElementById('PERDSCSTANDARD').value;
         const vsQtdDiaPremium = document.getElementById('QTDDIAPREMIUM').value;
         const vsPerDscPremium = document.getElementById('PERDSCPREMIUM').value;

         const vsTempoSessao = document.getElementById('TPOLMT_SEG').value;
         const vsKbImgPerfil = document.getElementById('MAXKBIMGPER').value;
         const vsKbImgDoc = document.getElementById('MAXKBIMGDOC').value;
         const vsKbImgEnd = document.getElementById('MAXKBIMGEND').value;

         // OBJETO DE DADOS PARA ENVIO VIA JSON
         const ajaxdados = {
            NOMEMP: vsNomEmp,
            CNPJ: vsCnpj,
            ENDEMP: vsEndEmp,
            EMAILCONTATO: vsEmailContato,
            EMAILNOREPLY: vsEmailNoReply,
            PWNOREPLY: vsPwNoReply,
            USRADMIN: vsUsrAdmin,
            PWADMIN: vsPwAdmin,
            LNKFACEBOOK: vsFacebook,
            LNKTWITTER: vsTwitter,
            LNKINSTAGRAM: vsInstagram,
            LNKYOUTUBE: vsYoutube,
            LNKWHATSAPP: vsWhatsapp,
            TXTTERMOS: vsTxtTermos,
            VLRDSCGRMVDA: vsVlrGrama,

            QTDDIASIMPLE: vsQtdDiaSimple,
            PERDSCSIMPLE: vsPerDscSimple,
            QTDDIACLASSIC: vsQtdDiaClassic,
            PERDSCCLASSIC: vsPerDscClassic,
            QTDDIASTANDARD: vsQtdDiaStandard,
            PERDSCSTANDARD: vsPerDscStandard,
            QTDDIAPREMIUM: vsQtdDiaPremium,
            PERDSCPREMIUM: vsPerDscPremium,

            TPOLMT_SEG: vsTempoSessao,
            MAXKBIMGPER: vsKbImgPerfil,
            MAXKBIMGDOC: vsKbImgDoc,
            MAXKBIMGEND: vsKbImgEnd
         };

         // ENVIO VIA FETCH COM JSON
         fetch('back-end/atualizardados-empresa.php', {
               method: 'POST',
               headers: {
                  'Content-Type': 'application/json'
               },
               body: JSON.stringify(ajaxdados)
            })
            .then(response => response.json())
            .then(data => {
               if (data.status === 'ok') {
                  alert(data.mensagem || 'Dados atualizados com sucesso!');
                  carregarConteudo('configuracoes.php');
               } else {
                  alert(data.mensagem || 'Erro ao atualizar os dados!');
               }
            })
            .catch(error => {
               console.error('Erro:', error);
               alert('Erro inesperado ao enviar os dados. Verifique sua conexão.');
            })
            .finally(() => {
               desbloquearBotao(botaoId, spinnerId);
            });



      }


      function forcarCaixaAlta(idInput) {
         let input = document.getElementById(idInput);
         if (input) {
            input.addEventListener('input', function() {
               this.value = this.value.toUpperCase();
            });
         }
      }

      // Aplicar automaticamente ao campo USRADMIN
      document.addEventListener("DOMContentLoaded", function() {
         forcarCaixaAlta("USRADMIN");
      });
   </script>


</body>

</html>