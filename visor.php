<?php require "inc/config/init.php" ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php require "inc/modules/head.php" ?>
</head>

<body>
    <?php include "inc/modules/menu.php" ?>

    <div class="container">
        <?php include "inc/services/analizador.php";

        if (isset($_GET['nit-entidad'])) {
            try {
                $nit = htmlspecialchars(strip_tags(trim($_GET['nit-entidad'])));

                if (!preg_match('/^\d{9,12}$/', $nit)) {
                    throw new Exception("NIT no válido. Debe contener entre 9 y 12 números.");
                }

                $analyzer = new ContractAnalyzer();
                $analisis = $analyzer->analyzeContractsByNit($nit);
                ?>
                <h2>Entidad NIT <?= $_GET['nit-entidad'] ?>:</h2>
                <p><strong>Total contratos analizados:</strong> <?= $analisis['total_contratos_analizados'] ?></p>
                <p><strong>Fecha y hora de análisis:</strong> <?= $analisis['fecha_analisis'] ?></p><br>
                <h3>Análisis generado con IA:</h3>
                <?php foreach ($analisis['anomalias_detectadas'] as $anomalias) { ?>
                    <p id="text-ia"><?= $anomalias ?></p>
                <?php }
            } catch (Exception $e) {
                echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        ?>
    </div>

    <?php require "inc/modules/footer.php" ?>
</body>

</html>