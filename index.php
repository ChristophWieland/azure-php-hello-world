<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Meine Azure PHP App</title>
</head>
<body>
    <h1>
        <?php echo "Hello World from PHP by Christoph W!"; ?>
    </h1>

    <p>
        <?php
            echo "PHP Version: " . PHP_VERSION;
        ?>
    </p>

    <p>
        Aktuelle Serverzeit:
        <?php echo date("d.m.Y H:i:s"); ?>
    </p>
</body>
</html>