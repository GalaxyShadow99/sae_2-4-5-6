<!DOCTYPE html>
<html lang="fr">

<?php include_once("./includes/head.php"); ?>

<body>
    <?php include_once("./includes/topbar.php"); ?>

    <main class="container mt-4">
        <h1 class="text-center">Réserver un voyage</h1>

        <?php
        require_once './bdd/env.php';
        require_once './bdd/BddUtils.php';
        define('MOD_BDD', 'ORACLE');
        $conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);
        $lignes = ListeLignes($conn);
        $communes = ListeCommunesLignes($conn);

        // --- TRAITEMENT DU FORMULAIRE (POST) ---
        $message = '';
        $messageType = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Nettoyage et validation des entrées
            $nom    = trim(htmlspecialchars($_POST['nom']    ?? ''));
            $prenom = trim(htmlspecialchars($_POST['prenom'] ?? ''));
            $email  = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
            $numLigne   = trim($_POST['Num_Ligne']  ?? '');
            $comDepart  = trim($_POST['Com_depart'] ?? '');
            $comArrivee = trim($_POST['Com_arrivee'] ?? '');

            if (empty($nom) || empty($prenom) || empty($email) || empty($numLigne) || empty($comDepart) || empty($comArrivee)) {
                $message = 'Veuillez remplir tous les champs obligatoires.';
                $messageType = 'danger';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'L\'adresse email saisie est invalide.';
                $messageType = 'danger';
            } elseif ($comDepart === $comArrivee) {
                $message = 'La ville de départ et la ville d\'arrivée ne peuvent pas être identiques.';
                $messageType = 'danger';
            } else {
                // Appel à la fonction de réservation
                $resultat = reserverLigne($conn, $nom, $prenom, $email, $numLigne, $comDepart, $comArrivee);
                if ($resultat) {
                    $message = 'Votre réservation a bien été enregistrée !';
                    $messageType = 'success';
                } else {
                    $message = 'Une erreur est survenue lors de la réservation. Veuillez réessayer.';
                    $messageType = 'danger';
                }
            }
        }

        $conn = null;
        ?>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> mt-3" role="alert">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center mt-4">
            <div class="col-md-6">
                <form method="post">

                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="nom" name="nom"
                               value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="prenom" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="prenom" name="prenom"
                               value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="Num_Ligne" class="form-label">Numéro de ligne *</label>
                        <select class="form-select" id="Num_Ligne" name="Num_Ligne" required>
                            <option value="" disabled selected>-- Choisir un numéro --</option>
                            <?php foreach ($lignes as $Ligne) { ?>
                                <option value="<?= htmlspecialchars($Ligne['LIG_NUM']) ?>"
                                    <?= (($_POST['Num_Ligne'] ?? '') == $Ligne['LIG_NUM']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($Ligne['LIG_NUM']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <!-- CORRECTION : le for du label correspond bien à l'id du select -->
                        <label for="Com_depart" class="form-label">Ville de départ *</label>
                        <select class="form-select" id="Com_depart" name="Com_depart" required>
                            <option value="" disabled selected>-- Choisir une ville --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <!-- CORRECTION : ville d'arrivée devient aussi un select dynamique -->
                        <label for="Com_arrivee" class="form-label">Ville d'arrivée *</label>
                        <select class="form-select" id="Com_arrivee" name="Com_arrivee" required>
                            <option value="" disabled selected>-- Choisir une ville --</option>
                        </select>
                    </div>

                    <p><small>* Champs obligatoires</small></p>

                    <button type="submit" class="btn btn-primary w-100">Réserver</button>

                </form>
            </div>
        </div>

        <script>
            // On passe les communes PHP vers JavaScript
            const communes = <?= json_encode($communes) ?>;

            function remplirSelects(ligNum) {
                const selectDepart  = document.getElementById('Com_depart');
                const selectArrivee = document.getElementById('Com_arrivee');

                // Vider les deux selects
                selectDepart.innerHTML  = '<option value="" disabled selected>-- Choisir une ville --</option>';
                selectArrivee.innerHTML = '<option value="" disabled selected>-- Choisir une ville --</option>';

                // Filtrer les communes de la ligne choisie
                const communesFiltrees = communes.filter(c => c['LIG_NUM'] == ligNum);

                communesFiltrees.forEach(c => {
                    // Départ
                    const optDepart = document.createElement('option');
                    optDepart.value       = c['COM_CODE_INSEE_ARRET'];
                    optDepart.textContent = c['COM_CODE_INSEE_ARRET']; // Remplacer par le nom si disponible en BDD
                    selectDepart.appendChild(optDepart);

                    // Arrivée (CORRECTION : même logique que départ)
                    const optArrivee = document.createElement('option');
                    optArrivee.value       = c['COM_CODE_INSEE_ARRET'];
                    optArrivee.textContent = c['COM_CODE_INSEE_ARRET'];
                    selectArrivee.appendChild(optArrivee);
                });
            }

            document.getElementById('Num_Ligne').addEventListener('change', function () {
                remplirSelects(this.value);
            });

            // Si le formulaire a été soumis avec des valeurs, on re-remplit les selects
            <?php if (!empty($_POST['Num_Ligne'])): ?>
            (function () {
                remplirSelects('<?= htmlspecialchars($_POST['Num_Ligne']) ?>');
                document.getElementById('Com_depart').value  = '<?= htmlspecialchars($_POST['Com_depart'] ?? '') ?>';
                document.getElementById('Com_arrivee').value = '<?= htmlspecialchars($_POST['Com_arrivee'] ?? '') ?>';
            })();
            <?php endif; ?>
        </script>

    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>

</html>