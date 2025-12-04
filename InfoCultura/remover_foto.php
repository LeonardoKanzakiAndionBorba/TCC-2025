<?php
// remover_foto.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "info_cultura";

if (isset($_POST['foto_id'])) {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $sql = "DELETE FROM fotos_evento WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_POST['foto_id']);

    if ($stmt->execute()) {
        echo "Foto removida com sucesso!";
    } else {
        echo "Erro ao remover foto!";
    }

    $stmt->close();
    $conn->close();
}
