<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <!-- Meta tags Obrigatórias -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" href="../imagens/favicon.ico" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <title>OuroCred - Recuperação de Acesso</title>
    <!-- Estilos personalizados -->
    <style>
      body {
        background-color: #f0f0f0; /* Cinza claro para o fundo da página */
      }
      .card {
        border-radius: 10px; /* Cantos arredondados do card */
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
      #cpfError {
        display: none;
      }
      /* css do spinner */
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
                <img src="../imagens/logo.png" alt="Logo OuroCred"> <!-- Logo -->
                <h3 class="d-inline">OuroCred</h3> <!-- Texto centralizado com logo -->
            </div>
            <div class="card-body">
                <form id="recuperarSenhaForm">
                    <div class="form-group">
                        <label for="cpfInput">CPF</label>
                        <input type="text" class="form-control" id="cpfInput" placeholder="Digite seu CPF" maxlength="14" onblur="formatarCPF()">
                        <small id="cpfError" class="form-text text-danger">CPF Inválido</small>
                    </div>
                    <button type="button" class="btn btn-primary btn-block" onclick="recuperarsenha()"> Recuperar Acesso <span id="spinnerbtn" class="loaderbtn" style="display: none;"></span></button>                    
                    <small id="msgresposta" class="form-text text-danger" style="display: none;">mensagen.</small>
                </form>
                <div id="resultado" class="mt-3"></div> <!-- Div para mostrar resultado -->
            </div>
        </div>
    </div>

    <script>
      function mascararEmail(email) {
          // Separar a parte local (antes do @) e o domínio (depois do @)
          const [parteLocal, dominio] = email.split("@");

          // Determinar o comprimento da parte local
          const comprimento = parteLocal.length;

          // Garantir pelo menos 2 caracteres visíveis no início e no final
          if (comprimento <= 4) {
              // Se for muito curto, exibir apenas o primeiro e o último caracteres
              const inicio = parteLocal.charAt(0);
              const fim = parteLocal.charAt(comprimento - 1);
              return `${inicio}*****${fim}@${dominio}`;
          } else {
              // Mostrar os dois primeiros e dois últimos caracteres e mascarar o meio
              const inicio = parteLocal.slice(0, 2);
              const fim = parteLocal.slice(-2);
              const mascarado = "*".repeat(comprimento - 4);
              return `${inicio}${mascarado}${fim}@${dominio}`;
          }
      }


      // Função para formatar o CPF no formato ###.###.###-##
      function formatarCPF() {
        var cpf = document.getElementById("cpfInput").value;
        var cpfError = document.getElementById("cpfError");

        // Remove caracteres não numéricos
        cpf = cpf.replace(/\D/g, "");
        
        // Verifica se o CPF possui o tamanho correto após a remoção de caracteres não numéricos
        if (cpf.length <= 11) {
          cpf = cpf.replace(/(\d{3})(\d)/, "$1.$2");
          cpf = cpf.replace(/(\d{3})(\d)/, "$1.$2");
          cpf = cpf.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
        }
        
        // Exibe o CPF formatado
        document.getElementById("cpfInput").value = cpf;

        // Valida o CPF
        if (!validarCPF(cpf.replace(/\D/g, ""))) {
          cpfError.style.display = "block"; // Exibe a mensagem de erro
        } else {
          cpfError.style.display = "none"; // Esconde a mensagem de erro
        }
      }

      // Função para validar o CPF (validação simples)
      function validarCPF(cpf) {
        if (cpf.length !== 11) return false;
        
        // Validações adicionais podem ser feitas aqui, como verificar se o CPF não é um número sequencial (ex: 11111111111)
        var soma = 0;
        var resto;

        for (var i = 0; i < 9; i++) {
          soma += parseInt(cpf.charAt(i)) * (10 - i);
        }

        resto = 11 - (soma % 11);
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.charAt(9))) return false;

        soma = 0;
        for (var i = 0; i < 10; i++) {
          soma += parseInt(cpf.charAt(i)) * (11 - i);
        }

        resto = 11 - (soma % 11);
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.charAt(10))) return false;

        return true;
      }

      // Função chamada no onclick do botão "Recuperar Acesso"
      function recuperarsenha() {
        //aparecer spinner
        const spinnerbtn   = document.getElementById('spinnerbtn');
        spinnerbtn.style.display = 'inline-block';

        //capturar elemento <small id="msgresposta">
        const msgresposta = document.getElementById('msgresposta');

        //tratavida do elemento small
        //document.getElementById('msgresposta').classList.remove('text-danger');
        //document.getElementById('msgresposta').classList.add('text-success');

        var cpf = document.getElementById("cpfInput").value;

        // Verifica se o CPF está no formato correto
        if (!cpf.match(/\d{3}\.\d{3}\.\d{3}-\d{2}/)) {
          alert("Por favor, insira um CPF válido.");
          return;
        }

        //parte do ajax que estamos textando

        const ajaxdados = {
           cpfcli: cpf};
       
           fetch('../ajax/recuperar-senha.php', {
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

                //desaparecer spinner
                spinnerbtn.style.display = 'none';

                return response.json();
            })
            .then(resposta => {
                 //Pega a mensagem do JSON e exibe no elemento 'resultado'
                 const staenv = resposta.staenv;
                 const mensagem = resposta.mensagem || 'Cadastro Efetuado com sucesso!';
                 msgresposta.classList.remove('text-danger');
                 msgresposta.classList.add('text-success');
                 if (staenv == 'S'){
                  const email = mascararEmail(mensagem);
                  msgresposta.innerText = `Acesse o e-mail ${email} para continuar com a recuperação do acesso` ;
                 }
                 msgresposta.style.display = 'block';
                //desaparecer spinner
                spinnerbtn.style.display = 'none';

            })
            .catch(error => {

                msgresposta.innerText = mensagem;  `Erro: ${error.message}`;;
                msgresposta.style.display = 'block';
                //desaparecer spinner
                spinnerbtn.style.display = 'none';
            });

      }
    </script>
    <!-- JavaScript (Opcional) -->
    <!-- jQuery primeiro, depois Popper.js, depois Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
  </body>
</html>
