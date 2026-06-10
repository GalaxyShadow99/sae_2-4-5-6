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
        $lignes  = ListeLignes($conn);       // LIG_NUM, COM_CODE_INSEE_DEBU, COM_CODE_INSEE_TERM
        $communes = ListeCommunesLignes($conn); // LIG_NUM, COM_CODE_INSEE_ARRET

        // --- TRAITEMENT DU FORMULAIRE (POST) ---
        $message = '';
        $messageType = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom        = trim(htmlspecialchars($_POST['nom']    ?? ''));
            $prenom     = trim(htmlspecialchars($_POST['prenom'] ?? ''));
            $email      = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
            $numLigne   = trim($_POST['Num_Ligne']   ?? '');
            $comDepart  = trim($_POST['Com_depart']  ?? '');
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
                        <label for="Num_Ligne" class="form-label">Ligne *</label>
                        <select class="form-select" id="Num_Ligne" name="Num_Ligne" required>
                            <option value="" disabled selected>-- Choisir une ligne --</option>
                            <?php foreach ($lignes as $Ligne): ?>
                                <?php
                                    // On construit un libellé lisible :
                                    // "Ligne 1A  (14118 → 50041)"
                                    $label = 'Ligne ' . trim($Ligne['LIG_NUM'])
                                           . '  (' . $Ligne['COM_CODE_INSEE_DEBU']
                                           . ' → ' . $Ligne['COM_CODE_INSEE_TERM'] . ')';
                                ?>
                                <option value="<?= htmlspecialchars(trim($Ligne['LIG_NUM'])) ?>"
                                    <?= (trim($_POST['Num_Ligne'] ?? '') === trim($Ligne['LIG_NUM'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="Com_depart" class="form-label">Arrêt de départ *</label>
                        <select class="form-select" id="Com_depart" name="Com_depart" required>
                            <option value="" disabled selected>-- Choisir d'abord une ligne --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="Com_arrivee" class="form-label">Arrêt d'arrivée *</label>
                        <select class="form-select" id="Com_arrivee" name="Com_arrivee" required>
                            <option value="" disabled selected>-- Choisir d'abord une ligne --</option>
                        </select>
                    </div>

                    <p><small>* Champs obligatoires</small></p>

                    <button type="submit" class="btn btn-primary w-100">Réserver</button>

                </form>
            </div>
        </div>

        <script>
            // Données des arrêts transmises depuis PHP
            // Chaque entrée : { LIG_NUM: "1A ", COM_CODE_INSEE_ARRET: "14118" }
            const communes = <?= json_encode($communes) ?>;

            function remplirSelects(ligNum) {
                const selectDepart  = document.getElementById('Com_depart');
                const selectArrivee = document.getElementById('Com_arrivee');

                selectDepart.innerHTML  = '<option value="" disabled selected>-- Choisir un arrêt --</option>';
                selectArrivee.innerHTML = '<option value="" disabled selected>-- Choisir un arrêt --</option>';

                // LIG_NUM en base contient parfois un espace en fin ("1A ") — on trim des deux côtés
                const arrets = communes.filter(c => c['LIG_NUM'].trim() === ligNum.trim());

                if (arrets.length === 0) {
                    selectDepart.innerHTML  = '<option value="" disabled selected>Aucun arrêt trouvé</option>';
                    selectArrivee.innerHTML = '<option value="" disabled selected>Aucun arrêt trouvé</option>';
                    return;
                }

                arrets.forEach(c => {
                    const code = c['COM_CODE_INSEE_ARRET'];

                    const optD = document.createElement('option');
                    optD.value       = code;
                    optD.textContent = code;
                    selectDepart.appendChild(optD);

                    const optA = document.createElement('option');
                    optA.value       = code;
                    optA.textContent = code;
                    selectArrivee.appendChild(optA);
                });
            }

            document.getElementById('Num_Ligne').addEventListener('change', function () {
                remplirSelects(this.value);
            });

            // Restaurer les sélections après un POST raté (validation serveur)
            <?php if (!empty($_POST['Num_Ligne'])): ?>
            (function () {
                remplirSelects('<?= htmlspecialchars(trim($_POST['Num_Ligne'])) ?>');
                document.getElementById('Com_depart').value  = '<?= htmlspecialchars($_POST['Com_depart']  ?? '') ?>';
                document.getElementById('Com_arrivee').value = '<?= htmlspecialchars($_POST['Com_arrivee'] ?? '') ?>';
            })();
            <?php endif; ?>
        </script>

    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>
</body>

</html>