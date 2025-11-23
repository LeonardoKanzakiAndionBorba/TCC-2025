<?php
session_start();

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

// Processar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Variáveis para controle de exibição
$show_reset_form = false;
$show_token_info = false;
$reset_token = '';
$reset_email = '';
$generated_token = '';
$generated_link = '';

// Processar ações do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        // Processar cadastro
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $senha = $_POST['senha'];
        $confirmar_senha = $_POST['confirmar_senha'];

        // Validações
        if (empty($nome) || empty($email) || empty($senha)) {
            $alert_message = 'Por favor, preencha todos os campos.';
            $alert_type = 'danger';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alert_message = 'Por favor, insira um email válido.';
            $alert_type = 'danger';
        } elseif (strlen($senha) < 6) {
            $alert_message = 'A senha deve ter pelo menos 6 caracteres.';
            $alert_type = 'danger';
        } elseif ($senha !== $confirmar_senha) {
            $alert_message = 'As senhas não coincidem.';
            $alert_type = 'danger';
        } else {
            try {
                // Verificar se email já existe
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);

                if ($stmt->fetch()) {
                    $alert_message = 'Este email já está cadastrado.';
                    $alert_type = 'danger';
                } else {
                    // Determinar tipo de usuário baseado no email
                    $tipo_usuario = determinarTipoUsuario($email);

                    // Inserir novo usuário no banco de dados
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, tipo_usuario, senha) VALUES (?, ?, ?, ?)");

                    if ($stmt->execute([$nome, $email, $tipo_usuario, $senha_hash])) {
                        $alert_message = 'Usuário cadastrado com sucesso!';
                        $alert_type = 'success';

                        // Limpar formulário após cadastro bem-sucedido
                        $nome = $email = '';
                    } else {
                        $alert_message = 'Erro ao cadastrar usuário.';
                        $alert_type = 'danger';
                    }
                }
            } catch (PDOException $e) {
                $alert_message = 'Erro no cadastro: ' . $e->getMessage();
                $alert_type = 'danger';
            }
        }
    } elseif ($action === 'login') {
        // Processar login
        $email = trim($_POST['email']);
        $senha = $_POST['senha'];
        $remember_me = isset($_POST['remember_me']);

        if (empty($email) || empty($senha)) {
            $alert_message = 'Por favor, preencha todos os campos.';
            $alert_type = 'danger';
        } else {
            try {
                // Buscar usuário no banco de dados
                $stmt = $pdo->prepare("SELECT id, nome, email, tipo_usuario, senha FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($senha, $user['senha'])) {
                    // ========== RE-HASH AUTOMÁTICO ==========
                    // Verificar se o hash precisa ser atualizado
                    if (password_needs_rehash($user['senha'], PASSWORD_DEFAULT)) {
                        $novo_hash = password_hash($senha, PASSWORD_DEFAULT);

                        // Atualizar o hash no banco de dados
                        $update_stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                        $update_stmt->execute([$novo_hash, $user['id']]);

                        // Log para debug (pode remover em produção)
                        error_log("Re-hash automático realizado para usuário ID: " . $user['id']);
                    }
                    // ========== FIM RE-HASH AUTOMÁTICO ==========

                    // Login bem-sucedido - salvar na sessão
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'nome' => $user['nome'],
                        'email' => $user['email'],
                        'tipo_usuario' => $user['tipo_usuario'],
                        'loggedIn' => true,
                        'loginTime' => date('Y-m-d H:i:s')
                    ];

                    // Redirecionar baseado no tipo de usuário
                    $redirect_url = getRedirectUrl($user['tipo_usuario']);
                    header('Location: ' . $redirect_url);
                    exit;
                } else {
                    $alert_message = 'Email ou senha incorretos.';
                    $alert_type = 'danger';
                }
            } catch (PDOException $e) {
                $alert_message = 'Erro no login: ' . $e->getMessage();
                $alert_type = 'danger';
            }
        }
    } elseif ($action === 'forgot_password') {
        // Processar "Esqueci minha senha"
        $email = trim($_POST['email']);

        if (empty($email)) {
            $alert_message = 'Por favor, insira seu email.';
            $alert_type = 'danger';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alert_message = 'Por favor, insira um email válido.';
            $alert_type = 'danger';
        } else {
            try {
                // Verificar se o email existe
                $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Gerar token único
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    // Salvar token no banco de dados
                    $stmt = $pdo->prepare("UPDATE usuarios SET token_reset = ?, token_expira = ? WHERE email = ?");
                    $stmt->execute([$token, $expiry, $email]);

                    // Gerar link de redefinição
                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?token=" . $token;

                    // Mostrar informações do token na página
                    $show_token_info = true;
                    $generated_token = $token;
                    $generated_link = $reset_link;

                    $alert_message = 'Link de redefinição gerado com sucesso! Use o link abaixo para redefinir sua senha.';
                    $alert_type = 'success';
                } else {
                    $alert_message = 'Email não encontrado em nosso sistema.';
                    $alert_type = 'danger';
                }
            } catch (PDOException $e) {
                $alert_message = 'Erro ao processar solicitação: ' . $e->getMessage();
                $alert_type = 'danger';
            }
        }
    } elseif ($action === 'reset_password') {
        // Processar redefinição de senha
        $token = $_POST['token'] ?? '';
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';

        if (empty($token) || empty($nova_senha) || empty($confirmar_senha)) {
            $alert_message = 'Por favor, preencha todos os campos.';
            $alert_type = 'danger';
        } elseif (strlen($nova_senha) < 6) {
            $alert_message = 'A senha deve ter pelo menos 6 caracteres.';
            $alert_type = 'danger';
        } elseif ($nova_senha !== $confirmar_senha) {
            $alert_message = 'As senhas não coincidem.';
            $alert_type = 'danger';
        } else {
            try {
                // Validar token
                $stmt = $pdo->prepare("SELECT id, email, token_expira FROM usuarios WHERE token_reset = ?");
                $stmt->execute([$token]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    $alert_message = 'Token inválido ou expirado.';
                    $alert_type = 'danger';
                } elseif (strtotime($user['token_expira']) < time()) {
                    $alert_message = 'Token expirado. Solicite uma nova redefinição.';
                    $alert_type = 'danger';
                } else {
                    // Atualizar senha
                    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

                    $update_stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, token_reset = NULL, token_expira = NULL WHERE id = ?");

                    if ($update_stmt->execute([$senha_hash, $user['id']])) {
                        $alert_message = 'Senha redefinida com sucesso! Você já pode fazer login com sua nova senha.';
                        $alert_type = 'success';

                        // Redirecionar para login após 3 segundos
                        header('Refresh: 3; URL=login.php');
                    } else {
                        $alert_message = 'Erro ao atualizar senha.';
                        $alert_type = 'danger';
                    }
                }
            } catch (PDOException $e) {
                $alert_message = 'Erro no processo: ' . $e->getMessage();
                $alert_type = 'danger';
            }
        }
    }
}

// Verificar se há token de redefinição na URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];

    try {
        // Validar token
        $stmt = $pdo->prepare("SELECT id, email, token_expira FROM usuarios WHERE token_reset = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && strtotime($user['token_expira']) >= time()) {
            $show_reset_form = true;
            $reset_token = $token;
            $reset_email = $user['email'];
        } else {
            $alert_message = 'Token inválido ou expirado.';
            $alert_type = 'danger';
        }
    } catch (PDOException $e) {
        $alert_message = 'Erro ao validar token: ' . $e->getMessage();
        $alert_type = 'danger';
    }
}

// Função para determinar tipo de usuário baseado no email (para cadastro)
function determinarTipoUsuario($email)
{
    $email_lower = strtolower($email);

    // Verificar se é Super Admin
    if (strpos($email_lower, 'superadmin') !== false || strpos($email_lower, 'infocultura2025@gmail.com') !== false) {
        return 'super_adm';
    }

    // Verificar se é NEABI (domínio institucional)
    $email_domain = explode('@', $email_lower)[1] ?? '';
    if (strpos($email_domain, 'neabi') !== false || strpos($email_domain, 'ifsp') !== false || strpos($email_domain, 'gov') !== false) {
        return 'membro_neabi';
    }

    // Usuário comum (padrão)
    return 'comum';
}

// Função para obter URL de redirecionamento baseado no tipo de usuário
function getRedirectUrl($tipo_usuario)
{
    switch ($tipo_usuario) {
        case 'super_adm':
            return 'aprovEvento.php';
        case 'membro_neabi':
            return 'areaAdm.php';
        case 'comum':
        default:
            return 'index.php';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Sistema de Login Info Cultura - Acesso via Email e Senha" />
    <meta name="author" content="Info Cultura" />
    <title>Info Cultura - Login</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />

    <!-- Import da fonte Alan Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alan+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Todos os estilos CSS anteriores permanecem aqui */
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

        body {
            background-color: #6f390d;
            font-family: "Alan Sans", sans-serif;
        }

        .login-container {
            background-color: #e89516;
            min-height: calc(100vh - 176px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
        }

        .login-left {
            background-color: #e6ae6c;
            padding: 3rem;
            color: #6f390d;
        }

        .login-right {
            padding: 3rem;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background-color: #ffbc6d;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .benefit-item:hover {
            background-color: #ffbc6d;
            transform: translateX(5px);
        }

        .benefit-icon {
            background-color: #6f390d;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .benefit-text {
            color: #6f390d;
            font-weight: 500;
        }

        .info-message {
            text-align: center;
            color: #6f390d;
            font-weight: 500;
            margin-bottom: 25px;
            padding: 15px;
            background-color: rgba(111, 57, 13, 0.1);
            border-radius: 10px;
            border-left: 4px solid #e89516;
        }

        .terms-text {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 20px;
            line-height: 1.5;
        }

        .terms-text a {
            color: #6f390d;
            text-decoration: none;
        }

        .terms-text a:hover {
            text-decoration: underline;
        }

        .info-box {
            background-color: rgba(230, 174, 108, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            border-left: 4px solid #e89516;
        }

        .info-box h5 {
            color: #6f390d;
            font-weight: bold;
        }

        .info-box p {
            color: #666;
            margin-bottom: 0;
        }

        .login-title {
            color: #6f390d;
            font-size: 2rem;
            font-weight: bold;
        }

        .login-title i {
            color: #6f390d;
        }

        .user-type-info {
            background-color: rgba(111, 57, 13, 0.05);
            border-radius: 8px;
            padding: 12px;
            margin-top: 15px;
            font-size: 14px;
        }

        .user-type-info h6 {
            color: #6f390d;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .user-type-info ul {
            margin-bottom: 0;
            padding-left: 20px;
        }

        .user-type-info li {
            margin-bottom: 4px;
        }

        .form-container {
            margin-top: 20px;
        }

        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #6f390d;
            box-shadow: 0 0 0 0.2rem rgba(111, 57, 13, 0.25);
        }

        .form-label {
            color: #6f390d;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .btn-primary {
            background-color: #6f390d;
            border-color: #6f390d;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #5a2e0a;
            border-color: #5a2e0a;
            transform: translateY(-2px);
        }

        .btn-outline-primary {
            color: #6f390d;
            border-color: #6f390d;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background-color: #6f390d;
            color: white;
            transform: translateY(-2px);
        }

        .form-switch .form-check-input:checked {
            background-color: #6f390d;
            border-color: #6f390d;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6f390d;
            cursor: pointer;
        }

        .password-container {
            position: relative;
        }

        .form-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .form-tab {
            flex: 1;
            text-align: center;
            padding: 12px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .form-tab.active {
            border-bottom-color: #6f390d;
            color: #6f390d;
            font-weight: 600;
        }

        .form-content {
            display: none;
        }

        .form-content.active {
            display: block;
        }

        .alert {
            border-radius: 8px;
            padding: 12px 15px;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            border-color: rgba(40, 167, 69, 0.3);
            color: #155724;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.3);
            color: #721c24;
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

        /* Modal de Esqueci Senha */
        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background-color: #6f390d;
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-title {
            font-weight: 600;
        }

        .btn-close-white {
            filter: invert(1);
        }

        /* Estilo para formulário de redefinição */
        .reset-info {
            background-color: rgba(111, 57, 13, 0.1);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #e89516;
        }

        .reset-info p {
            margin-bottom: 0;
            color: #6f390d;
            font-weight: 500;
        }

        /* Estilo para informações do token */
        .token-info {
            background-color: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .token-info h6 {
            color: #155724;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .token-link {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            word-break: break-all;
            font-family: monospace;
            font-size: 14px;
        }

        .copy-btn {
            background-color: #6f390d;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
        }

        .copy-btn:hover {
            background-color: #5a2e0a;
        }

        @media (max-width: 768px) {
            .login-left {
                display: none;
            }

            .login-container {
                padding: 1rem;
            }

            .login-right {
                padding: 2rem;
            }
        }
    </style>
</head>

<body class="d-flex flex-column h-100">
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

        <!-- Login Section -->
        <section class="login-container">
            <div class="login-card">
                <div class="row g-0">
                    <!-- Left Side - Benefits -->
                    <div class="col-lg-6 d-none d-lg-block">
                        <div class="login-left h-100">
                            <div class="d-flex flex-column h-100 justify-content-center">
                                <h2 class="fw-bold mb-4" style="color: #6f390d;">Bem-vindo ao Info Cultura</h2>
                                <p class="mb-5" style="color: #6f390d;">Faça login ou cadastre-se para acessar nosso sistema.</p>

                                <div class="benefits-container">
                                    <h3 class="fw-bold mb-4" style="color: #6f390d;">Tipos de Acesso</h3>

                                    <div class="benefit-item">
                                        <div class="benefit-icon">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="benefit-text">
                                            <strong>Usuário Comum</strong> - Acesso à área pública do sistema
                                        </div>
                                    </div>

                                    <div class="benefit-item">
                                        <div class="benefit-icon">
                                            <i class="fas fa-university"></i>
                                        </div>
                                        <div class="benefit-text">
                                            <strong>Membro NEABI</strong> - Acesso à área administrativa institucional
                                        </div>
                                    </div>

                                    <div class="benefit-item">
                                        <div class="benefit-icon">
                                            <i class="fas fa-shield-alt"></i>
                                        </div>
                                        <div class="benefit-text">
                                            <strong>Super Admin</strong> - Acesso completo ao sistema
                                        </div>
                                    </div>

                                    <div class="benefit-item">
                                        <div class="benefit-icon">
                                            <i class="fas fa-cogs"></i>
                                        </div>
                                        <div class="benefit-text">
                                            <strong>Controle de Acesso</strong> - Permissões automáticas conforme seu perfil
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side - Login Form -->
                    <div class="col-lg-6">
                        <div class="login-right h-100">
                            <div class="d-flex flex-column h-100 justify-content-center">
                                <div class="text-center mb-5">
                                    <h1 class="login-title">
                                        <i class="fas fa-unlock-alt me-2"></i>
                                        <?php
                                        if ($show_reset_form) {
                                            echo 'Redefinir Senha';
                                        } elseif ($show_token_info) {
                                            echo 'Link de Redefinição';
                                        } else {
                                            echo 'Acesso ao Sistema';
                                        }
                                        ?>
                                    </h1>
                                </div>

                                <!-- Mostrar alertas -->
                                <?php if (isset($alert_message)): ?>
                                    <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                                        <?php echo $alert_message; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if ($show_token_info): ?>
                                    <!-- Informações do Token Gerado -->
                                    <div class="token-info">
                                        <h6><i class="fas fa-link me-2"></i>Link de Redefinição Gerado</h6>
                                        <p>Use o link abaixo para redefinir sua senha. Este link expira em 1 hora.</p>

                                        <div class="token-link">
                                            <?php echo htmlspecialchars($generated_link); ?>
                                            <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($generated_link); ?>')">
                                                <i class="fas fa-copy me-1"></i>Copiar
                                            </button>
                                        </div>

                                        <p class="small text-muted mt-2">
                                            <strong>Token:</strong> <?php echo htmlspecialchars($generated_token); ?>
                                        </p>
                                    </div>

                                    <div class="text-center">
                                        <a href="login.php" class="btn btn-outline-primary">Voltar para o Login</a>
                                    </div>

                                <?php elseif ($show_reset_form): ?>
                                    <!-- Formulário de Redefinição de Senha -->
                                    <div class="reset-info">
                                        <p><i class="fas fa-info-circle me-2"></i>Redefinindo senha para: <strong><?php echo htmlspecialchars($reset_email); ?></strong></p>
                                    </div>

                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($reset_token); ?>">

                                        <div class="mb-3 password-container">
                                            <label for="nova_senha" class="form-label">Nova Senha</label>
                                            <input type="password" class="form-control" id="nova_senha" name="nova_senha"
                                                placeholder="Mínimo 6 caracteres" required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('nova_senha')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>

                                        <div class="mb-3 password-container">
                                            <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha"
                                                placeholder="Digite novamente a nova senha" required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('confirmar_senha')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100">Redefinir Senha</button>

                                        <div class="text-center mt-3">
                                            <a href="login.php">Voltar para o login</a>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <!-- Tabs para Login/Cadastro -->
                                    <div class="form-tabs">
                                        <div class="form-tab active" data-tab="login">Login</div>
                                        <div class="form-tab" data-tab="register">Cadastro</div>
                                    </div>

                                    <!-- Formulário de Login -->
                                    <div id="login-form" class="form-content active">
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="login">
                                            <div class="mb-3">
                                                <label for="loginEmail" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="loginEmail" name="email"
                                                    value="<?php echo isset($_POST['email']) && ($_POST['action'] ?? '') === 'login' ? htmlspecialchars($_POST['email']) : ''; ?>"
                                                    placeholder="seu@email.com" required>
                                            </div>
                                            <div class="mb-3 password-container">
                                                <label for="loginPassword" class="form-label">Senha</label>
                                                <input type="password" class="form-control" id="loginPassword" name="senha" placeholder="Sua senha" required>
                                                <button type="button" class="password-toggle" onclick="togglePassword('loginPassword')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" id="rememberMe" name="remember_me">
                                                <label class="form-check-label" for="rememberMe">Lembrar-me</label>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100 mb-3">Entrar</button>
                                            <div class="text-center">
                                                <a href="#" id="forgotPassword" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Esqueci minha senha</a>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Formulário de Cadastro -->
                                    <div id="register-form" class="form-content">
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="register">
                                            <div class="mb-3">
                                                <label for="userName" class="form-label">Nome Completo</label>
                                                <input type="text" class="form-control" id="userName" name="nome"
                                                    value="<?php echo isset($nome) ? htmlspecialchars($nome) : ''; ?>"
                                                    placeholder="Seu nome completo" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="registerEmail" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="registerEmail" name="email"
                                                    value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                                                    placeholder="seu@email.com" required>
                                            </div>
                                            <div class="mb-3 password-container">
                                                <label for="registerPassword" class="form-label">Senha</label>
                                                <input type="password" class="form-control" id="registerPassword" name="senha" placeholder="Mínimo 6 caracteres" required>
                                                <button type="button" class="password-toggle" onclick="togglePassword('registerPassword')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="mb-3 password-container">
                                                <label for="confirmPassword" class="form-label">Confirmar Senha</label>
                                                <input type="password" class="form-control" id="confirmPassword" name="confirmar_senha" placeholder="Digite novamente sua senha" required>
                                                <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">Cadastrar</button>
                                        </form>
                                    </div>

                                    <!-- Informação adicional -->
                                    <div class="info-box">
                                        <h5 class="text-center mb-2">Como funciona?</h5>
                                        <p class="text-center mb-0 small">
                                            O sistema identifica automaticamente seu perfil com base no e-mail cadastrado e concede as permissões adequadas.
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="py-4 mt-auto">
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

    <!-- Modal Esqueci Senha -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">
                        <i class="fas fa-key me-2"></i>Redefinir Senha
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Digite seu email cadastrado para gerar um link de redefinição de senha.</p>
                    <form id="forgotPasswordForm" method="POST" action="">
                        <input type="hidden" name="action" value="forgot_password">
                        <div class="mb-3">
                            <label for="forgotEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="forgotEmail" name="email" placeholder="seu@email.com" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-link me-2"></i>Gerar Link de Redefinição
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Função para alternar entre formulários de login e cadastro
        function setupFormTabs() {
            const tabs = document.querySelectorAll('.form-tab');
            const forms = document.querySelectorAll('.form-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const tabId = tab.getAttribute('data-tab');

                    // Ativar tab clicada
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    // Mostrar formulário correspondente
                    forms.forEach(form => {
                        form.classList.remove('active');
                        if (form.id === `${tabId}-form`) {
                            form.classList.add('active');
                        }
                    });
                });
            });
        }

        // Função para alternar visibilidade da senha
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleButton = passwordInput.parentNode.querySelector('.password-toggle i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.classList.remove('fa-eye');
                toggleButton.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleButton.classList.remove('fa-eye-slash');
                toggleButton.classList.add('fa-eye');
            }
        }

        // Função para copiar texto para a área de transferência
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Link copiado para a área de transferência!');
            }, function(err) {
                console.error('Erro ao copiar: ', err);
                // Fallback para navegadores mais antigos
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('Link copiado para a área de transferência!');
            });
        }

        // Inicializar quando a página carregar
        window.onload = function() {
            // Configurar abas de formulário (apenas se não estiver na redefinição)
            <?php if (!$show_reset_form && !$show_token_info): ?>
                setupFormTabs();
            <?php endif; ?>

            // Preencher automaticamente o email no modal de esqueci senha
            document.getElementById('forgotPassword')?.addEventListener('click', function() {
                const loginEmail = document.getElementById('loginEmail').value;
                if (loginEmail) {
                    document.getElementById('forgotEmail').value = loginEmail;
                }
            });

            // Focar no campo de email quando o modal abrir
            const forgotPasswordModal = document.getElementById('forgotPasswordModal');
            if (forgotPasswordModal) {
                forgotPasswordModal.addEventListener('shown.bs.modal', function() {
                    document.getElementById('forgotEmail').focus();
                });
            }
        };
    </script>
</body>

</html>