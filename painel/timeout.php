<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessão Expirada - OuroCred</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .jumbotron {
            text-align: center;
            padding: 3rem 2rem;
            margin-top: 80px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 20px;
            background-color: #ffffff;
        }

        @media (max-width: 576px) {
            .jumbotron {
                padding: 2rem 1rem;
            }
        }

        /* Ajuste para telas muito pequenas (menos de 400px) */
        @media (max-width: 400px) {
            .jumbotron h1.display-4 {
                font-size: 1.8rem;
            }

            .jumbotron p.lead {
                font-size: 1rem;
            }

            .jumbotron p {
                font-size: 0.95rem;
            }

            .jumbotron .btn-lg {
                font-size: 1rem;
                padding: 0.6rem 1rem;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="jumbotron">
            <img src="../imagens/logo.png" alt="OuroCred" class="img-fluid mb-3" style="max-width: 200px;">
            <h1 class="display-4">Sessão Expirada!</h1>
            <p class="lead">Por motivos de segurança, sua sessão foi finalizada devido à inatividade.</p>
            <hr class="my-4">
            <p>Para continuar utilizando o sistema, faça login novamente.</p>
            <a class="btn btn-primary btn-lg" href="../index.php" role="button">Voltar ao Login</a>
        </div>
    </div>

</body>
</html>
