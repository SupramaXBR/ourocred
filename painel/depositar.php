<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
   <meta name="referrer" content="strict-origin-when-cross-origin">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <link rel="stylesheet" href="../uses/estilo.css">
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <script>
      let countdownInterval; // Variável global para armazenar o ID do intervalo do timer
      let statusPollingInterval; // Variável global para armazenar o ID do intervalo do polling de status

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
   <link rel="stylesheet" href="css/depositar.css">
   <style>
      .pix-timer-display {
         font-size: 35px;
         /* Aumenta o tamanho da fonte (equivalente a 24px, dependendo da base) */
         font-weight: bold;
         /* Deixa o texto em negrito */
         color: #007bff;
         /* Uma cor azul padrão, você pode mudar para a cor principal do seu tema ou ouro */
         margin-top: 15px;
         /* Adiciona um pouco mais de espaçamento superior */
         letter-spacing: 1px;
         /* Espaçamento entre as letras para um visual mais "aberto" */
         text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
         /* Sombra suave para dar profundidade */
      }

      /* Opcional: Estilo para quando o timer estiver perto de expirar ou expirado */
      .pix-timer-display.text-danger {
         color: #dc3545;
         /* Vermelho padrão do Bootstrap */
         font-size: 1.7rem;
         /* Um pouco maior para chamar atenção */
         animation: pulse 1s infinite alternate;
         /* Animação sutil de "pulso" */
      }

      @keyframes pulse {
         from {
            transform: scale(1);
         }

         to {
            transform: scale(1.05);
         }
      }
   </style>
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
   $pagina = 'depositar';
   $acesso = obterStatusDeAcesso($cliente['IDECLI'], $pagina);
   if (!$acesso || $acesso['STATUS'] == 'N') {
      $urlVoltar = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
      echo "<script>alert('Acesso bloqueado à página Depositar; Motivo: {$acesso['MOTIVO']}');</script>";
      echo '<meta http-equiv="refresh" content="0;url=' . $urlVoltar . '">';
      exit;
   }
} catch (PDOException $e) {
   session_destroy();
   header("Location: ../index.php");
   exit;
}

$token = $_SESSION['token'];

// Obtém o valor mínimo para depósito (AJUSTADO PARA 1 REAL)
$valorMinimo = number_format(RetornarValorGrama(), 2, '.', '');

// rotina da foto de perfil
if ($cliente['IMG64'] == '') {
   $cliente['IMG64'] = ImagemPadrao(1);
}
?>

<body class="bg-light">
   <div class="container-fluid">
      <div class="row">
         <nav class="col-md-2 d-none d-md-block bg-nav-painel sidebar p-3 min-vh-100 border-end position-fixed">
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

         <nav class="d-md-none fixed-top">
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
         <main class="col-md-10 offset-md-2 d-flex justify-content-center align-items-center vh-100">
            <div class="custom-container bg-white p-4 shadow rounded">

               <div id="primeiroPasso">

                  <h4 class="text-center mb-3 fw-bold">Insira as informações de PIX</h4>

                  <div class="mb-3">
                     <label class="form-label">CPF do Depositante</label>
                     <input type="text" id="cpfDeposit" class="form-control" placeholder="Digite o CPF do Depositante" maxlength="14">
                     <small id="erroCpf" class="cor-texto-vermelho" style="display: none;"> * O CPF informado está vazio ou inválido </small>
                  </div>

                  <div class="row">
                     <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Primeiro Nome</label>
                        <input type="text" id="nomeDeposit" class="form-control" placeholder="Primeiro Nome">
                        <small id="erroNome" class="cor-texto-vermelho" style="display: none;"> * Informe o Nome do Depositante </small>
                     </div>
                     <div class="col-12 col-md-6 mb-3">
                        <label class="form-label">Último Nome</label>
                        <input type="text" id="sobrenomeDeposit" class="form-control" placeholder="Último Nome">
                        <small id="erroSobrenome" class="cor-texto-vermelho" style="display: none;"> * Sobrenome do Depositante </small>
                     </div>
                  </div>

                  <div class="row g-2 mb-3 text-center">
                     <div class="col-4">
                        <button type="button" class="btn btn-cor-ouro w-100" onclick="inserirValorOuro('1,00g')">1,0g</button>
                     </div>
                     <div class="col-4">
                        <button type="button" class="btn btn-cor-ouro w-100" onclick="inserirValorOuro('5,00g')">5,00g</button>
                     </div>
                     <div class="col-4">
                        <button type="button" class="btn btn-cor-ouro w-100" onclick="inserirValorOuro('31,11g')">31,11g</button>
                     </div>
                  </div>

                  <div class="mb-3">
                     <label class="form-label">Valor do deposito (R$)</label>
                     <input type="text" id="depositoInput" class="form-control" placeholder="Digite o valor" onblur="formatarValorBR('depositoInput')">
                  </div>

                  <p class="text-center text-muted small">Depósito Mínimo é R$: <?php echo number_format($valorMinimo, 2) ?></p>

                  <div class="text-center">
                     <button type="button" id="btnDepositar" class="btn btn-primary w-100" onclick="validarFormulario()">
                        DEPOSITAR <span id="spinnerbtnDepositar" class="loaderbtn-sm d-none"></span>
                     </button>
                  </div>
                  <div id="statusMessage" class="mt-3 text-center" style="display: none;"></div>


               </div>

               <div id="segundoPasso" class="d-none">
                  <button type="button" class="btn btn-prev" aria-label="Voltar" onclick="voltarPrimeirPasso()">
                     <i class="bi bi-arrow-left"></i>
                  </button>
                  <h4 class="text-center mb-3 fw-bold">PIX QRCode e Copia e Cola</h4>
                  <p class="text-center text-muted small">Recebedor: OuroCred D.T.V.M </p>

                  <div class="mb-3 text-center" id="divImg64QRcode">
                     <div class="d-flex justify-content-center">
                        <img id="qrcodeImg" class="img-fluid rounded border" width="250" height="250" alt="QR Code">
                     </div>
                     <p class="text-center text-muted small" id="p-valor"> </p>
                     <div class="input-group mt-3">
                        <input type="text" class="form-control" id="pixCopiaCola" readonly>
                        <button class="btn btn-primary" onclick="copiarPix()">Copiar</button>
                     </div>

                     <p id="pixTransactionLinkContainer" class="text-center mt-2 small" style="display: none;">
                        <a id="pixTransactionLink" href="#" target="_blank" rel="noopener noreferrer">Ver transação no banco</a>
                     </p>

                     <p id="pixTimer" class="text-center mt-2 small pix-timer-display"></p>

                  </div>
               </div>

            </div>
         </main>
      </div>
   </div>
   <?php echo footerPainel(); ?>
</body>
<script>
   // PARA PLENO FUNCIONAMENTO FAZ-SE NECESSARIO API DO BANCO;
   function depositar() {
      const vsIdeCli = "<?php echo $cliente['IDECLI']; ?>";
      const vsVlrDpt = document.getElementById("depositoInput").value;
      const vsDcrMov = "Deposito via Pix";
      const vsToken = "<?php echo $token ?>";
      const vsTpoMov = 'Entrada';

      // Bloquear o botão "Confirmar Pagamento" imediatamente (Se ainda existisse ou para casos de re-tentativa)
      const confirmButton = document.querySelector('#segundoPasso .btn-block');
      if (confirmButton) { // Verificação para garantir que o botão não cause erro se não existir
         confirmButton.disabled = true;
         confirmButton.textContent = 'Processando...';
         confirmButton.classList.remove('btn-success'); // Remover classes de sucesso se for re-tentativa
         confirmButton.classList.add('btn-secondary');
      }


      const ajaxdados = {
         IDECLI: vsIdeCli,
         VLRDPT: vsVlrDpt,
         DCRMOV: vsDcrMov,
         TPOMOV: vsTpoMov,
         TOKEN: vsToken
      }

      fetch('back-end/inserirsaldo-rs.php', {
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
            alert(resposta.mensagem || 'Saldo Adicionado com sucesso!');
            carregarConteudo('perfil.php'); // Redireciona para o perfil após o sucesso
         })
         .catch(error => {
            console.error('Erro ao adicionar saldo:', error);
            alert(`Erro ao adicionar saldo: ${error.message}`);
            // Em caso de erro, reabilita o botão e restaura o texto, se necessário.
            // Para o caso atual, o botão foi removido, então este bloco pode ser menos relevante,
            // mas é bom mantê-lo para robustez.
            if (confirmButton) {
               confirmButton.disabled = false;
               confirmButton.textContent = 'Erro ao confirmar!'; // Mensagem de erro para o usuário
               confirmButton.classList.remove('btn-success');
               confirmButton.classList.add('btn-danger');
            }
            if (error.message.includes('Sessão expirada')) {
               window.location.href = '../index.php';
            }
         });
   }


   // funções de front-end
   function verificarValorMinimo() {
      let valorMinimo = <?php echo $valorMinimo; ?>;
      let depositoInput = document.getElementById("depositoInput");

      // Converte o valor digitado para formato numérico
      let deposito = depositoInput.value.replace(/\./g, '').replace(',', '.');
      let valor = parseFloat(deposito);

      // Verifica se o valor é menor que o mínimo
      if (isNaN(valor) || valor < valorMinimo) {
         alert(`Valor mínimo para depósito é de R$ ${formatarValor(valorMinimo)}`);
         depositoInput.value = formatarValor(valorMinimo);
         depositoInput.focus();
      }
   }

   function formatarValor(valor) {
      return parseFloat(valor).toFixed(2).replace('.', ',');
   }

   function irSegundoPasso(base64) {
      const vsVlrDpt = document.getElementById("depositoInput").value;
      const pValor = document.getElementById("p-valor");
      const primeiroPasso = document.getElementById("primeiroPasso"); // Certifique-se que você tem um elemento com id="primeiroPasso"
      const segundoPasso = document.getElementById("segundoPasso");
      const imgQR = document.getElementById("qrcodeImg");

      // A Efí geralmente retorna 'imagemQrcode' como um "data:image/png;base64,...",
      // então podemos usar diretamente. Se por acaso viesse APENAS a base64 pura,
      // o `else if` lidaria com isso.
      if (base64 && typeof base64 === 'string' && base64.startsWith('data:image')) {
         imgQR.src = base64;
      } else if (base64 && typeof base64 === 'string') { // Caso raro onde vem base64 pura sem o prefixo
         imgQR.src = "data:image/png;base64," + base64;
      } else {
         // Fallback para imagem padrão se não houver base64 ou for inválido
         // Adapte esta linha para o seu caminho da imagem padrão
         imgQR.src = "<?php echo isset($ImagemPadrao) ? ImagemPadrao(2) : 'caminho/para/imagem_padrao.png'; ?>";
      }

      // Atualiza o valor exibido no segundo passo (usa retornarValorBR se disponível)
      pValor.innerHTML = "Valor: " + (typeof retornarValorBR === 'function' ? retornarValorBR("depositoInput") : vsVlrDpt);

      // Exibe o segundo passo e oculta o primeiro
      segundoPasso.classList.remove("d-none");
      if (primeiroPasso) { // Garante que a div existe antes de tentar manipular
         primeiroPasso.classList.add("d-none");
      }
      // Opcional: Limpar campos ou estados do primeiro passo, se necessário
   }

   function startTimer(creationTimeStr, expiresInSeconds) {
      // Limpa qualquer timer existente para evitar múltiplos timers rodando
      if (countdownInterval) {
         clearInterval(countdownInterval);
      }

      const timerDisplay = document.getElementById('pixTimer');
      if (!timerDisplay) {
         console.error("Elemento 'pixTimer' não encontrado no DOM.");
         return;
      }

      timerDisplay.classList.remove('text-danger'); // Remove caso tenha sido adicionada antes

      // Converte a string de data de criação para um objeto Date
      const creationTime = new Date(creationTimeStr);
      // Calcula o tempo final de expiração em milissegundos (tempo de criação + segundos de expiração)
      const endTime = new Date(creationTime.getTime() + expiresInSeconds * 1000);

      function updateCountdown() {
         const now = new Date().getTime(); // Tempo atual em milissegundos
         let distance = endTime - now; // Distância restante para o fim, em milissegundos

         // Se a distância for negativa, o timer expirou
         if (distance < 0) {
            clearInterval(countdownInterval); // Para o contador
            timerDisplay.innerHTML = "Pix Expirado!";
            timerDisplay.classList.add('text-danger'); // Adiciona classe para indicar expiração

            // Opcional: Desabilita o botão "Confirmar Pagamento" (se existisse)
            // const confirmButton = document.querySelector('#segundoPasso .btn-block');
            // if (confirmButton) {
            //    confirmButton.disabled = true;
            //    confirmButton.textContent = 'Pix Expirado';
            //    confirmButton.classList.remove('btn-primary');
            //    confirmButton.classList.add('btn-secondary');
            // }
            // Para o polling de status também, se estiver ativo
            if (statusPollingInterval) {
               clearInterval(statusPollingInterval);
            }
            return;
         }

         // Calcula minutos e segundos restantes
         const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
         const seconds = Math.floor((distance % (1000 * 60)) / 1000);

         // Formata para ter sempre dois dígitos (ex: 05:03)
         const formattedMinutes = minutes < 10 ? "0" + minutes : minutes;
         const formattedSeconds = seconds < 10 ? "0" + seconds : seconds;

         timerDisplay.innerHTML = `Tempo restante: ${formattedMinutes}:${formattedSeconds}`;

         // Opcional: Altera a cor do texto quando faltar pouco tempo (ex: 30 segundos)
         if (distance < 30 * 1000) {
            timerDisplay.classList.add('text-danger');
         }
      }

      // Chama a função imediatamente para exibir o tempo inicial e depois a cada segundo
      updateCountdown();
      countdownInterval = setInterval(updateCountdown, 1000);
   }

   function copiarPix() {
      const inputPix = document.getElementById("pixCopiaCola");

      // Seleciona e copia o conteúdo do input
      inputPix.select();
      inputPix.setSelectionRange(0, 99999); // Para mobile
      navigator.clipboard.writeText(inputPix.value).then(() => {
         alert('Pix Copia e Cola copiado para a Área de Transferencia');
      }).catch(err => {
         console.error("Erro ao copiar: ", err);
      });
   }

   function inserirValorOuro(qtdOuro) {
      // Captura o valor retornado pela função PHP
      let valorGrama = <?php echo RetornarValorGrama(); ?>;

      let valorDeposito = 0;

      // Verifica o peso do ouro selecionado e calcula o valor correspondente
      if (qtdOuro === '1,00g') {
         valorDeposito = valorGrama; // 0,01g = 1/100 de 1g
      } else if (qtdOuro === '5,00g') {
         valorDeposito = valorGrama * 5; // 1,00g equivale ao valor da grama
      } else if (qtdOuro === '31,11g') {
         valorDeposito = valorGrama * 31.11; // Multiplica pelo valor correspondente
      }

      // Formata o valor para ###.###,##
      let valorFormatado = valorDeposito.toLocaleString('pt-BR', {
         minimumFractionDigits: 2,
         maximumFractionDigits: 2
      });

      // Insere o valor formatado no input
      document.getElementById("depositoInput").value = valorFormatado;
   }

   document.addEventListener("DOMContentLoaded", function() {
      const cpfInput = document.getElementById("cpfDeposit");

      cpfInput.addEventListener("input", function() {
         formatarCPF(this);
      });

      cpfInput.addEventListener("blur", function() {
         validarCPF(this);
      });
   });


   function validarFormulario() {
      let cpfInput = document.getElementById("cpfDeposit");
      let nomeInput = document.getElementById("nomeDeposit");
      let sobrenomeInput = document.getElementById("sobrenomeDeposit");
      let valorInput = document.getElementById("depositoInput");

      let erroCpf = document.getElementById("erroCpf");
      let erroNome = document.getElementById("erroNome");
      let erroSobrenome = document.getElementById("erroSobrenome");
      let statusMessage = document.getElementById('statusMessage'); // Captura a nova div de mensagens

      let valido = true;

      // Limpa mensagens de status anteriores ao iniciar validação
      statusMessage.style.display = 'none';
      statusMessage.textContent = '';
      statusMessage.style.color = '';

      // Verifica CPF
      if (cpfInput.value.trim() === "" || (typeof validarCPF === 'function' && !validarCPF(cpfInput))) {
         erroCpf.style.display = "block";
         valido = false;
      } else {
         erroCpf.style.display = "none";
      }

      // Verifica Nome
      if (nomeInput.value.trim() === "") {
         erroNome.style.display = "block";
         valido = false;
      } else {
         erroNome.style.display = "none";
      }

      // Verifica Sobrenome
      if (sobrenomeInput.value.trim() === "") {
         erroSobrenome.style.display = "block";
         valido = false;
      } else {
         erroSobrenome.style.display = "none";
      }

      // Verifica se o valor do depósito foi informado e se é maior ou igual ao mínimo
      let valorMinimo = <?php echo $valorMinimo; ?>;
      let deposito = valorInput.value.replace(/\./g, '').replace(',', '.'); // Converte para formato numérico
      let valor = parseFloat(deposito);

      if (isNaN(valor) || valor < valorMinimo) {
         statusMessage.textContent = `Por favor, informe um valor para depósito válido. Mínimo: R$ ${formatarValor(valorMinimo)}`;
         statusMessage.style.color = 'red';
         statusMessage.style.display = 'block';
         valorInput.focus();
         valido = false; // Garante que a validação falha
      } else {
         // Garante que o input tenha o valor formatado corretamente se a validação passar
         valorInput.value = formatarValor(valor);
      }


      // Se tudo estiver válido, faça a requisição para emitir_pix.php
      if (valido) {
         // Agora chamamos a função assíncrona para emitir o Pix
         emitirPix(
            cpfInput.value,
            nomeInput.value + ' ' + sobrenomeInput.value, // Combina nome e sobrenome
            valorInput.value,
            'Depósito na OuroCred' // Descrição do Pix
         );
      }
   }

   // Função para formatar CPF enquanto o usuário digita
   function formatarCPF(campo) {
      let cpf = campo.value.replace(/\D/g, ""); // Remove tudo que não for número

      if (cpf.length > 11) {
         cpf = cpf.slice(0, 11);
      }

      if (cpf.length > 9) {
         campo.value = cpf.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, "$1.$2.$3-$4");
      } else if (cpf.length > 6) {
         campo.value = cpf.replace(/^(\d{3})(\d{3})(\d{0,3})$/, "$1.$2.$3");
      } else if (cpf.length > 3) {
         campo.value = cpf.replace(/^(\d{3})(\d{0,3})$/, "$1.$2");
      } else {
         campo.value = cpf;
      }
   }

   //funcao formatar valor padrao br ###.###.###,##
   function formatarValorBR(id) {
      var input = document.getElementById(id);
      if (!input) return;

      var value = input.value.trim();
      if (value === "") return;

      // Remove pontos usados como separadores de milhar e substitui vírgula por ponto
      var numericValue = value.replace(/\./g, '').replace(',', '.');

      // Converte para número
      var num = parseFloat(numericValue);
      if (isNaN(num)) {
         input.value = "";
         return;
      }

      // Formata o número para o padrão brasileiro com 2 casas decimais
      input.value = num.toLocaleString('pt-BR', {
         minimumFractionDigits: 2,
         maximumFractionDigits: 2
      });
   }

   // Função para validar CPF
   function validarCPF(campo) {
      let cpf = campo.value.replace(/\D/g, ""); // Remove caracteres não numéricos

      if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) {
         return false;
      }

      let soma = 0,
         resto;

      for (let i = 1; i <= 9; i++) soma += parseInt(cpf[i - 1]) * (11 - i);
      resto = (soma * 10) % 11;
      if (resto === 10 || resto === 11) resto = 0;
      if (resto !== parseInt(cpf[9])) return false;

      soma = 0;
      for (let i = 1; i <= 10; i++) soma += parseInt(cpf[i - 1]) * (12 - i);
      resto = (soma * 10) % 11;
      if (resto === 10 || resto === 11) resto = 0;
      if (resto !== parseInt(cpf[10])) return false;

      return true;
   }

   function esconderFooter() {
      const footer = document.getElementById('footerPainel');
      if (footer) {
         footer.style.display = 'none';
      }
   }

   function retornarValorBR(idElemento) {
      const el = document.getElementById(idElemento);
      if (!el) return "";

      let texto = el.innerText || el.value || "";

      // Remove tudo que não for número ou vírgula/ponto
      texto = texto.replace(/[^\d,.-]/g, '');

      // Substitui vírgula decimal por ponto, se necessário
      let valorNumerico = parseFloat(texto.replace(/\./g, '').replace(',', '.'));

      if (isNaN(valorNumerico)) return "";

      // Formata para padrão BR: ###.###.###,##
      return valorNumerico.toLocaleString('pt-BR', {
         minimumFractionDigits: 2,
         maximumFractionDigits: 2
      });
   }

   function voltarPrimeirPasso() {
      const primeiroPasso = document.getElementById("primeiroPasso");
      const segundoPasso = document.getElementById("segundoPasso");

      segundoPasso.classList.add("d-none");
      primeiroPasso.classList.remove("d-none");

      // Limpa os intervalos quando volta para o primeiro passo
      if (countdownInterval) {
         clearInterval(countdownInterval);
      }
      if (statusPollingInterval) {
         clearInterval(statusPollingInterval);
      }

      // Opcional: Reabilita o botão de depósito e limpa mensagens de status
      const btnDepositar = document.getElementById("btnDepositar");
      const spinner = document.getElementById("spinnerbtnDepositar");
      const statusMessage = document.getElementById('statusMessage');

      desbloquearBotao("btnDepositar", "spinnerbtnDepositar");
      statusMessage.style.display = 'none';
      statusMessage.textContent = '';
      statusMessage.style.color = '';

      // Reabilita o botão "Confirmar Pagamento" do segundo passo caso o usuário volte
      // (Embora o botão tenha sido removido, este bloco pode ser útil para depuração ou futura adição)
      const confirmButton = document.querySelector('#segundoPasso .btn-block');
      if (confirmButton) {
         confirmButton.disabled = false;
         confirmButton.textContent = 'Confirmar Pagamento';
         confirmButton.classList.remove('btn-secondary', 'btn-success', 'btn-danger'); // Limpa estados anteriores
         confirmButton.classList.add('btn-primary');
      }
   }

   // Função: Lida com a contagem regressiva do Pix
   function startTimer(creationTimeStr, expiresInSeconds) {
      // Limpa qualquer timer existente para evitar múltiplos timers rodando
      if (countdownInterval) {
         clearInterval(countdownInterval);
      }

      const timerDisplay = document.getElementById('pixTimer');
      if (!timerDisplay) {
         console.error("Elemento 'pixTimer' não encontrado no DOM.");
         return;
      }

      timerDisplay.classList.remove('text-danger'); // Remove caso tenha sido adicionada antes

      // Converte a string de data de criação para um objeto Date
      const creationTime = new Date(creationTimeStr);
      // Calcula o tempo final de expiração em milissegundos (tempo de criação + segundos de expiração)
      const endTime = new Date(creationTime.getTime() + expiresInSeconds * 1000);

      function updateCountdown() {
         const now = new Date().getTime(); // Tempo atual em milissegundos
         let distance = endTime - now; // Distância restante para o fim, em milissegundos

         // Se a distância for negativa, o timer expirou
         if (distance < 0) {
            clearInterval(countdownInterval); // Para o contador
            timerDisplay.innerHTML = "Pix Expirado!";
            timerDisplay.classList.add('text-danger'); // Adiciona classe para indicar expiração

            // Para o polling de status também, se estiver ativo, pois o Pix expirou
            if (statusPollingInterval) {
               clearInterval(statusPollingInterval);
               // Opcional: desabilitar o input pixCopiaCola se o pix expirar
               document.getElementById("pixCopiaCola").value = "Pix Expirado";
               document.getElementById("pixCopiaCola").disabled = true;
            }
            return;
         }

         // Calcula minutos e segundos restantes
         const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
         const seconds = Math.floor((distance % (1000 * 60)) / 1000);

         // Formata para ter sempre dois dígitos (ex: 05:03)
         const formattedMinutes = minutes < 10 ? "0" + minutes : minutes;
         const formattedSeconds = seconds < 10 ? "0" + seconds : seconds;

         timerDisplay.innerHTML = `Tempo restante: ${formattedMinutes}:${formattedSeconds}`;

         // Opcional: Altera a cor do texto quando faltar pouco tempo (ex: 30 segundos)
         if (distance < 30 * 1000) {
            timerDisplay.classList.add('text-danger');
         }
      }

      // Chama a função imediatamente para exibir o tempo inicial e depois a cada segundo
      updateCountdown();
      countdownInterval = setInterval(updateCountdown, 1000);
   }

   // Função para iniciar o polling do status do Pix
   function startPixStatusPolling(txid, creationTimeStr, expiresInSeconds) {
      // Limpa qualquer polling existente para evitar múltiplos rodando
      if (statusPollingInterval) {
         clearInterval(statusPollingInterval);
      }

      const pixTimerDisplay = document.getElementById('pixTimer');
      const pixCopiaColaInput = document.getElementById("pixCopiaCola");


      // Converte a string de data de criação para um objeto Date
      const creationTime = new Date(creationTimeStr);
      // Calcula o tempo final de expiração em milissegundos
      const endTime = new Date(creationTime.getTime() + expiresInSeconds * 1000);

      // Define o intervalo para verificar o status a cada 5 segundos
      statusPollingInterval = setInterval(async () => {
         const now = new Date().getTime();
         let distance = endTime - now;

         if (distance < 0) { // Pix expirou
            clearInterval(statusPollingInterval);
            if (pixTimerDisplay) pixTimerDisplay.innerHTML = "Pix Expirado!";
            pixCopiaColaInput.value = "Pix Expirado";
            pixCopiaColaInput.disabled = true; // Desabilita o campo de copia e cola
            return;
         }

         try {
            const response = await fetch('api-pix/consultar_pix.php', {
               method: 'POST',
               headers: {
                  'Content-Type': 'application/json'
               },
               body: JSON.stringify({
                  txid: txid
               })
            });

            const result = await response.json();

            if (response.ok && result.status === 'CONCLUIDA') {
               clearInterval(statusPollingInterval); // Para o polling
               clearInterval(countdownInterval); // Para o timer de contagem regressiva também
               if (pixTimerDisplay) pixTimerDisplay.innerHTML = "Pix Recebido! ✅";
               alert("Pagamento Pix recebido com sucesso! Seu saldo será atualizado.");

               pixCopiaColaInput.value = "Pagamento Confirmado!";
               pixCopiaColaInput.disabled = true; // Desabilita o campo de copia e cola

               // **Automaticamente executa a função depositar()**
               depositar();

            } else if (result.status === 'EM_PROCESSAMENTO' || result.status === 'ATIVA') {
               // Pix ainda ativo ou em processamento
               // O timer já está sendo atualizado pela função startTimer, então a mensagem aqui pode ser mais genérica
               if (pixTimerDisplay) {
                  // Apenas garante que a mensagem do timer não seja sobrescrita por algo como "Aguardando pagamento..."
                  // se o timer já estiver mostrando a contagem regressiva.
                  // Podemos usar o texto do timer se ele já estiver lá, ou adicionar um "Aguardando..."
                  if (!pixTimerDisplay.innerHTML.includes('Tempo restante')) {
                     pixTimerDisplay.innerHTML = `Aguardando pagamento...`;
                  }
               }
            } else {
               // Outros status ou erros da API
               console.warn("Status Pix desconhecido ou erro na consulta:", result.status, result.error);
            }

         } catch (error) {
            console.error('Erro na requisição de polling:', error);
            // Poderia exibir uma mensagem de erro temporária na interface
            // ou parar o polling se for um erro persistente
         }
      }, 5000); // Verifica a cada 5 segundos
   }


   // Sua função emitirPix() atualizada
   async function emitirPix(cpf, nomeCompleto, valorBruto, descricao) {
      const btnDepositar = document.getElementById("btnDepositar");
      const spinner = document.getElementById("spinnerbtnDepositar");
      const statusMessage = document.getElementById('statusMessage');

      // Formatação do valor para o padrão da API (ex: "10.50")
      // Remove R$, pontos de milhar, e troca vírgula por ponto decimal
      const valorFormatado = valorBruto.replace(/[^0-9,]/g, '').replace(',', '.');

      // Prepara os dados a serem enviados para emitir_pix.php
      const dataToSend = {
         expiracao: 3600, // Ex: Pix válido por 1 hora (3600 segundos)
         cpf: cpf.replace(/[^0-9]/g, ''), // Remove caracteres não numéricos do CPF
         nome_cliente: nomeCompleto,
         valor: valorFormatado,
         descricao: descricao
      };

      // Mostra o spinner e desabilita o botão para feedback visual ao usuário
      spinner.classList.remove('d-none');
      btnDepositar.disabled = true;
      statusMessage.style.display = 'none'; // Esconde mensagens anteriores

      try {
         // Faz a requisição POST para o backend PHP
         const response = await fetch('api-pix/emitir_pix.php', {
            method: 'POST',
            headers: {
               'Content-Type': 'application/json'
            },
            body: JSON.stringify(dataToSend)
         });

         const result = await response.json(); // Tenta parsear a resposta do PHP como JSON

         if (response.ok) { // Verifica se o status HTTP está na faixa 2xx (sucesso)
            console.log('Pix gerado com sucesso!', result);

            // Chama a função irSegundoPasso com a imagem base64 do QR Code
            irSegundoPasso(result.qrcode.imagemQrcode);

            // CORREÇÃO AQUI para o Pix Copia e Cola:
            const pixCopiaColaInput = document.getElementById('pixCopiaCola');
            if (result.pix && result.pix.pixCopiaECola) { // Verifica a existência da chave correta
               pixCopiaColaInput.value = result.pix.pixCopiaECola;
            } else {
               console.warn('Pix Copia e Cola (brcode) não encontrado em result.pix.pixCopiaECola:', result);
               pixCopiaColaInput.value = 'Erro: Código Pix não disponível.';
            }

            // CORREÇÃO AQUI para o Link da Transação:
            const pixTransactionLinkContainer = document.getElementById('pixTransactionLinkContainer');
            const pixTransactionLink = document.getElementById('pixTransactionLink');

            if (result.qrcode && result.qrcode.linkVisualizacao) { // Verifica a existência da chave correta
               pixTransactionLink.href = result.qrcode.linkVisualizacao;
               pixTransactionLinkContainer.style.display = 'block'; // Mostra o contêiner do link
            } else {
               console.warn('Link da transação Pix não encontrado em result.qrcode.linkVisualizacao:', result);
               pixTransactionLinkContainer.style.display = 'none'; // Garante que esteja oculto
            }

            // NOVO: Extrai dados do calendário e txid e inicia o timer e o polling
            if (result.pix && result.pix.calendario && result.pix.txid) {
               const creationTime = result.pix.calendario.criacao;
               const expiresInSeconds = result.pix.calendario.expiracao;
               const txid = result.pix.txid;

               startTimer(creationTime, expiresInSeconds);
               startPixStatusPolling(txid, creationTime, expiresInSeconds); // Inicia o polling de status
            } else {
               console.warn("Dados de calendário ou txid para o timer/polling não encontrados na resposta do Pix.");
               document.getElementById('pixTimer').innerHTML = ""; // Limpa o timer se não houver dados
            }

            // Exibe mensagem de sucesso
            statusMessage.textContent = result.message || "Pix gerado com sucesso! Aguarde a confirmação do pagamento.";
            statusMessage.style.color = 'green';
            statusMessage.style.display = 'block';

         } else {
            // Se o status HTTP não for 2xx, houve um erro (PHP ou API Efí)
            console.error('Erro ao gerar Pix:', result.error || result.message || result);
            statusMessage.textContent = 'Erro: ' + (result.description || result.message || 'Falha desconhecida ao gerar Pix.');
            statusMessage.style.color = 'red';
            statusMessage.style.display = 'block';
         }

      } catch (error) {
         // Erro na requisição Fetch (ex: problema de rede, CORS, erro inesperado)
         console.error('Erro na requisição Fetch:', error);
         statusMessage.textContent = 'Erro de conexão ou inesperado: ' + error.message;
         statusMessage.style.color = 'red';
         statusMessage.style.display = 'block';

      } finally {
         // Sempre desativa o spinner e reabilita o botão, independentemente do sucesso ou falha
         spinner.classList.add('d-none');
         btnDepositar.disabled = false;
      }
   }
</script>
<html>