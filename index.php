<!DOCTYPE html>
<html lang="pt-br">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
<link rel="stylesheet" href="css/index.css">
<?php

include_once "uses/components.php";  //incluindo components.php    
include_once "uses/funcoes.php";  //incluindo components.php    
echo head('uses/estilo.css', 'imagens/favicon.ico'); //invocando o <head>

$vsQtdDiaClassic = retornarQtddiaClassic('1');
$vsPerDscClassic = retornarPerdscClassic('1');

$vsQtdDiaStandard = retornarQtddiaStandard('1');
$vsPerDscStandard = retornarPerdscStandard('1');

$vsQtdDiaPremium = retornarQtddiaPremium('1');
$vsPerDscPremium = retornarPerdscPremium('1');

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
               <h5 class="offcanvas-title"><img src="imagens/logo.png" width="25" height="25" alt=""> OuroCred</h5>
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
   <!-- countainer de planos -->

   <div class="container my-4">
      <div class="alert alert-primary text-center" role="alert">
         PLANOS DE INVESTIMENTO SEGURO
      </div>
   </div>

   <div class="container">
      <div class="row justify-content-center">

         <div class="col-lg-4 col-md-6 col-sm-12 mb-3 d-flex justify-content-center">
            <div class="card text-center" style="width: 100%; max-width: 18rem;">
               <div class="card-body">
                  <h5 class="card-title">Premium</h5>
                  <p class="card-text">Neste plano você investe em ouro com venda garantida para daqui <b><?php echo $vsQtdDiaPremium; ?> dias</b> com <b><?php echo $vsPerDscPremium; ?>%</b> acima do valor de mercado.</p>
                  <a href="planopremium.php" class="btn btn-primary">Saiba Mais</a>
               </div>
            </div>
         </div>

         <div class="col-lg-4 col-md-6 col-sm-12 mb-3 d-flex justify-content-center">
            <div class="card text-center" style="width: 100%; max-width: 18rem;">
               <div class="card-body">
                  <h5 class="card-title">Standard</h5>
                  <p class="card-text">Neste plano você investe em ouro com venda garantida para daqui <b><?php echo $vsQtdDiaStandard; ?> dias</b> com <b><?php echo $vsPerDscStandard; ?>%</b> acima do valor de mercado.</p>
                  <a href="planostandart.php" class="btn btn-primary">Saiba Mais</a>
               </div>
            </div>
         </div>

         <div class="col-lg-4 col-md-6 col-sm-12 mb-3 d-flex justify-content-center">
            <div class="card text-center" style="width: 100%; max-width: 18rem;">
               <div class="card-body">
                  <h5 class="card-title">Classic</h5>
                  <p class="card-text">Neste plano você investe em ouro com venda garantida para daqui <b><?php echo $vsQtdDiaClassic; ?> dias</b> com <b><?php echo $vsPerDscClassic; ?>%</b> acima do valor de mercado.</p>
                  <a href="planoclassic.php" class="btn btn-primary">Saiba Mais</a>
               </div>
            </div>
         </div>

      </div>
   </div>
   <!-- countainer widget bolsa ouro -->
   <br>
   <div class="container">
      <div class="alert alert-primary text-center" role="alert">
         ACOMPANHE O MERCADO EM TEMPO REAL
      </div>
      <table class="table table-bordered text-center align-middle">
         <tr>
            <td colspan="2">
               <div class="card border-warning">
                  <div class="card-body">
                     <h5 class="card-title text-warning"><i class="bi bi-minecart"></i> Compra</h5>
                     <p class="card-text">R$: <?php echo number_format(RetornarValorGrama(), 2) ?> X 1g</p>
                  </div>
               </div>
            </td>
            <td></td>
            <td colspan="2">
               <div class="card border-danger">
                  <div class="card-body">
                     <h5 class="card-title text-danger"><i class="bi bi-minecart-loaded"></i> Venda</h5>
                     <p class="card-text">R$: <?php echo number_format((RetornarValorGrama() - obterValorDescGramaVendida()), 2) ?> X 1g</p>
                  </div>
               </div>
            </td>
         </tr>
      </table>
      <!-- TradingView Widget BEGIN -->
      <div class="tradingview-widget-container">
         <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-symbol-overview.js" async>
            {
               "symbols": [
                  [
                     "OANDA:XAUUSD|1M"
                  ],
                  [
                     "FOREXCOM:USDBRL|1D"
                  ],
                  [
                     "FX_IDC:EURBRL|1D"
                  ]
               ],
               "chartOnly": false,
               "width": "100%",

               "locale": "en",
               "colorTheme": "light",
               "autosize": true,
               "showVolume": false,
               "showMA": false,
               "hideDateRanges": false,
               "hideMarketStatus": false,
               "hideSymbolLogo": false,
               "scalePosition": "right",
               "scaleMode": "Normal",
               "fontFamily": "-apple-system, BlinkMacSystemFont, Trebuchet MS, Roboto, Ubuntu, sans-serif",
               "fontSize": "10",
               "noTimeScale": false,
               "valuesTracking": "1",
               "changeMode": "price-and-percent",
               "chartType": "area",
               "maLineColor": "#2962FF",
               "maLineWidth": 1,
               "maLength": 9,
               "headerFontSize": "medium",
               "lineWidth": 2,
               "lineType": 0,
               "dateRanges": [
                  "1d|1",
                  "1m|30",
                  "3m|60",
                  "12m|1D",
                  "60m|1W",
                  "all|1M"
               ]
            }
         </script>
      </div>
      <!-- TradingView Widget END -->
      <br>
      <div class="alert alert-primary text-center" role="alert">
         TABELA DE RENTABILIDADE
      </div>
      <div class="table-responsive">
         <table class="table table-bordered text-center">
            <thead class="table-light">
               <tr>
                  <th scope="col">Ano</th>
                  <th scope="col">Poupança</th>
                  <th scope="col">Ibovespa</th>
                  <th scope="col">CDI</th>
                  <th scope="col">Dólar</th>
                  <th scope="col">Ouro</th>
               </tr>
            </thead>
            <tbody>
               <tr>
                  <th scope="row">2014</th>
                  <td>7,16%</td>
                  <td>-2,91%</td>
                  <td>10,81%</td>
                  <td>13,42%</td>
                  <td>12,04%</td>
               </tr>
               <tr>
                  <th scope="row">2015</th>
                  <td>8,15%</td>
                  <td>-13,31%</td>
                  <td>13,25%</td>
                  <td>48,49%</td>
                  <td>32,15%</td>
               </tr>
               <tr>
                  <th scope="row">2016</th>
                  <td>8,30%</td>
                  <td>38,93%</td>
                  <td>14,00%</td>
                  <td>-17,69%</td>
                  <td>12,57%</td>
               </tr>
               <tr>
                  <th scope="row">2017</th>
                  <td>6,61%</td>
                  <td>26,86%</td>
                  <td>9,93%</td>
                  <td>1,50%</td>
                  <td>11,93%</td>
               </tr>
               <tr>
                  <th scope="row">2018</th>
                  <td>4,62%</td>
                  <td>15,03%</td>
                  <td>6,42%</td>
                  <td>16,92%</td>
                  <td>16,93%</td>
               </tr>
               <tr>
                  <th scope="row">2019</th>
                  <td>4,26%</td>
                  <td>31,58%</td>
                  <td>5,96%</td>
                  <td>3,50%</td>
                  <td>23,93%</td>
               </tr>
               <tr>
                  <th scope="row">2020</th>
                  <td>2,11%</td>
                  <td>2,92%</td>
                  <td>2,75%</td>
                  <td>29,36%</td>
                  <td>55,93%</td>
               </tr>
               <tr>
                  <th scope="row">2021</th>
                  <td>2,94%</td>
                  <td>-11,93%</td>
                  <td>4,35%</td>
                  <td>7,39%</td>
                  <td>-4,32%</td>
               </tr>
               <tr>
                  <th scope="row">2022</th>
                  <td>6,17%</td>
                  <td>-4,69%</td>
                  <td>13,65%</td>
                  <td>-5,12%</td>
                  <td>-0,19%</td>
               </tr>
               <tr>
                  <th scope="row">2023</th>
                  <td>7,42%</td>
                  <td>22,28%</td>
                  <td>13,04%</td>
                  <td>-8,21%</td>
                  <td>12,46%</td>
               </tr>
               <tr>
                  <th scope="row">2024</th>
                  <td>8,00%</td>
                  <td>-10,67%</td>
                  <td>12,25%</td>
                  <td>27,90%</td>
                  <td>15,00%</td>
               </tr>
            </tbody>
            <tfoot class="table-success">
               <tr>
                  <th scope="row">Total 10 anos</th>
                  <td>65,74%</td>
                  <td>94,09%</td>
                  <td>106,41%</td>
                  <td>116,46%</td>
                  <td><b>188,43%</b></td>
               </tr>
            </tfoot>
         </table>
      </div>
   </div>

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

   <script src="js/index.js"></script>
   <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
   <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

</body>

</html>