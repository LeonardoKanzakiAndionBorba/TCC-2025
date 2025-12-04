<?php
// api/get_event_details_home.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configurações de erro
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Conexão com o banco de dados
$host = 'localhost';
$dbname = 'info_cultura';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verificar se o parâmetro event_id foi passado
    if (!isset($_GET['event_id']) || empty($_GET['event_id'])) {
        throw new Exception('ID do evento não especificado');
    }

    $event_id = intval($_GET['event_id']);

    // Query para buscar detalhes do evento - CORRIGIDA (resultados_impacto sem 't' extra)
    $sql = "
        SELECT 
            e.id,
            e.nome_evento,
            e.descricao_evento,
            e.data_hora,
            e.local_evento,
            e.banner,
            e.resultados_impacto,
            e.publico_alvo,
            e.parceiros,
            n.nome as nucleo_nome
        FROM eventos e
        LEFT JOIN nucleos n ON e.nucleo_id = n.id
        WHERE e.id = :event_id
        AND e.status = 'ativo'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->execute();

    $evento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$evento) {
        throw new Exception('Evento não encontrado');
    }

    // Retornar sucesso
    echo json_encode([
        'success' => true,
        'data' => $evento
    ]);
} catch (Exception $e) {
    // Retornar erro
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
