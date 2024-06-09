<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <title>Party Manager</title>
    <style>
        * {
            user-select: none;
            -webkit-user-drag: none;
        }
    </style>
</head>
<body style="padding-top: 30px;">
    <div class="container">
        <h1>Sélectionner un mode</h1>

        <div class="list-group" style="margin-bottom: 1em;">
            <a href="/panes/arrivals.php" class="list-group-item list-group-item-action">Arrivées et départs</a>
            <a href="/panes/score.php" class="list-group-item list-group-item-action">Gestion des points générale</a>
            <a href="/panes/restaurant.php" class="list-group-item list-group-item-action">Restauration et commandes</a>
        </div>

        <a href="/inspect.php" target="_blank">Inspecteur en temps réel</a>
    </div>
</body>
</html>
