function carregarConteudo(pagina) {
   // Redireciona diretamente via GET
   window.location.href = pagina;
}

const ctxBar = document.getElementById('graficoBarras').getContext('2d');
new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago'],
        datasets: [
            {
                label: 'Entradas',
                backgroundColor: '#f8b739',
                data: [500, 700, 600, 900, 400, 600, 800, 650]
            },
            {
                label: 'Saídas',
                backgroundColor: '#3b7ddd',
                data: [300, 400, 450, 500, 350, 550, 600, 450]
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Gráfico de linha (performance)
const ctxLine = document.getElementById('graficoLinha').getContext('2d');
new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago'],
        datasets: [{
            label: 'Crescimento',
            data: [2, 4, 3.5, 5, 6, 5.5, 6.5, 7],
            fill: true,
            backgroundColor: 'rgba(59, 125, 221, 0.2)',
            borderColor: '#3b7ddd',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
