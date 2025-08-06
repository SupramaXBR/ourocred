function mostrarRelatorioRentabilidadeCapital() {
   const btn = 'btnBuscarRelatorioCapital';
   const spinner = 'spinnerRelatorioCapital';
   bloquearBotao(btn, spinner);

   let cpf = document.getElementById('inputCpf').value.replace(/\D/g, '');
   if (cpf.length === 11) {
      cpf = cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
   }

   const dataInicio = document.getElementById('inputDataInicio').value;
   const dataFim = document.getElementById('inputDataFim').value;

   const modal = new bootstrap.Modal(document.getElementById('modalRelatorioRentabilidadeCapital'));
   const container = document.getElementById('relatorioRentabilidadeConteudoCapital');
   container.innerHTML = `
      <div class="text-center text-muted">
         <div class="loaderbtn-sm mb-2" style="margin: auto;"></div><br>
         Carregando relatório de capital...
      </div>
   `;
   modal.show();

   $.ajax({
      url: 'back-end/relatorio-rentabilidadecapital.php',
      type: 'POST',
      dataType: 'json',
      data: {
         cpf: cpf || '',
         data_inicio: dataInicio || '',
         data_fim: dataFim || ''
      },
      success: function (data) {
         if (!data.relatorio || data.relatorio.length === 0) {
            container.innerHTML = `
               <div class="text-center text-muted">
                  <i class="bi bi-emoji-frown fs-2"></i><br>
                  Nenhuma movimentação encontrada para os filtros aplicados.
               </div>
            `;
            desbloquearBotao(btn, spinner);
            return;
         }

         const formatMoeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

         let html = `
            <div class="table-responsive">
            <table class="table table-sm table-bordered tabela-relatorio align-middle text-center">
               <thead class="table-light">
                  <tr>
                     <th>Data</th>
                     <th>Cliente</th>
                     <th>CPF</th>
                     <th>Tipo</th>
                     <th>Carteira</th>
                     <th>Valor (R$)</th>
                     <th>Lucro</th>
                  </tr>
               </thead>
               <tbody>
         `;

         let totalLucro = 0;

         data.relatorio.forEach(item => {
            const valor_total = parseFloat(item.valor_total || 0);
            const lucro = parseFloat(item.lucro || 0);
            totalLucro += lucro;

            const tipoIcone = item.tipo === 'Entrada'
               ? `<i class="bi bi-arrow-up-circle-fill text-success me-1"></i>Entrada`
               : `<i class="bi bi-arrow-down-circle-fill text-danger me-1"></i>Saída`;

            html += `
               <tr>
                  <td>${formatarDataBR(item.data)}</td>
                  <td>${item.cliente}</td>
                  <td>${item.cpf}</td>
                  <td class="fw-semibold">${tipoIcone}</td>
                  <td>${item.carteira}</td>
                  <td>${formatMoeda.format(valor_total)}</td>
                  <td class="${lucro >= 0 ? 'text-success' : 'text-danger'} fw-bold">
                     ${formatMoeda.format(lucro)}
                  </td>
               </tr>
            `;
         });

         html += `
               </tbody>
            </table>
            </div>
            <div class="text-end mt-3 resumo-lucro">
               <i class="bi bi-piggy-bank-fill me-1"></i>
               Lucro total da plataforma: <span class="${totalLucro >= 0 ? 'text-success' : 'text-danger'}"> ${formatMoeda.format(totalLucro)}</span>
            </div>
         `;

         container.innerHTML = html;
         desbloquearBotao(btn, spinner);
      },
      error: function (xhr, status, error) {
         console.error("Erro AJAX:", error);
         container.innerHTML = `
            <div class="alert alert-danger">
               <i class="bi bi-x-circle-fill me-2"></i> Erro ao carregar relatório: ${xhr.status} ${xhr.statusText}
            </div>
         `;
         desbloquearBotao(btn, spinner);
      }
   });
}
