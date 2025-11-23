<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Conexão com o banco
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "info_cultura";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Conexão falhou: ' . $conn->connect_error]);
    exit;
}

// Buscar apenas eventos aprovados
$sql = "SELECT e.*, n.nome as nucleo 
        FROM eventos e 
        LEFT JOIN nucleos n ON e.id_nucleo = n.id 
        WHERE e.status_aprovacao = 'aprovado' 
        ORDER BY e.data_hora ASC";

$result = $conn->query($sql);

$events = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

echo json_encode($events);

$conn->close();
