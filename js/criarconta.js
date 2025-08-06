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


function validarCPFCad() {
    const inputCpf = document.getElementById("inputcpf");
    const errorCpfElement = document.getElementById("errorcpf");

    // Remove pontos, traços e espaços, caso o usuário tenha inserido
    let cpf = inputCpf.value.replace(/[.\-\s]/g, "");

    // Função interna para exibir erro e limpar o campo
    const displayError = (message) => {
        errorCpfElement.textContent = message; // Define a mensagem de erro
        errorCpfElement.style.display = "block";
        inputCpf.value = ""; // Limpa o campo
        return false; // Retorna falso para indicar falha na validação
    };

    // 1. Verifica se o CPF está vazio após a limpeza
    if (cpf === "") {
        return displayError("O campo CPF não pode estar vazio.");
    }

    // 2. Verifica se o CPF possui exatamente 11 dígitos
    if (cpf.length !== 11) {
        return displayError("CPF deve ter 11 dígitos.");
    }

    // 3. Impede CPFs com todos os dígitos iguais (frequentemente usados para fraude/teste)
    if (/^(\d)\1{10}$/.test(cpf)) {
        return displayError("CPF inválido: todos os dígitos são iguais.");
    }

    // 4. Validação dos dígitos verificadores (algoritmo padrão do CPF)
    let soma = 0;
    let resto;

    // Calcula o primeiro dígito verificador
    for (let i = 1; i <= 9; i++) {
        soma = soma + parseInt(cpf.substring(i - 1, i)) * (11 - i);
    }
    resto = (soma * 10) % 11;
    if ((resto == 10) || (resto == 11)) {
        resto = 0;
    }
    if (resto != parseInt(cpf.substring(9, 10))) {
        return displayError("CPF inválido.");
    }

    soma = 0;
    // Calcula o segundo dígito verificador
    for (let i = 1; i <= 10; i++) {
        soma = soma + parseInt(cpf.substring(i - 1, i)) * (12 - i);
    }
    resto = (soma * 10) % 11;
    if ((resto == 10) || (resto == 11)) {
        resto = 0;
    }
    if (resto != parseInt(cpf.substring(10, 11))) {
        return displayError("CPF inválido.");
    }

    // Se chegou até aqui, o CPF é válido!
    errorCpfElement.style.display = "none"; // Esconde a mensagem de erro
    // Formata o CPF no padrão XXX.XXX.XXX-XX
    inputCpf.value = cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
    
    return true; // Retorna verdadeiro para indicar sucesso na validação
}

      function criarconta() {

         // Capturar os valores em variaveis JS

         const ajaxcpf = document.getElementById('inputcpf').value;
         const ajaxcod = 1;
         const ajaxid = gerarId(ajaxcpf);
         const ajaxrg = document.getElementById('rginput').value;
         const ajaxnome = document.getElementById('nomeinput').value;
         const ajaxdiansc = document.getElementById('slcDia').value;
         const ajaxmesnsc = document.getElementById('slcMes').value;
         const ajaxanonsc = document.getElementById('slcAno').value;
         const ajaxnommae = document.getElementById('nommaeinput').value;
         const ajaxnumtel = document.getElementById('nuntelinput').value;
         const ajaxemail = document.getElementById('emailinput').value;
         const ajaxcep = document.getElementById('cep').value;
         const ajaxendereco = document.getElementById('endereco').value;
         const ajaxnumcsa = document.getElementById('numcsainput').value;
         const ajaxcpl = document.getElementById('cplinput').value;
         const ajaxbairro = document.getElementById('bairro').value;
         const ajaxufd = document.getElementById('estadosBrasileiros').value;
         const ajaxcidade = document.getElementById('cidade').value;
         const ajaxsenha = document.getElementById('senhainput').value;

         //fazer o spinner do botao aparecer
         const spinnerbtn = document.getElementById('spinnerbtn');
         spinnerbtn.style.display = 'inline-block';

         let btnCriarConta = document.getElementById('btnCriarConta');
         btnCriarConta.disabled = true;


         let ajaxtermos;

         if (document.getElementById('chktermos').checked == true) {
            ajaxtermos = 'S';
         } else {
            ajaxtermos = 'N';
         }

         vaziovalidarcpf('inputcpf', 'errorcpf', 'errorcpf1');
         inputvazio('rginput', 'rgerror');
         inputvazio('nomeinput', 'nomeerror');
         inputvazio('nommaeinput', 'nommaeerror');
         inputvazio('numtelinput', 'numtelerror');
         inputvazio('slcDia', 'slcDiaError');
         inputvazio('slcMes', 'slcMesError');
         inputvazio('slcAno', 'slcAnoError');
         valirdarvazioemail('emailinput', 'emailerror', 'emailinval');

         if (document.getElementById('cep').value !== '') {
            vaziobuscarcep('cep');
         }
         // victor aslan - mexeu aqui...
         if (document.getElementById('endereco').value == '') {

            if (!(document.getElementById('endereco').readOnly === true || document.getElementById('endereco').disabled === true)) {
               inputvazio('endereco', 'enderror');
            }
         }

         inputvazio('numcsainput', 'numcsaerror');
         if (document.getElementById('bairro').value == '') {
            if (!(document.getElementById('bairro').readOnly === true || document.getElementById('bairro').disabled === true)) {
               inputvazio('bairro', 'baierror');
            }
         }

         if (document.getElementById('cidade').value == '') {
            if (!(document.getElementById('cidade').readOnly === true || document.getElementById('cidade').disabled === true)) {
               inputvazio('cidade', 'cdderror');
            }
         }

         validarSenha('senhainput', 'senhaError');
         confirmrepsenhavalidarsenha('senhainput', 'repsenhainput', 'repsenhaError', 'repsenhaError1');

         const isChecked = document.getElementById('chktermos').checked;

         if (isChecked) {
            document.getElementById('chkerror').style.display = "none";
         } else {
            document.getElementById('chkerror').style.display = "block";
         }

         if (document.getElementById('endereco').value !== '') {
            //console.log('tem valor no endereço');
            document.getElementById('enderror').style.display = "none";
         }

         // IDs dos elementos <small> que contêm mensagens de erro
         const idsSmall = ['errorcpf',
            'errorcpf1',
            'rgerror',
            'nomeerror',
            'slcDiaError',
            'slcMesError',
            'slcAnoError',
            'nommaeerror',
            'numtelerror',
            'emailerror',
            'emailinval',
            'mensagemErroCep',
            'enderror',
            'numcsaerror',
            'baierror',
            'ufderror',
            'cdderror',
            'senhaError',
            'repsenhaError',
            'repsenhaError1',
            'chkerror'
         ];

         // Verifica cada <small> na lista
         for (let id of idsSmall) {
            if (verificarErroExposto(id)) {
               // Abre o modal de erro do Bootstrap
               const modal = new bootstrap.Modal(document.getElementById('erroModal'));
               const h3msg = document.getElementById('h3msg');

               alert(' Existem campos obrigatórios não preenchidos, por favor verifique os campos do formulario');
               spinnerbtn.style.display = 'none';
               btnCriarConta.disabled = false;
               return; // Aborta a criação da conta
            }
         }

         var ajaxdtansc = ajaxanonsc + '-' +
            (ajaxmesnsc.length < 2 ? '0' + ajaxmesnsc : ajaxmesnsc) + '-' +
            (ajaxdiansc.length < 2 ? '0' + ajaxdiansc : ajaxdiansc);

         const ajaxdados = {
            idecli: ajaxid,
            codcli: ajaxcod,
            cpfcli: ajaxcpf,
            rgcli: ajaxrg,
            nomcli: ajaxnome,
            dtansc: ajaxdtansc,
            maecli: ajaxnommae,
            numtel: ajaxnumtel,
            email: ajaxemail,
            cepcli: ajaxcep,
            endcli: ajaxendereco,
            numcsa: ajaxnumcsa,
            cplend: ajaxcpl,
            baicli: ajaxbairro,
            ufdcli: ajaxufd,
            muncli: ajaxcidade,
            senha: ajaxsenha,
            statrm: ajaxtermos
         };

         //  console.log('Dados enviados:', JSON.stringify(ajaxdados));

         fetch('ajax/gravardados-clientes.php', {
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
               btnCriarConta.disabled = false;
               return response.json();
            })
            .then(resposta => {
               // Pega a mensagem do JSON e exibe no elemento 'resultado'
               // const mensagem = resposta.mensagem || 'Cadastro Efetuado com sucesso!';
               // document.getElementById('resultado').innerText = mensagem;

               const modal = new bootstrap.Modal(document.getElementById('erroModal'));
               const h3msg = document.getElementById('h3msg');

               //h3msg.style.color = "#267D39";
               alert('Sucesso! Sua conta foi criada, por favor verifique o email cadastrado.');
               spinnerbtn.style.display = 'none';
               btnCriarConta.disabled = false;
               carregarConteudo('index.php');
            })
            .catch(error => {
               alert(`Erro: ${error.message}`);
               spinnerbtn.style.display = 'none';
               btnCriarConta.disabled = false;
            });
      }

      function gerarId(ajaxcpf) {
         // Remove os caracteres não numéricos para garantir que o CPF esteja limpo
         const cpfLimpo = ajaxcpf.replace(/\D/g, '');

         // Verifica se o CPF tem o formato correto
         if (cpfLimpo.length !== 11) {
            console.error('CPF inválido');
            return null;
         }

         // Pega os 3 primeiros dígitos
         const prefixo = cpfLimpo.slice(0, 3);

         // Gera um número aleatório de 6 dígitos
         const numeroRandomico = Math.floor(100000 + Math.random() * 900000);

         // Concatena o prefixo com o número aleatório
         const novoId = prefixo + numeroRandomico;

         return novoId;
      }

      function verificarErroExposto(smallId) {
         const elemento = document.getElementById(smallId);
         return elemento && elemento.style.display !== 'none' && elemento.innerText.trim() !== '';
      }

      function confirmrepsenhavalidarsenha(senhainput, repsenhainput, repsenhaerror, repsenhaerror1) {
         if (document.getElementById(senhainput).value !== document.getElementById(repsenhainput).value) {
            document.getElementById(repsenhaerror1).style.display = "block";
         } else {
            document.getElementById(repsenhaerror1).style.display = "none";
         }
         validarSenha(repsenhainput, repsenhaerror);
      }

      function vaziobuscarcep(cepinput) {
         if (document.getElementById(cepinput).value !== '') {
            buscarDadosCep();
         } else {
            desbloquearCampos();
            document.getElementById('endereco').value = '';
            document.getElementById('bairro').value = '';
            document.getElementById('estadosBrasileiros').value = '';
            document.getElementById('cidade').value = '';
         }
      }

      function vaziovalidarcpf(cpfinput, cpferror, cpferror1) {
         inputvazio(cpfinput, cpferror1);

         if (document.getElementById(cpfinput).value !== '') {
            validarCPFCad();
         }
      }

      function valirdarvazioemail(einputid, eerrorid, emailinval) {
         inputvazio(einputid, eerrorid);
         validaremail(einputid, emailinval);
      }

      function validaremail(emailinput, emailerror) {
         const emailField = document.getElementById(emailinput);
         const errorElement = document.getElementById(emailerror);
         const email = emailField.value;

         // Verifica se o e-mail contém "@" e se há um "." após o "@"
         const isValidEmail = email.includes('@') && email.indexOf('.', email.indexOf('@')) > email.indexOf('@');

         if (!isValidEmail) {
            errorElement.style.display = "block"; // Exibe mensagem de erro
         } else {
            errorElement.style.display = "none"; // Oculta mensagem de erro
         }
      }

      function inputvazio(inputid, errorid) {
         const varinput = document.getElementById(inputid);
         const varerror = document.getElementById(errorid);

         if (varinput !== null) {
            if (varinput.value === '') {
               varerror.style.display = "block"; // Exibe mensagem de erro
            } else {
               varerror.style.display = "none"; // Oculta mensagem de erro
            }
         }
      }

      function validarSenha(inputId, errorId) {
         const senhaInput = document.getElementById(inputId);
         const errorElement = document.getElementById(errorId);
         const senha = senhaInput.value;

         // Regex para verificar se a senha contém pelo menos 1 letra maiúscula, 1 caractere especial e tem no mínimo 10 caracteres
         const regex = /^(?=.*[A-Z])(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]).{10,}$/;

         if (!regex.test(senha)) {
            errorElement.style.display = "block"; // Exibe a mensagem de erro
         } else {
            errorElement.style.display = "none"; // Oculta a mensagem de erro
         }
      }

      function buscarDadosCep() {
         let cep = document.getElementById('cep').value;
         let mensagemErro = document.getElementById('mensagemErroCep');

         // Limpa mensagem de erro e campos antes de buscar o CEP
         mensagemErro.innerText = "";
         limparCampos();

         // Verifica se o CEP tem pelo menos 8 dígitos
         if (cep.length >= 8) {
            //fazer spinner aparecer
            const spinner = document.getElementById('loader-cep');
            spinner.style.display = "block";

            // Faz a requisição para o PHP busca_cep.php
            fetch('uses/busca_cep.php', {
                  method: 'POST',
                  headers: {
                     'Content-Type': 'application/json'
                  },
                  body: JSON.stringify({
                     cep: cep
                  })
               })
               .then(response => response.json())
               .then(data => {
                  spinner.style.display = "none";
                  if (!data.logradouro) {
                     // Exibe mensagem de erro se o CEP for inexistente
                     mensagemErro.innerText = "CEP inexistente";
                     desbloquearCampos(); // Desbloqueia os campos se o CEP não existir
                  } else {
                     // Preenche os campos com os dados retornados pela API
                     document.getElementById('endereco').value = data.logradouro;
                     document.getElementById('bairro').value = data.bairro;
                     document.getElementById('estadosBrasileiros').value = data.uf;

                     // Limpa o select de cidade antes de carregar novas opções
                     const cidadeSelect = document.getElementById('cidade');
                     cidadeSelect.innerHTML = '<option value="">Selecione a Cidade</option>'; // Opcional, adiciona uma opção padrão

                     // Faz a requisição para carregar as cidades do estado
                     fetch(`uses/cidades.php?estado=${data.uf}&online=n`)
                        .then(response => response.json())
                        .then(cidades => {
                           cidades.forEach(cidade => {
                              const option = document.createElement('option');
                              option.value = cidade.Id; // ID da cidade do DB
                              option.text = cidade.NOMMUN; // Nome da cidade
                              cidadeSelect.appendChild(option);
                           });
                           // Se a cidade retornada for válida, selecione-a
                           const cidadeEncontrada = cidades.find(c => c.NOMMUN.toLowerCase() === data.localidade.toLowerCase());
                           if (cidadeEncontrada) {
                              cidadeSelect.value = cidadeEncontrada.Id; // Define o valor do select como o ID da cidade encontrada
                           }
                        })
                        .catch(error => console.error('Erro ao carregar as cidades:', error));

                     // Torna os campos somente leitura e muda o fundo para cinza claro
                     bloquearInput('endereco');
                     bloquearInput('bairro');
                     bloquearInput('estadosBrasileiros');
                     bloquearInput('cidade');

                     // limpando msg de erro
                     document.getElementById('enderror').style.display = "none";
                     document.getElementById('baierror').style.display = "none";
                     document.getElementById('cdderror').style.display = "none";
                     document.getElementById('numcsaerror').style.display = "none";
                     //

                     // Formata o CEP para o padrão 78000-000
                     document.getElementById('cep').value = formatarCep(cep);
                  }
               })
               .catch(error => {
                  spinner.style.display = "none";
                  console.error('Erro:', error);
                  mensagemErro.innerText = "Erro ao buscar o CEP";
                  desbloquearCampos(); // Desbloqueia os campos em caso de erro na requisição
               });
         }

      }

      // Função para tornar o campo somente leitura e mudar o fundo para cinza claro
      function bloquearInput(id) {
         const elemento = document.getElementById(id);
         elemento.readOnly = true; // Funciona apenas para inputs
         elemento.disabled = true; // Funciona para inputs e selects
         elemento.classList.add('input-preenchido'); // Adiciona uma classe para o fundo cinza claro
      }

      // Função para formatar o CEP
      function formatarCep(cep) {
         return cep.replace(/(\d{5})(\d{3})/, "$1-$2");
      }

      // Função para limpar os campos ao iniciar uma nova consulta
      function limparCampos() {
         document.getElementById('endereco').value = "";
         document.getElementById('bairro').value = "";
         document.getElementById('cidade').value = "";
         document.getElementById('estadosBrasileiros').value = "";
      }

      // Função para desbloquear os campos e restaurar a cor padrão
      function desbloquearCampos() {
         const ids = ['endereco', 'bairro', 'cidade', 'estadosBrasileiros'];
         ids.forEach(id => {
            const elemento = document.getElementById(id);
            elemento.readOnly = false;
            elemento.disabled = false;
            elemento.classList.remove('input-preenchido'); // Remove a classe de fundo cinza claro
         });
      }

      // Função JavaScript para Carregar as Cidades
      function carregarCidades() {
         const estado = document.getElementById('estadosBrasileiros').value;
         const cidadeSelect = document.getElementById('cidade');

         if (estado) {
            fetch(`uses/cidades.php?estado=${estado}&online=n`)
               .then(response => response.json())
               .then(cidades => {
                  cidadeSelect.innerHTML = '<option value="">Selecione a Cidade</option>';
                  cidades.forEach(cidade => {
                     const option = document.createElement('option');
                     option.value = cidade.Id; // ID da cidade do DB
                     option.text = cidade.NOMMUN; // Nome da cidade
                     cidadeSelect.appendChild(option);
                  });
               })
               .catch(error => console.error('Erro ao carregar as cidades:', error));
         } else {
            cidadeSelect.innerHTML = '<option value="">Selecione a Cidade</option>';
         }
      }

      function carregarConteudo(pagina) {
         // Redireciona diretamente via GET
         window.location.href = pagina;
      }

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