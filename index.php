<?php require "inc/config/init.php" ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php require "inc/modules/head.php" ?>
</head>

<body>
    <?php include "inc/modules/menu.php" ?>

    <div class="container">
        <div class="alert-section">
            <div class="alert-title">
                <i class="fas fa-exclamation-triangle"></i>
                Advertencia
            </div>
            <div class="alert-content">
                En estos momentos nos encontramos implementando las nuevas funcionalidades.
            </div>
        </div>

        <div class="main-modules">
            <div class="module-card">
                <div class="module-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <h3 class="module-title">Visor Inteligente</h3>
                <p class="module-description">
                    Análisis en tiempo real de patrones y anomalías en contratos públicos mediante IA.
                </p>
            </div>
            <div class="module-card">
                <div class="module-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3 class="module-title">Buscador Avanzado</h3>
                <p class="module-description">
                    Encuentra y analiza contratistas con búsqueda intuitiva y visualización de conexiones.
                </p>
            </div>
            <a href="licitaciones.php" style="text-decoration:none">
                <div class="module-card">
                    <div class="module-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h3 class="module-title">Explorador de Oportunidades</h3>
                    <p class="module-description">
                        Descubre y recibe alertas de nuevas licitaciones adaptadas a tu perfil basadas en IA.
                    </p>
                </div>
            </a>
        </div>

        <div class="search-section">
            <select id="entidades" class="search-input">
                <option></option>
            </select>
            <button class="search-button" id="searchBtn">
                <i class="fas fa-search"></i>
            </button>
        </div>

        <div class="dashboard">
            <div class="stat-card">
                <div class="stat-title">Contratos analizados hoy</div>
                <div class="stat-value">10</div>
                <div class="stat-change">
                    <i class="fas fa-arrow-up"></i> +10% vs. ayer
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Alertas detectadas</div>
                <div class="stat-value">7</div>
                <div class="stat-change pulse">
                    <span class="badge">2 nuevas</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Posibles ingresos identificados</div>
                <div class="stat-value">$2.5M</div>
                <div class="stat-change">
                    <i class="fas fa-arrow-up"></i> Esta semana
                </div>
            </div>
        </div>
    </div>

    <?php require "inc/modules/footer.php" ?>
</body>

</html>