<?php
session_start();

// On affiche direct la superglobale POST dès qu'on clique sur envoyer
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    echo "<h3>Données reçues en POST :</h3>";
    echo "<pre>"; 
    print_r($_POST); 
    echo "</pre>"; 
    die(); // On stoppe le script immédiatement ici
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Debug Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card p-4 shadow-sm">
                    <form action="connexion.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="text" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" name="mdp" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Tester POST</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>