<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tabela de Rentabilidade</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .table-responsive {
      font-size: 1rem;
    }
    @media (max-width: 992px) {
      .table-responsive {
        font-size: 0.875rem;
      }
    }
    @media (max-width: 768px) {
      .table-responsive {
        font-size: 0.75rem;
      }
    }
    @media (max-width: 576px) {
      .table-responsive {
        font-size: 0.625rem;
      }
    }
  </style>
</head>
<body>
  <div class="container my-4">
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
