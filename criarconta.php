<!DOCTYPE html>
<html lang="pt-br">

<head>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
   <link rel="stylesheet" href="css/criarconta.css">
   <meta charset="UTF-8">
</head>

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

   <div class="container">
      <!-- Alerta com o título centralizado -->
      <div class="alert alert-primary" role="alert">
         <div class="center">FORMULARIO DE CRIAÇÃO DE CONTA</div>
      </div>
      </form>
      <div class="form-group">
         <label for="inputcpf">CPF</label>
         <input type="text" class="form-control" id="inputcpf" placeholder="Digite seu CPF" maxlength="14" onblur="vaziovalidarcpf('inputcpf' , 'errorcpf' , 'errorcpf1')">
         <small id="errorcpf" class="form-text text-danger" style="display: none;">CPF inválido</small>
         <small id="errorcpf1" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
      </div>
      <div class="form-group">
         <label for="rginput">RG</label>
         <input type="text" class="form-control" id="rginput" placeholder="Digite seu RG sem traços" onblur="inputvazio('rginput' , 'rgerror')">
         <small id="rgerror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
      </div>
      <div class="form-group">
         <label for="nomeinput">Nome</label>
         <input type="text" class="form-control" id="nomeinput" placeholder="Digite seu nome completo" onblur="inputvazio('nomeinput' , 'nomeerror')">
         <small id="nomeerror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
      </div>
      <div class="form-group">
         <label for="dataNascimento" class="form-label">Data de Nascimento</label>
         <div class="d-flex justify-content-between">
            <div class="col-md-3">
               <select id="slcDia" class="form-control" onblur="inputvazio('slcDia' , 'slcDiaError')">
                  <option value="" disabled selected>Selecione o dia</option>
                  <?php echo cntdSelect_DiaDtNsc(); ?>
               </select>
               <small id="slcDiaError" class="form-text text-danger mt-2" style="display: none;">* Campo Obrigatório</small>
            </div>
            <div class="col-md-3">
               <select id="slcMes" class="form-control" onblur="inputvazio('slcMes' , 'slcMesError')">
                  <option value="" disabled selected>Selecione o mês</option>
                  <!-- Opções para os meses -->
                  <option value="01">Janeiro</option>
                  <option value="02">Fevereiro</option>
                  <option value="03">Março</option>
                  <option value="04">Abril</option>
                  <option value="05">Maio</option>
                  <option value="06">Junho</option>
                  <option value="07">Julho</option>
                  <option value="08">Agosto</option>
                  <option value="09">Setembro</option>
                  <option value="10">Outubro</option>
                  <option value="11">Novembro</option>
                  <option value="12">Dezembro</option>
               </select>
               <small id="slcMesError" class="form-text text-danger mt-2" style="display: none;">* Campo Obrigatório</small>
            </div>
            <div class="col-md-3">
               <select id="slcAno" class="form-control" onblur="inputvazio('slcAno' , 'slcAnoError')">
                  <option value="" disabled selected>Selecione o ano</option>
                  <?php echo cntdSelect_AnoDtNsc(); ?>
               </select>
               <small id="slcAnoError" class="form-text text-danger mt-2" style="display: none;">* Campo Obrigatório</small>
            </div>
         </div>
      </div>
      <div class="form-group">
         <label for="nommaeinput">Nome da Mãe</label>
         <input type="text" class="form-control" id="nommaeinput" placeholder="Digite o nome completo da sua mãe" onblur="inputvazio('nommaeinput' , 'nommaeerror')">
         <small id="nommaeerror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
      </div>
      <div class="form-group">
         <label for="numtelinput">Telefone</label>
         <input type="text" class="form-control" id="nuntelinput" placeholder="Digite seu Numero de Telefone" onblur="inputvazio('numtelinput' , 'numtelerror')">
         <small id="numtelerror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
      </div>
      <div class="form-group">
         <label for="emailinput">e-Mail</label>
         <input type="text" class="form-control" id="emailinput" placeholder="Digite seu email" onblur="valirdarvazioemail('emailinput' , 'emailerror' , 'emailinval')">
         <small id="emailerror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
         <small id="emailinval" class="form-text text-danger" style="display: none;">* e-Mail Invalido</small>
      </div>
      <div class="form-group">
         <label for="cep">CEP</label>
         <input type="text" class="form-control" id="cep" placeholder="Digite o CEP da sua rua" onblur="vaziobuscarcep('cep')">
         <div id="mensagemErroCep" class="mensagem-erro"></div>
         <div id="loader-cep" class="loader" style="display: none;"></div>
      </div>
      <div class="form-group">
         <label for="endereco">Endereço</label>
         <input type="text" class="form-control" id="endereco" placeholder="Digite seu endereço (sem o número)" onblur="inputvazio('endereco' , 'enderror')">
         <small id="enderror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
      </div>
      <div class="form-group">
         <label for="numcsainput">Numero</label>
         <input type="text" class="form-control" id="numcsainput" placeholder="Digite o numero da sua casa" onblur="inputvazio('numcsainput' , 'numcsaerror')">
         <small id="numcsaerror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
      </div>
      <div class="form-group">
         <label for="exampleFormControlInput1">Complemento</label>
         <input type="text" class="form-control" id="cplinput" placeholder="Digite o complemento do seu endereço">
      </div>
      <div class="form-group">
         <label for="bairro">Bairro</label>
         <input type="text" class="form-control" id="bairro" placeholder="Digite o nome do seu bairro" onblur="inputvazio('bairro' , 'baierror')">
         <small id="baierror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
      </div>
      <div class="form-group">
         <label for="estadosBrasileiros">Estados da Federação</label>
         <select class="form-control" id="estadosBrasileiros" onchange="carregarCidades()">
            <option value="">Selecione o Estado</option>
            <option value="AC">Acre (AC)</option>
            <option value="AL">Alagoas (AL)</option>
            <option value="AP">Amapá (AP)</option>
            <option value="AM">Amazonas (AM)</option>
            <option value="BA">Bahia (BA)</option>
            <option value="CE">Ceará (CE)</option>
            <option value="DF">Distrito Federal (DF)</option>
            <option value="ES">Espírito Santo (ES)</option>
            <option value="GO">Goiás (GO)</option>
            <option value="MA">Maranhão (MA)</option>
            <option value="MT">Mato Grosso (MT)</option>
            <option value="MS">Mato Grosso do Sul (MS)</option>
            <option value="MG">Minas Gerais (MG)</option>
            <option value="PA">Pará (PA)</option>
            <option value="PB">Paraíba (PB)</option>
            <option value="PR">Paraná (PR)</option>
            <option value="PE">Pernambuco (PE)</option>
            <option value="PI">Piauí (PI)</option>
            <option value="RJ">Rio de Janeiro (RJ)</option>
            <option value="RN">Rio Grande do Norte (RN)</option>
            <option value="RS">Rio Grande do Sul (RS)</option>
            <option value="RO">Rondônia (RO)</option>
            <option value="RR">Roraima (RR)</option>
            <option value="SC">Santa Catarina (SC)</option>
            <option value="SP">São Paulo (SP)</option>
            <option value="SE">Sergipe (SE)</option>
            <option value="TO">Tocantins (TO)</option>
         </select>
         <small id="ufdeerror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
      </div>
      <div class="form-group">
         <label for="cidade">Cidade</label>
         <select class="form-control" id="cidade" onblur="inputvazio('cidade' , 'cdderror')">
            <option value="">Selecione a Cidade</option>
         </select>
         <small id="cdderror" class="form-text text-danger" style="display: none;">* Campo Obrigatorio</small>
      </div>
      <div class="form-group">
         <label for="senhainput">Senha</label>
         <input type="password" class="form-control" id="senhainput" placeholder="Digite sua senha de acesso" onblur="validarSenha('senhainput', 'senhaError')">
         <small id="senhaError" class="form-text text-danger" style="display: none;">A senha deve conter pelo menos 1 letra maiúscula, 1 caractere especial e ter pelo menos 10 dígitos.</small>
      </div>
      <div class="form-group">
         <label for="repsenhainput">Repita a Senha</label>
         <input type="password" class="form-control" id="repsenhainput" placeholder="Repita sua Senha de acesso" onblur="confirmrepsenhavalidarsenha('senhainput' ,'repsenhainput', 'repsenhaError', 'repsenhaError1')">
         <small id="repsenhaError" class="form-text text-danger" style="display: none;">A senha deve conter pelo menos 1 letra maiúscula, 1 caractere especial e ter pelo menos 10 dígitos.</small>
         <small id="repsenhaError1" class="form-text text-danger" style="display: none;">Os campo <b>senha</b> e <b>repetir senha</b> nao são iguais.</small>
      </div>
      <div class="form-group form-check">
         <input type="checkbox" class="form-check-input" id="chktermos">
         <label class="form-check-label" for="chktermos">Eu li e concordo com os <a href="#" data-toggle="modal" data-target="#termsModal">Termos de Uso e Política de Privacidade</a>.</label>
         <?php echo modaltermosdeuso(); ?>
         <small id="chkerror" class="form-text text-danger" style="display: none;">Se concorda com os termos e deseja criar uma conta, voce precisa marcar este campo</small>
      </div>
      <div class="form-group">
         <h1> ESPAÇO PARA O RECAPTCHA </h1>
      </div>

      <div id="resultado"></div> <!-- DIV de resposta do da requisição AJAX feita em ajax/gravardados-clientes.php -->


      <div class="form-group">
         <button type="button" id="btnCriarConta" class="btn btn-primary btn-lg btn-block" onclick="criarconta()">Criar Conta <span id="spinnerbtn" class="loaderbtn" style="display: none;"></span> </button>
      </div>
      <div class="modal fade" id="erroModal" tabindex="-1" aria-labelledby="erroModalLabel" aria-hidden="true">
         <div class="modal-dialog">
            <div class="modal-content">
               <div class="modal-header">
                  <h5 class="modal-title" id="erroModalLabel">OuroCred</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
               </div>
               <div class="modal-body">
                  <h3 id="h3msg" style="text-align: center;"> TEXTO MODELO </h2>
               </div>
               <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
               </div>
            </div>
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


   <script src="js/criarconta.js"></script>
   <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
   <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

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
</body>

</html>