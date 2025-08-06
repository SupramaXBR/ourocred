<html>
<?php
session_start();

include_once "../uses/components.php";
include_once "../uses/funcoes.php";
include_once "../uses/conexao.php";

//echo head('../uses/estilo.css', '../imagens/favicon.ico');

// Gera o token de sessão, caso ainda não exista
if (!isset($_SESSION['token'])) {
   $_SESSION['token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['token'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logincpf'])) {
   $cpfcli = $_POST['logincpf'];
   $senha = $_POST['loginpw'];

   $vsCodLog = inserirLogLogin();

   try {
      $sql = "SELECT 
                    IDECLI, CODCLI, CPFCLI, RGCLI, NOMCLI, DTANSC, MAECLI, NUMTEL, EMAIL, 
                    CEPCLI, ENDCLI, NUMCSA, CPLEND, BAICLI, UFDCLI, CODMUNIBGE, MUNCLI, 
                    MD5PW, IMG64, STACTAATV, STACMFEML, STATRM, DTAINS, DTAALT 
                FROM clientes 
                WHERE CPFCLI = :cpfcli";

      $stmt = $pdo->prepare($sql);
      $stmt->bindParam(':cpfcli', $cpfcli, PDO::PARAM_STR);
      $stmt->execute();

      $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($cliente) {
         // Verifica senha e status da conta
         if (md5($senha) !== $cliente['MD5PW'] || $cliente['STACTAATV'] !== 'S') {
            atualizarLogLogin($vsCodLog, '', 'N');
            header("Location: ../index.php");
            exit;
         }

         // Amarra o token e o timestamp de último acesso à sessão do cliente
         $cliente['token'] = $token;
         $cliente['ultimo_acesso'] = time(); // <-- Aqui adicionamos o timestamp

         // Salva cliente na sessão
         $_SESSION['cliente'] = $cliente;

         if (!atualizarLogLogin($vsCodLog, $cliente['IDECLI'], 'S')) {
            header("Location: ../index.php");
            exit;
         }

         // Redireciona para home.php (PRG aplicado)
         header("Location: home.php");
         exit;
      } else {
         atualizarLogLogin($vsCodLog, '', 'N');
         header("Location: ../index.php");
         exit;
      }
   } catch (PDOException $e) {
      atualizarLogLogin($vsCodLog, '', 'N');
      header("Location: ../index.php");
      exit;
   }
} else {
   header("Location: ../index.php");
   exit;
}
?>

</html>