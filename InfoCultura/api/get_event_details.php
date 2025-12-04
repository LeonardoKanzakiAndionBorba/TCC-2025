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
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['event_id'])) {
    $event_id = $conn->real_escape_string($_GET['event_id']);

    $sql = "SELECT id, nome_evento, descricao_evento, data_hora, local_evento, banner 
            FROM eventos 
            WHERE id = '$event_id' AND status_evento = 'ativo'";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $evento = $result->fetch_assoc();
        echo json_encode($evento);
    } else {
        echo json_encode(['error' => 'Evento não encontrado']);
    }
} else {
    echo json_encode(['error' => 'ID do evento não fornecido']);
}

$conn->close();
