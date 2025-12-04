<?php
// Configurações do banco de dados
$host = 'localhost';
$usuario_bd = 'root';
$senha_bd = '';
$banco = 'INFO_CULTURA';

// Conexão com o banco de dados
$conn = new mysqli($host, $usuario_bd, $senha_bd, $banco);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Processar formulários de redefinição de senha
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['solicitar_reset'])) {
        // Processar solicitação de reset
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = "Email inválido.";
        } else {
            // Verificar se email existe
            $stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $usuario = $result->fetch_assoc();
                
                // Gerar token único
                $token = bin2hex(random_bytes(32));
                $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));
                
                // Salvar token no banco
                $update = $conn->prepare("UPDATE usuarios SET token_reset = ?, token_expira = ? WHERE email = ?");
                $update->bind_param("sss", $token, $expira, $email);
                
                if ($update->execute()) {
                    // Simular envio de email (em produção, implemente o envio real)
                    $sucesso = "Email de redefinição enviado! Verifique sua caixa de entrada.";
                    $_SESSION['email_solicitado'] = $email;
                    $_SESSION['token_simulado'] = $token; // Apenas para demonstração
                } else {
                    $erro = "Erro ao processar solicitação.";
                }
            } else {
                $erro = "Email não encontrado em nosso sistema.";
            }
        }
    } 
    elseif (isset($_POST['definir_nova_senha'])) {
        // Processar definição de nova senha
        $token = $_POST['token'];
        $nova_senha = $_POST['nova_senha'];
        $confirmar_senha = $_POST['confirmar_senha'];
        
        if ($nova_senha !== $confirmar_senha) {
            $erro = "As senhas não coincidem.";
        } elseif (strlen($nova_senha) < 6) {
            $erro = "A senha deve ter pelo menos 6 caracteres.";
        } else {
            // Verificar token válido e não expirado
            $stmt = $conn->prepare("SELECT id, email FROM usuarios WHERE token_reset = ? AND token_expira > NOW()");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $usuario = $result->fetch_assoc();
                
                // Hash da nova senha
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                
                // Atualizar senha e limpar token
                $update = $conn->prepare("UPDATE usuarios SET senha = ?, token_reset = NULL, token_expira = NULL WHERE token_reset = ?");
                $update->bind_param("ss", $senha_hash, $token);
                
                if ($update->execute()) {
                    $sucesso = "Senha alterada com sucesso!";
                } else {
                    $erro = "Erro ao atualizar senha.";
                }
            } else {
                $erro = "Token inválido ou expirado. Solicite um novo link de redefinição.";
            }
        }
    }
}

// Verificar se há token na URL (vindo do email)
$token_url = isset($_GET['token']) ? $_GET['token'] : '';

// Verificar se token é válido para exibir o formulário de nova senha
$token_valido = false;
if (!empty($token_url)) {
    $stmt = $conn->prepare("SELECT id, token_expira > NOW() as valido FROM usuarios WHERE token_reset = ?");
    $stmt->bind_param("s", $token_url);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $dados = $result->fetch_assoc();
        $token_valido = (bool)$dados['valido'];
        
        if (!$token_valido) {
            $erro = "Este link de redefinição expirou. Solicite um novo.";
        }
    } else {
        $erro = "Link de redefinição inválido.";
    }
}

// Processar login normal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin-login-btn'])) {
    $email = $_POST['admin-email'];
    $password = $_POST['admin-password'];

    if (!empty($email) && !empty($password)) {
        // Buscar usuário no banco
        $stmt = $conn->prepare("SELECT id, nome, email, senha, tipo_usuario FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
            
            // Verificar senha (usando password_verify para senhas hasheadas)
            if (password_verify($password, $usuario['senha'])) {
                // Login bem-sucedido
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_tipo'] = $usuario['tipo_usuario'];
                
                // Redirecionar conforme o tipo de usuário
                if ($usuario['tipo_usuario'] == 'membro_neabi') {
                    header("Location: areaAdm.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $erro_login = "Senha incorreta.";
            }
        } else {
            $erro_login = "Usuário não encontrado.";
        }
    } else {
        $erro_login = "Por favor, preencha todos os campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Info Cultura</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />

    <!-- Bootstrap CSS primeiro -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS por último para evitar conflitos -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Estilos para o cabeçalho próximo às bordas */
        .navbar {
            padding-left: 0;
            padding-right: 0;
        }

        .navbar .container {
            padding-left: 15px;
            padding-right: 15px;
        }

        /* Estilos personalizados */
        body {
            background-color: #6f390d;
        }

        .card-switch {
            transition: all 0.3s ease;
        }

        .card-active {
            background-color: #6f390d;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-inactive {
            opacity: 0.7;
        }

        .hover-effect:hover {
            transform: scale(1.03);
            transition: transform 0.3s ease;
        }

        /* Garantir que a navegação fique visível */
        .navbar {
            position: relative;
            z-index: 1000;
        }

        /* Corrigir o padding do lado direito do formulário */
        .md\:p-120 {
            padding: 2rem;
        }

        @media (min-width: 768px) {
            .md\:p-120 {
                padding: 3rem;
            }
        }

        /* Estilos para o modal de redefinição de senha */
        .modal-reset {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-reset.active {
            display: flex;
            opacity: 1;
        }

        .modal-reset-content {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            padding: 30px;
            position: relative;
            transform: translateY(-50px);
            transition: transform 0.3s ease;
        }

        .modal-reset.active .modal-reset-content {
            transform: translateY(0);
        }

        .close-modal-reset {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #999;
            background: none;
            border: none;
        }

        .close-modal-reset:hover {
            color: #333;
        }

        .modal-reset-title {
            text-align: center;
            color: #6f390d;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .modal-reset-message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            display: none;
        }

        .modal-reset-error {
            background-color: #ffebee;
            color: #d32f2f;
            border: 1px solid #ffcdd2;
        }

        .modal-reset-success {
            background-color: #e8f5e9;
            color: #388e3c;
            border: 1px solid #c8e6c9;
        }

        .modal-reset-step {
            display: none;
        }

        .modal-reset-step.active {
            display: block;
        }

        .modal-reset-instructions {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .modal-reset-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .modal-reset-link a {
            color: #6f390d;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-reset-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body class="d-flex flex-column">
    <main class="flex-shrink-0">
        <!-- Navigation - Cabeçalho ajustado para ficar próximo às bordas -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid px-4"> <!-- Alterado para container-fluid e px-4 -->
                <a class="navbar-brand" href="index.php">Info Cultura</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="calendario.php">Calendário Cultural</a></li>
                    <li class="nav-item"><a class="nav-link" href="quemsomos.php">Quem Somos</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                </ul>
            </div>
        </nav>

        <!-- Login Form -->
        <div class="bg-[#e89516] min-h-screen flex items-center justify-center p-4">
            <div class="w-full max-w-4xl bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="grid md:grid-cols-2">
                    <!-- Left Side - Illustration -->
                    <div class="hidden md:block bg-[#e6ae6c] p-8 text-gray-800">
                        <div class="flex flex-col h-full justify-center">
                            <h2 class="text-[#6f390d] font-bold mb-4">Bem-vindo ao nosso sistema</h2>
                            <p class="text-[#6f390d] mb-6">Gerencie seus recursos ou explore nosso conteúdo como
                                visitante.</p>

                            <div class="space-y-4">
                                <div class="flex items-start">
                                    <div class="bg-white text-[#6f390d] rounded-full p-2 mr-4">
                                        <i class="fas fa-users-cog text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-[#6f390d] font-bold">Área do Administrador</h3>
                                        <p class="text-sm text-[#6f390d]">Exclusivo para coordenadores do NEABI</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="bg-white text-[#6f390d] rounded-full p-2 mr-4">
                                        <i class="fas fa-user-friends text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-[#6f390d] font-bold">Acesso Visitante</h3>
                                        <p class="text-sm text-[#6f390d]">Explore nosso conteúdo público</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side - Form -->
                    <div class="p-8 md:p-12">
                        <div class="flex justify-center mb-6">
                            <h1 class="text-3xl font-bold text-[#6f390d]">
                                <i class="fas fa-unlock-alt text-[#6f390d] mr-2"></i> Acesso ao Sistema
                            </h1>
                        </div>

                        <!-- Switch between admin and visitor -->
                        <div class="flex justify-center mb-8">
                            <div class="bg-gray-100 p-1 rounded-full flex">
                                <button id="visitor-btn"
                                    class="px-6 py-2 rounded-full font-medium card-switch card-active hover-effect">
                                    <i class="fas fa-user mr-2"></i> Visitante
                                </button>
                                <button id="admin-btn"
                                    class="px-6 py-2 rounded-full font-medium text-gray-500 card-switch card-inactive hover-effect">
                                    <i class="fas fa-user-shield mr-2"></i> Administrador
                                </button>
                            </div>
                        </div>

                        <!-- Visitor Form (Default) -->
                        <div id="visitor-form" class="space-y-4">
                            <div>
                                <label for="visitor-name" class="block text-sm font-medium text-gray-700 mb-1">Nome ou
                                    Apelido</label>
                                <input type="text" id="visitor-name"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#e6ae6c] focus:border-[#e6ae6c] outline-none transition"
                                    placeholder="Digite seu nome ou apelido">
                            </div>
                            <button id="visitor-login-btn"
                                class="w-full bg-[#6f390d] text-white py-3 rounded-lg font-medium hover:bg-[#a75c1e] focus:outline-none focus:ring-2 focus:ring-[#e6ae6c] focus:ring-offset-2 transition-all duration-300 hover:shadow-md">
                                <i class="fas fa-sign-in-alt mr-2"></i> Entrar como Visitante
                            </button>
                            <div class="text-center text-sm text-gray-500 mt-2">
                                <p>Ao continuar, você concorda com nossos <a href="#"
                                        class="text-[#6f390d] hover:underline">Termos de Uso</a>.</p>
                            </div>
                        </div>

                        <!-- Admin Form (Hidden by default) -->
                        <div id="admin-form" class="space-y-4 hidden">
                            <?php if (isset($erro_login)): ?>
                                <div class="message error"><?php echo $erro_login; ?></div>
                            <?php endif; ?>
                            
                            <div>
                                <label for="admin-email" class="block text-sm font-medium text-gray-700 mb-1">E-mail
                                    Administrativo</label>
                                <input type="email" id="admin-email" name="admin-email"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#e6ae6c] focus:border-[#e6ae6c] outline-none transition"
                                    placeholder="Digite seu e-mail admin" required>
                            </div>
                            <div>
                                <label for="admin-password"
                                    class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                                <div class="relative">
                                    <input type="password" id="admin-password" name="admin-password"
                                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#e6ae6c] focus:border-[#e6ae6c] outline-none transition pr-10"
                                        placeholder="Digite sua senha" required>
                                    <button class="absolute right-3 top-3 text-gray-400 hover:text-gray-600"
                                        onclick="togglePassword('admin-password')">
                                        <i class="far fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <input id="remember-me" type="checkbox"
                                        class="h-4 w-4 text-[#a75c1e] focus:ring-[#a75c1e] border-gray-300 rounded">
                                    <label for="remember-me" class="ml-2 block text-sm text-gray-700">Manter-me
                                        conectado</label>
                                </div>
                                <div>
                                    <a href="#" class="text-sm text-[#6f390d] hover:underline" id="esqueciSenhaLink">Esqueceu sua senha?</a>
                                </div>
                            </div>
                            <button type="submit" name="admin-login-btn" id="admin-login-btn"
                                class="w-full bg-[#6f390d] text-white py-3 rounded-lg font-medium hover:bg-[#a75c1e] focus:outline-none focus:ring-2 focus:ring-[#e6ae6c] focus:ring-offset-2 transition-all duration-300 hover:shadow-md">
                                <i class="fas fa-sign-in-alt mr-2"></i> Entrar como Administrador
                            </button>
                        </div>

                        <div class="text-center mt-6">
                            <p class="text-sm text-gray-500">Não tem uma conta? <a href="#"
                                    class="text-[#6f390d] font-medium hover:underline">Solicitar acesso</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer ajustado para ficar próximo às bordas -->
    <footer class="bg-dark py-4 mt-auto">
        <div class="container-fluid px-4"> <!-- Alterado para container-fluid e px-4 -->
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

    <!-- Modal de Redefinição de Senha -->
    <div class="modal-reset" id="modalResetSenha">
        <div class="modal-reset-content">
            <button class="close-modal-reset" id="closeModalReset">&times;</button>
            
            <!-- Etapa 1: Solicitar reset -->
            <div class="modal-reset-step active" id="resetStep1">
                <h2 class="modal-reset-title">Redefinir Senha</h2>
                
                <div class="modal-reset-message" id="resetMessage1">
                    <?php if (!empty($erro)): ?>
                        <div class="modal-reset-error"><?php echo $erro; ?></div>
                    <?php elseif (!empty($sucesso)): ?>
                        <div class="modal-reset-success"><?php echo $sucesso; ?></div>
                    <?php endif; ?>
                </div>
                
                <p class="modal-reset-instructions">Digite seu endereço de email e enviaremos um link para redefinir sua senha.</p>
                <form id="formSolicitarReset" method="POST" action="">
                    <div class="form-group">
                        <label for="reset-email" class="block text-sm font-medium text-gray-700 mb-1">Email:</label>
                        <input type="email" id="reset-email" name="email" required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#e6ae6c] focus:border-[#e6ae6c] outline-none transition"
                            value="<?php echo isset($_SESSION['email_solicitado']) ? $_SESSION['email_solicitado'] : ''; ?>">
                    </div>
                    <button type="submit" name="solicitar_reset" class="w-full bg-[#6f390d] text-white py-3 rounded-lg font-medium hover:bg-[#a75c1e] focus:outline-none focus:ring-2 focus:ring-[#e6ae6c] focus:ring-offset-2 transition-all duration-300 hover:shadow-md mt-4">
                        Enviar Link de Redefinição
                    </button>
                </form>
                
                <div class="modal-reset-link">
                    <a id="voltarLoginReset">Voltar para o login</a>
                </div>
            </div>
            
            <!-- Etapa 2: Definir nova senha -->
            <div class="modal-reset-step" id="resetStep2">
                <h2 class="modal-reset-title">Criar Nova Senha</h2>
                
                <div class="modal-reset-message" id="resetMessage2">
                    <?php if (!empty($erro)): ?>
                        <div class="modal-reset-error"><?php echo $erro; ?></div>
                    <?php elseif (!empty($sucesso)): ?>
                        <div class="modal-reset-success"><?php echo $sucesso; ?></div>
                    <?php endif; ?>
                </div>
                
                <p class="modal-reset-instructions">Digite sua nova senha abaixo.</p>
                <form id="formNovaSenha" method="POST" action="">
                    <input type="hidden" id="reset-token" name="token" value="<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : (isset($_SESSION['token_simulado']) ? $_SESSION['token_simulado'] : ''); ?>">
                    <div class="form-group">
                        <label for="nova-senha" class="block text-sm font-medium text-gray-700 mb-1">Nova Senha:</label>
                        <input type="password" id="nova-senha" name="nova_senha" required minlength="6"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#e6ae6c] focus:border-[#e6ae6c] outline-none transition">
                    </div>
                    <div class="form-group">
                        <label for="confirmar-senha" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Nova Senha:</label>
                        <input type="password" id="confirmar-senha" name="confirmar_senha" required minlength="6"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#e6ae6c] focus:border-[#e6ae6c] outline-none transition">
                    </div>
                    <button type="submit" name="definir_nova_senha" class="w-full bg-[#6f390d] text-white py-3 rounded-lg font-medium hover:bg-[#a75c1e] focus:outline-none focus:ring-2 focus:ring-[#e6ae6c] focus:ring-offset-2 transition-all duration-300 hover:shadow-md mt-4">
                        Redefinir Senha
                    </button>
                </form>
                
                <div class="modal-reset-link">
                    <a id="voltarSolicitacaoReset">Voltar</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JS-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Core theme JS-->
    <script src="js/scripts.js"></script>
    <script>
        // Lista de e-mails especiais que redirecionam para outra página
        const specialEmails = [
            'infocultura2025@gmail.com'
        ];
        
        // Senha especial para redirecionamento
        const specialPassword = 'infocultura2025';

        // Toggle between visitor and admin forms 
        document.getElementById('visitor-btn').addEventListener('click', function () {
            document.getElementById('visitor-btn').classList.remove('text-gray-500', 'card-inactive');
            document.getElementById('visitor-btn').classList.add('text-white', 'bg-[#6f390d]', 'card-active');
            document.getElementById('admin-btn').classList.add('text-gray-500', 'card-inactive');
            document.getElementById('admin-btn').classList.remove('text-white', 'bg-[#6f390d]', 'card-active');
            document.getElementById('visitor-form').classList.remove('hidden');
            document.getElementById('admin-form').classList.add('hidden');
        });

        document.getElementById('admin-btn').addEventListener('click', function () {
            document.getElementById('admin-btn').classList.remove('text-gray-500', 'card-inactive');
            document.getElementById('admin-btn').classList.add('text-white', 'bg-[#6f390d]', 'card-active');
            document.getElementById('visitor-btn').classList.add('text-gray-500', 'card-inactive');
            document.getElementById('visitor-btn').classList.remove('text-white', 'bg-[#6f390d]', 'card-active');
            document.getElementById('admin-form').classList.remove('hidden');
            document.getElementById('visitor-form').classList.add('hidden');
        });

        // Toggle password visibility
        function togglePassword(id) {
            const passwordField = document.getElementById(id);
            const icon = passwordField.nextElementSibling.querySelector('i');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Login button actions
        document.getElementById('visitor-login-btn').addEventListener('click', function () {
            const name = document.getElementById('visitor-name').value;
            if (!name) {
                alert('Por favor, digite seu nome ou apelido para continuar.');
                return;
            }
            alert(`Bem-vindo, ${name}! Você entrou como visitante. Redirecionando...`);
            // Aqui você adicionaria a lógica de redirecionamento
            window.location.href = 'index.php'; // Redireciona para a página inicial
        });

        // Animate cards on hover
        const cards = document.querySelectorAll('.hover-effect');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'scale(1.03)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'scale(1)';
            });
        });

        // =============================================
        // CÓDIGO PARA O MODAL DE REDEFINIÇÃO DE SENHA
        // =============================================
        
        // Elementos do DOM para o modal de reset
        const modalReset = document.getElementById('modalResetSenha');
        const btnAbrirModalReset = document.getElementById('esqueciSenhaLink');
        const btnFecharModalReset = document.getElementById('closeModalReset');
        const btnVoltarLoginReset = document.getElementById('voltarLoginReset');
        const btnVoltarSolicitacaoReset = document.getElementById('voltarSolicitacaoReset');
        const formSolicitarReset = document.getElementById('formSolicitarReset');
        const formNovaSenha = document.getElementById('formNovaSenha');
        const resetStep1 = document.getElementById('resetStep1');
        const resetStep2 = document.getElementById('resetStep2');
        const resetMessage1 = document.getElementById('resetMessage1');
        const resetMessage2 = document.getElementById('resetMessage2');
        
        // Abrir modal de reset
        if (btnAbrirModalReset) {
            btnAbrirModalReset.addEventListener('click', (e) => {
                e.preventDefault();
                modalReset.classList.add('active');
                showResetStep(1);
            });
        }
        
        // Fechar modal de reset
        if (btnFecharModalReset) {
            btnFecharModalReset.addEventListener('click', closeModalReset);
        }
        
        if (btnVoltarLoginReset) {
            btnVoltarLoginReset.addEventListener('click', closeModalReset);
        }
        
        // Voltar para a etapa 1 no modal de reset
        if (btnVoltarSolicitacaoReset) {
            btnVoltarSolicitacaoReset.addEventListener('click', () => {
                showResetStep(1);
            });
        }
        
        // Fechar modal clicando fora dele
        if (modalReset) {
            modalReset.addEventListener('click', (e) => {
                if (e.target === modalReset) {
                    closeModalReset();
                }
            });
        }
        
        // Função para mostrar uma etapa específica no modal de reset
        function showResetStep(stepNumber) {
            // Esconder todas as etapas
            if (resetStep1) resetStep1.classList.remove('active');
            if (resetStep2) resetStep2.classList.remove('active');
            
            // Limpar mensagens
            if (resetMessage1) {
                resetMessage1.style.display = 'none';
                resetMessage1.classList.remove('modal-reset-error', 'modal-reset-success');
            }
            if (resetMessage2) {
                resetMessage2.style.display = 'none';
                resetMessage2.classList.remove('modal-reset-error', 'modal-reset-success');
            }
            
            // Mostrar a etapa solicitada
            if (stepNumber === 1 && resetStep1) {
                resetStep1.classList.add('active');
            } else if (stepNumber === 2 && resetStep2) {
                resetStep2.classList.add('active');
            }
        }
        
        // Função para fechar o modal de reset
        function closeModalReset() {
            if (modalReset) modalReset.classList.remove('active');
            // Resetar formulários
            if (formSolicitarReset) formSolicitarReset.reset();
            if (formNovaSenha) formNovaSenha.reset();
            // Voltar para a primeira etapa
            showResetStep(1);
        }
        
        // Tecla ESC fecha o modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalReset && modalReset.classList.contains('active')) {
                closeModalReset();
            }
        });

        // Verificar se há token na URL (quando o usuário clica no link do email)
        const urlParams = new URLSearchParams(window.location.search);
        const tokenUrl = urlParams.get('token');
        
        if (tokenUrl) {
            // Preencher o token e mostrar o formulário de nova senha
            document.getElementById('reset-token').value = tokenUrl;
            modalReset.classList.add('active');
            showResetStep(2);
        }
    </script>
</body>

</html>

<?php
// Limpar sessão após uso
if (isset($_SESSION['email_solicitado'])) {
    unset($_SESSION['email_solicitado']);
}
if (isset($_SESSION['token_simulado'])) {
    unset($_SESSION['token_simulado']);
}

// Fechar conexão com o banco de dados
$conn->close();
?>