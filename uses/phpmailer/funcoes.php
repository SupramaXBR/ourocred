<?php
//funcao converter data
function converterData($data) {
    // Converte a data para o formato timestamp
    $timestamp = strtotime($data);
    
    // Formata a data para o formato desejado e retorna como string
    return date('d/m/Y', $timestamp);
}

function coordenadaAleatoriaAmazonica() {
    // Coordenadas aproximadas da Floresta Amazônica
    $latitude_min = -12.0;
    $latitude_max = 2.0;
    $longitude_min = -74.0;
    $longitude_max = -50.0;

    // Gerar coordenadas aleatórias dentro da faixa definida
    $latitude = rand($latitude_min * 10000, $latitude_max * 10000) / 10000;
    $longitude = rand($longitude_min * 10000, $longitude_max * 10000) / 10000;

    // Formatando a string de retorno
    $coordenada = 'Latitude: ' . number_format($latitude, 4) . ' | Longitude: ' . number_format($longitude, 4);
    return $coordenada;
}

function criptografarMD5($valor) {
    // Verificar se o valor é válido (até 8 dígitos)
    if (!is_numeric($valor) || strlen($valor) > 8) {
        return false;
    }

    // Adiciona zeros à esquerda para garantir que o valor tenha 8 dígitos
    $valor = str_pad($valor, 8, "0", STR_PAD_LEFT);

    // Criptografar usando MD5
    $hash_md5 = md5($valor);

    return $hash_md5;
}


?>