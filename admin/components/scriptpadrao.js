function carregarConteudo(pagina) {
   window.location.href = pagina;
}

function bloquearBotao(botaoId, spinnerId) {
   let botao = document.getElementById(botaoId);
   let spinner = document.getElementById(spinnerId);
   if (botao && spinner) {
      botao.disabled = true;
      spinner.style.display = 'inline-block';
   }
}

function desbloquearBotao(botaoId, spinnerId) {
   let botao = document.getElementById(botaoId);
   let spinner = document.getElementById(spinnerId);
   if (botao && spinner) {
      botao.disabled = false;
      spinner.style.display = 'none';
   }
}