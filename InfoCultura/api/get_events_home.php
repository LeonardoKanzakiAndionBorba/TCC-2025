<?php
// api/get_events_home.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $host = 'localhost';
    $dbname = 'info_cultura';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    $sql = "
        SELECT 
            e.id,
            e.nome_evento,
            e.descricao_evento,
            e.data_hora,
            e.local_evento,
            e.banner,
            e.resultados_impacto,
            n.nome as nucleo_nome
        FROM eventos e
        LEFT JOIN nucleos n ON e.nucleo_id = n.id
        WHERE e.status = 'ativo'
        ORDER BY e.data_hora DESC
        LIMIT 6
    ";
    
    $stmt = $pdo->query($sql);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($eventos);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>