<!DOCTYPE html>
<html lang="pt-br">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/planos.css">
<?php
include_once "uses/components.php";  //incluindo components.php    
echo head('uses/estilo.css', 'imagens/favicon.ico'); //invocando o <head>
?>

<body>

   <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
      <div class="container">
         <a class="navbar-brand" href="index.php">
            <img src="imagens/logo.png" width="30" height="30" alt=""> OuroCred
         </a>
         <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#navbarOffcanvas" aria-controls="navbarOffcanvas">
            <span class="navbar-toggler-icon"></span>
         </button>

         <div class="offcanvas offcanvas-end" tabindex="-1" id="navbarOffcanvas">
            <div class="offcanvas-header">
               <h5 class="offcanvas-title"> <img src="imagens/logo.png" width="25" height="25" alt=""> OuroCred</h5>
               <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body">
               <ul class="navbar-nav ms-auto">
                  <li class="nav-item">
                     <a class="nav-link active" href="index.php">Home</a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link" href="criarconta.php">Abra sua Conta</a>
                  </li>
                  <li class="nav-item dropdown">
                     <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Planos</a>
                     <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="planopremium.php">Premium</a></li>
                        <li><a class="dropdown-item" href="planostandart.php">Standard</a></li>
                        <li><a class="dropdown-item" href="planoclassic.php">Classic</a></li>
                        <li><a class="dropdown-item" href="planosimple.php">Simple</a></li>
                     </ul>
                  </li>
                  <li class="nav-item dropdown">
                     <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Ouro</a>
                     <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="origemouro.php">Origem do Ouro</a></li>
                        <li><a class="dropdown-item" href="pqinvestir.php">Por que Investir?</a></li>
                        <li><a class="dropdown-item" href="tributacaoouro.php">Tributação do Ouro</a></li>
                        <li><a class="dropdown-item" href="curiosidades.php">Curiosidades do Ouro</a></li>
                     </ul>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link" href="sobrenos.php">Sobre nós</a>
                  </li>
               </ul>

               <div class="btn-group">
                  <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                     <b>Login</b>
                  </button>

                  <div class="dropdown-menu">
                     <form class="px-4 py-3">
                        <div class="form-group">
                           <label for="cpfInput">CPF</label>
                           <input type="text" class="form-control" id="cpfInput" placeholder="Digite seu CPF" maxlength="14">
                           <small id="cpfError" class="form-text text-danger" style="display: none;">CPF inválido!</small>
                        </div>
                        <div class="form-group">
                           <label for="exampleDropdownFormPassword1">Senha</label>
                           <input type="password" class="form-control" id="password1" placeholder="Senha">
                        </div>
                        <h2> RECAPTCHA </h2>
                        <div class="form-check">
                           <input type="checkbox" class="form-check-input" id="dropdownCheck">
                           <label class="form-check-label" for="dropdownCheck">
                              Lembrar de mim.
                           </label>
                        </div>
                        <button type="submit" class="btn btn-primary" onclick="login(event)">Entrar</button>
                     </form>
                     <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="criarconta.php">Novo por aqui?, Abra sua conta</a>
                     <a class="dropdown-item" href="recuperarsenha/index.php">Esqueceu a senha?</a>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </nav>

   <div id="carouselOuroCred" class="carousel slide carousel-fade" data-bs-ride="carousel">
      <div class="carousel-inner">
         <div class="carousel-item active fundo1" style="background-image: url('imagens/fundo1.png');">

         </div>

         <div class="carousel-item fundo2" style="background-image: url('imagens/fundo2.png');">

         </div>

         <div class="carousel-item fundo3" style="background-image: url('imagens/fundo3.png');">

         </div>

      </div>

      <button class="carousel-control-prev" type="button" data-bs-target="#carouselOuroCred" data-bs-slide="prev">
         <span class="carousel-control-prev-icon"></span>
         <span class="visually-hidden">Anterior</span>
      </button>

      <button class="carousel-control-next" type="button" data-bs-target="#carouselOuroCred" data-bs-slide="next">
         <span class="carousel-control-next-icon"></span>
         <span class="visually-hidden">Próximo</span>
      </button>

      <div class="carousel-indicators">
         <button type="button" data-bs-target="#carouselOuroCred" data-bs-slide-to="0" class="active"></button>
         <button type="button" data-bs-target="#carouselOuroCred" data-bs-slide-to="1"></button>
         <button type="button" data-bs-target="#carouselOuroCred" data-bs-slide-to="2"></button>
      </div>

   </div>

   <br>
   <div class="container my-5">
      <div class="row align-items-center">
         <div class="col-lg-8 col-md-12 text-justificado">
            <h2><strong>Plano Standard OuroCred</strong></h2>

            <p>O plano <strong>Standard</strong> foi desenvolvido especialmente para investidores que desejam retorno garantido em curto prazo. Com este plano, você investe em ouro com a segurança de recompra garantida em <b>três meses</b>, assegurando um retorno de <b>1%</b> acima do valor de mercado na data da venda.</p>

            <p>Este plano é ideal para investidores que buscam segurança e valorização rápida, aproveitando o potencial do ouro sem comprometer o capital por um longo período.</p>

            <p>Invista com a OuroCred no plano Standard e tenha a garantia de um retorno rápido e seguro.</p>

            <a class="btn btn-primary" href="#" role="button">Escolher este plano</a>
         </div>

         <div class="col-lg-4 col-md-12 d-none d-lg-block text-center">
            <img src="imagens/cifrao.png" alt="Imagem Circular" class="img-fluid rounded-circle">
         </div>
      </div>
   </div>
   <br>
   <br>
   <br>
   <br>
   <br>
   <br>
   <br>


   <?php
   echo footer(
      'imagens/logo.png',
      'imagens/icone_x.webp',
      'imagens/icone_fb.webp',
      'imagens/icone_insta.webp',
      'imagens/icone_yt.webp',
      'imagens/icone_wpp.webp'
   ); // invocando <footer>
   ?>


   <script src="js/planos.js"></script>
   <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
   <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>


</body>

</html>