<?php
require_once 'uses/funcoes.php';
$valorDesconto = obterValorDescGramaVendida();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Balanço da Carteira do Cliente - Últimos 6 Meses</title>

   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

   <style>
      body {
         font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
         display: flex;
         align-items: center;
         justify-content: center;
         min-height: 100vh;
         padding: 20px;
         background-color: #f0f2f5;
      }

      .container {
         width: 95%;
         max-width: 1000px;
         background-color: #ffffff;
         border-radius: 10px;
         box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
         padding: 25px;
      }

      #loading {
         text-align: center;
         font-size: 1.5em;
         color: #555;
      }
   </style>
</head>

<body>
   <div class="container">
      <canvas id="graficoSemestral"></canvas>
      <p id="loading">Calculando e buscando dados para os últimos 6 meses. Por favor, aguarde...</p>
   </div>

   <script>
      const descontoPorGrama = <?php echo json_encode($valorDesconto); ?>;

      const transacoesHipoteticas = [{
            comprados: 10,
            vendidos: 5
         },
         {
            comprados: 15,
            vendidos: 0
         },
         {
            comprados: 5,
            vendidos: 20.3
         },
         {
            comprados: 25.5,
            vendidos: 10
         },
         {
            comprados: 0,
            vendidos: 15
         },
         {
            comprados: 12.123,
            vendidos: 12.1
         },
      ];

      const FATOR_ONCA_GRAMA = 31.1034768;
      const seuToken = '85d4e2aa9a21f8b0cece488a26fc1588e9a0e1ab57deb3110625c12558b05da3';

      async function gerarGraficoSeisMeses() {
         try {
            const loadingElement = document.getElementById('loading');

            const hoje = new Date();
            const mesesParaCalcular = [];
            const nomesDosMeses = [];

            for (let i = 6; i > 0; i--) {
               const dataAlvo = new Date(hoje.getFullYear(), hoje.getMonth() - i, 1);
               const primeiroDia = new Date(dataAlvo.getFullYear(), dataAlvo.getMonth(), 1);
               const ultimoDia = new Date(dataAlvo.getFullYear(), dataAlvo.getMonth() + 1, 0);

               mesesParaCalcular.push({
                  inicio: `${primeiroDia.getFullYear()}${(primeiroDia.getMonth() + 1).toString().padStart(2, '0')}${primeiroDia.getDate().toString().padStart(2, '0')}`,
                  fim: `${ultimoDia.getFullYear()}${(ultimoDia.getMonth() + 1).toString().padStart(2, '0')}${ultimoDia.getDate().toString().padStart(2, '0')}`,
                  dias: ultimoDia.getDate()
               });

               const mesAbreviado = primeiroDia.toLocaleString('pt-BR', {
                  month: 'short'
               }).replace('.', '');
               nomesDosMeses.push(mesAbreviado.charAt(0).toUpperCase() + mesAbreviado.slice(1));
            }

            const precosMediosMensais = await Promise.all(mesesParaCalcular.map(mes => calcularPrecoMedioMensal(mes.inicio, mes.fim, mes.dias)));

            const dadosEntradasCliente = [];
            const dadosSaidasCliente = [];
            const labelsDoGrafico = []; // O rótulo final será construído aqui

            // ======================================================================================
            // ALTERAÇÃO AQUI: Montando o rótulo final com 3 linhas dentro do loop principal
            // ======================================================================================
            for (let i = 0; i < 6; i++) {
               const precoMedioDoMes = precosMediosMensais[i];
               const transacoesDoMes = transacoesHipoteticas[i];

               // Calcula os valores para as barras
               if (precoMedioDoMes === 0) {
                  dadosEntradasCliente.push(0);
                  dadosSaidasCliente.push(0);
               } else {
                  const precoDeVendaComDesconto = precoMedioDoMes - descontoPorGrama;
                  dadosEntradasCliente.push(transacoesDoMes.comprados * precoMedioDoMes);
                  dadosSaidasCliente.push(transacoesDoMes.vendidos * precoDeVendaComDesconto);
               }

               // Formata os textos para o rótulo
               const precoFormatado = new Intl.NumberFormat('pt-BR', {
                  style: 'currency',
                  currency: 'BRL'
               }).format(precoMedioDoMes);
               const comprasFormatado = transacoesDoMes.comprados.toFixed(4).replace('.', ',');
               const vendasFormatado = transacoesDoMes.vendidos.toFixed(4).replace('.', ',');

               // Cria o rótulo de três linhas
               labelsDoGrafico.push([
                  nomesDosMeses[i],
                  `[${precoFormatado}/g]`,
                  `[C: ${comprasFormatado}g - V: ${vendasFormatado}g]`
               ]);
            }

            loadingElement.style.display = 'none';
            const ctx = document.getElementById('graficoSemestral').getContext('2d');
            new Chart(ctx, {
               type: 'bar',
               data: {
                  labels: labelsDoGrafico, // O novo rótulo de 3 linhas entra aqui
                  datasets: [{
                        label: 'Entradas na Carteira (Compras)',
                        backgroundColor: '#f8b739',
                        data: dadosEntradasCliente
                     },
                     {
                        label: 'Saídas da Carteira (Vendas)',
                        backgroundColor: '#3b7ddd',
                        data: dadosSaidasCliente
                     }
                  ]
               },
               options: {
                  responsive: true,
                  plugins: {
                     title: {
                        display: true,
                        text: 'Balanço da Carteira do Cliente - Últimos 6 Meses',
                        font: {
                           size: 20
                        }
                     },
                     tooltip: {
                        callbacks: {
                           label: (context) => new Intl.NumberFormat('pt-BR', {
                              style: 'currency',
                              currency: 'BRL'
                           }).format(context.raw)
                        }
                     }
                  },
                  scales: {
                     x: {
                        ticks: {
                           font: {
                              size: 10
                           }
                        }
                     },
                     y: {
                        beginAtZero: true,
                        ticks: {
                           callback: (value) => new Intl.NumberFormat('pt-BR', {
                              style: 'currency',
                              currency: 'BRL'
                           }).format(value)
                        }
                     }
                  }
               }
            });

         } catch (error) {
            document.getElementById('loading').textContent = `Erro ao gerar o gráfico: ${error.message}`;
            document.getElementById('loading').style.color = 'red';
            console.error(error);
         }
      }

      // Funções auxiliares completas
      function calcularMedia(dados, campo = 'bid') {
         if (!dados || dados.length === 0) return 0;
         const soma = dados.reduce((acc, item) => acc + parseFloat(item[campo]), 0);
         return soma / dados.length;
      }
      async function calcularPrecoMedioMensal(dataInicio, dataFim, diasNoMes) {
         const urlOuro = `https://economia.awesomeapi.com.br/json/daily/XAU-USD/${diasNoMes}?start_date=${dataInicio}&end_date=${dataFim}&token=${seuToken}`;
         const urlCambio = `https://economia.awesomeapi.com.br/json/daily/USD-BRL/${diasNoMes}?start_date=${dataInicio}&end_date=${dataFim}&token=${seuToken}`;
         const [respostaOuro, respostaCambio] = await Promise.all([fetch(urlOuro), fetch(urlCambio)]);
         if (!respostaOuro.ok || !respostaCambio.ok) throw new Error(`Falha na API para o mês ${dataInicio.substring(4,6)}`);
         const dadosOuro = await respostaOuro.json();
         const dadosCambio = await respostaCambio.json();
         if (dadosOuro.length === 0 || dadosCambio.length === 0) return 0;
         const mediaOncaUsd = calcularMedia(dadosOuro, 'bid');
         const mediaCambioBrl = calcularMedia(dadosCambio, 'bid');
         const mediaGramaUsd = mediaOncaUsd / FATOR_ONCA_GRAMA;
         return mediaGramaUsd * mediaCambioBrl;
      }

      gerarGraficoSeisMeses();
   </script>
</body>

</html>