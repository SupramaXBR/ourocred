document.getElementById('cpfInput').addEventListener('input', function(e) {
   let input = e.target.value;

   // Remove tudo que não for número
   input = input.replace(/\D/g, '');

   // Formata CPF (###.###.###-##)
   if (input.length <= 11) {
      input = input.replace(/(\d{3})(\d)/, '$1.$2');
      input = input.replace(/(\d{3})(\d)/, '$1.$2');
      input = input.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
   }

   e.target.value = input;

   // Valida CPF quando o formato estiver completo (11 dígitos)
   if (input.length === 14) {
      const cpfValido = validarCPF(input);
      const cpfError = document.getElementById('cpfError');
      if (!cpfValido) {
         cpfError.style.display = 'block'; // Mostra mensagem de erro
      } else {
         cpfError.style.display = 'none'; // Esconde mensagem de erro
      }
   }  
});

// Função para validar CPF
function validarCPF(cpf) {
   cpf = cpf.replace(/\D/g, ''); // Remove traços e pontos

   if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) return false;

   let soma = 0, resto;

   // Valida os 9 primeiros dígitos
   for (let i = 1; i <= 9; i++) soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
      resto = (soma * 10) % 11;
      if ((resto === 10) || (resto === 11)) resto = 0;
      if (resto !== parseInt(cpf.substring(9, 10))) return false;

      soma = 0;
      // Valida os 10 primeiros dígitos
      for (let i = 1; i <= 10; i++) soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
         resto = (soma * 10) % 11;
         if ((resto === 10) || (resto === 11)) resto = 0;
         if (resto !== parseInt(cpf.substring(10, 11))) return false;

   return true;
}

function login(event) {
    // ESSA É A CORREÇÃO PRINCIPAL: Impede o comportamento padrão de submit do formulário
    // Se a função for chamada a partir de um evento (como 'onclick' em um botão de submit),
    // ele impede que a página recarregue ou que o formulário seja enviado tradicionalmente.
    if (event) {
        event.preventDefault();
    }

    const logincpf = document.getElementById("cpfInput").value;
    const loginpw = document.getElementById("password1").value;

    // Opcional: Adicionar uma validação básica de campos vazios antes de enviar a requisição
    if (!logincpf || !loginpw) {
        alert("Por favor, preencha o CPF e a senha.");
        return; // Interrompe a execução se os campos estiverem vazios
    }

    const ajaxdados = {
        cpf: logincpf,
        senha: loginpw
    };

    fetch("ajax/verificar-credenciais.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(ajaxdados)
    })
    .then(response => {
        // Verifica se a resposta HTTP não foi bem-sucedida (status 4xx, 5xx)
        if (!response.ok) {
            // Tenta ler a mensagem de erro do servidor se for um JSON
            return response.json().then(err => {
                throw new Error(err.mensagem || "Erro desconhecido do servidor.");
            });
        }
        // Se a resposta HTTP for OK, retorna o JSON
        return response.json();
    })
    .then(resposta => {
        // Lógica para lidar com a resposta do servidor
        if (resposta.mensagem === "ACEITO") {
            // Cria um formulário oculto para redirecionar via POST
            const form = document.createElement("form");
            form.method = "POST";
            form.action = "painel/index.php";

            const inputCpf = document.createElement("input");
            inputCpf.type = "hidden";
            inputCpf.name = "logincpf";
            inputCpf.value = logincpf;

            const inputSenha = document.createElement("input");
            inputSenha.type = "hidden";
            inputSenha.name = "loginpw";
            inputSenha.value = loginpw;

            form.appendChild(inputCpf);
            form.appendChild(inputSenha);

            document.body.appendChild(form);
            form.submit(); // Envia o formulário e redireciona

        } else if (resposta.mensagem === "SENHA-INCORRETA") {
            alert("A senha informada está incorreta.");
        } else if (resposta.mensagem === "CPF-INEXISTENTE") {
            alert("Usuário não cadastrado, se você é novo por aqui crie sua conta.");
        } else if (resposta.mensagem === "NAO-CONFIRMADA") {
            alert("Conta não confirmada, verifique seu email e tente novamente.");
        } else {
            // Lida com mensagens de resposta inesperadas
            alert("Resposta inesperada do servidor: " + resposta.mensagem);
        }
    })
    .catch(error => {
        // Captura e exibe erros de rede ou erros lançados nos blocos .then()
        console.error("Erro na requisição de login:", error); // Log para depuração
        alert("Erro: " + error.message + ". Por favor, tente novamente.");
    });
}