<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");

include_once '../../uses/conexao.php';
include_once '../../uses/funcoes.php';

if (!$pdo) {
   http_response_code(500);
   die(json_encode(['mensagem' => 'Erro de conexão com o banco de dados.']));
}

// Segurança por Referer
$dominiosPermitidos = [
   'www.ourocreddtvm.com.br',
   'ourocreddtvm.com.br',
   '192.168.18.88'
];

$referer = $_SERVER['HTTP_REFERER'] ?? '';
if ($referer) {
   $hostReferer = parse_url($referer, PHP_URL_HOST);
   if (!in_array($hostReferer, $dominiosPermitidos)) {
      error_log("Acesso negado ao relatório: $hostReferer");
      die("Acesso negado!");
   }
}

// Sessão do admin obrigatória
if (!isset($_SESSION['admin'])) {
   http_response_code(401);
   echo json_encode(['mensagem' => 'Sessão expirada ou inválida']);
   exit;
}

$codemp = 1;
$tempoLimite = retornaTempoLimite($codemp);
if ((time() - ($_SESSION['admin']['ultimo_acesso'] ?? 0)) > $tempoLimite) {
   session_destroy();
   http_response_code(401);
   echo json_encode(['mensagem' => 'Sessão expirada por inatividade']);
   exit;
}

$_SESSION['admin']['ultimo_acesso'] = time();

try {
   // Lê via POST
   $cpfFiltro = $_POST['cpf'] ?? null;
   $dataInicio = $_POST['data_inicio'] ?? null;
   $dataFim = $_POST['data_fim'] ?? null;
   $param = [];

   $sql = "
        SELECT 
            clientes_saldo.IDEMOV,
            clientes.NOMCLI,
            clientes.CPFCLI,
            clientes_saldo.IDECLI,
            clientes_saldo.DTAMOV,
            clientes_saldo.TPOMOV,
            clientes_saldo.carteira,
            clientes_saldo.VLRBSECLC,
            clientes_saldo.saldo_simple,
            clientes_saldo.saldo_classic,
            clientes_saldo.saldo_standard,
            clientes_saldo.saldo_premium,
            clientes_saldo.saldo_reais
        FROM clientes_saldo
        INNER JOIN clientes ON clientes_saldo.IDECLI = clientes.IDECLI
        WHERE clientes_saldo.STAMOV = 'A'
          AND clientes_saldo.TPOMOV IN ('Entrada', 'Saida')
    ";

   if (!empty($cpfFiltro)) {
      $sql .= " AND clientes.CPFCLI = :cpf ";
      $param[':cpf'] = $cpfFiltro;
   }

   if (!empty($dataInicio) && !empty($dataFim)) {
      $sql .= " AND clientes_saldo.DTAMOV BETWEEN :inicio AND :fim ";
      $param[':inicio'] = $dataInicio . ' 00:00:00';
      $param[':fim'] = $dataFim . ' 23:59:59';
   }

   $sql .= " ORDER BY clientes_saldo.DTAMOV DESC";

   $stmt = $pdo->prepare($sql);
   $stmt->execute($param);
   $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

   $relatorio = [];

   foreach ($rows as $row) {
      $carteira = strtolower($row['carteira']);
      $quantidade = 0.0;

      if ($carteira === 'simple') $quantidade = $row['saldo_simple'];
      elseif ($carteira === 'classic') $quantidade = $row['saldo_classic'];
      elseif ($carteira === 'standard') $quantidade = $row['saldo_standard'];
      elseif ($carteira === 'premium') $quantidade = $row['saldo_premium'];
      elseif ($carteira === 'reais') $quantidade = $row['saldo_reais'];

      if ($row['TPOMOV'] === 'Saida') {
         $quantidade = abs($quantidade);
         $valor_total = $quantidade * $row['VLRBSECLC'];

         $relatorio[] = [
            'cliente'        => $row['NOMCLI'],
            'cpf'            => $row['CPFCLI'],
            'data'           => $row['DTAMOV'],
            'tipo'           => 'Saida',
            'carteira'       => $row['carteira'],
            'quantidade'     => $quantidade,
            'valor_unitario' => $row['VLRBSECLC'],
            'valor_total'    => $valor_total,
            'lucro'          => $valor_total
         ];
      }

      if ($row['TPOMOV'] === 'Entrada') {
         $quantidade = abs($quantidade);
         $valor_total = $quantidade * $row['VLRBSECLC'];

         $relatorio[] = [
            'cliente'        => $row['NOMCLI'],
            'cpf'            => $row['CPFCLI'],
            'data'           => $row['DTAMOV'],
            'tipo'           => 'Entrada',
            'carteira'       => $row['carteira'],
            'quantidade'     => $quantidade,
            'valor_unitario' => $row['VLRBSECLC'],
            'valor_total'    => $valor_total,
            'lucro'          => -$valor_total
         ];
      }
   }

   echo json_encode(['relatorio' => $relatorio]);
} catch (PDOException $e) {
   http_response_code(500);
   echo json_encode(['mensagem' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
