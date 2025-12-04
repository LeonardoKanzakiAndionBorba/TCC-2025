<?php
// navbar.php - PRIMEIRA LINHA do arquivo, sem espaços antes!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
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