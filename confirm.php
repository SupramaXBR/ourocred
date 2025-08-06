<!DOCTYPE html>
<html lang="pt-br">
  <?php
      include_once "uses/components.php";  //incluindo components.php    
      include_once "uses/conexao.php"; // Incluindo o arquivo de conexão com o banco de dados
      echo head('uses/estilo.css','imagens/favicon.ico'); //invocando o <head>
  ?>
  <body>
    <?php
        // Verificar se as variáveis 'ide' e 'hash' foram passadas pelo método GET
        if (!isset($_GET['ide'], $_GET['hash'])) {
            header("Location: index.php");
            exit();
        }

        // Sanitizando as entradas
        $ide = strip_tags($_GET['ide']);
        $hash = strip_tags($_GET['hash']);

        try {
            // Preparar o SELECT para verificar o cliente
            $query = $pdo->prepare("SELECT IDECLI, MD5PW FROM clientes WHERE IDECLI = :ide");
            $query->bindParam(':ide', $ide, PDO::PARAM_INT);
            $query->execute();

            $cliente = $query->fetch(PDO::FETCH_ASSOC);

            // Verificar se o cliente foi encontrado e se o hash corresponde
            if ($cliente && $cliente['MD5PW'] === $hash) {
                // Atualizar os campos STACTAATV e STACMFEML
                $update = $pdo->prepare("UPDATE clientes SET STACTAATV = 'S', STACMFEML = 'S' WHERE IDECLI = :ide");
                $update->bindParam(':ide', $ide, PDO::PARAM_INT);
                $update->execute();
            } else {
                // Redirecionar para a página index.php caso a validação falhe
                echo '<meta http-equiv="refresh" content="5; index.php">';
                exit();
            }
        } catch (PDOException $e) {
            // Em caso de erro, redirecionar para index.php
            error_log("Erro ao validar cliente: " . $e->getMessage());
            header("Location: index.php");
            exit();
        }
    ?>
    <div class="container">
        <div class="jumbotron">
            <h1 class="display-4"> <img src="imagens/logo.png" alt="OuroCred" width="200" height="200"> Cadastro Confirmado!</h1>       
            <p class="lead">Apartir de agora voce já pode acessar sua conta OuroCred em nosso site.</p>
            <hr class="my-4">        
            <a class="btn btn-primary btn-lg" href="index.php" role="button">Ir para o site.</a>
        </div>
    </div>

    <!-- JavaScript (Opcional) -->
    <!-- jQuery primeiro, depois Popper.js, depois Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
  </body>
</html>