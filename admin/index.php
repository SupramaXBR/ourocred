<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OuroCred D.T.V.M</title>
    <link rel="icon" href="../imagens/favicon.ico" type="image/x-icon">
    <!-- CSS Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Ícones do Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body, html {
            height: 100%;
            background-color: #f8f9fa;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background-color: #ffffff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 10px;
            padding: 25px;
        }
        .input-group-text { cursor: pointer; }
        .loaderbtn-sm {
            width: 18px;
            height: 18px;
            border: 4px solid;
            border-color: #a57c00 transparent;
            border-radius: 50%;
            display: inline-block;
            animation: rotation 1s linear infinite;
        }
    </style>
</head>
<body class="d-flex justify-content-center align-items-center">
    <div class="login-card">
        <div class="text-center">
            <img src="../imagens/logo.png" alt="Logo" width="50" height="50" class="mb-2">
            <h4 class="fw-bold text-primary">Administrador</h4>
        </div>
        <hr>
        <div class="mb-3">
            <label for="usuario" class="form-label">Usuário</label>
            <input type="text" class="form-control" id="usuario" placeholder="Digite seu usuário" required>
            <small id="alert-usuario" class="text-danger"></small>
        </div>
        <div class="mb-4">
            <label for="senha" class="form-label">Senha</label>
            <div class="input-group">
                <input type="password" class="form-control" id="senha" placeholder="Digite sua senha" required>
                <span class="input-group-text" onclick="toggleSenha()">
                    <i class="bi bi-eye-fill" id="icone-senha"></i>
                </span>
            </div>            
        </div>
        <button id="btnLogin" class="btn btn-primary btn-lg w-100" onclick="loginAdm()">
            Entrar
            <span id="spinnerbtnLogin" class="loaderbtn-sm" style="display: none;"></span>
        </button>
    </div>

    <script>
        function bloquearBotao(botaoId, spinnerId) {
            document.getElementById(botaoId).disabled = true;
            document.getElementById(spinnerId).style.display = 'inline-block';
        }
        function desbloquearBotao(botaoId, spinnerId) {
            document.getElementById(botaoId).disabled = false;
            document.getElementById(spinnerId).style.display = 'none';
        }

        function loginAdm() {
            bloquearBotao('btnLogin', 'spinnerbtnLogin');

            const usuario = document.getElementById('usuario').value;
            const senha = document.getElementById('senha').value;

            fetch('back-end/verificar-usradmin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ usuario, senha })
            })
            .then(resp => resp.json())
            .then(data => {
                // Limpa mensagens anteriores
                document.getElementById('alert-usuario').textContent = '';                

                if (data.status === 'success') {
                    // Se a autenticação for bem-sucedida, redireciona via POST para a URL de redirecionamento
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = data.redirect; // URL de redirecionamento enviada pelo PHP

                    form.innerHTML = `
                        <input type="hidden" name="usuario" value="${usuario}">
                        <input type="hidden" name="senha" value="${senha}">
                    `;

                    document.body.appendChild(form);
                    form.submit();
                } else if (data.message === 'Usuário ou senha incorretos') {
                    document.getElementById('alert-usuario').textContent = 'Usuário ou senha incorretos.';
                } else if (data.message === 'Empresa não encontrada ou configuração inválida') {
                    alert('Empresa não encontrada ou configuração inválida.');
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(err => alert('Erro ao conectar ao servidor: ' + err.message))
            .finally(() => desbloquearBotao('btnLogin', 'spinnerbtnLogin'));
        }


        function toggleSenha() {
            const campoSenha = document.getElementById('senha');
            const icone = document.getElementById('icone-senha');

            if (campoSenha.type === "password") {
                campoSenha.type = "text";
                icone.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
            } else {
                campoSenha.type = "password";
                icone.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
            }
        }
        
        document.getElementById('usuario').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });        
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>