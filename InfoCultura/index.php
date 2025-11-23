<?php
session_start();

// VERIFICAÇÃO DE LOGOUT
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Simulação de dados de usuário para teste
$userLoggedIn = false;
$userName = '';

// Verificar se usuário está logado na sessão
if (isset($_SESSION['user'])) {
    if (is_array($_SESSION['user']) && isset($_SESSION['user']['loggedIn']) && $_SESSION['user']['loggedIn'] === true) {
        $userLoggedIn = true;
        $userName = $_SESSION['user']['nome'] ?? 'Usuário';
    } elseif ($_SESSION['user'] === true) {
        $userLoggedIn = true;
        $userName = 'Usuário';
    }
}

// ========== API UNIFICADA ==========

// Configurações do banco de dados - AJUSTE COM SUAS CREDENCIAIS
$host = 'localhost';
$dbname = 'info_cultura';
$username = 'root';
$password = '';

// Endpoint para buscar eventos E datas culturais
if (isset($_GET['action']) && $_GET['action'] == 'get_events') {
    header('Content-Type: application/json');

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Buscar 6 registros aprovados ALEATORIAMENTE (incluindo eventos e datas culturais)
        // Agora busca tanto eventos aprovados quanto datas culturais (que têm status_data = 'aprovado')
        $sql = "SELECT 
                    id,
                    nome,
                    descricao,
                    data,
                    local_evento,
                    imagem,
                    importancia,
                    id_nucleo,
                    status_evento,
                    status_data,
                    eventos
                FROM eventos 
                WHERE (status_evento = 'aprovado' OR status_data = 'aprovado')
                ORDER BY RAND()
                LIMIT 6";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($eventos);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erro ao buscar eventos: ' . $e->getMessage()]);
        exit;
    }
}

// Endpoint para detalhes do evento
if (isset($_GET['action']) && $_GET['action'] == 'get_event_details' && isset($_GET['event_id'])) {
    header('Content-Type: application/json');
    $eventId = (int)$_GET['event_id'];

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Buscar detalhes do evento (agora busca tanto eventos quanto datas culturais)
        $sql = "SELECT 
                    id,
                    nome,
                    descricao,
                    data,
                    local_evento,
                    imagem,
                    importancia,
                    id_nucleo,
                    status_evento,
                    status_data,
                    eventos
                FROM eventos 
                WHERE id = ? AND (status_evento = 'aprovado' OR status_data = 'aprovado')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$eventId]);
        $evento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$evento) {
            echo json_encode(['success' => false, 'error' => 'Evento não encontrado']);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $evento]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Erro ao buscar detalhes: ' . $e->getMessage()]);
        exit;
    }
}

// Endpoint para imagens - CORRIGIDO para exibir imagens corretamente
if (isset($_GET['action']) && $_GET['action'] == 'get_image') {
    $id = (int)($_GET['id'] ?? 0);
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT imagem FROM eventos WHERE id = ? AND imagem IS NOT NULL";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && !empty($result['imagem'])) {
            // Verificar se é um blob válido
            $imagemData = $result['imagem'];
            
            // Tentar detectar o tipo da imagem
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imagemData);
            
            // Se não conseguir detectar, usar jpeg como padrão
            if (!$mimeType || $mimeType == 'application/octet-stream') {
                $mimeType = 'image/jpeg';
            }
            
            header('Content-Type: ' . $mimeType);
            header('Cache-Control: public, max-age=86400'); // Cache de 1 dia
            echo $imagemData;
        } else {
            // Imagem padrão baseada no tipo de evento
            $tipo = $_GET['tipo'] ?? 'evento';
            if ($tipo === 'data') {
                header('Location: assets/ft2.png');
            } else {
                header('Location: assets/ft1.png');
            }
        }
        exit;
    } catch (PDOException $e) {
        // Imagem padrão em caso de erro
        $tipo = $_GET['tipo'] ?? 'evento';
        if ($tipo === 'data') {
            header('Location: assets/ft2.png');
        } else {
            header('Location: assets/ft1.png');
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit-no" />
    <meta name="description" content="Info Cultura - Plataforma de eventos culturais" />
    <meta name="author" content="Info Cultura" />
    <title>Info Cultura</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />

    <!-- Import da fonte Alan Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alan+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* ========== VARIÁVEIS CSS PERSONALIZÁVEIS ========== */
        :root {
            /* Cores principais */
            --primary-color: #e89516;
            --secondary-color: #6f390d;
            --accent-color: #a75c1e;
            --light-color: #e6ae6c;
            --dark-color: #333333;

            /* Cores de texto */
            --text-primary: #333333;
            --text-secondary: #333333;
            --text-light: #ffffff;
            --text-muted: #333333;

            /* Cores de fundo */
            --bg-body: #e89516;
            --bg-card: #e6ae6c;
            --bg-modal: #f0f0f0;
            --bg-footer: #6f390d;

            /* Cores de estado */
            --success-color: #b3ae26;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #797624;

            /* Cores para tags */
            --tag-evento-color: #cd3e05;
            --tag-data-color: #797624;

            /* Tipografia */
            --font-family-primary: 'Alan Sans', sans-serif;
            --font-weight-normal: 400;
            --font-weight-medium: 500;
            --font-weight-semibold: 600;
            --font-weight-bold: 700;

            /* Tamanhos de fonte */
            --font-size-xs: 0.75rem;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
            --font-size-3xl: 1.875rem;

            /* Espaçamentos */
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;

            /* Bordas e sombras */
            --border-radius-sm: 0.25rem;
            --border-radius-md: 0.5rem;
            --border-radius-lg: 0.75rem;
            --border-radius-xl: 1rem;
            --border-radius-2xl: 1.5rem;

            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 8px 16px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 0 10px rgba(255, 255, 255, 1);

            /* Transições */
            --transition-fast: 0.2s ease;
            --transition-normal: 0.3s ease;
            --transition-slow: 0.5s ease;

            /* Z-index */
            --z-dropdown: 1000;
            --z-sticky: 1020;
            --z-fixed: 1030;
            --z-modal: 1040;
            --z-popover: 1050;
            --z-tooltip: 1060;
        }

        /* ========== ESTILOS BASE ========== */
        body {
            background-color: var(--bg-body);
            font-family: var(--font-family-primary);
            color: var(--text-primary);
        }

        /* ========== NAVBAR ========== */
        .navbar {
            padding-left: 0;
            padding-right: 0;
            font-family: 'Alan Sans', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--secondary-color) !important;
        }

        .navbar .container-fluid {
            padding-left: 15px;
            padding-right: 15px;
            background-color: var(--secondary-color);
        }

        .navbar-brand,
        .navbar-nav .nav-link {
            font-family: "Alan Sans", sans-serif;
            font-optical-sizing: auto;
            font-weight: 600;
            font-style: normal;
            font-size: 1.3rem;
            color: var(--text-light) !important;
        }

        .navbar-nav .nav-link:hover {
            color: var(--primary-color) !important;
        }

        .dropdown-menu {
            background-color: var(--secondary-color);
            border: none;
            border-radius: var(--border-radius-md);
        }

        .dropdown-item {
            color: var(--text-light) !important;
            font-family: var(--font-family-primary);
        }

        .dropdown-item:hover {
            background-color: var(--accent-color);
            color: var(--text-light) !important;
        }

        /* ========== FOOTER ========== */
        footer.bg-dark {
            background-color: var(--bg-footer) !important;
            padding-left: 0;
            padding-right: 0;
            font-family: "Alan Sans", sans-serif;
        }

        footer .container-fluid {
            padding-left: 15px;
            padding-right: 15px;
            background-color: var(--bg-footer);
        }

        footer .small,
        footer .footer-link {
            font-family: "Alan Sans", sans-serif;
            font-optical-sizing: auto;
            font-weight: 500;
            font-style: normal;
            font-size: 1.2rem;
        }

        .footer-link {
            color: var(--text-light) !important;
            text-decoration: none;
        }

        .footer-link:hover {
            color: var(--primary-color) !important;
        }

        /* ========== CARDS ========== */
        .card-simple {
            transition: transform var(--transition-fast);
            border-radius: var(--border-radius-2xl);
            overflow: hidden;
            border: none;
            box-shadow: var(--shadow-md);
            height: 100%;
            background: var(--bg-card);
        }

        .card-simple:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .card-img-container {
            position: relative;
            overflow: hidden;
            height: 250px;
        }

        .card-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform var(--transition-normal);
        }

        .card-simple:hover .card-img-container img {
            transform: scale(1.05);
        }

        .card-caption {
            padding: var(--spacing-lg);
            background-color: var(--bg-card);
            text-align: center;
        }

        .card-caption h5 {
            font-family: var(--font-family-primary);
            font-weight: var(--font-weight-semibold);
            margin-bottom: var(--spacing-sm);
            color: var(--text-primary);
            font-size: var(--font-size-lg);
        }

        /* ========== TAGS ========== */
        .evento-tag {
            position: absolute;
            top: var(--spacing-sm);
            left: var(--spacing-sm);
            background: var(--tag-evento-color);
            color: var(--text-light);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--border-radius-sm);
            font-size: var(--font-size-xs);
            z-index: var(--z-fixed);
            font-family: var(--font-family-primary);
            font-weight: var(--font-weight-medium);
        }

        .data-tag {
            position: absolute;
            top: var(--spacing-sm);
            left: var(--spacing-sm);
            background: var(--tag-data-color);
            color: var(--text-light);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--border-radius-sm);
            font-size: var(--font-size-xs);
            z-index: var(--z-fixed);
            font-family: var(--font-family-primary);
            font-weight: var(--font-weight-medium);
        }

        .evento-passado-badge {
            position: absolute;
            top: var(--spacing-sm);
            right: var(--spacing-sm);
            background: var(--success-color);
            color: var(--text-light);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--border-radius-lg);
            font-size: var(--font-size-xs);
            z-index: var(--z-fixed);
            font-family: var(--font-family-primary);
            font-weight: var(--font-weight-medium);
        }

        /* ========== LOADING ========== */
        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-radius: 50%;
            border-top: 4px solid var(--primary-color);
            animation: spin 1s linear infinite;
            margin-bottom: var(--spacing-sm);
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* ========== CARROSSEL ========== */
        .carousel-control-prev,
        .carousel-control-next {
            width: 60px;
            height: 60px;
            background-color: var(--accent-color);
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.8;
        }

        .carousel-control-prev {
            left: var(--spacing-lg);
        }

        .carousel-control-next {
            right: var(--spacing-lg);
        }

        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            opacity: 1;
            background-color: var(--secondary-color);
        }

        /* ========== MODAIS ========== */
        .event-modal-img {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: var(--border-radius-md);
            margin-bottom: var(--spacing-md);
        }

        .event-details-section {
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid #eee;
        }

        .event-details-section h6 {
            color: var(--accent-color);
            font-weight: var(--font-weight-semibold);
            margin-bottom: var(--spacing-sm);
            font-size: var(--font-size-lg);
        }

        .event-info-badge {
            background-color: var(--light-color);
            color: var(--text-primary);
            font-weight: var(--font-weight-medium);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: 20px;
            margin-right: var(--spacing-sm);
            margin-bottom: var(--spacing-sm);
            display: inline-block;
            font-size: var(--font-size-sm);
        }

        .event-modal-header {
            background: #6f390d;
            color: var(--text-light);
            border-bottom: none;
        }

        .modal-content {
            border-radius: var(--border-radius-xl);
            border: none;
            background: var(--bg-modal);
        }

        /* ========== BOTÕES ========== */
        .btn-ler-mais {
            background-color: var(--accent-color);
            color: var(--text-light);
            border: none;
            padding: var(--spacing-sm) var(--spacing-xl);
            border-radius: 20px;
            transition: background-color var(--transition-normal);
            font-family: var(--font-family-primary);
            font-weight: var(--font-weight-medium);
        }

        .btn-ler-mais:hover {
            background-color: var(--secondary-color);
            color: var(--text-light);
        }

        .btn-recargar {
            margin: var(--spacing-sm);
            border-radius: 20px;
            font-family: var(--font-family-primary);
            font-weight: var(--font-weight-medium);
        }

        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        /* ========== BADGES ========== */
        .badge.bg-primary {
            background-color: var(--accent-color) !important;
        }

        /* ========== SEÇÕES ========== */
        .eventos-container {
            background-color: var(--accent-color);
            padding: var(--spacing-xl);
            border-radius: var(--border-radius-xl);
            margin-bottom: var(--spacing-2xl);
        }

        .eventos-container h3 {
            color: var(--text-light);
            font-family: var(--font-family-primary);
            font-weight: var(--font-weight-bold);
            font-size: var(--font-size-2xl);
        }

        /* ========== TIPOGRAFIA ========== */
        .h2,
        h2 {
            font-family: var(--font-family-primary);
            font-weight: var(--font-weight-bold);
            color: var(--text-primary);
        }

        .h3,
        h3 {
            font-family: var(--font-family-primary);
            font-weight: var(--font-weight-semibold);
        }

        .h5,
        h5 {
            font-family: var(--font-family-primary);
            font-weight: var(--font-weight-semibold);
        }

        p {
            font-family: var(--font-family-primary);
            font-weight: var(--font-weight-normal);
            color: var(--text-secondary);
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        /* ========== ALERTS ========== */
        .alert {
            border-radius: var(--border-radius-lg);
            border: none;
            font-family: var(--font-family-primary);
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        .alert-info {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info-color);
        }

        /* ========== CARDS DE DESTAQUE ========== */
        .card.border-0 {
            background: var(--bg-card);
            border-radius: var(--border-radius-xl);
        }

        .bg-featured-blog {
            background-size: cover;
            background-position: center;
            min-height: 300px;
            border-radius: 0 var(--border-radius-xl) var(--border-radius-xl) 0;
        }

        /* ========== RESPONSIVIDADE ========== */
        @media (max-width: 768px) {
            :root {
                --font-size-xl: 1.1rem;
                --font-size-2xl: 1.3rem;
                --spacing-xl: 1.5rem;
            }

            .card-img-container {
                height: 200px;
            }

            .carousel-control-prev,
            .carousel-control-next {
                width: 50px;
                height: 50px;
            }

            .eventos-container {
                padding: var(--spacing-lg);
            }
        }

        @media (max-width: 576px) {
            :root {
                --font-size-xl: 1rem;
                --font-size-2xl: 1.2rem;
                --spacing-xl: 1rem;
            }

            .navbar-brand,
            .navbar-nav .nav-link {
                font-size: var(--font-size-lg);
            }

            footer .small,
            footer .footer-link {
                font-size: var(--font-size-base);
            }
        }
    </style>
</head>

<body class="d-flex flex-column h-100">
    <main class="flex-shrink-0">
        <!-- Navigation - CORRIGIDA para ficar igual ao calendário -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid px-4">
                <a class="navbar-brand" href="index.php">
                    <img src="assets/logoo.png" alt="Info Cultura" class="logo" style="height: 40px;">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a class="nav-link" href="calendario.php">Calendário Cultural</a></li>
                        <li class="nav-item"><a class="nav-link" href="quemsomos.php">Quem Somos</a></li>
                        <?php if ($userLoggedIn): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i>Perfil
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                    <li><span class="dropdown-item-text text-white">Olá, <?php echo htmlspecialchars($userName); ?></span></li>
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
            </div>
        </nav>

        <!-- Carrossel -->
        <div id="carouselExampleCaptions" class="carousel slide">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="0" class="active" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="2" aria-label="Slide 3"></button>
                <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="3" aria-label="Slide 4"></button>
                <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="4" aria-label="Slide 5"></button>
                <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="5" aria-label="Slide 6"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="assets/destq_set.png" class="d-block w-100" alt="Destaque 1">
                </div>
                <div class="carousel-item">
                    <img src="assets/destq_set1.png" class="d-block w-100" alt="Destaque 2">
                </div>
                <div class="carousel-item">
                    <img src="assets/destq_set2.png" class="d-block w-100" alt="Destaque 3">
                </div>
                <div class="carousel-item">
                    <img src="assets/destq_set3.png" class="d-block w-100" alt="Destaque 4">
                </div>
                <div class="carousel-item">
                    <img src="assets/destq_set4.png" class="d-block w-100" alt="Destaque 5">
                </div>
                <div class="carousel-item">
                    <img src="assets/destq_set_final.png" class="d-block w-100" alt="Destaque 6">
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>

        <!-- Seção de Destaque -->
        <section class="py-5">
            <div class="container px-5">
                <div class="card border-0 shadow rounded-8 overflow-hidden">
                    <div class="card-body p-0">
                        <div class="row gx-0">
                            <div class="col-lg-6 col-xl-5 py-lg-5">
                                <div class="p-4 p-md-5">
                                    <div class="h2 fw-bolder">Bem-vindo ao Info Cultura</div>
                                    <p>Explore os eventos e datas culturais desfrutando de descrições detalhadas! </p>
                                    <a class="stretched-link text-decoration-none" href="calendario.php">
                                        Ver Calendário Completo
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="col-lg-6 col-xl-7">
                                <div class="bg-featured-blog" style="background-image: url(assets/logo.png)"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Seção de Eventos e Datas Culturais -->
        <section class="py-5">
            <div class="container px-5">
                <div class="eventos-container">
                    <div class="row align-items-center mb-4">
                        <div class="col-md-8">
                            <h3 class="mb-0">Eventos e Datas Culturais em Destaque</h3>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-primary btn-recargar" onclick="loadContent()">
                                <i class="bi bi-arrow-clockwise"></i> Nova Seleção
                            </button>
                        </div>
                    </div>

                    <!-- Mensagens de Loading e Erro -->
                    <div id="loading-message" class="text-center py-5">
                        <div class="loading-spinner"></div>
                        <p>Carregando eventos e datas culturais...</p>
                    </div>

                    <div id="error-message" class="alert alert-danger text-center" style="display: none;">
                        <h5><i class="bi bi-exclamation-triangle"></i> Erro ao carregar conteúdo</h5>
                        <p id="error-text"></p>
                        <button class="btn btn-primary mt-2" onclick="loadContent()">
                            <i class="bi bi-arrow-clockwise"></i> Tentar Novamente
                        </button>
                    </div>

                    <!-- Container dos Cards -->
                    <div id="conteudo-container" class="row">
                        <!-- Os cards serão inseridos aqui via JavaScript -->
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer - CORRIGIDO para ficar igual ao calendário -->
    <footer class="bg-dark py-4 mt-auto">
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
                    <a class="link-light small footer-link" href="login.php">Login</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JavaScript Unificado -->
    <script>
        // Configurações globais
        let eventosCache = [];

        // Função para buscar eventos do banco de dados
        async function fetchEvents() {
            try {
                const response = await fetch('index.php?action=get_events');

                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status}`);
                }

                const eventos = await response.json();

                if (!Array.isArray(eventos)) {
                    if (eventos.error) {
                        throw new Error(eventos.error);
                    }
                    throw new Error('Resposta não é um array válido');
                }

                return eventos;
            } catch (error) {
                console.error('Erro ao buscar eventos:', error);
                throw error;
            }
        }

        // Função para criar cards de eventos
        function createEventCards(eventos) {
            if (!eventos || eventos.length === 0) {
                return `
                    <div class="col-12 text-center">
                        <div class="alert alert-info">
                            <h5><i class="bi bi-info-circle"></i> Nenhum conteúdo encontrado</h5>
                            <p>Não há eventos ou datas culturais disponíveis no momento. Tente recarregar ou volte mais tarde.</p>
                        </div>
                    </div>
                `;
            }

            let cardsHTML = '';

            eventos.forEach(evento => {
                // Formatar apenas a DATA (sem hora)
                const dataEvento = new Date(evento.data);
                const dataFormatada = dataEvento.toLocaleDateString('pt-BR');

                // Verificar se o evento já aconteceu (considerando apenas a data)
                const hoje = new Date();
                hoje.setHours(0, 0, 0, 0); // Zera as horas para comparar apenas a data
                const dataEventoSemHora = new Date(dataEvento);
                dataEventoSemHora.setHours(0, 0, 0, 0);
                const eventoPassado = dataEventoSemHora < hoje;

                // Determinar a tag baseada na coluna "eventos"
                const isEvento = evento.eventos === 'sim';
                const tagTexto = isEvento ? 'Evento Cultural' : 'Data Cultural';
                const tagClasse = isEvento ? 'evento-tag' : 'data-tag';

                // Gerar URL da imagem - CORRIGIDO para usar as imagens do banco
                let imagemUrl = '';
                if (evento.imagem) {
                    // Se tem imagem no banco, usar endpoint com parâmetro de tipo
                    imagemUrl = `index.php?action=get_image&id=${evento.id}&tipo=${isEvento ? 'evento' : 'data'}&t=${Date.now()}`;
                } else {
                    // Se não tem imagem, usar imagem padrão baseada no tipo
                    imagemUrl = isEvento ? 'assets/ft1.png' : 'assets/ft2.png';
                }

                cardsHTML += `
                    <div class="col-lg-4 mb-4">
                        <div class="card-simple h-100">
                            <div class="card-img-container">
                                <div class="${tagClasse}">
                                    <i class="bi ${isEvento ? 'bi-calendar-event' : 'bi-calendar-date'}"></i> ${tagTexto}
                                </div>
                                ${eventoPassado ? '<div class="evento-passado-badge"><i class="bi bi-check-circle"></i> Realizado</div>' : ''}
                                <img src="${imagemUrl}" 
                                     alt="${evento.nome}" 
                                     onerror="this.onerror=null; this.src='${isEvento ? 'assets/ft1.png' : 'assets/ft2.png'}'"
                                     loading="lazy">
                            </div>
                            <div class="card-caption">
                                <h5>${evento.nome}</h5>
                                <small class="text-muted">
                                    <i class="bi bi-calendar"></i> ${dataFormatada}
                                </small>
                                ${evento.local_evento ? `<br><small class="text-muted"><i class="bi bi-pin-map"></i> ${evento.local_evento.substring(0, 30)}${evento.local_evento.length > 30 ? '...' : ''}</small>` : ''}
                                
                                <div class="mt-3">
                                    <button class="btn btn-ler-mais" onclick="openEventModal(${evento.id})">
                                        <i class="bi bi-eye"></i> Ler Mais
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            return cardsHTML;
        }

        // Função para renderizar o conteúdo
        function renderContent() {
            const container = document.getElementById('conteudo-container');
            container.innerHTML = createEventCards(eventosCache);
        }

        // Função principal para carregar conteúdo
        async function loadContent() {
            const loading = document.getElementById('loading-message');
            const error = document.getElementById('error-message');
            const container = document.getElementById('conteudo-container');

            // Mostrar loading
            loading.style.display = 'block';
            error.style.display = 'none';
            container.innerHTML = '';

            try {
                // Buscar eventos
                const eventos = await fetchEvents();
                eventosCache = eventos;

                // Renderizar conteúdo
                renderContent();

                // Esconder loading
                loading.style.display = 'none';

            } catch (error) {
                console.error('Erro ao carregar conteúdo:', error);

                // Esconder loading e mostrar erro
                loading.style.display = 'none';
                error.style.display = 'block';
                document.getElementById('error-text').textContent = error.message;
            }
        }

        // ========== MODAL DE EVENTO ==========

        // Função para abrir modal de evento
        async function openEventModal(eventId) {
            try {
                const response = await fetch(`index.php?action=get_event_details&event_id=${eventId}`);

                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status}`);
                }

                const resultado = await response.json();

                if (!resultado.success) {
                    throw new Error(resultado.error || 'Erro desconhecido');
                }

                const evento = resultado.data;

                // Formatar apenas a DATA (sem hora)
                const dataEvento = new Date(evento.data);
                const dataFormatada = dataEvento.toLocaleDateString('pt-BR');
                const diaSemana = dataEvento.toLocaleDateString('pt-BR', {
                    weekday: 'long'
                });

                // Verificar se o evento já aconteceu (considerando apenas a data)
                const hoje = new Date();
                hoje.setHours(0, 0, 0, 0);
                const dataEventoSemHora = new Date(dataEvento);
                dataEventoSemHora.setHours(0, 0, 0, 0);
                const eventoPassado = dataEventoSemHora < hoje;

                // Determinar o tipo baseado na coluna "eventos"
                const isEvento = evento.eventos === 'sim';
                const tipoTexto = isEvento ? 'Evento Cultural' : 'Data Cultural';

                // Gerar URL da imagem - CORRIGIDO para usar as imagens do banco
                let imagemUrl = '';
                if (evento.imagem) {
                    // Se tem imagem no banco, usar endpoint com parâmetro de tipo
                    imagemUrl = `index.php?action=get_image&id=${evento.id}&tipo=${isEvento ? 'evento' : 'data'}&t=${Date.now()}`;
                } else {
                    // Se não tem imagem, usar imagem padrão baseada no tipo
                    imagemUrl = isEvento ? 'assets/ft1.png' : 'assets/ft2.png';
                }

                // Criar modal
                const modalHTML = `
                    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header event-modal-header">
                                    <h5 class="modal-title" id="eventModalLabel">
                                        <i class="bi ${isEvento ? 'bi-calendar-event' : 'bi-calendar-date'}"></i> ${evento.nome}
                                        ${eventoPassado ? '<span class="badge bg-success ms-2"><i class="bi bi-check-circle"></i> Realizado</span>' : ''}
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="mb-3">
                                        <span class="badge ${isEvento ? 'bg-warning' : 'bg-info'}">
                                            <i class="bi ${isEvento ? 'bi-calendar-event' : 'bi-calendar-date'} me-1"></i> 
                                            ${tipoTexto}
                                        </span>
                                    </div>
                                    
                                    <div class="text-center mb-4">
                                        <img src="${imagemUrl}" alt="${evento.nome}" 
                                             class="event-modal-img" 
                                             onerror="this.onerror=null; this.src='${isEvento ? 'assets/ft1.png' : 'assets/ft2.png'}'"
                                             loading="lazy">
                                    </div>
                                    
                                    <div class="event-details-section">
                                        <h6><i class="bi bi-info-circle"></i> Descrição</h6>
                                        <p>${evento.descricao || 'Descrição não disponível'}</p>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="event-details-section">
                                                <h6><i class="bi bi-calendar-event"></i> Data</h6>
                                                <span class="event-info-badge">${diaSemana}</span>
                                                <span class="event-info-badge">${dataFormatada}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="event-details-section">
                                                <h6><i class="bi bi-geo-alt"></i> Localização</h6>
                                                <p class="mb-0">${evento.local_evento || 'Local não definido'}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    ${evento.importancia ? `
                                        <div class="event-details-section">
                                            <h6><i class="bi bi-star"></i> Importância</h6>
                                            <p class="mb-0">${evento.importancia}</p>
                                        </div>
                                    ` : ''}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="bi bi-x-circle"></i> Fechar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Adicionar modal ao body
                document.body.insertAdjacentHTML('beforeend', modalHTML);

                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('eventModal'));
                modal.show();

                // Remover modal do DOM quando fechado
                document.getElementById('eventModal').addEventListener('hidden.bs.modal', function() {
                    this.remove();
                });

            } catch (error) {
                console.error('Erro ao carregar detalhes do evento:', error);
                alert('Erro ao carregar detalhes do evento: ' + error.message);
            }
        }

        // Carregar conteúdo quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            loadContent();
        });
    </script>
</body>

</html>