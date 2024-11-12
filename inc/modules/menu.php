<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5C2MCQ7H" height="0" width="0"
        style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<nav class="navbar">
    <div class="menu-container">
        <div class="menu-trigger" id="menuTrigger">
            <span class="line"></span>
            <span class="line"></span>
            <span class="line"></span>
        </div>
    </div>

    <a href="<?php echo home() ?>" class="navbar-brand">
        Info<i class="fa-solid fa-bolt"></i>Contratos
    </a>

    <div class="user-section">
        <div class="user-name">
            <i class="fas fa-user user-icon"></i>Usuario Invitado
        </div>
        <div class="user-ip"><?php echo getClientIP(); ?></div>
    </div>
</nav>

<div class="sidebar" id="sidebar">
    <div class="sidebar-content">
        <a href="<?php echo home() ?>" class="sidebar-link">
            <i class="fa-solid fa-house"></i>
            Inicio
        </a>
        <a href="<?php echo home('visor.php') ?>" class="sidebar-link">
            <i class="fas fa-robot"></i>
            Visor Inteligente
        </a>
        <a href="<?php echo home('buscador.php') ?>" class="sidebar-link">
            <i class="fas fa-search"></i>
            Buscador Avanzado
        </a>
        <a href="<?php echo home('licitaciones.php') ?>" class="sidebar-link">
            <i class="fas fa-bullhorn"></i>
            Explorador de Oportunidades
        </a>
        <a href="<?php echo $linkWhatsapp ?>" class="sidebar-link" target="_blank">
            <i class="fa-brands fa-whatsapp"></i>
            Soporte
        </a>
    </div>
</div>