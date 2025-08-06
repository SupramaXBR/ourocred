<html>
<?php 
session_start();

include_once "../uses/components.php";    
include_once "../uses/funcoes.php";
include_once "../uses/conexao.php";

// Gera o token de sessão, caso ainda não exista
if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['token'];

// Verifica se a requisição é POST e se os dados foram enviados
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['usuario']) && isset($_POST['senha'])) {
    $usuario = $_POST['usuario'];
    $senha = $_POST['senha'];
    
    // Exemplo: definir o código da empresa (ajuste conforme sua aplicação)
    $vsCodEmp = 1;
    
    // Obtém as credenciais do admin para a empresa
    $credenciais = obterCredenciaisAdminEmpresa($vsCodEmp);
    
    if ($credenciais) {
        // Verifica se o usuário e a senha estão corretos (a senha é verificada via MD5)
        if ($usuario === $credenciais['USRADMIN'] && md5($senha) === $credenciais['PWADMIN']) {
            // Armazena os dados do admin na sessão, incluindo token e timestamp do último acesso
            $_SESSION['admin'] = [
                'usuario'       => $usuario,
                'senha'         => $senha,
                'token'         => $token,
                'ultimo_acesso' => time()
            ];
            
            // Redireciona para a página principal do admin (altere o destino conforme necessário)
            header("Location: dashboard.php");
            exit;
        } else {
            // Usuário ou senha incorretos
            echo "<script>
                    alert('Usuário ou senha incorretos');
                    window.location.href = '" . $_SERVER['HTTP_REFERER'] . "';
                  </script>";
            exit;
        }
    } else {
        // Empresa não encontrada ou configuração inválida
        echo "<script>
                alert('Empresa não encontrada ou configuração inválida');
                window.location.href = '" . $_SERVER['HTTP_REFERER'] . "';
              </script>";
        exit;
    }
} else {
    // Requisição inválida
    echo "<script>
            alert('Requisição inválida');
            window.location.href = '" . $_SERVER['HTTP_REFERER'] . "';
          </script>";
    exit;
}
?>
</html>
