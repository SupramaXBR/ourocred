<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exemplo Fetch</title>
</head>
<body>
    <input type="text" id="codigo" placeholder="Código" required>
    <input type="text" id="nome" placeholder="Nome" required>
    <input type="email" id="email" placeholder="Email" required>
    <input type="button" id="boton" value="Enviar">
    
    <div id="resultado"></div> <!-- Para mostrar o retorno do servidor -->

    <script>
        // Função para enviar os dados ao clicar no botão
        document.getElementById('boton').addEventListener('click', function() {
            // Captura os valores dos inputs
            const codigo = document.getElementById('codigo').value;
            const nome = document.getElementById('nome').value;
            const email = document.getElementById('email').value;

            // Cria um objeto com os dados
            const dados = {
                codigo: codigo,
                nome: nome,
                email: email
            };

            // Envia os dados usando fetch
            fetch('gravardados.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json' // Define o tipo de conteúdo como JSON
                },
                body: JSON.stringify(dados) // Converte os dados para JSON
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na rede');
                }
                return response.json(); // Espera a resposta como JSON
            })
            .then(resposta => {
              document.getElementById('resultado').innerText = 
                `Código: ${resposta.codigo}\nNome: ${resposta.nome}\nEmail: ${resposta.email}\nMensagem: ${resposta.mensagem}`;
            })
            .catch(error => {
                console.error('Erro:', error); // Tratamento de erro
            });
        });
    </script>
</body>
</html>
