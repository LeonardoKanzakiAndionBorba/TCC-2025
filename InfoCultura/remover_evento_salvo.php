<?php
session_start();

// Configurações do banco
$host = 'localhost';
$dbname = 'info_cultura';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão']);
    exit;
}

if (!isset($_SESSION['user']) || !$_SESSION['user']['loggedIn']) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$event_id = $input['event_id'] ?? null;
$user_id = $_SESSION['user']['id'];

if (!$event_id) {
    echo json_encode(['success' => false, 'message' => 'ID do evento não informado']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM eventos_salvos WHERE user_id = ? AND event_id = ?");
    $stmt->execute([$user_id, $event_id]);

    echo json_encode(['success' => true, 'message' => 'Evento removido com sucesso']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao remover evento']);
}
