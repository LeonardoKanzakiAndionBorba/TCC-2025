<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Conexão com o banco
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "info_cultura";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Conexão falhou: ' . $conn->connect_error]);
    exit;
}

// Verificar método da requisição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
} else {
    $action = $_GET['action'] ?? '';
}

// Log para debug
error_log("Action recebida: " . $action);

switch ($action) {
    case 'pending':
        getPendingEvents();
        break;
    case 'approved':
        getApprovedEvents();
        break;
    case 'rejected':
        getRejectedEvents();
        break;
    case 'details':
        getEventDetails();
        break;
    case 'approve':
        approveEvent();
        break;
    case 'reject':
        rejectEvent();
        break;
    case 'undo_approval':
        undoApproval();
        break;
    case 'restore':
        restoreEvent();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Ação não especificada: ' . $action]);
}

function getPendingEvents()
{
    global $conn;

    $sql = "SELECT e.*, n.nome as nucleo_nome 
            FROM eventos e 
            LEFT JOIN nucleos n ON e.id_nucleo = n.id 
            WHERE e.status_evento = 'pendente' 
            ORDER BY e.data ASC";

    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Erro na consulta: ' . $conn->error]);
        return;
    }

    $events = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    }

    echo json_encode(['success' => true, 'events' => $events]);
}

function getApprovedEvents()
{
    global $conn;

    $sql = "SELECT e.*, n.nome as nucleo_nome 
            FROM eventos e 
            LEFT JOIN nucleos n ON e.id_nucleo = n.id 
            WHERE e.status_evento = 'aprovado' 
            ORDER BY e.data DESC";

    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Erro na consulta: ' . $conn->error]);
        return;
    }

    $events = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    }

    echo json_encode(['success' => true, 'events' => $events]);
}

function getRejectedEvents()
{
    global $conn;

    $sql = "SELECT e.*, n.nome as nucleo_nome 
            FROM eventos e 
            LEFT JOIN nucleos n ON e.id_nucleo = n.id 
            WHERE e.status_evento = 'rejeitado' 
            ORDER BY e.data DESC";

    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Erro na consulta: ' . $conn->error]);
        return;
    }

    $events = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    }

    echo json_encode(['success' => true, 'events' => $events]);
}

function getEventDetails()
{
    global $conn;

    $eventId = $_GET['id'] ?? 0;

    $sql = "SELECT e.*, n.nome as nucleo_nome 
            FROM eventos e 
            LEFT JOIN nucleos n ON e.id_nucleo = n.id 
            WHERE e.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $event = $result->fetch_assoc();
        echo json_encode(['success' => true, 'event' => $event]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Evento não encontrado']);
    }
}

function approveEvent()
{
    global $conn;

    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = $input['eventId'] ?? 0;

    $sql = "UPDATE eventos SET status_evento = 'aprovado' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $eventId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Evento aprovado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao aprovar evento: ' . $stmt->error]);
    }
}

function rejectEvent()
{
    global $conn;

    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = $input['eventId'] ?? 0;
    $reason = $input['reason'] ?? '';

    $sql = "UPDATE eventos SET status_evento = 'rejeitado' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $eventId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Evento rejeitado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao rejeitar evento: ' . $stmt->error]);
    }
}

function undoApproval()
{
    global $conn;

    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = $input['eventId'] ?? 0;

    $sql = "UPDATE eventos SET status_evento = 'pendente' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $eventId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Aprovação desfeita com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao desfazer aprovação: ' . $stmt->error]);
    }
}

function restoreEvent()
{
    global $conn;

    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = $input['eventId'] ?? 0;

    $sql = "UPDATE eventos SET status_evento = 'pendente' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $eventId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Evento restaurado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao restaurar evento: ' . $stmt->error]);
    }
}

$conn->close();
