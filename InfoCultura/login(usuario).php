<?php
// Iniciar sessão para gerenciar login
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

// Configurações do banco de dados
$host = 'localhost';
$dbname = 'info_cultura';
$username = 'root';
$password = '';

// Conectar ao banco de dados
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

// Verificar se usuário está logado
if (!isset($_SESSION['user']) || !$_SESSION['user']['loggedIn']) {
    header('Location: login.php');
    exit;
}

// Buscar informações do usuário no banco
$user_id = $_SESSION['user']['id'];
try {
    $stmt = $pdo->prepare("SELECT id, nome, email, tipo_usuario FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_data) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    die("Erro ao buscar dados do usuário: " . $e->getMessage());
}

// Processar remoção de evento salvo
if (isset($_GET['action']) && $_GET['action'] === 'remove_saved' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $saved_id = $_POST['saved_id'] ?? 0;

    if ($saved_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM eventos_salvos WHERE id = ? AND user_id = ?");
            $stmt->execute([$saved_id, $user_id]);

            echo json_encode(['success' => true, 'message' => 'Evento removido com sucesso']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao remover evento: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
    }
    exit;
}

// Buscar eventos salvos do usuário
$saved_events = [];
try {
    $stmt = $pdo->prepare("
        SELECT e.*, es.id as saved_id
        FROM eventos e 
        INNER JOIN eventos_salvos es ON e.id = es.event_id 
        WHERE es.user_id = ?
        ORDER BY e.data DESC
    ");
    $stmt->execute([$user_id]);
    $saved_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $saved_events = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Info Cultura - Perfil</title>
    <!-- Favicon-->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Importando a fonte Alan Sans do Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alan+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Importando Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />

    <style>
        .navbar {
            padding-left: 0;
            padding-right: 0;
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

        footer .small,
        footer .footer-link {
            font-family: "Alan Sans", sans-serif;
            font-optical-sizing: auto;
            font-weight: 500;
            font-style: normal;
            font-size: 1.2rem;
        }

        .navbar .container-fluid,
        footer .container-fluid {
            max-width: 100%;
        }

        .logo {
            height: 40px;
        }

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

        .gradient-bg {
            background-image: url(assets/fundoFooter.png);
        }

        .event-card {
            transition: transform 0.2s ease;
            border: 1px solid #e2e8f0;
        }

        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-content {
            background-color: white;
            border-radius: 8px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: #64748b;
            z-index: 10;
        }

        .modal-close:hover {
            color: #334155;
        }

        .hidden {
            display: none !important;
        }
    </style>
</head>

<body class="d-flex flex-column">
    <main class="flex-shrink-0">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid px-4">
                <a class="navbar-brand" href="index.php">
                    <img src="assets/logoo.png" alt="Info Cultura" class="logo">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
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
        </nav>

        <!-- Container principal -->
        <div class="container mx-auto px-4 py-8">
            <!-- Header do perfil -->
            <header class="gradient-bg text-white rounded-lg p-6 mb-8">
                <div class="flex flex-col md:flex-row items-center">
                    <div class="w-24 h-24 mb-4 md:mb-0 md:mr-6 relative">
                        <img src="assets/ftUsuario.jpg"
                            class="w-full h-full object-cover rounded-full border-2 border-white">
                    </div>

                    <div class="text-center md:text-left">
                        <h1 id="profile-name" class="text-2xl font-semibold"><?php echo htmlspecialchars($user_data['nome']); ?></h1>
                        <p id="profile-email" class="text-white opacity-90"><?php echo htmlspecialchars($user_data['email']); ?></p>
                        <p id="profile-type" class="text-white opacity-80 text-sm mt-1">
                            <i class="fas fa-user-tag mr-1"></i>
                            <?php
                            $tipo_usuario = $user_data['tipo_usuario'];
                            $tipo_texto = [
                                'super_adm' => 'Super Administrador',
                                'membro_neabi' => 'Membro NEABI',
                                'comum' => 'Usuário Comum'
                            ];
                            echo $tipo_texto[$tipo_usuario] ?? 'Usuário';
                            ?>
                        </p>
                    </div>
                </div>
            </header>

            <!-- Seção de eventos salvos -->
            <section class="mb-12">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                    <i class="fas fa-bookmark text-[#797624] mr-2"></i> Eventos Salvos
                </h2>

                <div id="no-events-message" class="bg-white p-8 rounded-lg border border-gray-100 text-center <?php echo empty($saved_events) ? '' : 'hidden'; ?>">
                    <i class="fas fa-calendar-plus text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">Nenhum evento salvo</h3>
                    <p class="text-gray-500 mb-4">Explore eventos culturais e salve os que mais gostar.</p>
                    <a href="calendario.php"
                        class="bg-[#797624] hover:bg-[#686e20] text-white px-5 py-1.5 rounded-full font-medium transition text-sm">
                        Explorar Eventos
                    </a>
                </div>

                <div id="events-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <?php if (!empty($saved_events)): ?>
                        <?php foreach ($saved_events as $event): ?>
                            <div class="event-card bg-white rounded-lg overflow-hidden flex flex-col border border-gray-200">
                                <div class="p-5 flex-grow">
                                    <div class="flex justify-between items-start mb-3">
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($event['nome']); ?></h3>
                                        <button onclick="removeSavedEvent(<?php echo $event['saved_id']; ?>, <?php echo $event['id']; ?>)"
                                            class="text-gray-400 hover:text-red-500 transition" title="Remover dos salvos">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <p class="text-gray-600 text-sm mb-4 leading-relaxed">
                                        <?php echo htmlspecialchars($event['descricao']); ?>
                                    </p>
                                    <?php if (isset($event['data'])): ?>
                                        <div class="flex items-center text-gray-500 text-xs mb-2">
                                            <i class="fas fa-calendar-day mr-1.5"></i>
                                            <span><?php echo date('d/m/Y', strtotime($event['data'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex items-center text-gray-500 text-xs mb-3">
                                        <i class="fas fa-map-marker-alt mr-1.5"></i>
                                        <span><?php echo htmlspecialchars($event['local_evento']); ?></span>
                                    </div>
                                    <?php if ($event['eventos'] === 'sim'): ?>
                                        <span class="inline-block bg-[#797624] text-white text-xs px-2 py-1 rounded-full">
                                            Evento Cultural
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-block bg-[#6f390d] text-white text-xs px-2 py-1 rounded-full">
                                            Data Cultural
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="px-5 pb-4">
                                    <button onclick="showEventDetails(<?php echo $event['id']; ?>)"
                                        class="w-full bg-[#797624] hover:bg-[#686e20] text-white text-sm font-medium py-2 px-4 rounded transition flex items-center justify-center">
                                        <i class="fas fa-eye mr-2"></i> Ver Detalhes
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Modal de confirmação para remover evento -->
        <div id="confirm-modal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 px-4">
            <div class="bg-white rounded-lg p-5 max-w-md w-full">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-medium text-gray-800">Remover evento?</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p class="mb-5 text-gray-600">Tem certeza que deseja remover este evento dos seus salvos?</p>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeModal()"
                        class="px-4 py-1.5 rounded text-gray-700 bg-gray-100 hover:bg-gray-200 text-sm">
                        Cancelar
                    </button>
                    <button id="confirm-delete"
                        class="bg-[#e53e3e] hover:bg-[#c53030] text-white px-4 py-1.5 rounded text-sm">
                        Remover
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de detalhes do evento -->
        <div id="event-details-modal" class="modal-overlay">
            <div class="modal-content">
                <button class="modal-close" onclick="closeDetailsModal()">
                    <i class="fas fa-times"></i>
                </button>
                <div class="p-6">
                    <h2 id="modal-event-title" class="text-xl font-semibold text-gray-800 mb-3"></h2>

                    <div class="flex items-center text-sm text-gray-500 mb-4">
                        <i class="fas fa-calendar-day mr-2"></i>
                        <span id="modal-event-date" class="mr-4"></span>
                        <i class="fas fa-clock mr-2"></i>
                        <span id="modal-event-time"></span>
                    </div>

                    <div class="flex items-center text-sm text-gray-500 mb-5">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <span id="modal-event-location"></span>
                    </div>

                    <p id="modal-event-description" class="text-gray-700 mb-5 leading-relaxed"></p>

                    <div class="mb-5">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Detalhes</h3>
                        <p id="modal-event-details" class="text-gray-600 text-sm leading-relaxed"></p>
                    </div>

                    <div class="flex justify-between">
                        <button id="remove-from-profile"
                            class="text-sm text-[#e53e3e] hover:text-[#c53030] px-4 py-1.5 rounded border border-[#e53e3e] hover:border-[#c53030] transition">
                            <i class="fas fa-trash-alt mr-1.5"></i> Remover Evento
                        </button>
                        <button onclick="closeDetailsModal()"
                            class="text-sm text-gray-600 hover:text-gray-800 px-4 py-1.5 rounded border border-gray-300 hover:border-gray-400 transition">
                            Fechar
                        </button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const savedEvents = <?php echo json_encode($saved_events); ?>;

        let eventToDelete = null;
        let currentEventDetails = null;

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('remove-from-profile').addEventListener('click', function() {
                if (currentEventDetails) {
                    openDeleteModal(currentEventDetails.saved_id, currentEventDetails.id);
                    closeDetailsModal();
                }
            });
        });

        function openDeleteModal(savedId, eventId) {
            eventToDelete = {
                savedId,
                eventId
            };
            document.getElementById('confirm-modal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('confirm-modal').classList.add('hidden');
            eventToDelete = null;
        }

        async function deleteEvent() {
            if (eventToDelete) {
                try {
                    const response = await fetch('login(usuario).php?action=remove_saved', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `saved_id=${eventToDelete.savedId}`
                    });

                    const result = await response.json();

                    if (result.success) {
                        const eventElement = document.querySelector(`[onclick="removeSavedEvent(${eventToDelete.savedId}, ${eventToDelete.eventId})"]`)?.closest('.event-card');
                        if (eventElement) {
                            eventElement.remove();
                        }

                        if (document.querySelectorAll('.event-card').length === 0) {
                            document.getElementById('no-events-message').classList.remove('hidden');
                        }

                        alert('Evento removido dos salvos!');
                    } else {
                        alert('Erro ao remover evento: ' + result.message);
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    alert('Erro ao remover evento');
                } finally {
                    closeModal();
                }
            }
        }

        function removeSavedEvent(savedId, eventId) {
            openDeleteModal(savedId, eventId);
        }

        function showEventDetails(eventId) {
            const event = savedEvents.find(e => e.id == eventId);
            if (event) {
                currentEventDetails = event;

                document.getElementById('modal-event-title').textContent = event.nome;
                document.getElementById('modal-event-description').textContent = event.descricao;

                if (event.data) {
                    const date = new Date(event.data);
                    document.getElementById('modal-event-date').textContent = date.toLocaleDateString('pt-BR');
                    document.getElementById('modal-event-time').textContent = '';
                } else {
                    document.getElementById('modal-event-date').textContent = 'Data não informada';
                    document.getElementById('modal-event-time').textContent = '';
                }

                document.getElementById('modal-event-location').textContent = event.local_evento || 'Local não informado';
                document.getElementById('modal-event-details').textContent = event.importancia || 'Detalhes adicionais não disponíveis.';

                document.getElementById('event-details-modal').style.display = 'flex';
            }
        }

        function closeDetailsModal() {
            document.getElementById('event-details-modal').style.display = 'none';
            currentEventDetails = null;
        }

        document.getElementById('confirm-delete').addEventListener('click', deleteEvent);

        document.getElementById('event-details-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDetailsModal();
            }
        });

        document.getElementById('confirm-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>

</html>