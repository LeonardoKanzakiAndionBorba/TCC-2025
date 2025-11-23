<?php
session_start();

// VERIFICAÇÃO DE LOGOUT
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Verificar se o usuário está logado usando a mesma sessão do login
if (!isset($_SESSION['user']) || !$_SESSION['user']['loggedIn']) {
    header("Location: login.php");
    exit();
}

// Conexão com o banco
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "info_cultura";

// Obter informações do usuário logado
$usuario_logado = $_SESSION['user'];
$tipo_usuario = $usuario_logado['tipo_usuario'];
$usuario_id = $usuario_logado['id'];

// Processar exclusão de evento
if (isset($_GET['excluir'])) {
    $event_id = $_GET['excluir'];

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Conexão falhou: " . $conn->connect_error);
    }

    // Verificar se o evento existe e é um evento cadastrado
    $sql_verifica = "SELECT id FROM eventos WHERE id = ? AND eventos = 'sim'";
    $stmt_verifica = $conn->prepare($sql_verifica);
    $stmt_verifica->bind_param("i", $event_id);
    $stmt_verifica->execute();
    $result_verifica = $stmt_verifica->get_result();

    if ($result_verifica->num_rows > 0) {
        // Excluir o evento
        $sql_excluir = "DELETE FROM eventos WHERE id = ?";
        $stmt_excluir = $conn->prepare($sql_excluir);
        $stmt_excluir->bind_param("i", $event_id);

        if ($stmt_excluir->execute()) {
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Evento excluído com sucesso!'
            ];
            header("Location: areaAdm.php");
            exit();
        } else {
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => 'Erro ao excluir evento: ' . $stmt_excluir->error
            ];
            header("Location: areaAdm.php");
            exit();
        }
        $stmt_excluir->close();
    } else {
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'Evento não encontrado ou você não tem permissão para excluí-lo.'
        ];
        header("Location: areaAdm.php");
        exit();
    }

    $stmt_verifica->close();
    $conn->close();
    exit();
}

// Processar o formulário quando for submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coletar dados do formulário
    $event_id = $_POST['event_id'] ?? null;
    $nome = $_POST['nome'];
    $id_nucleo = $_POST['id_nucleo'];
    $descricao = $_POST['descricao'];
    $data = $_POST['data'];
    $local_evento = $_POST['local_evento'];
    $importancia = $_POST['importancia'];

    // Tipos de arquivo permitidos
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    // PROCESSAR IMAGEM
    $imagem_blob = null;
    $manter_imagem = $_POST['manter_imagem'] ?? false;

    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $imagem = $_FILES['imagem'];
        $file_type = mime_content_type($imagem['tmp_name']);

        if (in_array($file_type, $allowed_types) && $imagem['size'] <= 2 * 1024 * 1024) {
            $imagem_blob = file_get_contents($imagem['tmp_name']);

            // Salvar também na pasta uploads para visualização rápida
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            $extensao = pathinfo($imagem['name'], PATHINFO_EXTENSION);
            $nome_arquivo = uniqid() . '_evento.' . $extensao;
            move_uploaded_file($imagem['tmp_name'], 'uploads/' . $nome_arquivo);
        }
    }

    // Conexão com o banco
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Verificar conexão
    if ($conn->connect_error) {
        die("Conexão falhou: " . $conn->connect_error);
    }

    // Inserir/Atualizar evento - SEMPRE definir eventos = 'sim'
    if ($event_id) {
        // UPDATE para edição - verificar se o evento pertence ao usuário
        $sql_verifica = "SELECT id FROM eventos WHERE id = ? AND eventos = 'sim'";
        $stmt_verifica = $conn->prepare($sql_verifica);
        $stmt_verifica->bind_param("i", $event_id);
        $stmt_verifica->execute();
        $result_verifica = $stmt_verifica->get_result();

        if ($result_verifica->num_rows > 0) {
            if ($imagem_blob !== null) {
                // Atualizar com nova imagem
                $sql = "UPDATE eventos SET nome=?, id_nucleo=?, descricao=?, data=?, local_evento=?, importancia=?, imagem=? WHERE id=? AND eventos = 'sim'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sissssbi", $nome, $id_nucleo, $descricao, $data, $local_evento, $importancia, $imagem_blob, $event_id);
            } else if ($manter_imagem) {
                // Manter a imagem atual
                $sql = "UPDATE eventos SET nome=?, id_nucleo=?, descricao=?, data=?, local_evento=?, importancia=? WHERE id=? AND eventos = 'sim'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sissssi", $nome, $id_nucleo, $descricao, $data, $local_evento, $importancia, $event_id);
            } else {
                // Remover imagem
                $sql = "UPDATE eventos SET nome=?, id_nucleo=?, descricao=?, data=?, local_evento=?, importancia=?, imagem=NULL WHERE id=? AND eventos = 'sim'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sissssi", $nome, $id_nucleo, $descricao, $data, $local_evento, $importancia, $event_id);
            }

            if ($stmt->execute()) {
                $_SESSION['notification'] = [
                    'type' => 'success',
                    'message' => 'Evento atualizado com sucesso!'
                ];
                header("Location: areaAdm.php");
                exit();
            } else {
                $_SESSION['notification'] = [
                    'type' => 'error',
                    'message' => 'Erro ao atualizar evento: ' . $stmt->error
                ];
                header("Location: areaAdm.php");
                exit();
            }
            $stmt->close();
        } else {
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => 'Evento não encontrado ou você não tem permissão para editá-lo.'
            ];
            header("Location: areaAdm.php");
            exit();
        }
        $stmt_verifica->close();
    } else {
        // INSERT para novo evento - SEMPRE definir eventos = 'sim'
        if ($imagem_blob !== null) {
            $sql = "INSERT INTO eventos (nome, id_nucleo, descricao, data, local_evento, importancia, imagem, status_evento, eventos) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente', 'sim')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sissssb", $nome, $id_nucleo, $descricao, $data, $local_evento, $importancia, $imagem_blob);
        } else {
            $sql = "INSERT INTO eventos (nome, id_nucleo, descricao, data, local_evento, importancia, status_evento, eventos) VALUES (?, ?, ?, ?, ?, ?, 'pendente', 'sim')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sissss", $nome, $id_nucleo, $descricao, $data, $local_evento, $importancia);
        }

        // Executar a query do evento
        if ($stmt->execute()) {
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Evento cadastrado com sucesso! Aguarde a aprovação.'
            ];
            header("Location: areaAdm.php");
            exit();
        } else {
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => 'Erro ao cadastrar evento: ' . $stmt->error
            ];
            header("Location: areaAdm.php");
            exit();
        }
        $stmt->close();
    }

    // Fechar conexão
    $conn->close();
}

// Buscar núcleos para o select
try {
    $conn_nucleos = new mysqli($servername, $username, $password, $dbname);
    $sql_nucleos = "SELECT id, nome FROM nucleos ORDER BY nome";
    $result_nucleos = $conn_nucleos->query($sql_nucleos);
    $conn_nucleos->close();
} catch (Exception $e) {
    $result_nucleos = false;
    error_log("Erro ao carregar núcleos: " . $e->getMessage());
}

// Buscar evento específico para edição
$evento_edicao = null;
if (isset($_GET['editar'])) {
    $event_id = $_GET['editar'];
    $conn_edicao = new mysqli($servername, $username, $password, $dbname);

    $sql_edicao = "SELECT * FROM eventos WHERE id = ? AND eventos = 'sim'";
    $stmt_edicao = $conn_edicao->prepare($sql_edicao);
    $stmt_edicao->bind_param("i", $event_id);
    $stmt_edicao->execute();
    $result_edicao = $stmt_edicao->get_result();

    if ($result_edicao->num_rows > 0) {
        $evento_edicao = $result_edicao->fetch_assoc();
    }

    $stmt_edicao->close();
    $conn_edicao->close();
}

// Buscar eventos - APENAS eventos com eventos = 'sim' (eventos cadastrados)
$filtro_data = $_GET['filtro_data'] ?? 'todos';
$conn_eventos = new mysqli($servername, $username, $password, $dbname);

// Query base para buscar eventos - FILTRAR APENAS eventos = 'sim'
$sql_eventos = "SELECT e.*, n.nome as nucleo_nome 
                FROM eventos e 
                LEFT JOIN nucleos n ON e.id_nucleo = n.id 
                WHERE e.eventos = 'sim'";

// Ajustar query baseado no filtro simplificado
switch ($filtro_data) {
    case 'futuros':
        $sql_eventos .= " AND e.data >= CURDATE()";
        break;
    case 'passados':
        $sql_eventos .= " AND e.data < CURDATE()";
        break;
        // 'todos' não adiciona filtro adicional
}

// Ordenar: primeiro pendentes, depois por data
$sql_eventos .= " ORDER BY 
    CASE WHEN e.status_evento = 'pendente' THEN 1 ELSE 2 END,
    e.data ASC";

$result_eventos = $conn_eventos->query($sql_eventos);
$conn_eventos->close();

// Separar eventos pendentes dos outros
$eventos_pendentes = [];
$eventos_outros = [];

if ($result_eventos->num_rows > 0) {
    while ($evento = $result_eventos->fetch_assoc()) {
        if ($evento['status_evento'] == 'pendente') {
            $eventos_pendentes[] = $evento;
        } else {
            $eventos_outros[] = $evento;
        }
    }
}

// Função auxiliar para evitar erros com valores nulos
function safe_html($value)
{
    return htmlspecialchars($value ?? '');
}

// Verificar se há notificação na sessão
$notification = $_SESSION['notification'] ?? null;
unset($_SESSION['notification']); // Limpar notificação após exibir
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Info Cultura - Área Administrativa</title>
    <!-- Favicon-->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Pré-conexão para otimização de carregamento da fonte -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Importar a fonte Alan Sans do Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Alan+Sans:wght@300..900&display=swap" rel="stylesheet">
    <!-- Bootstrap icons-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />
    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- ==================== -->
    <!-- CONFIGURAÇÕES DE CSS -->
    <!-- ==================== -->
    <style>
        body {
            font-family: "Alan Sans", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
            margin: 0;
            background-color: #e89516;
            color: #333;
        }

        /* ===== CORES PRINCIPAIS ===== */
        :root {
            --primary-color: #6f390d;
            --secondary-color: #e89516;
            --accent-color: #a75c1e;
            --text-dark: #6f390d;
            --text-light: #ffffff;
            --bg-light: #f8f9fa;
            --success-color: #28a745;
            --error-color: #dc3545;
            --warning-color: #ffc107;
        }

        /* ===== ESTILOS GERAIS ===== */
        .logo {
            height: 40px;
        }

        .navbar-brand,
        .navbar-nav .nav-link {
            font-family: "Alan Sans", sans-serif;
            font-optical-sizing: auto;
            font-weight: 600;
            font-style: normal;
            font-size: 1.3rem;
            color: #fff;
        }

        .navbar-nav .nav-link:hover {
            color: #e89516;
        }

        footer.bg-dark,
        footer .small,
        footer .footer-link {
            font-family: "Alan Sans", sans-serif;
            font-optical-sizing: auto;
            font-weight: 500;
            font-style: normal;
            font-size: 1.3rem;
        }

        footer .small {
            font-size: 1.2rem;
        }

        footer .footer-link {
            font-size: 1.2rem;
        }

        footer.bg-dark {
            padding-left: 0;
            padding-right: 0;
            background-color: #6f390d !important;
        }

        footer .container-fluid {
            padding-left: 15px;
            padding-right: 15px;
            background-color: #6f390d;
        }

        .navbar .container-fluid,
        footer .container-fluid {
            max-width: 100%;
        }

        /* ===== DROPDOWN MENU STYLES - IGUAL AO OUTRO CÓDIGO ===== */
        .dropdown-menu {
            background-color: #6f390d;
            border: none;
            border-radius: 8px;
            min-width: 200px;
            max-width: 300px;
        }

        .dropdown-item {
            color: #fff;
            font-family: "Alan Sans", sans-serif;
            font-weight: 500;
            white-space: normal;
            word-wrap: break-word;
        }

        .dropdown-item-text {
            color: #fff !important;
            white-space: normal;
            word-wrap: break-word;
            max-width: 100%;
            padding: 0.5rem 1rem;
        }

        .dropdown-item:hover {
            background-color: #e89516;
            color: #6f390d;
        }

        .dropdown-divider {
            border-color: #e89516;
        }

        .text-break {
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
        }

        /* ===== NOTIFICAÇÕES ===== */
        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 1000;
            min-width: 300px;
            border-radius: 8px;
            padding: 15px 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateX(400px);
            transition: trans 0.3s ease-in-out;
            backdrop-filter: blur(10px);
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: linear-gradient(135deg, var(--success-color), #34ce57);
            color: white;
            border-left: 4px solid #218838;
        }

        .notification.error {
            background: linear-gradient(135deg, var(--error-color), #e4606d);
            color: white;
            border-left: 4px solid #ffffffff;
        }

        .notification.info {
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            color: white;
            border-left: 4px solid var(--primary-color);
        }

        .notification-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 1.1rem;
        }

        .notification-message {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .notification-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }

        .notification-close:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        /* ===== CARDS DE EVENTOS ===== */
        .evento-card {
            transition: all 0.3s ease-in-out;
            border: 1px solid #6f390d;
            background: #ffbc6d;
        }

        .evento-card:hover {
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        /* ===== STATUS DOS EVENTOS ===== */
        .status-pendente {
            background-color: #e89516;
            color: white;
        }

        .status-aprovado {
            background-color: #797624;
            color: white;
        }

        .status-rejeitado {
            background-color: #cd3e05;
            color: white;
        }

        /* ===== SEÇÃO DO USUÁRIO ===== */
        .user-section {
            background: linear-gradient(135deg, var(--bg-light), #ffffff);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            margin-right: 15px;
        }

        .user-info h3 {
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .user-info p {
            color: #6f390d;
            margin-bottom: 3px;
        }

        /* ===== BOTÕES ===== */
        .btn-primary {
            background: #797624;
            border: none;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #605d16;
        }

        .btn-ver-mais {
            background: #a75c1e;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 20px auto;
            display: block;
            font-weight: 600;
        }

        .btn-ver-mais:hover {
            background-color: #6f390d;
        }

        /* ===== NAVEGAÇÃO ===== */
        .nav-link {
            color: var(--text-light) !important;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--secondary-color) !important;
        }

        /* ===== TIPOGRAFIA ===== */
        .descricao-text {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .section-title {
            border-left: 4px solid var(--primary-color);
            padding-left: 15px;
            margin: 30px 0 20px 0;
            color: var(--text-dark);
        }

        /* ===== LAYOUT RESPONSIVO ===== */
        .eventos-comprimidos {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease-in-out;
        }

        .eventos-comprimidos.expandido {
            max-height: 5000px;
        }

        .imagem-preview {
            max-width: 200px;
            max-height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 10px;
            border: 2px solid var(--secondary-color);
        }

        /* ===== BACKGROUND DA PÁGINA ===== */
        .page-background {
            background: #e89516;
            min-height: 100vh;
        }

        /* ===== CARDS BRANCOS ===== */
        .content-card {
            background: #e6ae6c;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #6f390d;
        }
    </style>
</head>

<body class="d-flex flex-column">
    <main class="flex-shrink-0">
        <!-- Navigation-->
        <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--primary-color) !important;">
            <div class="container-fluid px-4">
                <a class="navbar-brand" href="index.php">
                    <img src="assets/logoo.png" alt="Info Cultura" class="logo">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation"><span
                        class="navbar-toggler-icon"></span></button>
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="calendario.php">Calendário Cultural</a></li>
                    <li class="nav-item"><a class="nav-link" href="quemsomos.php">Quem Somos</a></li>
                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['loggedIn']): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>Perfil
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><span class="dropdown-item-text text-white text-break">Olá, <?php echo safe_html($_SESSION['user']['nome']); ?></span></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="login(usuario).php"><i class="fas fa-user me-2"></i>Meu Perfil</a></li>
                                <li><a class="dropdown-item" href="?logout=true"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <!-- Notificação -->
        <?php if ($notification): ?>
            <div class="notification <?php echo $notification['type']; ?> show" id="notification">
                <div class="notification-icon">
                    <?php if ($notification['type'] == 'success'): ?>
                        <i class="fas fa-check-circle"></i>
                    <?php elseif ($notification['type'] == 'error'): ?>
                        <i class="fas fa-exclamation-circle"></i>
                    <?php else: ?>
                        <i class="fas fa-info-circle"></i>
                    <?php endif; ?>
                </div>
                <div class="notification-content">
                    <div class="notification-title">
                        <?php
                        if ($notification['type'] == 'success') echo 'Sucesso!';
                        elseif ($notification['type'] == 'error') echo 'Erro!';
                        else echo 'Informação';
                        ?>
                    </div>
                    <div class="notification-message"><?php echo safe_html($notification['message']); ?></div>
                </div>
                <button class="notification-close" onclick="closeNotification()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Page Content-->
        <div class="page-background">
            <!-- Conteúdo Principal -->
            <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-3xl font-bold text-[var(--text-dark)]">Área Administrativa - Eventos NEABI</h1>
                    <button onclick="openNewEventModal()" class="btn-primary px-10 py-2 rounded-md">
                        <i class="fas fa-plus mr-2"></i>Cadastrar Novo Evento
                    </button>
                </div>

                <!-- Filtros Simplificados -->
                <div class="content-card p-6 mb-6">
                    <h2 class="text-xl font-semibold text-[var(--text-dark)] mb-4">Filtrar Eventos</h2>
                    <form method="GET" class="flex flex-wrap gap-4">
                        <select name="filtro_data" onchange="this.form.submit()"
                            class="px-4 py-2 border border-gray-300 rounded-md bg-white text-[var(--text-dark)]">
                            <option value="todos" <?= $filtro_data == 'todos' ? 'selected' : '' ?>>Todos os Eventos</option>
                            <option value="futuros" <?= $filtro_data == 'futuros' ? 'selected' : '' ?>>Eventos Futuros</option>
                            <option value="passados" <?= $filtro_data == 'passados' ? 'selected' : '' ?>>Eventos Passados</option>
                        </select>
                    </form>
                </div>

                <!-- Lista de Eventos -->
                <div class="content-card p-6">
                    <h2 class="text-xl font-semibold text-[var(--text-dark)] mb-4">Todos os Eventos Cadastrados</h2>

                    <!-- Seção de Eventos Pendentes -->
                    <?php if (count($eventos_pendentes) > 0): ?>
                        <h3 class="text-lg font-semibold section-title">Eventos Pendentes de Aprovação</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                            <?php foreach ($eventos_pendentes as $evento): ?>
                                <div class="evento-card rounded-lg p-4">
                                    <?php if ($evento['imagem']): ?>
                                        <div class="mb-3">
                                            <img src="data:image/jpeg;base64,<?= base64_encode($evento['imagem']) ?>"
                                                alt="<?= safe_html($evento['nome']) ?>"
                                                class="w-full h-32 object-cover rounded-md">
                                        </div>
                                    <?php endif; ?>

                                    <h3 class="font-bold text-lg text-[var(--text-dark)] mb-2"><?= safe_html($evento['nome']) ?></h3>

                                    <div class="space-y-2 text-sm text-[#6f390d]">
                                        <div class="flex items-center gap-2">
                                            <i class="bi bi-calendar-event"></i>
                                            <span><?= date('d/m/Y', strtotime($evento['data'] ?? '')) ?></span>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <i class="bi bi-geo-alt"></i>
                                            <span><?= safe_html($evento['local_evento']) ?></span>
                                        </div>

                                        <!-- Descrição do Evento -->
                                        <div class="mt-2">
                                            <p class="text-sm text-black descricao-text">
                                                <?= safe_html($evento['descricao']) ?>
                                            </p>
                                        </div>

                                        <div class="mt-2">
                                            <span class="inline-block px-2 py-1 rounded text-xs font-medium status-pendente">
                                                ⏳ <?= ucfirst($evento['status_evento'] ?? 'pendente') ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex gap-2">
                                        <button onclick="editarEvento(<?= $evento['id'] ?>)"
                                            class="flex-1 bg-[#7b7b7b] text-white px-3 py-1 rounded text-sm hover:bg-[#575757]">
                                            Editar
                                        </button>
                                        <button onclick="excluirEvento(<?= $evento['id'] ?>)"
                                            class="flex-1 bg-[#f54e0c] text-white px-3 py-1 rounded text-sm hover:bg-[#cd3e05]">
                                            Excluir
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Seção de Outros Eventos (Aprovados/Rejeitados) -->
                    <?php if (count($eventos_outros) > 0): ?>
                        <h3 class="text-lg font-semibold section-title">Outros Eventos</h3>

                        <!-- Botão  -->
                        <button class="btn-ver-mais" onclick="toggleEventosComprimidos()">
                            <span id="texto-botao">Ver Mais Eventos</span>
                            <i class="fas fa-chevron-down ml-2" id="icone-botao"></i>
                        </button>

                        <!-- Eventos Comprimidos -->
                        <div id="eventos-comprimidos" class="eventos-comprimidos">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($eventos_outros as $evento): ?>
                                    <div class="evento-card rounded-lg p-4">
                                        <?php if ($evento['imagem']): ?>
                                            <div class="mb-3">
                                                <img src="data:image/jpeg;base64,<?= base64_encode($evento['imagem']) ?>"
                                                    alt="<?= safe_html($evento['nome']) ?>"
                                                    class="w-full h-32 object-cover rounded-md">
                                            </div>
                                        <?php endif; ?>

                                        <h3 class="font-bold text-lg text-[var(--text-dark)] mb-2"><?= safe_html($evento['nome']) ?></h3>

                                        <div class="space-y-2 text-sm text-black">
                                            <div class="flex items-center gap-2">
                                                <i class="bi bi-calendar-event"></i>
                                                <span><?= date('d/m/Y', strtotime($evento['data'] ?? '')) ?></span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <i class="bi bi-geo-alt"></i>
                                                <span><?= safe_html($evento['local_evento']) ?></span>
                                            </div>

                                            <!-- Descrição do Evento -->
                                            <div class="mt-2">
                                                <p class="text-sm text-black descricao-text">
                                                    <?= safe_html($evento['descricao']) ?>
                                                </p>
                                            </div>

                                            <div class="mt-2">
                                                <span class="inline-block px-2 py-1 rounded text-xs font-medium 
                                                    <?= ($evento['status_evento'] ?? 'pendente') == 'aprovado' ? 'status-aprovado' : (($evento['status_evento'] ?? 'pendente') == 'rejeitado' ? 'status-rejeitado' : 'status-pendente') ?>">
                                                    <?php
                                                    if (($evento['status_evento'] ?? 'pendente') == 'aprovado') echo '✅ ';
                                                    elseif (($evento['status_evento'] ?? 'pendente') == 'rejeitado') echo '❌ ';
                                                    else echo '⏳ ';
                                                    ?>
                                                    <?= ucfirst($evento['status_evento'] ?? 'pendente') ?>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="mt-4 flex gap-2">
                                            <button onclick="editarEvento(<?= $evento['id'] ?>)"
                                                class="flex-1 bg-[#7b7b7b] text-white px-3 py-1 rounded text-sm hover:bg-[#575757]">
                                                Editar
                                            </button>
                                            <button onclick="excluirEvento(<?= $evento['id'] ?>)"
                                                class="flex-1 bg-[#f54e0c] text-white px-3 py-1 rounded text-sm hover:bg-[#cd3e05]">
                                                Excluir
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (count($eventos_pendentes) == 0 && count($eventos_outros) == 0): ?>
                        <div class="text-center py-8">
                            <i class="bi bi-calendar-x text-4xl text-gray-400 mb-4"></i>
                            <p class="text-gray-500">Nenhum evento cadastrado encontrado.</p>
                            <p class="text-sm text-gray-400 mt-2">Clique em "Cadastrar Novo Evento" para adicionar seu primeiro evento.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Modal de Cadastro/Edição -->
                <div id="eventModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
                    <div class="bg-[var(--secondary-color)] rounded-lg shadow-xl w-full max-w-3xl p-6 max-h-[90vh] overflow-y-auto">
                        <div class="flex justify-between items-center mb-4">
                            <h2 id="modalTitle" class="text-2xl font-bold text-gray-800">
                                <?php echo $evento_edicao ? 'Editar Evento' : 'Cadastrar Evento'; ?>
                            </h2>
                            <button type="button" onclick="closeEventModal()" class="text-black hover:text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <form id="eventForm" action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                            <input type="hidden" id="eventId" name="event_id" value="<?php echo $evento_edicao ? safe_html($evento_edicao['id']) : ''; ?>">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Nome do Evento -->
                                <div class="md:col-span-2">
                                    <label for="nome" class="block text-sm font-medium text-[var(--text-dark)] mb-1">Nome do
                                        Evento *</label>
                                    <input type="text" id="nome" name="nome" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                        value="<?php echo $evento_edicao ? safe_html($evento_edicao['nome']) : ''; ?>">
                                </div>

                                <!-- Núcleo Responsável -->
                                <div class="md:col-span-2">
                                    <label for="id_nucleo" class="block text-sm font-medium text-[var(--text-dark)] mb-1">NEABI Responsável *</label>
                                    <select id="id_nucleo" name="id_nucleo" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                        <option value="">Selecione o NEABI</option>
                                        <?php
                                        if ($result_nucleos && $result_nucleos->num_rows > 0) {
                                            while ($row = $result_nucleos->fetch_assoc()) {
                                                $selected = ($evento_edicao && $evento_edicao['id_nucleo'] == $row['id']) ? 'selected' : '';
                                                echo "<option value='" . safe_html($row['id']) . "' $selected>" . safe_html($row['nome']) . "</option>";
                                            }
                                        } else {
                                            echo "<option value=''>Nenhum NEABI cadastrado</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Descrição -->
                                <div class="md:col-span-2">
                                    <label for="descricao" class="block text-sm font-medium text-[var(--text-dark)] mb-1">Descrição do Evento *</label>
                                    <textarea id="descricao" name="descricao" rows="4" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md" maxlength="255"><?php echo $evento_edicao ? safe_html($evento_edicao['descricao']) : ''; ?></textarea>
                                    <p class="text-xs text-gray-500 mt-1">Máximo 255 caracteres</p>
                                </div>

                                <!-- Data -->
                                <div>
                                    <label for="data" class="block text-sm font-medium text-[var(--text-dark)] mb-1">Data do Evento *</label>
                                    <input type="date" id="data" name="data" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                        value="<?php echo $evento_edicao ? safe_html($evento_edicao['data']) : ''; ?>">
                                </div>

                                <!-- Local -->
                                <div>
                                    <label for="local_evento" class="block text-sm font-medium text-[var(--text-dark)] mb-1">Local do Evento *</label>
                                    <input type="text" id="local_evento" name="local_evento" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md" maxlength="255"
                                        value="<?php echo $evento_edicao ? safe_html($evento_edicao['local_evento']) : ''; ?>">
                                </div>

                                <!-- Importância -->
                                <div class="md:col-span-2">
                                    <label for="importancia" class="block text-sm font-medium text-[var(--text-dark)] mb-1">Importância do Evento</label>
                                    <textarea id="importancia" name="importancia" rows="3"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md" maxlength="255"><?php echo $evento_edicao ? safe_html($evento_edicao['importancia']) : ''; ?></textarea>
                                    <p class="text-xs text-gray-500 mt-1">Máximo 255 caracteres</p>
                                </div>

                                <!-- Imagem -->
                                <div class="md:col-span-2">
                                    <label for="imagem" class="block text-sm font-medium text-[var(--text-dark)] mb-1">
                                        🖼️ Imagem do Evento
                                    </label>

                                    <?php if ($evento_edicao && $evento_edicao['imagem']): ?>
                                        <div class="mb-3">
                                            <p class="text-sm text-gray-600 mb-2">Imagem atual:</p>
                                            <img src="data:image/jpeg;base64,<?= base64_encode($evento_edicao['imagem']) ?>"
                                                class="imagem-preview">
                                            <div class="mt-2">
                                                <label class="inline-flex items-center">
                                                    <input type="checkbox" name="manter_imagem" value="1" checked class="rounded border-gray-300">
                                                    <span class="ml-2 text-sm text-gray-600">Manter imagem atual</span>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex items-center gap-3 mb-2">
                                        <input type="file" id="imagem" name="imagem" accept="image/*"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-[var(--primary-color)] file:text-white hover:file:bg-[var(--accent-color)]">
                                    </div>
                                    <p class="text-xs text-[var(--text-dark)] bg-amber-100 p-2 rounded-md">
                                        ⓘ Formatos aceitos: JPEG, PNG, GIF, WebP (Máximo: 2MB)
                                    </p>
                                    <div id="imagePreview" class="mt-2 hidden">
                                        <img id="previewImage" src="" class="imagem-preview">
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-4">
                                <button type="button" onclick="closeEventModal()"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-white bg-[var(--primary-color)] hover:bg-[var(--accent-color)]">
                                    Cancelar
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-[var(--primary-color)] hover:bg-[var(--accent-color)]">
                                    <?php echo $evento_edicao ? 'Atualizar Evento' : 'Salvar Evento'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>

        <!-- Footer -->
        <footer class="py-4 mt-auto" style="background-color: var(--primary-color) !important;">
            <div class="container-fluid px-4">
                <div class="row align-items-center justify-content-between flex-column flex-sm-row">
                    <div class="col-auto">
                        <div class="small m-0 text-white">Copyright &copy; InfoCultura 2025</div>
                    </div>
                    <div class="col-auto">
                        <a class="link-light small footer-link" href="index.php">Início</a>
                        <span class="text-white mx-1">&middot;</span>
                        <a class="link-light small footer-link" href="calendario.php">Calendário</a>
                        <span class="text-white mx-1">&middot;</span>
                        <a class="link-light small footer-link" href="quemsomos.php">Quem Somos</a>
                        <span class="text-white mx-1">&middot;</span>
                        <a class="link-light small footer-link" href="?logout=true">Sair</a>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Font Awesome -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

        <script>
            // Variável para controlar o estado dos eventos comprimidos
            let eventosExpandidos = false;

            // Função para fechar notificação
            function closeNotification() {
                const notification = document.getElementById('notification');
                if (notification) {
                    notification.classList.remove('show');
                    setTimeout(() => notification.remove(), 300);
                }
            }

            // Auto-fechar notificação após 5 segundos
            <?php if ($notification): ?>
                setTimeout(closeNotification, 5000);
            <?php endif; ?>

            // Função para expandir/recolher eventos comprimidos
            function toggleEventosComprimidos() {
                const container = document.getElementById('eventos-comprimidos');
                const textoBotao = document.getElementById('texto-botao');
                const iconeBotao = document.getElementById('icone-botao');

                if (eventosExpandidos) {
                    // Recolher
                    container.classList.remove('expandido');
                    textoBotao.textContent = 'Ver Mais Eventos';
                    iconeBotao.classList.remove('fa-chevron-up');
                    iconeBotao.classList.add('fa-chevron-down');
                } else {
                    // Expandir
                    container.classList.add('expandido');
                    textoBotao.textContent = 'Ver Menos Eventos';
                    iconeBotao.classList.remove('fa-chevron-down');
                    iconeBotao.classList.add('fa-chevron-up');
                }

                eventosExpandidos = !eventosExpandidos;
            }

            // Funções para controle do modal
            function openNewEventModal() {
                document.getElementById('modalTitle').textContent = 'Cadastrar Evento';
                document.getElementById('eventForm').reset();
                document.getElementById('eventId').value = '';
                document.getElementById('imagePreview').classList.add('hidden');
                document.getElementById('eventModal').classList.remove('hidden');
            }

            function closeEventModal() {
                document.getElementById('eventModal').classList.add('hidden');
                // Limpar parâmetros da URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }

            function editarEvento(eventId) {
                // Redirecionar para a página com parâmetro de edição
                window.location.href = 'areaAdm.php?editar=' + eventId;
            }

            function excluirEvento(eventId) {
                if (confirm('Tem certeza que deseja excluir este evento? Esta ação não pode ser desfeita.')) {
                    // Redirecionar para a página com parâmetro de exclusão
                    window.location.href = 'areaAdm.php?excluir=' + eventId;
                }
            }

            // Preview da imagem
            document.getElementById('imagem').addEventListener('change', function(e) {
                const file = e.target.files[0];
                const preview = document.getElementById('imagePreview');
                const previewImage = document.getElementById('previewImage');

                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        preview.classList.remove('hidden');
                    }
                    reader.readAsDataURL(file);
                } else {
                    preview.classList.add('hidden');
                }
            });

            // Validação de tamanho da imagem
            function validateImage(input) {
                if (input.files && input.files[0]) {
                    const file = input.files[0];
                    const maxSize = 2 * 1024 * 1024; // 2MB

                    if (file.size > maxSize) {
                        alert('A imagem deve ter no máximo 2MB');
                        input.value = '';
                        document.getElementById('imagePreview').classList.add('hidden');
                    }
                }
            }

            document.getElementById('imagem').addEventListener('change', function() {
                validateImage(this);
            });

            // Fechar modal ao clicar fora
            document.addEventListener('click', function(e) {
                if (e.target.id === 'eventModal') {
                    closeEventModal();
                }
            });

            // Validação de caracteres para textareas
            document.getElementById('descricao').addEventListener('input', function() {
                if (this.value.length > 255) {
                    this.value = this.value.substring(0, 255);
                }
            });

            document.getElementById('importancia').addEventListener('input', function() {
                if (this.value.length > 255) {
                    this.value = this.value.substring(0, 255);
                }
            });

            // Abrir modal automaticamente se estiver editando
            <?php if (isset($_GET['editar']) && $evento_edicao): ?>
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('eventModal').classList.remove('hidden');
                });
            <?php endif; ?>
        </script>
</body>

</html>