<?php
// Inclua sua conexão com o banco de dados
require_once '../uses/conexao.php';

// Inicialize variáveis
$ide = isset($_GET['ide']) ? $_GET['ide'] : null;
$hash = isset($_GET['hash']) ? $_GET['hash'] : null;
$clienteValido = false;

if ($ide && $hash) {
    try {
        // Prepare a consulta para verificar se o cliente existe
        $sql = "SELECT md5pw FROM clientes WHERE idecli = :ide";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':ide', $ide, PDO::PARAM_INT);
        $stmt->execute();

        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se o cliente foi encontrado e se o hash confere
        if ($cliente && $cliente['md5pw'] === $hash) {
            $clienteValido = true;
        }
    } catch (PDOException $e) {
        die("Erro ao acessar o banco de dados: " . $e->getMessage());
    }
}

// Caso o cliente não seja válido, redirecione para uma página de erro
if (!$clienteValido) {
    header("Location: erro.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <!-- Meta tags Obrigatórias -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" href="../imagens/favicon.ico" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <title>OuroCred - Mudar Senha</title>
    <style>
      body {
        background-color: #f0f0f0;
      }
      .card {
        border-radius: 10px;
      }
      .card-header {
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .card-header img {
        width: 50px;
        height: 50px;
        margin-right: 10px;
      }
      .card-header h3 {
        margin: 0;
      }
      .loaderbtn {
        width: 24px;
        height: 24px;
        border: 5px solid;
        border-color: #a57c00 transparent;
        border-radius: 50%;
        display: inline-block;
        box-sizing: border-box;
        animation: rotation 1s linear infinite;
      }
      @keyframes rotation {
        0% {
          transform: rotate(0deg);
        }
        100% {
          transform: rotate(360deg);
        }
      }
    </style>
  </head>
  <body>
    <div class="container d-flex justify-content-center align-items-center" style="height: 100vh;">
      <div class="card shadow" style="width: 100%; max-width: 400px;">
        <div class="card-header text-center">
          <img src="../imagens/logo.png" alt="Logo OuroCred">
          <h3 class="d-inline">OuroCred</h3>
        </div>
        <div class="card-body">
          <form id="mudarSenhaForm">
            <!-- Campo oculto com o valor do idecli -->
            <input type="hidden" id="idecli" name="idecli" value="<?php echo htmlspecialchars($ide); ?>" style="display: none;">

            <div class="form-group">
              <label for="novaSenhaInput">Nova senha</label>
              <input type="password" class="form-control" id="novaSenhaInput" placeholder="Digite a nova senha" onblur="validarNovaSenha()">
              <small id="novaSenhaError" class="form-text text-danger" style="display: none;">A senha deve ter pelo menos 10 caracteres, uma letra maiúscula e um símbolo (!@#$%&*).</small>
            </div>
            <div class="form-group">
              <label for="repetirSenhaInput">Repetir Nova senha</label>
              <input type="password" class="form-control" id="repetirSenhaInput" placeholder="Repita a nova senha" onblur="validarSenhaEComparar()">
              <small id="repetirSenhaError" class="form-text text-danger" style="display: none;">As senhas não coincidem.</small>
            </div>
            <button type="button" class="btn btn-primary btn-block" onclick="mudarsenha()">Alterar Senha <span id="spinnerbtn" class="loaderbtn" style="display: none;"></span></button>
            <small id="h3msg" style="display: none;">Senha alterada com Sucesso <a href="../index.php">clique aqui</a> para ir para o site.</small>
          </form>
        </div>
      </div>
    </div>

    <script>
      // Função para validar a complexidade da senha     
      function validarNovaSenha() {
        const senhaInput = document.getElementById('novaSenhaInput');
        const errorSmall = document.getElementById('novaSenhaError');
        const senha = senhaInput.value;
        const regex = /^(?=.*[A-Z])(?=.*[!@#$%&*])(?=.*[a-zA-Z]).{10,}$/;

        if (!regex.test(senha)) {
          errorSmall.style.display = 'block';
          return false;
        } else {
          errorSmall.style.display = 'none';
          return true;
        }
      }

      // Função para comparar as senhas
      function validarSenhaEComparar() {
        const novaSenhaInput = document.getElementById('novaSenhaInput');
        const repetirSenhaInput = document.getElementById('repetirSenhaInput');
        const errorSmall = document.getElementById('repetirSenhaError');
        const senha = novaSenhaInput.value;
        const repetirSenha = repetirSenhaInput.value;

        // Validar a senha antes de comparar
        const isValid = validarNovaSenha();

        if (senha !== repetirSenha) {
          errorSmall.style.display = 'block';
          return false;
        } else {
          errorSmall.style.display = 'none';
          return isValid;
        }
      }

      // Função para alterar a senha
      function mudarsenha() {
        const novaSenhaError = document.getElementById('novaSenhaError');
        const repetirSenhaError = document.getElementById('repetirSenhaError');

        //aparecer spinner
        const spinnerbtn   = document.getElementById('spinnerbtn');
        spinnerbtn.style.display = 'inline-block';


        if (novaSenhaError.style.display === 'block' || repetirSenhaError.style.display === 'block') {
          alert('Corrija os erros indicados antes de prosseguir.');
          return;
        }

        const novaSenha = document.getElementById('novaSenhaInput').value;
        const idecli    = document.getElementById('idecli').value;

        // Exemplo de AJAX (usando jQuery)
        const ajaxdados = {
           idecli: idecli,
           novasenha: novaSenha };
        
           fetch('../ajax/mudar-senha.php', {
                method: 'POST',
                headers: {
                'Content-Type': 'application/json'
                },
                body: JSON.stringify(ajaxdados)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        // Lança o erro com a mensagem recebida
                        throw new Error(err.mensagem || 'Erro desconhecido');
                    });
                }
                spinnerbtn.style.display = 'none';
                return response.json();
            })
            .then(resposta => {
                // Pega a mensagem do JSON e exibe no elemento 'resultado'
                // const mensagem = resposta.mensagem || 'Cadastro Efetuado com sucesso!';
                // document.getElementById('resultado').innerText = mensagem;

               const h3msg = document.getElementById('h3msg');

               h3msg.style.color = "#267D39";
               h3msg.style.display = 'block';
               spinnerbtn.style.display = 'none';

            })
            .catch(error => {
                
                const h3msg = document.getElementById('h3msg');

                h3msg.style.color = "#Ff0000";
                h3msg.innerText =  `Erro: ${error.message}`;
                h3msg.style.display = 'block';
                spinnerbtn.style.display = 'none';
                
            });
      }


    </script>

    <!-- JavaScript (Opcional) -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
  </body>
</html>
