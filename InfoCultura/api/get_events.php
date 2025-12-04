<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "info_cultura";

// Conexão com o banco
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Buscar eventos (apenas eventos aprovados) - ADICIONAR COLUNA eventos
$sql = "SELECT id, nome, descricao, data, local_evento, imagem as banner, importancia, id_nucleo, eventos 
        FROM eventos 
        WHERE status_evento = 'aprovado' AND status_data = 'aprovado'
        ORDER BY data";

$result = $conn->query($sql);

$eventos = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $eventos[] = $row;
    }
}

$conn->close();

echo json_encode($eventos);
