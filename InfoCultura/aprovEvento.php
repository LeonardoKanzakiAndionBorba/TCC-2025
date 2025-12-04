<?php
session_start();

// VERIFICAÇÃO DE LOGOUT
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Verificar se o usuário está logado e é super administrador
if (!isset($_SESSION['user']) || !$_SESSION['user']['loggedIn'] || $_SESSION['user']['tipo_usuario'] !== 'super_adm') {
    header("Location: login.php");
    exit();
}

// Conexão com o banco
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "info_cultura";

// Processar aprovação/rejeição via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Erro de conexão: ' . $conn->connect_error]);
        exit();
    }

    $event_id = $_POST['event_id'];
    $action = $_POST['action'];
    $reason = $_POST['reason'] ?? '';

    // Determinar o novo status baseado na ação
    if ($action === 'approve') {
        $new_status = 'aprovado';
    } elseif ($action === 'reject') {
        $new_status = 'rejeitado';
    } elseif ($action === 'pending') {
        $new_status = 'pendente';
    } else {
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
        exit();
    }

    // Atualizar o status do evento
    $sql = "UPDATE eventos SET status_evento = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $event_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Evento movido para ' . $new_status . ' com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar evento: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// Buscar eventos do banco
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Buscar eventos pendentes
$sql_pendentes = "SELECT e.*, n.nome as nucleo_nome 
                  FROM eventos e 
                  LEFT JOIN nucleos n ON e.id_nucleo = n.id 
                  WHERE e.status_evento = 'pendente' AND e.eventos = 'sim'
                  ORDER BY e.data ASC";
$result_pendentes = $conn->query($sql_pendentes);

// Buscar eventos aprovados
$sql_aprovados = "SELECT e.*, n.nome as nucleo_nome 
                  FROM eventos e 
                  LEFT JOIN nucleos n ON e.id_nucleo = n.id 
                  WHERE e.status_evento = 'aprovado' AND e.eventos = 'sim'
                  ORDER BY e.data ASC";
$result_aprovados = $conn->query($sql_aprovados);

// Buscar eventos rejeitados
$sql_rejeitados = "SELECT e.*, n.nome as nucleo_nome 
                   FROM eventos e 
                   LEFT JOIN nucleos n ON e.id_nucleo = n.id 
                   WHERE e.status_evento = 'rejeitado' AND e.eventos = 'sim'
                   ORDER BY e.data ASC";
$result_rejeitados = $conn->query($sql_rejeitados);

$conn->close();

// Função auxiliar para evitar erros com valores nulos
function safe_html($value)
{
    return htmlspecialchars($value ?? '');
}

// ========== VARIÁVEIS PARA O NAV ==========
$userLoggedIn = true; // Na curadoria, o usuário sempre está logado (super_adm)
$userName = $_SESSION['user']['nome'] ?? 'Super Administrador';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content />
    <meta name="author" content />
    <title>Info Cultura - Curadoria</title>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Aplicar a fonte Alan Sans globalmente */
        body {
            font-family: "Alan Sans", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
            margin: 0;
            background-color: #e89516;
            color: #333;
        }

        /* Estilos para o cabeçalho próximo às bordas */
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #e6ae6c;
            border: 1px solid #6f390d;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 0 100px rgba(0, 0, 0, 0.1);
        }

        /* Título principal centralizado e menor */
        .main-title {
            color: #6f390d;
            margin-top: 30px;
            padding: 5px;
            font-family: "Alan Sans", sans-serif;
            font-weight: 700;
            text-align: center;
            font-size: 2rem;
            /* Diminuído de aproximadamente 2.5rem para 2rem */
        }

        h2 {
            color: #6f390d;
            margin-top: 40px;
            font-family: "Alan Sans", sans-serif;
            font-weight: 600;
        }

        h3 {
            font-family: "Alan Sans", sans-serif;
            font-weight: 600;
        }

        .events-table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        th {
            background-color: #a75c1e;
            color: white;
            position: sticky;
            top: 0;
            font-family: "Alan Sans", sans-serif;
            font-weight: 600;
        }

        tr:hover {
            background-color: #ffbc6d;
        }

        .btn-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
            white-space: nowrap;
            font-family: "Alan Sans", sans-serif;
            font-weight: 500;
        }

        .approve {
            background-color: #797624;
            color: white;
        }

        .reject {
            background-color: #f54e0c;
            color: white;
        }

        .approve:hover {
            background-color: #605d16;
            color: white
        }

        .reject:hover {
            background-color: #cd3e05;
            color: white;
        }

        #approvedEvents {
            margin-top: 30px;
        }

        .event-card {
            background-color: #ffbc6d;
            border: 1px solid #6f390d;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            position: relative;
        }

        .rejected-event {
            background-color: #ffbc6d;
            border: 1px solid #6f390d;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .event-info {
            flex: 1;
            min-width: 300px;
        }

        .event-actions {
            display: flex;
            align-items: flex-start;
            padding-top: 10px;
        }

        /* Tag de status */
        .status-tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .status-aprovado {
            background-color: #797624;
            color: white;
        }

        .status-rejeitado {
            background-color: #cd3e05;
            color: white;
        }

        .status-pendente {
            background-color: #ffc107;
            color: #212529;
        }

        /* Estilo para eventos comprimidos */
        .eventos-comprimidos {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease-in-out;
        }

        .eventos-comprimidos.expandido {
            max-height: 5000px;
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

        .loading {
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border-left-color: #797624;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Estilo para o formulário de justificativa */
        .justification-form {
            margin-top: 15px;
        }

        .justification-textarea {
            width: 100%;
            min-height: 100px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            font-family: "Alan Sans", sans-serif;
        }

        .justification-textarea:focus {
            outline: none;
            border-color: #e89516;
        }

        .rejected-events {
            margin-top: 40px;
        }

        .justification-text {
            background-color: #fff;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            border-left: 3px solid #f54e0c;
        }

        .email-preview {
            background-color: #ffbc6c;
            border: 1px solid #6f390d;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
            font-family: "Alan Sans", sans-serif;
        }

        .email-header {
            border-bottom: 1px solid #6f390d;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .email-content {
            line-height: 1.6;
        }

        .user-info {
            font-size: 0.9em;
            color: #a75c1e;
            margin-top: 5px;
        }

        .event-image {
            max-width: 100px;
            max-height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-top: 5px;
        }

        /* SweetAlert customização com Alan Sans */
        .swal2-title,
        .swal2-content,
        .swal2-confirm,
        .swal2-cancel {
            font-family: "Alan Sans", sans-serif !important;
        }

        /* Responsividade */
        @media (max-width: 992px) {
            .container {
                padding: 10px;
            }

            .btn-container {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                margin-bottom: 5px;
            }
        }

        @media (max-width: 768px) {

            th,
            td {
                padding: 8px 10px;
                font-size: 0.9em;
            }

            .event-info {
                min-width: 250px;
            }

            .navbar-brand,
            .navbar-nav .nav-link {
                font-size: 1.1rem;
            }

            footer.bg-dark,
            footer .small,
            footer .footer-link {
                font-size: 1rem;
            }

            .main-title {
                font-size: 1.7rem;
                /* Ajuste para mobile */
            }
        }
    </style>
</head>

<body class="d-flex flex-column h-100">
    <main class="flex-shrink-0">
        <!-- Navigation - ATUALIZADO para ficar igual ao código fornecido -->
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
                                    <li><span class="dropdown-item-text text-white text-break">Olá, <?php echo safe_html($userName); ?></span></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="login(usuario).php"><i class="fas fa-user me-2"></i>Meu Perfil</a></li>
                                    <?php if ($_SESSION['user']['tipo_usuario'] === 'super_adm'): ?>
                                        <li><a class="dropdown-item" href="areaAdm.php"><i class="fas fa-cog me-2"></i>Área Administrativa</a></li>
                                        <li><a class="dropdown-item" href="#"><i class="fas fa-star me-2"></i>Curadoria</a></li>
                                    <?php endif; ?>
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

        <!-- Conteúdo principal -->
        <!-- Título principal centralizado fora da div container -->
        <h1 class="main-title">Curadoria de Eventos - Super Administrador</h1>

        <div class="container px-5 my-5">
            <div id="pendingEvents">
                <h2>Eventos Pendentes de Aprovação</h2>
                <?php if ($result_pendentes && $result_pendentes->num_rows > 0): ?>
                    <div class="events-table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nome do Evento</th>
                                    <th>Data</th>
                                    <th>Local</th>
                                    <th>Núcleo</th>
                                    <th>Descrição</th>
                                    <th style="width: 180px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($evento = $result_pendentes->fetch_assoc()): ?>
                                    <tr id="event-<?php echo $evento['id']; ?>">
                                        <td>
                                            <strong><?php echo safe_html($evento['nome']); ?></strong>
                                            <?php if ($evento['imagem']): ?>
                                                <div>
                                                    <img src="data:image/jpeg;base64,<?= base64_encode($evento['imagem']) ?>"
                                                        alt="<?php echo safe_html($evento['nome']); ?>"
                                                        class="event-image">
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($evento['data'])); ?></td>
                                        <td><?php echo safe_html($evento['local_evento']); ?></td>
                                        <td><?php echo safe_html($evento['nucleo_nome'] ?? 'N/A'); ?></td>
                                        <td><?php echo safe_html($evento['descricao']); ?></td>
                                        <td>
                                            <div class="btn-container">
                                                <button class="btn approve" onclick="approveEvent(<?php echo $evento['id']; ?>)">Aprovar</button>
                                                <button class="btn reject" onclick="showRejectionForm(<?php echo $evento['id']; ?>, '<?php echo safe_html($evento['nome']); ?>')">Rejeitar</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Nenhum evento pendente no momento.</p>
                <?php endif; ?>
            </div>

            <div id="approvedEvents">
                <h2>Eventos Aprovados</h2>
                <div id="approvedList">
                    <?php if ($result_aprovados && $result_aprovados->num_rows > 0): ?>
                        <!-- Mostrar apenas os primeiros 3 eventos -->
                        <?php
                        $eventos_aprovados = [];
                        while ($evento = $result_aprovados->fetch_assoc()) {
                            $eventos_aprovados[] = $evento;
                        }
                        $primeiros_eventos = array_slice($eventos_aprovados, 0, 3);
                        $restante_eventos = array_slice($eventos_aprovados, 3);
                        ?>

                        <!-- Primeiros 3 eventos (sempre visíveis) -->
                        <?php foreach ($primeiros_eventos as $evento): ?>
                            <div class="event-card">
                                <div class="event-info">
                                    <span class="status-tag status-aprovado">✅ Aprovado</span>
                                    <h3><?php echo safe_html($evento['nome']); ?></h3>
                                    <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($evento['data'])); ?></p>
                                    <p><strong>Local:</strong> <?php echo safe_html($evento['local_evento']); ?></p>
                                    <p><strong>Núcleo:</strong> <?php echo safe_html($evento['nucleo_nome'] ?? 'N/A'); ?></p>
                                    <p><strong>Descrição:</strong> <?php echo safe_html($evento['descricao']); ?></p>
                                    <?php if ($evento['importancia']): ?>
                                        <p><strong>Importância:</strong> <?php echo safe_html($evento['importancia']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="event-actions">
                                    <button class="btn" onclick="undoApproval(<?php echo $evento['id']; ?>)" style="background-color: #f39c12;">Voltar para Pendente</button>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Eventos restantes (comprimidos) -->
                        <?php if (count($restante_eventos) > 0): ?>
                            <!-- Botão Ver Mais -->
                            <button class="btn-ver-mais" onclick="toggleEventosAprovados()">
                                <span id="texto-botao-aprovados">Ver Mais Eventos Aprovados (<?php echo count($restante_eventos); ?>)</span>
                                <i class="fas fa-chevron-down ml-2" id="icone-botao-aprovados"></i>
                            </button>

                            <!-- Eventos Comprimidos -->
                            <div id="eventos-aprovados-comprimidos" class="eventos-comprimidos">
                                <?php foreach ($restante_eventos as $evento): ?>
                                    <div class="event-card">
                                        <div class="event-info">
                                            <span class="status-tag status-aprovado">✅ Aprovado</span>
                                            <h3><?php echo safe_html($evento['nome']); ?></h3>
                                            <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($evento['data'])); ?></p>
                                            <p><strong>Local:</strong> <?php echo safe_html($evento['local_evento']); ?></p>
                                            <p><strong>Núcleo:</strong> <?php echo safe_html($evento['nucleo_nome'] ?? 'N/A'); ?></p>
                                            <p><strong>Descrição:</strong> <?php echo safe_html($evento['descricao']); ?></p>
                                            <?php if ($evento['importancia']): ?>
                                                <p><strong>Importância:</strong> <?php echo safe_html($evento['importancia']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="event-actions">
                                            <button class="btn" onclick="undoApproval(<?php echo $evento['id']; ?>)" style="background-color: #f39c12;">Voltar para Pendente</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Nenhum evento aprovado ainda.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div id="rejectedEvents" class="rejected-events">
                <h2>Eventos Rejeitados</h2>
                <div id="rejectedList">
                    <?php if ($result_rejeitados && $result_rejeitados->num_rows > 0): ?>
                        <?php while ($evento = $result_rejeitados->fetch_assoc()): ?>
                            <div class="rejected-event">
                                <div class="event-info">
                                    <span class="status-tag status-rejeitado">❌ Rejeitado</span>
                                    <h3><?php echo safe_html($evento['nome']); ?></h3>
                                    <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($evento['data'])); ?></p>
                                    <p><strong>Local:</strong> <?php echo safe_html($evento['local_evento']); ?></p>
                                    <p><strong>Núcleo:</strong> <?php echo safe_html($evento['nucleo_nome'] ?? 'N/A'); ?></p>
                                    <p><strong>Descrição:</strong> <?php echo safe_html($evento['descricao']); ?></p>
                                </div>
                                <div class="event-actions" style="margin-top: 10px;">
                                    <button class="btn" onclick="restoreRejectedEvent(<?php echo $evento['id']; ?>)" style="background-color: #e89516;">Restaurar para Aprovação</button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>Nenhum evento rejeitado ainda.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
            // Variável para controlar o estado dos eventos aprovados comprimidos
            let eventosAprovadosExpandidos = false;

            // Função para expandir/recolher eventos aprovados comprimidos
            function toggleEventosAprovados() {
                const container = document.getElementById('eventos-aprovados-comprimidos');
                const textoBotao = document.getElementById('texto-botao-aprovados');
                const iconeBotao = document.getElementById('icone-botao-aprovados');

                if (eventosAprovadosExpandidos) {
                    // Recolher
                    container.classList.remove('expandido');
                    textoBotao.textContent = 'Ver Mais Eventos Aprovados (<?php echo count($restante_eventos); ?>)';
                    iconeBotao.classList.remove('fa-chevron-up');
                    iconeBotao.classList.add('fa-chevron-down');
                } else {
                    // Expandir
                    container.classList.add('expandido');
                    textoBotao.textContent = 'Ver Menos Eventos Aprovados';
                    iconeBotao.classList.remove('fa-chevron-down');
                    iconeBotao.classList.add('fa-chevron-up');
                }

                eventosAprovadosExpandidos = !eventosAprovadosExpandidos;
            }

            function approveEvent(eventId) {
                Swal.fire({
                    title: 'Aprovar Evento',
                    text: 'Tem certeza que deseja aprovar este evento?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, aprovar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Enviar requisição AJAX
                        const formData = new FormData();
                        formData.append('event_id', eventId);
                        formData.append('action', 'approve');

                        fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: 'Sucesso!',
                                        text: data.message,
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Erro!',
                                        text: data.message,
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            })
                            .catch(error => {
                                Swal.fire({
                                    title: 'Erro!',
                                    text: 'Erro ao processar a requisição.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            });
                    }
                });
            }

            function showRejectionForm(eventId, eventName) {
                // Gerar preview do email
                let emailPreviewHTML = `
                    <div class="email-preview">
                        <div class="email-header">
                            <p><strong>De:</strong> administracao@infocultura.com</p>
                            <p><strong>Assunto:</strong> Seu evento "${eventName}" foi rejeitado</p>
                        </div>
                        <div class="email-content">
                            <p>Prezado(a) usuário,</p>
                            <p>Lamentamos informar que o evento <strong>"${eventName}"</strong> que você cadastrou em nosso sistema foi rejeitado pela nossa equipe de moderação.</p>
                            <p><strong>Motivo da rejeição:</strong></p>
                            <p id="preview-reason" style="background-color: #e6ae6c; padding: 10px; border-left: 5px solid #e89516; font-style: italic;">
                                [Sua justificativa aparecerá aqui]
                            </p>
                            <p>Agradecemos sua compreensão e estamos à disposição para esclarecimentos adicionais.</p>
                            <p>Atenciosamente,<br>Equipe InfoCultura</p>
                        </div>
                    </div>
                `;

                Swal.fire({
                    title: 'Rejeitar Evento',
                    html: `
                        <p>Você está rejeitando o evento <strong>${eventName}</strong>.</p>
                        <div class="justification-form">
                            <label for="rejectionReason">Justificativa da rejeição:</label>
                            <textarea 
                                id="rejectionReason" 
                                class="justification-textarea" 
                                placeholder="Descreva o motivo da rejeição deste evento."
                                oninput="updateEmailPreview()"
                                required
                            ></textarea>
                        </div>
                        ${emailPreviewHTML}
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Confirmar rejeição',
                    cancelButtonText: 'Cancelar',
                    width: '800px',
                    preConfirm: () => {
                        const reason = document.getElementById('rejectionReason').value;
                        if (!reason.trim()) {
                            Swal.showValidationMessage('Por favor, forneça uma justificativa para a rejeição.');
                            return false;
                        }
                        return reason;
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        rejectEvent(eventId, result.value);
                    }
                });
            }

            function updateEmailPreview() {
                const reason = document.getElementById('rejectionReason').value;
                const previewElement = document.getElementById('preview-reason');
                if (previewElement) {
                    previewElement.textContent = reason || '[Sua justificativa aparecerá aqui]';
                }
            }

            function rejectEvent(eventId, rejectionReason) {
                // Enviar requisição AJAX
                const formData = new FormData();
                formData.append('event_id', eventId);
                formData.append('action', 'reject');
                formData.append('reason', rejectionReason);

                fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Rejeitado!',
                                html: `
                                <p>Evento rejeitado com sucesso.</p>
                                <div class="email-preview">
                                    <div class="email-header">
                                        <p><strong>Assunto:</strong> Evento rejeitado</p>
                                    </div>
                                    <div class="email-content">
                                        <p>O evento foi rejeitado com a seguinte justificativa:</p>
                                        <p style="background-color: #e6ae6c; padding: 10px; border-left: 5px solid #e89516; font-style: italic;">
                                            ${rejectionReason}
                                        </p>
                                    </div>
                                </div>
                            `,
                                icon: 'success',
                                confirmButtonText: 'OK',
                                width: '700px'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Erro!',
                                text: data.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Erro!',
                            text: 'Erro ao processar a requisição.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
            }

            function undoApproval(eventId) {
                Swal.fire({
                    title: 'Voltar para Pendente',
                    text: 'Tem certeza que deseja voltar este evento para a lista de pendentes?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, voltar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Enviar requisição AJAX para voltar para pendente
                        const formData = new FormData();
                        formData.append('event_id', eventId);
                        formData.append('action', 'pending');

                        fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: 'Sucesso!',
                                        text: data.message,
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Erro!',
                                        text: data.message,
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            })
                            .catch(error => {
                                Swal.fire({
                                    title: 'Erro!',
                                    text: 'Erro ao processar a requisição.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            });
                    }
                });
            }

            function restoreRejectedEvent(eventId) {
                Swal.fire({
                    title: 'Restaurar Evento',
                    text: 'Tem certeza que deseja restaurar este evento para a lista de aprovação?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, restaurar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Enviar requisição AJAX para voltar para pendente
                        const formData = new FormData();
                        formData.append('event_id', eventId);
                        formData.append('action', 'pending');

                        fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: 'Sucesso!',
                                        text: data.message,
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Erro!',
                                        text: data.message,
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            })
                            .catch(error => {
                                Swal.fire({
                                    title: 'Erro!',
                                    text: 'Erro ao processar a requisição.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            });
                    }
                });
            }
        </script>
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
</body>

</html>