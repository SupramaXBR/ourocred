<!DOCTYPE html>
<html lang="pt-br">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Verificar Documentos de Clientes</title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
   <!-- meus codigos -->
   <link href="css/verificardocs.css" rel="stylesheet">
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

   $clientes_img = obterClientesComDocumentosAguardandoAprovacao();

   ?>
   <div class="container-fluid">
      <div class="row">
         <!-- Inclusão da NAVBAR -->
         <?php include 'components/navbar.php'; ?>

         <!-- Conteúdo principal da Dashboard -->
         <main class="col-lg-10 offset-lg-2 px-4 mt-4">
            <h3 class="mb-4">Documentos Aguardando Aprovação</h3>

            <?php if (empty($clientes_img)) : ?>
               <div class="alert alert-info">Nenhum cliente com documentos pendentes de aprovação.</div>
            <?php else : ?>
               <div class="card">
                  <div class="card-body">
                     <div class="table-responsive">
                        <table class="table align-middle table-hover">
                           <thead class="table-light">
                              <tr>
                                 <th>Foto</th>
                                 <th>Nome</th>
                                 <th>Conta</th>
                                 <th>Documento</th>
                                 <th>Comp. Endereço</th>
                                 <th>Ação</th>
                              </tr>
                           </thead>
                           <tbody>
                              <?php foreach ($clientes_img as $cliente) : ?>
                                 <tr>
                                    <td>
                                       <img src="<?= $cliente['IMG64'] ?>" class="rounded-circle" width="50" height="50" alt="Foto Perfil">
                                    </td>
                                    <td><?= htmlspecialchars($cliente['NOMCLI']) ?></td>
                                    <td><?= htmlspecialchars($cliente['IDECLI']) ?></td>

                                    <!-- Documento -->
                                    <td>
                                       <?php if (strpos($cliente['IMG64DOC'], 'application/pdf') !== false): ?>
                                          <a href="<?= $cliente['IMG64DOC'] ?>" download>
                                             <i class="bi bi-file-earmark-pdf-fill text-danger fs-4"></i>
                                          </a>
                                       <?php else: ?>
                                          <a href="#" onclick="abrirImagemModal('<?= $cliente['IMG64DOC'] ?>')">
                                             <i class="bi bi-file-image-fill text-primary fs-4"></i>
                                          </a>
                                       <?php endif; ?>
                                    </td>

                                    <!-- Comprovante -->
                                    <td>
                                       <?php if (strpos($cliente['IMG64CPREND'], 'application/pdf') !== false): ?>
                                          <a href="<?= $cliente['IMG64CPREND'] ?>" download>
                                             <i class="bi bi-file-earmark-pdf-fill text-danger fs-4"></i>
                                          </a>
                                       <?php else: ?>
                                          <a href="#" onclick="abrirImagemModal('<?= $cliente['IMG64CPREND'] ?>')">
                                             <i class="bi bi-file-image-fill text-primary fs-4"></i>
                                          </a>
                                       <?php endif; ?>
                                    </td>

                                    <!-- Ações -->
                                    <td>
                                       <div class="d-flex gap-2">
                                          <button class="btn btn-sm btn-success" onclick="aprovarCliente('<?php echo $cliente['IDECLI']; ?>')">Aceitar</button>
                                          <button class="btn btn-sm btn-danger" onclick="rejeitarCliente('<?php echo $cliente['IDECLI']; ?>')">Rejeitar</button>
                                       </div>
                                    </td>
                                 </tr>
                              <?php endforeach; ?>
                           </tbody>
                        </table>
                     </div>
                  </div>
               </div>
            <?php endif; ?>
         </main>


      </div>
   </div>
   <!-- Modal Visualizar Imagem -->
   <div class="modal fade" id="imagemModal" tabindex="-1" aria-labelledby="imagemModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
         <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title" id="imagemModalLabel">Visualizar Imagem</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body text-center">
               <img id="imagemModalPreview" src="" class="img-fluid rounded" style="max-height: 80vh;">
            </div>
         </div>
      </div>
   </div>

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
   <script>
      function abrirImagemModal(base64) {
         const img = document.getElementById('imagemModalPreview');
         img.src = base64;
         const modal = new bootstrap.Modal(document.getElementById('imagemModal'));
         modal.show();
      }

      function aprovarCliente(id) {
         if (confirm("Tem certeza que deseja aprovar este cliente?")) {
            alert("Cliente " + id + " aprovado!");
            // AJAX aqui futuramente
         }
      }

      function rejeitarCliente(id) {
         if (confirm("Deseja rejeitar este cliente?")) {
            alert("Cliente " + id + " rejeitado!");
            // AJAX aqui futuramente
         }
      }

      function aprovarCliente(idecli) {
         if (confirm("Tem certeza que deseja aprovar este cliente?")) {

            const token = "<?= $_SESSION['token'] ?>";

            fetch("back-end/atualizardados-verificardocs.php", {
                  method: "POST",
                  headers: {
                     "Content-Type": "application/json"
                  },
                  body: JSON.stringify({
                     IDECLI: String(idecli), // <== 🔒 Garante que o zero inicial não se perca
                     TOKEN: token
                  })
               })
               .then(response => response.json())
               .then(data => {
                  if (data.mensagem) {
                     alert(data.mensagem);
                     location.reload();
                  } else {
                     alert("Erro inesperado na resposta do servidor.");
                  }
               })
               .catch(error => {
                  console.error("Erro ao aprovar cliente:", error);
                  alert("Erro ao aprovar cliente. Tente novamente.");
               });
         }
      }
   </script>
</body>

</html>