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

$dominiosPermitidos = ['www.ourocreddtvm.com.br', 'ourocreddtvm.com.br', '192.168.18.88'];
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if ($referer) {
   $hostReferer = parse_url($referer, PHP_URL_HOST);
   if (!in_array($hostReferer, $dominiosPermitidos)) {
      error_log("Acesso negado ao relatório financeiro: $hostReferer");
      die("Acesso negado!");
   }
}

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

$cpfFiltro = isset($_GET['cpf']) ? trim($_GET['cpf']) : '';
$param = [];

try {
   $sql = "
      SELECT 
         clientes.IDECLI,
         clientes.NOMCLI,
         clientes.CPFCLI,
         clientes_saldo.TPOMOV,
         clientes_saldo.VLRBSECLC,
         clientes_saldo.saldo_reais,
         clientes_saldo.saldo_simple,
         clientes_saldo.saldo_classic,
         clientes_saldo.saldo_standard,
         clientes_saldo.saldo_premium
      FROM clientes_saldo
      INNER JOIN clientes ON clientes.IDECLI = clientes_saldo.IDECLI
      WHERE clientes_saldo.STAMOV <> 'N'
   ";

   if (!empty($cpfFiltro)) {
      $sql .= " AND clientes.CPFCLI = :cpf ";
      $param[':cpf'] = $cpfFiltro;
   }

   $sql .= " ORDER BY clientes.IDECLI, clientes_saldo.DTAMOV ";

   $stmt = $pdo->prepare($sql);
   $stmt->execute($param);
   $movimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

   $clientes = [];

   foreach ($movimentos as $mov) {
      $id = $mov['IDECLI'];

      if (!isset($clientes[$id])) {
         $clientes[$id] = [
            'IDECLI' => $id,
            'NOMCLI' => $mov['NOMCLI'],
            'CPFCLI' => $mov['CPFCLI'],
            'total_entrada' => 0,
            'total_saida' => 0,
            'total_compra' => 0,
            'total_venda' => 0,
            'saldo_estimado' => 0
         ];
      }

      $tipo = $mov['TPOMOV'];
      $vlr = floatval($mov['VLRBSECLC']);
      $sr = floatval($mov['saldo_reais']);
      $gs = abs(floatval($mov['saldo_simple']));
      $gc = abs(floatval($mov['saldo_classic']));
      $gst = abs(floatval($mov['saldo_standard']));
      $gp = abs(floatval($mov['saldo_premium']));
      $gramas = $gs + $gc + $gst + $gp;

      if ($tipo == 'Entrada') {
         $clientes[$id]['total_entrada'] += $sr;
      } elseif ($tipo == 'Saida') {
         $clientes[$id]['total_saida'] += abs($sr);
      } elseif ($tipo == 'Compra') {
         $clientes[$id]['total_compra'] += ($vlr * $gramas);
      } elseif ($tipo == 'Venda') {
         $clientes[$id]['total_venda'] += ($vlr * $gramas);
      }

      $clientes[$id]['saldo_estimado'] += $sr;
   }

   $resumo = [];
   foreach ($clientes as $c) {
      $lucro = $c['total_venda'] - $c['total_compra'];

      $resumo[] = [
         'IDECLI' => $c['IDECLI'],
         'NOMCLI' => $c['NOMCLI'],
         'CPFCLI' => $c['CPFCLI'],
         'total_entrada' => number_format($c['total_entrada'], 2, '.', ''),
         'total_saida' => number_format($c['total_saida'], 2, '.', ''),
         'total_compra' => number_format($c['total_compra'], 2, '.', ''),
         'total_venda' => number_format($c['total_venda'], 2, '.', ''),
         'saldo_estimado' => number_format($c['saldo_estimado'], 2, '.', ''),
         'lucro_plataforma' => number_format($lucro, 2, '.', '')
      ];
   }

   echo json_encode(['resumo' => $resumo]);
} catch (PDOException $e) {
   http_response_code(500);
   echo json_encode(['mensagem' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
