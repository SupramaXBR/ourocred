<?php 
# Substitua abaixo os dados, de acordo com o banco criado
$hostname = "ecoflora11.mysql.dbaas.com.br"; 
$user = "ecoflora11"; 
$password = "Ecoflora100@"; 
$database = "ecoflora11"; 

$mysqli = new mysqli($hostname,$user,$password,$database);
if ($mysqli->connect_errno) {
    echo 'erro ao conectar ao DB -- ' . $mysqli->connect_errno . ' --  ' . $mysqli->connection_error;
}

?>