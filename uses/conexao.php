<?php
// Configurações do banco de dados
$host = 'localhost'; // IP da máquina local
$dbname = 'ourocreddb'; // Nome do banco de dados
$user = 'root'; // Usuário padrão do phpMyAdmin
$password = ''; // Senha padrão do phpMyAdmin, deixe em branco se não tiver senha

try {
    global $pdo;
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erro na conexão com o banco de dados: ' . $e->getMessage());
}
?>
