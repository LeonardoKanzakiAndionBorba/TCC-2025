<?php
session_start();

// VERIFICAÇÃO DE LOGOUT
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// VERIFICAÇÃO SE USUÁRIO ESTÁ LOGADO
$userLoggedIn = false;
$userName = '';

if (isset($_SESSION['user'])) {
    if (is_array($_SESSION['user']) && isset($_SESSION['user']['loggedIn']) && $_SESSION['user']['loggedIn'] === true) {
        $userLoggedIn = true;
        $userName = $_SESSION['user']['nome'] ?? 'Usuário';
    } elseif ($_SESSION['user'] === true) {
        $userLoggedIn = true;
        $userName = 'Usuário';
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
    <style>
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

        /* Ajuste do carrossel para não ter margens laterais */
        .carousel-inner,
        .carousel-item,
        .carousel-item img {
            border-radius: 0;
        }

        /* Estilos para as setas do carrossel - MAIORES E MAIS VISÍVEIS */
        .carousel-control-prev,
        .carousel-control-next {
            width: 60px;
            height: 60px;
            background-color: #a75c1e;
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.8;
            transition: opacity 0.3s ease, background-color 0.3s ease;
        }

        .carousel-control-prev {
            left: 20px;
        }

        .carousel-control-next {
            right: 20px;
        }

        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            opacity: 1;
            background-color: #6f390d;
        }

        /* Ícones das setas maiores */
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            width: 2.5rem;
            height: 2.5rem;
            background-size: 100% 100%;
        }

        /* CORREÇÃO ADICIONAL: Remover qualquer margem ou padding extra */
        body {
            margin: 0;
            padding: 0;
        }

        main {
            margin: 0;
            padding: 0;
        }

        /* Estilos específicos para a página Quem Somos */
        .img-fluid.rounded-circle {
            border: 3px solid #6f390d;
        }

        .fw-bolder {
            color: #6f390d;
        }

        .bg-light {
            background-color: #e6ae6c !important;
        }

        .text-muted {
            color: #5a2e0a !important;
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
                        <?php if ($userLoggedIn): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i>Perfil
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                    <li><span class="dropdown-item-text text-white text-break">Olá, <?php echo htmlspecialchars($userName); ?></span></li>
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

        <!--INÍCIO CÓDIGO CARROSSEL-->
        <div id="carouselExampleCaptions" class="carousel slide">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="0" class="active"
                    aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="1"
                    aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="2"
                    aria-label="Slide 3"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="assets/QuemSomos1.png" class="d-block w-100" alt="...">
                </div>
                <div class="carousel-item">
                    <img src="assets/QuemSomos2.png" class="d-block w-100" alt="...">
                </div>
                <div class="carousel-item">
                    <img src="assets/QuemSomos3.png" class="d-block w-100" alt="...">
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions"
                data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions"
                data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
        <!--FIM CÓDIGO CARROSSEL-->

        <!-- CONTEÚDO DA PÁGINA-->
        <section class="py-5">
            <div class="container px-5 my-5">
                <div class="row gx-5 align-items-center">
                    <div class="col-lg-6 order-first order-lg-last"><img class="img-fluid rounded mb-5 mb-lg-0"
                            src="assets/ftInfoCultura.png" alt="..." /></div>
                    <div class="col-lg-6">
                        <h2 class="fw-bolder">Info Cultura</h2>
                        <p class="lead fw-normal text-muted mb-0">Info Cultura é uma plataforma que promove o acesso à
                            informação sobre a diversidade cultural, com foco nas culturas afro-brasileira e indígena.
                            Seu objetivo é fornecer recursos educacionais e materiais didáticos que ajudem educadores e
                            estudantes a valorizar essas manifestações culturais. Através de conteúdos interativos, a
                            Info Cultura facilita a inclusão de temas étnico-raciais nas práticas pedagógicas,
                            contribuindo para uma educação mais inclusiva e antirracista.</p>
                    </div>
                </div>
            </div>
        </section>
        <!-- Team members section-->
        <section class="py-5 bg-light">
            <div class="container px-5 my-5">
                <div class="text-center">
                    <h2 class="fw-bolder">Equipe InfoCultura</h2>
                    <p class="lead fw-normal text-muted mb-5">ESTUDANTES DO ENSINO MÉDIO TÉCNICO EM INFORMÁTICA IFPR
                        CAMPUS PINHAIS</p>
                </div>
                <div class="row gx-5 row-cols-1 row-cols-sm-2 row-cols-xl-4 justify-content-center">
                    <div class="col mb-5 mb-5 mb-xl-0">
                        <div class="text-center">
                            <img class="img-fluid rounded-circle mb-4 px-4" src="assets/ftAna.jpg" alt="..." />
                            <h5 class="fw-bolder">Ana Julia Schuenck</h5>
                            <div class="fst-italic text-muted"></div>
                        </div>
                    </div>
                    <div class="col mb-5 mb-5 mb-xl-0">
                        <div class="text-center">
                            <img class="img-fluid rounded-circle mb-4 px-4" src="assets/ftLeo.jpg" alt="..." />
                            <h5 class="fw-bolder">Leonardo Kanzaki</h5>
                            <div class="fst-italic text-muted"></div>
                        </div>
                    </div>
                    <div class="col mb-5 mb-5 mb-sm-0">
                        <div class="text-center">
                            <img class="img-fluid rounded-circle mb-4 px-4" src="assets/ftRay.jpg" alt="..." />
                            <h5 class="fw-bolder">Raianny Paixão</h5>
                            <div class="fst-italic text-muted"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="py-5">
            <div class="container px-5 my-5">
                <div class="row gx-5 align-items-center">
                    <div class="col-lg-6 order-first order-lg-last"><img class="img-fluid rounded mb-5 mb-lg-0"
                            src="assets/ftNEABI.png" alt="..." /></div>
                    <div class="col-lg-6">
                        <h2 class="fw-bolder">NEABI</h2>
                        <p class="lead fw-normal text-muted mb-0">Os Núcleos de Estudos Afro-Brasileiros e Indígenas
                            (NEABI) do Instituto Federal do Paraná (IFPR) exercem um papel institucional fundamental na
                            promoção da formação, da produção de conhecimento e na realização de ações voltadas à
                            valorização das histórias, identidades e culturas negras, africanas, afrodescendentes e dos
                            povos originários. Suas atividades buscam, ainda, contribuir para a superação das diversas
                            formas de discriminação étnico-racial no âmbito do IFPR.</p>
                    </div>
                </div>
            </div>
        </section>

    </main>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>