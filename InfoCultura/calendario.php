<?php
session_start();

// Processar logout
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    // Destruir todas as variáveis de sessão
    $_SESSION = array();

    // Destruir cookie de sessão
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destruir a sessão
    session_destroy();

    // Redirecionar para a página inicial
    header("Location: index.php");
    exit;
}

// Processar ações AJAX
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

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

    if ($_GET['action'] === 'save_event' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $event_id = $_POST['event_id'] ?? 0;
        $user_id = $_POST['user_id'] ?? 0;

        if ($event_id && $user_id) {
            try {
                // Verificar se evento é cultural
                $stmt = $pdo->prepare("SELECT eventos FROM eventos WHERE id = ? AND eventos = 'sim'");
                $stmt->execute([$event_id]);
                $event = $stmt->fetch();

                if (!$event) {
                    echo json_encode(['success' => false, 'message' => 'Apenas eventos culturais podem ser salvos']);
                    exit;
                }

                // Verificar se já está salvo
                $stmt = $pdo->prepare("SELECT id FROM eventos_salvos WHERE user_id = ? AND event_id = ?");
                $stmt->execute([$user_id, $event_id]);

                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Evento já salvo']);
                    exit;
                }

                // Salvar evento
                $stmt = $pdo->prepare("INSERT INTO eventos_salvos (user_id, event_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $event_id]);

                echo json_encode(['success' => true, 'message' => 'Evento salvo com sucesso']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erro ao salvar evento: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        }
        exit;
    }

    if ($_GET['action'] === 'check_saved') {
        $event_id = $_GET['event_id'] ?? 0;
        $user_id = $_GET['user_id'] ?? 0;

        if ($event_id && $user_id) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM eventos_salvos WHERE user_id = ? AND event_id = ?");
                $stmt->execute([$user_id, $event_id]);
                echo json_encode(['saved' => (bool)$stmt->fetch()]);
            } catch (PDOException $e) {
                echo json_encode(['saved' => false]);
            }
        } else {
            echo json_encode(['saved' => false]);
        }
        exit;
    }

    // Nova ação para servir imagens
    if ($_GET['action'] === 'get_image') {
        $event_id = $_GET['event_id'] ?? 0;

        if ($event_id) {
            try {
                $stmt = $pdo->prepare("SELECT imagem FROM eventos WHERE id = ? AND imagem IS NOT NULL");
                $stmt->execute([$event_id]);
                $event = $stmt->fetch();

                if ($event && $event['imagem']) {
                    header("Content-Type: image/jpeg");
                    header("Content-Length: " . strlen($event['imagem']));
                    echo $event['imagem'];
                    exit;
                }
            } catch (PDOException $e) {
                // Silenciosamente falha
            }
        }

        // Se não encontrar imagem, retorna uma imagem padrão
        header("Content-Type: image/svg+xml");
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <svg width="400" height="200" xmlns="http://www.w3.org/2000/svg">
            <rect width="100%" height="100%" fill="#e89516"/>
            <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="16" fill="#6f390d">Imagem não disponível</text>
        </svg>';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content />
    <meta name="author" content />
    <title>Info Cultura</title>
    <!-- Favicon-->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
    <!-- Bootstrap icons-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />
    <!-- Google Fonts - Alan Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alan+Sans:wght@300..900&display=swap" rel="stylesheet">
    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="css/styles.css" rel="stylesheet" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos para o cabeçalho próximo às bordas */
        .navbar {
            padding-left: 0;
            padding-right: 0;
            font-family: 'Alan Sans', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #6f390d !important;
        }

        .navbar .container-fluid {
            padding-left: 15px;
            padding-right: 15px;
            background-color: #6f390d;
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

        /* CORREÇÃO: Footer com exatamente a mesma estrutura do nav */
        footer.bg-dark {
            background-color: #6f390d !important;
            padding-left: 0;
            padding-right: 0;
        }

        footer .container-fluid {
            padding-left: 15px;
            padding-right: 15px;
            background-color: #6f390d;
        }

        /* Garantir que ambos tenham exatamente a mesma largura */
        .navbar .container-fluid,
        footer .container-fluid {
            width: 100%;
            margin-left: 0;
            margin-right: 0;
        }

        /* Estilo para a logo - MESMO TAMANHO DAS OUTRAS PÁGINAS */
        .logo {
            height: 40px;
            width: auto;
        }

        /* Estilos para textos do footer */
        footer .small,
        footer .footer-link {
            font-family: "Alan Sans", sans-serif;
            font-optical-sizing: auto;
            font-weight: 500;
            font-style: normal;
            font-size: 1.2rem;
        }

        /* Dropdown menu styles */
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

        /* Estilos existentes */
        body {
            background-color: #e89516;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .calendar {
            max-width: 900px;
            margin: 25px auto;
            border: 1px solid #e89516;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .calendar-header {
            background-image: url('assets/fundoSite2.png');
            color: white;
            text-align: center;
            padding: 15px 0;
        }

        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            background-color: transparent;
        }

        .day {
            padding: 15px;
            border: 1px solid #6f390d;
            background-color: #ffbc6d;
            cursor: pointer;
            color: #6f390d;
            transition: all 0.2s ease;
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .day:hover {
            background-color: #e89516;
            transform: scale(1.05);
        }

        /* Estilo para dias com eventos */
        .day.event-day {
            background-color: #797624;
            color: white;
            font-weight: bold;
            position: relative;
        }

        .day.event-day:hover {
            background-color: #605d16;
            transform: scale(1.05);
        }

        /* Indicador visual para dias com eventos */
        .day.event-day::after {
            content: "";
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 8px;
            height: 8px;
            background-color: #b3ae26;
            border-radius: 50%;
        }

        /* Dias da semana sem efeitos de movimento */
        .day-name {
            font-weight: bold;
            color: #6f390d;
            background-color: #e6ae6c;
            cursor: default;
        }

        .day-name:hover {
            transform: none;
            background-color: #e6ae6c;
        }

        .empty-day {
            background-color: #e6ae6c;
            border: 1px solid #6f390d;
        }

        .calendar-controls {
            margin: 15px 0;
        }

        select.form-select {
            margin: 0 5px;
        }

        /* Estilos para o modal */
        .modal-content {
            border-radius: 15px;
            border: 2px solid #6f390d;
        }

        .modal-header {
            background-color: #6f390d;
            color: white;
            border-bottom: 5px solid #a75c1e;
        }

        .modal-body {
            padding: 25px;
            background-color: #e89516;
            max-height: 70vh;
            overflow-y: auto;
        }

        .modal-footer {
            background-color: #6f390d;
            border-top: 5px solid #a75c1e;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        /* Botões personalizados */
        .btn-custom {
            border-radius: 25px;
            padding: 8px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid;
        }

        .btn-primary-custom {
            background-color: #6f390d;
            border-color: #6f390d;
            color: white;
        }

        .btn-primary-custom:hover {
            background-color: #8a4b1d;
            border-color: #8a4b1d;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary-custom {
            background-color: #e89516;
            border-color: #e89516;
            color: white;
        }

        .btn-secondary-custom:hover {
            background-color: #ffae3a;
            border-color: #ffae3a;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Estilos para o botão salvar */
        .btn-success {
            background-color: #797624;
            border-color: #797624;
            color: white;
        }

        .btn-success:hover {
            background-color: #686e20;
            border-color: #686e20;
        }

        .event-title {
            color: #6f390d;
            font-weight: bold;
            margin-bottom: 15px;
            border-bottom: 2px solid #6f390d;
            padding-bottom: 10px;
        }

        .event-description {
            line-height: 1.6;
            margin-bottom: 20px;
            text-align: justify;
        }

        .event-details {
            background-color: #ffe9cc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #6f390d;
        }

        .event-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            margin: 15px 0;
            border: 2px solid #e89516;
            object-fit: cover;
        }

        /* Estilo para contador de múltiplos eventos */
        .event-count {
            font-size: 0.8em;
            background: #b3ae26;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            margin-left: 5px;
        }

        /* Estilo para itens de evento múltiplo */
        .event-item {
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 15px;
            border-left: 4px solid #6f390d;
            background-color: #ffe9cc;
        }

        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, .3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }

        /* Estilos para tags */
        .event-tags {
            margin: 15px 0;
        }

        .tag {
            display: inline-block;
            background-color: #797624;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin: 2px 4px 2px 0;
            font-weight: 500;
        }

        .tag-evento-cultural {
            background-color: #797624;
        }

        .tag-data-cultural {
            background-color: #6f390d;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .day {
                padding: 10px;
                min-height: 60px;
            }

            .modal-dialog {
                margin: 20px;
            }

            .modal-footer {
                flex-direction: column;
                gap: 10px;
            }

            .btn-custom {
                width: 100%;
            }

            .event-image {
                max-height: 200px;
            }
        }
    </style>
</head>

<body class="d-flex flex-column h-100">
    <main class="flex-shrink-0">
        <!-- Navigation - Cabeçalho ajustado para ficar próximo às bordas -->
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid px-4">
                <a class="navbar-brand" href="index.php">
                    <img src="assets/logoo.png" alt="Info Cultura" class="logo">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation"><span
                        class="navbar-toggler-icon"></span></button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
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
                                    <li><span class="dropdown-item-text text-white text-break">Olá, <?php echo htmlspecialchars($_SESSION['user']['nome']); ?></span></li>
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

        <!-- Calendário Interativo -->
        <div class="calendar">
            <div class="calendar-header">
                <h2 id="calendar-title">Calendário Cultural</h2>
                <div class="calendar-controls">
                    <?php
                    $anoAtual = date("Y");
                    $anoLimite = $anoAtual + 1;

                    echo '<select id="year-select" class="form-select d-inline-block w-auto">\n';
                    for ($ano = 2022; $ano <= $anoLimite; $ano++) {
                        echo '    <option value=' . $ano . '>' . $ano . '</option>';
                    }
                    echo "</select>\n"; ?>
                    <select id="month-select" class="form-select d-inline-block w-auto">
                        <option value="0">Janeiro</option>
                        <option value="1">Fevereiro</option>
                        <option value="2">Março</option>
                        <option value="3">Abril</option>
                        <option value="4">Maio</option>
                        <option value="5">Junho</option>
                        <option value="6">Julho</option>
                        <option value="7">Agosto</option>
                        <option value="8">Setembro</option>
                        <option value="9">Outubro</option>
                        <option value="10">Novembro</option>
                        <option value="11">Dezembro</option>
                    </select>
                </div>
            </div>
            <div id="calendar-days" class="calendar-days"></div>
        </div>

        <!-- Modal para informações do dia -->
        <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eventModalLabel">Evento Cultural</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="modal-body">
                        <h4 class="event-title" id="modal-date"></h4>
                        <div id="modal-content">
                            <!-- Conteúdo do evento será inserido aqui -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary-custom btn-custom"
                            data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
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
                    <a class="link-light small footer-link" href="?logout=true">Sair</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap core JS-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const monthNames = [
            "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho",
            "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"
        ];

        const dayNames = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"];

        // Objeto global para armazenar eventos
        let eventos = {};

        // Função para verificar se usuário está logado
        function isUserLoggedIn() {
            return <?php echo isset($_SESSION['user']) && $_SESSION['user']['loggedIn'] ? 'true' : 'false'; ?>;
        }

        // Função para obter o ID do usuário logado
        function getUserId() {
            return <?php echo isset($_SESSION['user']) ? $_SESSION['user']['id'] : '0'; ?>;
        }

        // Função para salvar evento
        async function saveEvent(eventId) {
            if (!isUserLoggedIn()) {
                alert('Você precisa estar logado para salvar eventos!');
                window.location.href = 'login.php';
                return;
            }

            try {
                const response = await fetch('calendario.php?action=save_event', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `event_id=${eventId}&user_id=${getUserId()}`
                });

                const result = await response.json();

                if (result.success) {
                    alert('Evento salvo com sucesso!');
                    // Atualizar o botão
                    const saveBtn = document.querySelector(`[onclick="saveEvent(${eventId})"]`);
                    if (saveBtn) {
                        saveBtn.innerHTML = '<i class="fas fa-check me-1"></i> Salvo';
                        saveBtn.classList.remove('btn-success');
                        saveBtn.classList.add('btn-secondary');
                        saveBtn.onclick = null;
                    }
                } else {
                    alert('Erro ao salvar evento: ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao salvar evento');
            }
        }

        // Função para verificar se evento já foi salvo
        async function checkEventSaved(eventId) {
            if (!isUserLoggedIn()) return false;

            try {
                const response = await fetch(`calendario.php?action=check_saved&event_id=${eventId}&user_id=${getUserId()}`);
                const result = await response.json();
                return result.saved;
            } catch (error) {
                console.error('Erro ao verificar evento salvo:', error);
                return false;
            }
        }

        // Função para buscar eventos do banco de dados
        async function fetchEventsFromDB(year, month) {
            try {
                const response = await fetch(`api/get_events.php`);
                if (!response.ok) {
                    throw new Error('Erro ao buscar eventos');
                }
                const eventosDB = await response.json();

                const formattedEvents = {};

                eventosDB.forEach(event => {
                    try {
                        // Processar a data corretamente para evitar problema de UTC
                        const date = new Date(event.data + 'T00:00:00'); // Adicionar horário para evitar UTC

                        const day = date.getDate().toString().padStart(2, '0');
                        const month = date.getMonth(); // Já está 0-11
                        const year = date.getFullYear();

                        // Usar a mesma chave que será usada no calendário
                        const dateKey = `${year}-${month}-${day}`;

                        // Gerar tag baseada na coluna "eventos"
                        const tags = generateEventTags(event);

                        // Buscar o nome do núcleo baseado no id_nucleo
                        const nucleoNome = getNucleoNome(event.id_nucleo);

                        const eventData = {
                            id: event.id,
                            titulo: event.nome,
                            descricao: event.descricao,
                            local: event.local_evento,
                            data: event.data,
                            imagem: event.imagem, // Agora incluímos a informação da imagem
                            importancia: event.importancia,
                            nucleo: nucleoNome,
                            id_nucleo: event.id_nucleo,
                            eventos: event.eventos,
                            tags: tags
                        };

                        if (formattedEvents[dateKey]) {
                            formattedEvents[dateKey].push(eventData);
                        } else {
                            formattedEvents[dateKey] = [eventData];
                        }
                    } catch (error) {
                        console.error('Erro ao processar evento:', event, error);
                    }
                });

                return formattedEvents;
            } catch (error) {
                console.error('Erro ao buscar eventos:', error);
                return {};
            }
        }

        // Função para obter o nome do núcleo baseado no ID
        function getNucleoNome(idNucleo) {
            const nucleos = {
                1: 'NEABI - Campus Arapongas',
                2: 'NEABI - Campus Assis Chateaubriand',
                3: 'NEABI - Campus Astorga',
                4: 'NEABI - Campus Barracão',
                5: 'NEABI - Campus Campo Largo',
                6: 'NEABI - Campus Capanema',
                7: 'NEABI - Campus Cascavel',
                8: 'NEABI - Campus Colombo',
                9: 'NEABI - Campus Coronel Vivida',
                10: 'NEABI - Campus Curitiba',
                11: 'NEABI - Campus Foz do Iguaçu',
                12: 'NEABI - Campus Goioerê',
                13: 'NEABI - Campus Irati',
                14: 'NEABI - Campus Ivaiporã',
                15: 'NEABI - Campus Jacarezinho',
                16: 'NEABI - Campus Jaguariaíva',
                17: 'NEABI - Campus Londrina',
                18: 'NEABI - Campus Palmas',
                19: 'NEABI - Campus Paranaguá',
                20: 'NEABI - Campus Paranavaí',
                21: 'NEABI - Campus Pinhais',
                22: 'NEABI - Campus Pitanga',
                23: 'NEABI - Campus Ponta Grossa',
                24: 'NEABI - Campus Quedas do Iguaçu',
                25: 'NEABI - Campus Telêmaco Borba',
                26: 'NEABI - Campus Toledo',
                27: 'NEABI - Campus Umuarama',
                28: 'NEABI - Campus União da Vitória'
            };

            return nucleos[idNucleo] || 'NEABI';
        }

        // Função para gerar tags baseadas na coluna "eventos"
        function generateEventTags(event) {
            const tags = [];

            if (event.eventos === 'sim') {
                tags.push({
                    text: 'Evento Cultural',
                    class: 'tag-evento-cultural'
                });
            } else {
                tags.push({
                    text: 'Data Cultural',
                    class: 'tag-data-cultural'
                });
            }

            return tags;
        }

        // Função para renderizar tags
        function renderTags(tags) {
            if (!tags || tags.length === 0) return '';

            return `
                <div class="event-tags">
                    ${tags.map(tag => `<span class="tag ${tag.class}">${tag.text}</span>`).join('')}
                </div>
            `;
        }

        // Função para obter URL da imagem do evento
        function getEventImageUrl(eventId) {
            return `calendario.php?action=get_image&event_id=${eventId}&t=${Date.now()}`;
        }

        // Função para verificar se um ano é bissexto
        function isLeapYear(year) {
            return (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0);
        }

        // Função para obter o número de dias em um mês
        function getDaysInMonth(year, month) {
            const daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

            if (month === 1 && isLeapYear(year)) {
                return 29;
            }

            return daysInMonth[month];
        }

        // Função principal para gerar o calendário
        async function generateCalendar(year, month) {
            const daysContainer = document.getElementById('calendar-days');
            daysContainer.innerHTML = '';
            const title = document.getElementById('calendar-title');
            title.innerText = `${monthNames[month]} ${year}`;

            // Buscar eventos do banco
            eventos = await fetchEventsFromDB(year, month);

            // Adiciona os dias da semana
            dayNames.forEach(day => {
                const dayNameElement = document.createElement('div');
                dayNameElement.className = 'day day-name';
                dayNameElement.innerText = day;
                daysContainer.appendChild(dayNameElement);
            });

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = getDaysInMonth(parseInt(year), parseInt(month));

            // Dias vazios no início
            for (let i = 0; i < firstDay; i++) {
                const emptyDay = document.createElement('div');
                emptyDay.className = 'day empty-day';
                emptyDay.innerHTML = '&nbsp;';
                daysContainer.appendChild(emptyDay);
            }

            // Dias do mês
            for (let day = 1; day <= daysInMonth; day++) {
                const dayElement = document.createElement('div');
                dayElement.className = 'day';
                dayElement.innerText = day;

                const dayFormatted = day.toString().padStart(2, '0');
                const eventKey = `${year}-${month}-${dayFormatted}`;

                // Verifica se há evento neste dia
                if (eventos[eventKey]) {
                    dayElement.classList.add('event-day');
                    if (eventos[eventKey].length > 1) {
                        dayElement.innerHTML += ` <span class="event-count">${eventos[eventKey].length}</span>`;
                    }
                }

                dayElement.onclick = () => showEventModal(year, month, day);
                daysContainer.appendChild(dayElement);
            }

            // Preenche os dias restantes
            const totalCells = 42;
            const filledCells = firstDay + daysInMonth;
            const remainingCells = totalCells - filledCells;

            for (let i = 0; i < remainingCells; i++) {
                const emptyDay = document.createElement('div');
                emptyDay.className = 'day empty-day';
                emptyDay.innerHTML = '&nbsp;';
                daysContainer.appendChild(emptyDay);
            }
        }

        // Função para formatar data
        function formatDate(dateString) {
            const date = new Date(dateString + 'T00:00:00'); // Adicionar horário
            return {
                date: date.toLocaleDateString('pt-BR'),
                weekday: dayNames[date.getDay()]
            };
        }

        // Função para mostrar o modal com os eventos do dia
        async function showEventModal(year, month, day) {
            const dayFormatted = day.toString().padStart(2, '0');
            const eventKey = `${year}-${month}-${dayFormatted}`;

            const modalDate = document.getElementById('modal-date');
            const modalContent = document.getElementById('modal-content');

            modalDate.textContent = `${day} de ${monthNames[month]} de ${year}`;

            // Mostrar indicador de carregamento
            modalContent.innerHTML = `
                <div class="text-center">
                    <span class="loading-spinner"></span> Carregando informações...
                </div>
            `;

            const modal = new bootstrap.Modal(document.getElementById('eventModal'));
            modal.show();

            const dayEvents = eventos[eventKey];

            if (dayEvents && dayEvents.length > 0) {
                let modalHTML = '';

                if (dayEvents.length === 1) {
                    // Um único evento
                    const event = dayEvents[0];
                    modalHTML = await formatEventHTML(event);
                } else {
                    // Múltiplos eventos
                    modalHTML = `<h5>${dayEvents.length} Eventos neste dia</h5>`;

                    for (const event of dayEvents) {
                        const dateInfo = formatDate(event.data);
                        const isSaved = await checkEventSaved(event.id);
                        const saveButton = (event.eventos === 'sim' && !isSaved) ?
                            `<button onclick="saveEvent(${event.id})" class="btn btn-success btn-sm mt-2"><i class="fas fa-bookmark me-1"></i> Salvar</button>` :
                            (event.eventos === 'sim' ? '<button class="btn btn-secondary btn-sm mt-2" disabled><i class="fas fa-check me-1"></i> Salvo</button>' : '');

                        modalHTML += `
                            <div class="event-item">
                                <h6>${event.titulo}</h6>
                                ${event.tags ? renderTags(event.tags) : ''}
                                <p class="mb-1"><strong>Data:</strong> ${dateInfo.weekday}, ${dateInfo.date}</p>
                                ${event.local ? `<p class="mb-1"><strong>Local:</strong> ${event.local}</p>` : ''}
                                ${event.nucleo ? `<p class="mb-1"><strong>Núcleo:</strong> ${event.nucleo}</p>` : ''}
                                ${saveButton}
                                <button class="btn btn-sm btn-primary-custom mt-2" onclick="showEventDetails(${event.id})">
                                    Ver Detalhes
                                </button>
                            </div>
                        `;
                    }
                }

                modalContent.innerHTML = modalHTML;
            } else {
                modalContent.innerHTML = `
                    <div class="event-description">
                        <p>Não há eventos culturais cadastrados para esta data.</p>
                    </div>
                    <div class="event-details">
                        <strong>Informações:</strong> Consulte nossa programação regular para mais detalhes.
                    </div>
                `;
            }
        }

        // Função para formatar HTML do evento
        async function formatEventHTML(event) {
            const dateInfo = formatDate(event.data);
            const isSaved = await checkEventSaved(event.id);

            // Verificar se o evento tem imagem
            const hasImage = event.imagem !== null && event.imagem !== '';

            const imageHTML = hasImage ? `
                <img src="${getEventImageUrl(event.id)}" alt="${event.titulo}" class="event-image">
            ` : '';

            const saveButton = (event.eventos === 'sim' && !isSaved) ? `
                <div class="mt-4">
                    <button onclick="saveEvent(${event.id})" class="btn btn-success btn-sm">
                        <i class="fas fa-bookmark me-1"></i> Salvar Evento
                    </button>
                </div>
            ` : (event.eventos === 'sim' ? `
                <div class="mt-4">
                    <button class="btn btn-secondary btn-sm" disabled>
                        <i class="fas fa-check me-1"></i> Salvo
                    </button>
                </div>
            ` : '');

            return `
                <h5>${event.titulo}</h5>
                ${event.tags ? renderTags(event.tags) : ''}
                ${imageHTML}
                <div class="event-description">${event.descricao}</div>
                <div class="event-details">
                    <strong>Data:</strong> ${dateInfo.weekday}, ${dateInfo.date}<br>
                    ${event.local ? `<strong>Local:</strong> ${event.local}<br>` : ''}
                    ${event.nucleo ? `<strong>Núcleo Responsável:</strong> ${event.nucleo}<br>` : ''}
                    ${event.importancia ? `<strong>Resultados e Impacto:</strong> ${event.importancia}` : ''}
                </div>
                ${saveButton}
            `;
        }

        // Função para mostrar detalhes de um evento específico
        async function showEventDetails(eventId) {
            const modalContent = document.getElementById('modal-content');

            modalContent.innerHTML = `
                <div class="text-center">
                    <span class="loading-spinner"></span> Carregando detalhes...
                </div>
            `;

            try {
                const response = await fetch(`api/get_events.php`);
                const eventos = await response.json();

                const event = eventos.find(e => e.id === eventId);

                if (!event) {
                    modalContent.innerHTML = `<p class="text-danger">Erro: Evento não encontrado.</p>`;
                    return;
                }

                const nucleoNome = getNucleoNome(event.id_nucleo);
                const tags = generateEventTags(event);
                const isSaved = await checkEventSaved(event.id);

                // Verificar se o evento tem imagem
                const hasImage = event.imagem !== null && event.imagem !== '';
                const imageHTML = hasImage ? `
                    <img src="${getEventImageUrl(event.id)}" alt="${event.nome}" class="event-image">
                ` : '';

                const saveButton = (event.eventos === 'sim' && !isSaved) ?
                    `<div class="mt-4"><button onclick="saveEvent(${event.id})" class="btn btn-success btn-sm"><i class="fas fa-bookmark me-1"></i> Salvar Evento</button></div>` :
                    (event.eventos === 'sim' ? `<div class="mt-4"><button class="btn btn-secondary btn-sm" disabled><i class="fas fa-check me-1"></i> Salvo</button></div>` : '');

                modalContent.innerHTML = `
                    <h5>${event.nome}</h5>
                    ${renderTags(tags)}
                    ${imageHTML}
                    <div class="event-description">${event.descricao}</div>
                    <div class="event-details">
                        <strong>Data:</strong> ${new Date(event.data + 'T00:00:00').toLocaleDateString('pt-BR')}<br>
                        ${event.local_evento ? `<strong>Local:</strong> ${event.local_evento}<br>` : ''}
                        ${event.id_nucleo ? `<strong>Núcleo Responsável:</strong> ${nucleoNome}<br>` : ''}
                        ${event.importancia ? `<strong>Resultados e Impacto:</strong> ${event.importancia}` : ''}
                    </div>
                    ${saveButton}
                `;

            } catch (error) {
                console.error('Erro ao buscar detalhes:', error);
                modalContent.innerHTML = `<p class="text-danger">Erro ao carregar detalhes.</p>`;
            }
        }

        // Event listeners para os selects
        document.getElementById('year-select').addEventListener('change', function() {
            const year = parseInt(this.value);
            const month = parseInt(document.getElementById('month-select').value);
            generateCalendar(year, month);
        });

        document.getElementById('month-select').addEventListener('change', function() {
            const year = parseInt(document.getElementById('year-select').value);
            const month = parseInt(this.value);
            generateCalendar(year, month);
        });

        // Inicializar o calendário com o mês e ano atuais
        const currentDate = new Date();
        document.getElementById('year-select').value = currentDate.getFullYear();
        document.getElementById('month-select').value = currentDate.getMonth();
        generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
    </script>
</body>

</html>