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

// Incluir funções do login
include 'login.php';

$alert_message = '';
$alert_type = 'danger';
$token_valido = false;
$token = $_GET['token'] ?? '';

// Verificar se token é válido
if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT id, token_expira FROM usuarios WHERE token_reset = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && strtotime($user['token_expira']) >= time()) {
            $token_valido = true;
        } elseif ($user) {
            $alert_message = 'Este link de redefinição expirou. Solicite um novo.';
        } else {
            $alert_message = 'Token inválido.';
        }
    } catch (PDOException $e) {
        $alert_message = 'Erro ao verificar token: ' . $e->getMessage();
    }
}

// Processar redefinição de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $token = $_POST['token'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    $result = processPasswordReset($pdo, $token, $nova_senha, $confirmar_senha);

    if ($result['success']) {
        $alert_message = $result['message'];
        $alert_type = 'success';
        $token_valido = false; // Impedir novo uso do token
    } else {
        $alert_message = $result['message'];
        $alert_type = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Redefinir Senha - Info Cultura" />
    <meta name="author" content="Info Cultura" />
    <title>Redefinir Senha - Info Cultura</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />

    <!-- Import da fonte Alan Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alan+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #6f390d;
            font-family: "Alan Sans", sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .reset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            padding: 3rem;
            max-width: 500px;
            width: 100%;
        }

        .logo {
            height: 50px;
            margin-bottom: 2rem;
        }

        .login-title {
            color: #6f390d;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
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

        .password-container {
            position: relative;
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

        .back-link {
            color: #6f390d;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-top: 1rem;
        }

        .back-link:hover {
            color: #5a2e0a;
            text-decoration: underline;
        }

        .success-message {
            text-align: center;
            padding: 2rem;
        }

        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="reset-card">
        <div class="text-center mb-4">
            <img src="assets/logoo.png" alt="Info Cultura" class="logo">
            <h1 class="login-title">
                <i class="fas fa-key me-2"></i>Redefinir Senha
            </h1>
        </div>

        <?php if (isset($alert_message)): ?>
            <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $alert_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($token_valido): ?>
            <!-- Formulário de redefinição de senha -->
            <form method="POST" action="">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="mb-3 password-container">
                    <label for="nova_senha" class="form-label">Nova Senha</label>
                    <input type="password" class="form-control" id="nova_senha" name="nova_senha"
                        placeholder="Mínimo 6 caracteres" required minlength="6">
                    <button type="button" class="password-toggle" onclick="togglePassword('nova_senha')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <div class="mb-3 password-container">
                    <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha"
                        placeholder="Digite novamente a senha" required minlength="6">
                    <button type="button" class="password-toggle" onclick="togglePassword('confirmar_senha')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="fas fa-save me-2"></i>Redefinir Senha
                </button>
            </form>
        <?php elseif ($alert_type === 'success'): ?>
            <!-- Mensagem de sucesso -->
            <div class="success-message">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="text-success">Senha Redefinida!</h3>
                <p class="text-muted">Sua senha foi redefinida com sucesso. Você já pode fazer login com sua nova senha.</p>
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Fazer Login
                </a>
            </div>
        <?php elseif (empty($alert_message)): ?>
            <div class="text-center">
                <p>Token não fornecido ou inválido.</p>
            </div>
        <?php endif; ?>

        <?php if ($alert_type !== 'success'): ?>
            <div class="text-center">
                <a href="login.php" class="back-link">
                    <i class="fas fa-arrow-left me-2"></i>Voltar para o Login
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
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

        // Validação de senha em tempo real
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            if (form) {
                const novaSenha = document.getElementById('nova_senha');
                const confirmarSenha = document.getElementById('confirmar_senha');

                function validatePasswords() {
                    if (novaSenha.value.length > 0 && novaSenha.value.length < 6) {
                        novaSenha.setCustomValidity('A senha deve ter pelo menos 6 caracteres.');
                    } else {
                        novaSenha.setCustomValidity('');
                    }

                    if (confirmarSenha.value !== novaSenha.value) {
                        confirmarSenha.setCustomValidity('As senhas não coincidem.');
                    } else {
                        confirmarSenha.setCustomValidity('');
                    }
                }

                novaSenha.addEventListener('input', validatePasswords);
                confirmarSenha.addEventListener('input', validatePasswords);

                form.addEventListener('submit', function(e) {
                    validatePasswords();
                    if (!form.checkValidity()) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>

</html>