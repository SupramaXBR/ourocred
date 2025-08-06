<?php
// Inclui o arquivo de conexão
require_once 'conexao.php';
include_once 'funcoes.php';

// Verifica se o parâmetro estado foi passado
if (isset($_GET['estado'])) {
    $estado = $_GET['estado'];
    $online = Strtoupper($_GET['online']);

    // Consulta as cidades do estado selecionado
    $stmt = $pdo->prepare("SELECT Id, NOMMUN, CODMUNIBGE FROM municipio WHERE UFD = :estado ORDER BY NOMMUN");
    $stmt->bindParam(':estado', $estado);
    $stmt->execute();

    if ($online == 'S'){
        echo converterParaJson($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {        
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    //$idades = array("João" => 25, "Maria" => 30, "Pedro" => 35);
    //echo json_encode($idades);
}
?>