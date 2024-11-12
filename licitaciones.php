<?php require "inc/config/init.php" ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php require "inc/modules/head.php" ?>
</head>

<body>
    <?php include "inc/modules/menu.php" ?>

    <div class="container licitaciones-container">
        <h1>Licitaciones vigentes SECOP II</h1>

        <table id="licitacionesTable">
            <thead>
                <tr>
                    <th>Entidad</th>
                    <th>Ciudad</th>
                    <th>Descripción</th>
                    <th>Precio Base</th>
                    <th>Duración</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>

    <div id="loadingSpinner" class="loading-spinner">
        <div class="spinner"></div>
    </div>

    <?php require "inc/modules/footer.php" ?>
</body>

</html>