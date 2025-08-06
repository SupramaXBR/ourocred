<?php
include_once 'conexao.php';
include_once 'funcoes.php';

function head($end_estilo_css, $end_favicon)
{

   $head = '<head>
                    <!-- Meta tags Obrigatórias -->
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
                    <link rel="icon" href="' . $end_favicon . '" type="image/x-icon">
                    <!-- Bootstrap CSS -->
                    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
                    <link rel="stylesheet" href="' . $end_estilo_css . '">    
                    <title>OuroCred D.T.V.M </title>
                    <!-- estilo do spiner, nao esta funcionando se colocar no arquivo estilo.css -->
                    <style>

                        .titulo-form {
                            /* background-color: #FFD700; /* Cor azul Bootstrap */
                            color: white; /* Texto branco */
                            font-size: 1.25rem; /* Tamanho do texto */
                            /* font-weight: bold; /* Texto em negrito */
                            padding: 10px 15px; /* Espaçamento interno */
                           /* border-radius: 5px 5px 5px 5px; /* Bordas arredondadas apenas no topo */
                            text-align: center; /* Centralizar texto */
                        }                    

                        .tabela-wallet {
                            background: #EEEEFF;
                            border-radius: 12px;
                            overflow: hidden;
                            border: 1px solid #ccc;
                        }

                        .linha-wallet {
                            border-bottom: 1px solid #ddd;
                        }

                        .linha-wallet:last-child {
                            border-bottom: none;
                        }

                        .coluna-wallet {
                            border-right: 1px solid #ddd;
                        }

                        .coluna-wallet:last-child {
                            border-right: none;
                        }

                        /* Ícones menores e alinhados */
                        .icone-wallet {
                            font-size: 0.9rem; /* Tamanho reduzido */
                        }

                        /* Texto menor e alinhado */
                        .valor-wallet {
                            font-size: 0.75rem; /* Menor ainda */
                            font-weight: 500;
                            text-align: center;
                        }

                        .link-branco {
                            color: white !important;
                            text-decoration: none; /* Remove o sublinhado (opcional) */
                        }

                        .link-branco:hover {
                            color: #f8f9fa; /* Um branco um pouco mais suave no hover */
                            text-decoration: underline; /* Adiciona um sublinhado no hover (opcional) */
                        }

                        .icone-grande {
                            font-size: 24px; /* Ajuste o tamanho conforme necessário */
                        }

                        .texto-grande {
                            font-size: 20px; /* Ajuste o tamanho conforme necessário */
                        }

                        .btn-cor-ouro {
                            background-color: #f9db4a; /* Amarelo Ouro */
                            color: #ffffff; /* Texto branco */
                            border: none; /* Remove borda */
                            font-weight: bold; /* Fonte destacada */
                            padding: 10px 20px; /* Ajusta o espaçamento interno */
                            border-radius: 5px; /* Borda levemente arredondada */
                            transition: background-color 0.3s ease-in-out; /* Efeito suave ao passar o mouse */
                        }

                        .btn-cor-ouro:hover {
                            background-color: #e0c13b; /* Tom de amarelo mais escuro */
                            color: #ffffff; /* Mantém o texto branco */
                        }
                            
                        .bg-nav-painel {
                            background-color: #EEEEFF !important;
                        }
                        hr {
                            border: none; /* Remove a borda padrão */
                            height: 2px; /* Define a espessura */
                            background-color: #007bff; /* Cor azul Bootstrap */
                        }
                        .bg-color-azul-B9E7F8 {
                            background-color: #B9E7F8;
                        }
                        .titulo-cinza {
                            color: #6c757d; /* Cinza médio */
                            font-weight: bold; /* Mantém o negrito */
                            font-size: 16px; /* Tamanho adequado para tabelas */                            
                            letter-spacing: 0.5px; /* Leve espaçamento */
                        }

                        .cor-texto-saldo {
                            color: #198754;
                        }
                        .cor-texto-simple {
                            color: #cd7f32;
                        }
                        .cor-texto-classic {
                            color: #C0C0C0;
                        }
                        .cor-texto-standard {
                            color: #212529;
                        }
                        .cor-texto-premium {
                            color: #FFD700;
                        }                            

                        .cor-texto-vermelho {
                            color:rgb(206, 40, 53);
                        }

                        .cor-texto-verde {
                            color:rgb(27, 185, 27);
                        }
                        .cor-texto-azul {
                            color: #007bff;
                        }
                        .cor-texto-amarelo {
                            color:rgb(150, 147, 14);
                        }                            
                        .img-lil-circle-perfil {
                            border-radius: 50%; /* Torna a imagem circular */
                            width: 80px; /* Ajuste conforme necessário */
                            height: 80px; /* Ajuste conforme necessário */
                            object-fit: cover; /* Corta a imagem para se ajustar ao contêiner */
                        }                 
                        .bg-color-vermelho-F01E2C {
                            background-color: #F01E2C;
                        }
                       .dropdown-menu {
                            right: 0 !important;
                            left: auto !important;
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

                        .loaderbtn-sm {
                            width: 18px;
                            height: 18px;
                            border: 4px solid;
                            border-color: #a57c00 transparent;
                            border-radius: 50%;
                            display: inline-block;
                            box-sizing: border-box;
                            animation: rotation 1s linear infinite;
                        }                        

                        .loader {
                            transform: rotateZ(45deg);
                            perspective: 1000px;
                            border-radius: 50%;
                            width: 20px;
                            height: 20px;
                            color: #0d6efd;
                            position: relative;
                        }

                        .loader:before,
                        .loader:after {
                            content: "";
                            display: block;
                            position: absolute;
                            top: 0;
                            left: 0;
                            width: inherit;
                            height: inherit;
                            border-radius: 50%;
                            transform: rotateX(70deg);
                            animation: 1s spin linear infinite;
                        }

                        .loader:after {
                            color: #a57c00;
                            transform: rotateY(70deg);
                            animation-delay: .4s;
                        }

                        @keyframes rotation {
                            0% {
                                transform: rotate(0deg);
                            }
                            100% {
                                transform: rotate(360deg);
                            }
                        } 

                        @keyframes rotate {
                            0% {
                                transform: translate(-50%, -50%) rotateZ(0deg);
                            }
                            100% {
                                transform: translate(-50%, -50%) rotateZ(360deg);
                            }
                        }

                        @keyframes rotateccw {
                            0% {
                                transform: translate(-50%, -50%) rotate(0deg);
                            }
                            100% {
                                transform: translate(-50%, -50%) rotate(-360deg);
                            }
                        }

                        @keyframes spin {
                            0%, 100% {
                                box-shadow: .2em 0px 0 0px currentcolor;
                            }
                            12% {
                                box-shadow: .2em .2em 0 0 currentcolor;
                            }
                            25% {
                                box-shadow: 0 .2em 0 0px currentcolor;
                            }
                            37% {
                                box-shadow: -.2em .2em 0 0 currentcolor;
                            }
                            50% {
                                box-shadow: -.2em 0 0 0 currentcolor;
                            }
                            62% {
                                box-shadow: -.2em -.2em 0 0 currentcolor;
                            }
                            75% {
                                box-shadow: 0px -.2em 0 0 currentcolor;
                            }
                            87% {
                                box-shadow: .2em -.2em 0 0 currentcolor;
                            }
                        }
                    </style>                    
                 </head>';
   return $head;
}

function navbar_desktop()
{

   $navbar = ' <nav class="navbar navbar-expand-lg fixed-top navbar-light bg-light">
                        <div class="container">
                            <a class="navbar-brand" href="index.php">
                                <img src="imagens/logo.png" width="30" height="30" alt="">
                                OuroCred
                            </a>

                            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#conteudoNavbarSuportado" aria-controls="conteudoNavbarSuportado" aria-expanded="false" aria-label="Alterna navegação">
                                <span class="navbar-toggler-icon"></span>
                            </button>

                            <div class="collapse navbar-collapse" id="conteudoNavbarSuportado">
                                <ul class="navbar-nav justify-content-end mr-auto">

                                    <li class="nav-item active">
                                        <a class="nav-link" href="index.php">Home <span class="sr-only"></span></a>
                                    </li>
                                    <li class="nav-item active">
                                        <a class="nav-link" href="criarconta.php">Abra sua Conta <span class="sr-only"></span></a>
                                    </li>           
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Planos
                                        </a>
                                        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                            <a class="dropdown-item" href="planopremium.php">Premium</a>
                                            <a class="dropdown-item" href="planostandart.php">Standard</a>
                                            <a class="dropdown-item" href="planoclassic.php">Classic</a>
                                            <a class="dropdown-item" href="planosimple.php">Simple</a>
                                        </div>
                                    </li>
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Ouro
                                        </a>
                                        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                            <a class="dropdown-item" href="origemouro.php">Origem do Ouro</a>
                                            <a class="dropdown-item" href="pqinvestir.php">Por que Invesir?</a>
                                            <a class="dropdown-item" href="tributacaoouro.php">Tributação do Ouro</a>
                                            <a class="dropdown-item" href="curiosidades.php">Curiosidades do Ouro</a>
                                        </div>
                                    </li>
                                    <li class="nav-item active">
                                        <a class="nav-link" href="sobrenos.php">Sobre nós <span class="sr-only"></span></a>
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
                                            <button type="submit" class="btn btn-primary" onclick="login()">Entrar</button>
                                        </form>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="criarconta.php">Novo por aqui?, Abra sua conta</a>
                                        <a class="dropdown-item" href="recuperarsenha/index.php">Esqueceu a senha?</a>
                                    </div>                                                         
                                </div>
                            </div>
                        </div>
                    </nav> ';
   return $navbar;
}

function carousel_desktop()
{

   $carousel = '<div id="carouselExampleControls" class="carousel slide" data-ride="carousel">
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <div style="background-image: url(imagens/fundo1.png)" >
                                    <div class="container" >
                                        <table class="table">
                                            <tr>
                                                <td class="align-right">
                                                    <br>
                                                    <br>
                                                    <br>
                                                    <div class="container mt-5">
                                                        <h1 class="text-right cor_ouro">OuroCred</h1>
                                                        <h3 class="text-right cor_ouro">Invista em ouro, seguro e rapido</h3>
                                                        <p class="text-right mt-4 fs-5 cor_ouro">Compre e venda seu ouro digital de maneira rapida e facil.</p>
                                                        <div class="text-right mt-3">
                                                            <a href="criarconta.php" class="btn btn-primary btn-sm">Comece a investir</a>
                                                        </div>    
                                                    </div>
                                                    <br>   
                                                    <br>
                                                    <br>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>  
                            </div>
    
                            <div class="carousel-item">
                                <div style="background-image: url(imagens/fundo2.png)" >
                                    <div class="container" >
                                        <table class="table">
                                            <tr>
                                                <td class="align-right">
                                                    <br>
                                                    <br>
                                                    <br>
                                                    <div class="container mt-5">
                                                        <h1 class="text-right cor_ouro">Planos de Investimento Seguro</h1>
                                                        <h3 class="text-right cor_ouro">Conheça nossos planos de investimento</h3>
                                                        <p class="text-right mt-4 fs-5 cor_ouro">Temos varios modelos de planos que se adequam a sua realidade.</p>
                                                        <div class="text-right mt-3">
                                                            <a href="criarconta.php" class="btn btn-primary btn-sm">Comece a investir</a>
                                                        </div>    
                                                    </div>
                                                    <br>   
                                                    <br>
                                                    <br>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>  
                            </div>

                            <div class="carousel-item">
                                <div style="background-image: url(imagens/fundo3.png)" >
                                    <div class="container" >
                                        <table class="table">
                                            <tr>
                                                <td class="align-right">
                                                    <br>
                                                    <br>
                                                    <br>
                                                    <div class="container mt-5">
                                                        <h1 class="text-right cor_ouro">Aprenda a investir com segurança</h1>
                                                        <h3 class="text-right cor_ouro">Confiança e garantia em seu investimento</h3>
                                                        <p class="text-right mt-4 fs-5 cor_ouro">Trabalhe com quem proporciona confiança e segurança em seu investimento.</p>
                                                        <div class="text-right mt-3">
                                                            <a href="criarconta.php" class="btn btn-primary btn-sm">Comece a investir</a>
                                                        <div>    
                                                    </div>
                                                    <br>   
                                                    <br>
                                                    <br>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>  
                            </div>
                        </div>
                        <a class="carousel-control-prev" href="#carouselExampleControls" role="button" data-slide="prev">
                            <span class="carousel-control-prev-icon carousel-prev-icone-azul" aria-hidden="true"></span>
                            <span class="sr-only">Anterior</span>
                        </a>
                        <a class="carousel-control-next" href="#carouselExampleControls" role="button" data-slide="next">
                            <span class="carousel-control-next-icon carousel-next-icone-azul" aria-hidden="true"></span>
                            <span class="sr-only">Próximo</span>
                        </a>
                    </div>';
   return $carousel;
}

function footer($end_logo, $end_icon_x, $end_icon_fb, $end_icon_insta, $end_icon_yt, $end_icon_wpp)
{
   $footer = '<footer style="background-color: #3271a5; color: white; padding: 2rem 0;">
          <div class="container">
              <div class="row">
                  <div class="col-md-6">
                      <h5><img src="' . $end_logo . '" width="30" height="30" alt=""> OuroCred</h5>
                      <ul class="list-unstyled">
                          <li><a href="index.php" class="text-light">Home</a></li>
                          <li><a href="#" class="text-light">Abra sua conta</a></li>
                          <li><a href="#" class="text-light">Planos</a></li>
                          <li><a href="origemouro.php" class="text-light">Ouro</a></li>
                          <li><a href="#" class="text-light">Sobre Nós</a></li>
                      </ul>
                      <!-- Endereço inserido -->
                      <p class="mt-3">
                          <strong>Endereço</strong><br>
                          Avenida Couto Magalhães, 2277<br>
                          Centro-Norte, Várzea Grande, MT<br>
                          CEP: 78110-400
                      </p>
                  </div>
  
                  <div class="col-md-6 text-right">
                      <h5>Redes Sociais</h5>
                      <a href="#" class="text-light me-2">
                          <i class="fab fa-facebook-f"></i>
                      </a>
                      <a href="#" class="text-light me-2">
                          <i class="fab fa-twitter"></i>
                      </a>
                      <a href="#" class="text-light me-2">
                          <i class="fab fa-instagram"></i>
                      </a>
                      <a href="#" class="text-light me-2">
                          <i class="fab fa-linkedin-in"></i>
                      </a>
  
                      <div class="mt-2 d-flex justify-content-end">
                          <a href="' . retornarCampoEmpresa(1, 'LNKFACEBOOK') . '"><img src="' . $end_icon_fb . '" width="30" height="30" alt="Facebook" class="me-2"></a>
                          <a href="' . retornarCampoEmpresa(1, 'LNKTWITTER') . '"><img src="' . $end_icon_x . '" width="30" height="30" alt="Twitter/X" class="me-2"></a>
                          <a href="' . retornarCampoEmpresa(1, 'LNKINSTAGRAM') . '"><img src="' . $end_icon_insta . '" width="30" height="30" alt="Instagram" class="me-2"></a>
                          <a href="' . retornarCampoEmpresa(1, 'LNKYOUTUBE') . '"><img src="' . $end_icon_yt . '" width="30" height="30" alt="YouTube" class="me-2"></a>
                          <a href="' . retornarCampoEmpresa(1, 'LNKWHATSAPP') . '"><img src="' . $end_icon_wpp . '" width="30" height="30" alt="WhatsApp" class="me-2"></a>
                      </div>
                  </div>
              </div>
  
              <div class="text-center mt-3">
                  <p>&copy; 2025 ' . retornarCampoEmpresa(1, 'NOMEMP') . ' Todos os direitos reservados.</p>
              </div>
          </div>
      </footer>';
   return $footer;
}

function footerPainel()
{

   $footer = '<footer class="footer-custom" id="footerPainel">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 d-none d-md-block">
                </div>
                <div class="col-md-6 text-center text-md-start">
                    <p class="texto-grande">&copy; 2025 ' . retornarCampoEmpresa(1, 'NOMEMP') . ' Todos os direitos reservados.</p>
                </div>
                <div class="col-md-4 text-center text-md-end">
                    <a class="link-branco" href="' . retornarCampoEmpresa(1, 'LNKFACEBOOK') . '"><i class="bi bi-facebook icone-grande"></i></a>
                    <a class="link-branco" href="' . retornarCampoEmpresa(1, 'LNKTWITTER') . '"><i class="bi bi-twitter-x icone-grande"></i></a>
                    <a class="link-branco" href="' . retornarCampoEmpresa(1, 'LNKINSTAGRAM') . '"><i class="bi bi-instagram icone-grande"></i></a>
                    <a class="link-branco" href="' . retornarCampoEmpresa(1, 'LNKYOUTUBE') . '"><i class="bi bi-youtube icone-grande"></i></a>
                    <a class="link-branco" href="' . retornarCampoEmpresa(1, 'LNKWHATSAPP') . '"><i class="bi bi-whatsapp icone-grande"></i></a>
                </div>
            </div>
        </div>
    </footer>';

   return $footer;
}


function script_footer_bootstrap()
{

   $script = '<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
                   <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
                   <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>';

   return $script;
}


function modaltermosdeuso()
{
   $modal = '    <!-- Modal -->
                        <div class="modal fade" id="termsModal" tabindex="-1" role="dialog" aria-labelledby="termsModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="termsModalLabel">Termos de Uso e Política de Privacidade</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                 ' . mb_convert_encoding(retornarCampoEmpresa(1, 'TXTTERMOS'), "UTF-8", "ISO-8859-1") . '
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                                </div>
                            </div>
                        </div>
                </div>';
   return $modal;
}
function script_validador_cpf()
{
   $scriptCpf = "<script>
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
                        </script>";

   return $scriptCpf;
}

function script_api_viacep_format_cep($end_arq_busca_cep_php)
{
   $scriptCep = "<script>
                        function buscarDadosCep() {
                            let cep = document.getElementById('cep').value;

                            // Verifica se o CEP tem pelo menos 8 dígitos
                            if (cep.length >= 8) {
                                // Faz a requisição para o PHP busca_cep.php
                                fetch('" . $end_arq_busca_cep_php . "', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ cep: cep })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    // Preenche os campos com os dados retornados pela API
                                    document.getElementById('endereco').value = data.logradouro;
                                    document.getElementById('bairro').value = data.bairro;
                                    document.getElementById('cidade').value = data.localidade;
                                    document.getElementById('estadosBrasileiros').value = data.uf;

                                    // Torna os campos somente leitura e muda o fundo para cinza claro
                                    bloquearInput('endereco');
                                    bloquearInput('bairro');
                                    bloquearInput('cidade');
                                    bloquearInput('estadosBrasileiros');

                                    // Formata o CEP para o padrão 78000-000
                                    document.getElementById('cep').value = formatarCep(cep);
                                })
                                .catch(error => console.error('Erro:', error));
                            }
                        }

                        // Função para tornar o campo somente leitura e mudar o fundo para cinza claro
                        function bloquearInput(id) {
                            const input = document.getElementById(id);
                            input.readOnly = true;
                            input.classList.add('input-preenchido'); // Adiciona uma classe para o fundo cinza claro
                        }

                        // Função para formatar o CEP
                        function formatarCep(cep) {
                            return cep.replace(/(\d{5})(\d{3})/, '$1-$2');
                        }
                    </script>";
   return $scriptCep;
}

function script_funcao_login()
{
   $scriptLogin = '
            function login() {
                const logincpf = document.getElementById("cpfInput").value;
                const loginpw  = document.getElementById("password1").value;
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
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.mensagem || "Erro desconhecido");
                        });
                    }
                    return response.json();
                })
                .then(resposta => {
                    if (resposta.mensagem == "ACEITO") {
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
                        form.submit();
                    } else if (resposta.mensagem == "SENHA-INCORRETA") {
                        alert("A senha informada está incorreta.");
                    } else if (resposta.mensagem == "CPF-INEXISTENTE") {
                        alert("Usuário não cadastrado, se você é novo por aqui crie sua conta.");
                    } else if (resposta.mensagem == "NAO-CONFIRMADA") {
                        alert("Conta não confirmada, verifique seu email e tente novamente.");       
                    }
                })
                .catch(error => {
                    alert("Erro: " + error.message);
                });
            }';

   return $scriptLogin;
}

function script_carregar_conteudo($cpfcli, $idecli, $senha)
{
   return '
            function carregarConteudo(pagina) {
                let dados = {};
    
                if (pagina === "index.php") {
                    dados = {
                        logincpf: "' . $cpfcli . '",
                        loginpw: "' . $senha . '"
                    };
                } else {
                    dados = {
                        idecli: "' . $idecli . '",
                        loginpw: "' . $senha . '"
                    };
                }
    
                const form = document.createElement("form");
                form.method = "POST";
                form.action = pagina;
    
                Object.keys(dados).forEach(chave => {
                    const input = document.createElement("input");
                    input.type = "hidden";
                    input.name = chave;
                    input.value = dados[chave];
                    form.appendChild(input);
                });
    
                document.body.appendChild(form);
                form.submit();
            }
        ';
}

function cntdSelect_AnoDtNsc()
{
   $vsCntdAno = '';
   $currentYear = date('Y'); // Obtém o ano atual
   $minYear = $currentYear - 18; // Calcula o ano mínimo (ano atual - 18)

   // Loop para gerar os anos de forma decrescente, começando do ano mínimo
   for ($ano = $minYear; $ano >= 1950; $ano--) {
      $vsCntdAno .= '<option value="' . $ano . '">' . $ano . '</option>';
   }

   return $vsCntdAno;
}

function cntdSelect_DiaDtNsc()
{
   $vsCntdDia = ''; // Variável renomeada para $vsCntdDia
   for ($dia = 1; $dia <= 31; $dia++) {
      // Formata o dia para garantir dois dígitos (01, 02, ..., 31)
      $vsCntdDia .= '<option value="' . str_pad($dia, 2, '0', STR_PAD_LEFT) . '">' . str_pad($dia, 2, '0', STR_PAD_LEFT) . '</option>';
   }
   return $vsCntdDia;
}
