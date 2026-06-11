<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
include_once("./includes/head.php"); ?>
<!DOCTYPE html>
<html lang="fr" class="h-100">

<body class="d-flex flex-column h-100 bg-light text-dark">
    <?php include_once("./includes/topbar.php"); ?>

    <?php
        include_once("./bdd/env.php");
        include_once("./bdd/BddClientUtils.php");

        $conn = OuvrirConnexionPDO($dbOracle, $db_usernameOracle, $db_passwordOracle);

        $cli_num = $_SESSION['user_id'];

        $client = null;
        $reservations = [];
        $succes = null;
        $erreur = null;

        if ($cli_num && $conn) {
            $client = ListeInfosClient($conn, $cli_num);
            $reservations = HistoriqueReservationsClient($conn, $cli_num);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier' && $conn && $cli_num) {
            $nom      = trim($_POST['cli_nom'] ?? '');
            $prenom   = trim($_POST['cli_prenom'] ?? '');
            $ville    = trim($_POST['cli_ville'] ?? '');
            $tel      = trim($_POST['cli_telephone'] ?? '');
            $mail     = trim($_POST['cli_courriel'] ?? '');

            if ($nom && $prenom && $mail) {
                $sql = "UPDATE vik_client SET 
                            cli_nom = :nom,
                            cli_prenom = :prenom,
                            cli_ville = :ville,
                            cli_telephone = :tel,
                            cli_courriel = :mail
                        WHERE cli_num = :num";
                $stmt = preparerRequetePDO($conn, $sql);
                $stmt->execute([
                    'nom'    => strtoupper($nom),
                    'prenom' => ucfirst(strtolower($prenom)),
                    'ville'  => $ville,
                    'tel'    => $tel,
                    'mail'   => $mail,
                    'num'    => $cli_num
                ]);
                $succes = "Vos informations ont bien été mises à jour.";
                $client = ListeInfosClient($conn, $cli_num);
            } else {
                $erreur = "Veuillez remplir tous les champs obligatoires.";
            }
        }
    ?>

    <main class="container my-5 flex-shrink-0">

        <div class="row mb-5 text-center">
            <div class="col-lg-8 mx-auto">
                <div class="mb-3">
                    <span class="badge px-3 py-2 rounded-pill fw-semibold text-uppercase tracking-wider"
                          style="background-color: rgba(210, 10, 40, 0.1); color: rgb(210, 10, 40);">
                        Mon espace
                    </span>
                </div>
                <h1 class="display-5 fw-bold text-dark mb-3">
                    Mon Profil
                </h1>
                <p class="lead text-secondary">
                    Consultez vos informations, votre historique de trajets et gérez votre compte fidélité.
                </p>
            </div>
        </div>

        <?php if (!$client): ?>
        <div class="p-4 p-md-5 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 text-center">
            <i class="bi bi-person-x fs-1 text-secondary mb-3 d-block"></i>
            <h4 class="fw-bold text-dark mb-2">Accès réservé aux clients connectés</h4>
            <p class="text-secondary mb-4">Veuillez vous connecter pour accéder à votre profil.</p>
            <a href="connexion.php" class="btn px-4 py-2 fw-semibold rounded-3 shadow-sm text-white"
               style="background-color: rgb(210, 10, 40); border: 1px solid rgb(210, 10, 40);">
                <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
            </a>
        </div>

        <?php else: ?>

        <?php if ($succes): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($succes) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($erreur): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($erreur) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row gy-4">

            <div class="col-lg-4">

                <div class="p-4 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 mb-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">
								<span style="color: rgb(210, 10, 40);">|</span>
                                <?= htmlspecialchars($client['CLI_PRENOM']) ?>
                                <?= htmlspecialchars($client['CLI_NOM']) ?>
                            </h5>
                            <span class="text-secondary small">Client n°<?= $cli_num ?></span>
                        </div>
                    </div>
                    <hr class="border-secondary opacity-25">
                    <ul class="list-unstyled mb-0 small text-secondary">
                        <li class="mb-2"><i class="bi bi-geo-alt me-2"></i><?= htmlspecialchars($client['CLI_VILLE'] ?: '—') ?></li>
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i><?= htmlspecialchars($client['CLI_TELEPHONE'] ?: '—') ?></li>
                        <li><i class="bi bi-envelope me-2"></i><?= htmlspecialchars($client['CLI_COURRIEL'] ?: '—') ?></li>
                    </ul>
                </div>

                <div class="p-4 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10">
                    <h6 class="fw-bold text-dark text-uppercase small mb-3">
                        <span style="color: rgb(210, 10, 40);">|</span> Points fidélité
                    </h6>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-center">
                            <div class="fs-3 fw-bold" style="color: rgb(210, 10, 40);">
                                <?= number_format((int)($client['CLI_NB_POINTS_EC'] ?? 0)) ?>
                            </div>
                            <div class="text-secondary small">Points en cours</div>
                        </div>
                        <div class="text-center">
                            <div class="fs-3 fw-bold text-dark">
                                <?= number_format((int)($client['CLI_NB_POINTS_TOT'] ?? 0)) ?>
                            </div>
                            <div class="text-secondary small">Points cumulés</div>
                        </div>
                    </div>
   
                    <?php if ($client['CLI_DATE_CONNEC']): ?>
                    <p class="text-secondary small mt-3 mb-0">
                        <i class="bi bi-clock-history me-1"></i>
                        Dernière connexion : <?= htmlspecialchars($client['CLI_DATE_CONNEC']) ?>
                    </p>
                    <?php endif; ?>
                </div>

            </div>

            <div class="col-lg-8">

                <div class="p-4 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="fw-bold text-dark h5 mb-0 text-uppercase tracking-wider">
                            <span style="color: rgb(210, 10, 40);">|</span> Mes informations
                        </h2>
                        <button class="btn btn-sm btn-outline-secondary rounded-3" type="button"
                                data-bs-toggle="collapse" data-bs-target="#formModif" aria-expanded="false">
                            <i class="bi bi-pencil me-1"></i>Modifier
                        </button>
                    </div>

                    <div class="row gy-2 small text-secondary mb-2" id="infoLecture">
                        <div class="col-sm-6"><strong class="text-dark">Nom</strong><br><?= htmlspecialchars($client['CLI_NOM']) ?></div>
                        <div class="col-sm-6"><strong class="text-dark">Prénom</strong><br><?= htmlspecialchars($client['CLI_PRENOM']) ?></div>
                        <div class="col-sm-6"><strong class="text-dark">Ville</strong><br><?= htmlspecialchars($client['CLI_VILLE'] ?: '—') ?></div>
                        <div class="col-sm-6"><strong class="text-dark">Téléphone</strong><br><?= htmlspecialchars($client['CLI_TELEPHONE'] ?: '—') ?></div>
                        <div class="col-12"><strong class="text-dark">E-mail</strong><br><?= htmlspecialchars($client['CLI_COURRIEL'] ?: '—') ?></div>
                    </div>

                    <div class="collapse" id="formModif">
                        <hr class="border-secondary opacity-25">
                        <form method="POST" action="profil.php<?= $cli_num ? '?cli_num='.$cli_num : '' ?>">
                            <input type="hidden" name="action" value="modifier">
                            <div class="row gy-3">
                                <div class="col-sm-6">
                                    <label class="form-label small fw-semibold text-dark">Nom <span class="text-danger">*</span></label>
                                    <input type="text" name="cli_nom" class="form-control rounded-3"
                                           value="<?= htmlspecialchars($client['CLI_NOM']) ?>" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small fw-semibold text-dark">Prénom <span class="text-danger">*</span></label>
                                    <input type="text" name="cli_prenom" class="form-control rounded-3"
                                           value="<?= htmlspecialchars($client['CLI_PRENOM']) ?>" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small fw-semibold text-dark">Ville</label>
                                    <input type="text" name="cli_ville" class="form-control rounded-3"
                                           value="<?= htmlspecialchars($client['CLI_VILLE'] ?? '') ?>">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small fw-semibold text-dark">Téléphone</label>
                                    <input type="tel" name="cli_telephone" class="form-control rounded-3"
                                           value="<?= htmlspecialchars($client['CLI_TELEPHONE'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-dark">E-mail <span class="text-danger">*</span></label>
                                    <input type="email" name="cli_courriel" class="form-control rounded-3"
                                           value="<?= htmlspecialchars($client['CLI_COURRIEL'] ?? '') ?>" required>
                                </div>
                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn px-4 py-2 fw-semibold rounded-3 shadow-sm text-white"
                                            style="background-color: rgb(210, 10, 40); border: 1px solid rgb(210, 10, 40);">
                                        <i class="bi bi-check-lg me-1"></i>Enregistrer
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary rounded-3"
                                            data-bs-toggle="collapse" data-bs-target="#formModif">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="p-4 bg-white rounded-4 shadow-sm border border-secondary border-opacity-10">
                    <h2 class="fw-bold text-dark h5 mb-4 text-uppercase tracking-wider">
                        <span style="color: rgb(210, 10, 40);">|</span> Mes trajets
                    </h2>

                    <?php if (empty($reservations)): ?>
                    <div class="text-center py-4 text-secondary">
                        <i class="bi bi-ticket-perforated fs-2 mb-2 d-block"></i>
                        <p class="mb-3">Aucune réservation pour le moment.</p>
                        <a href="reserver.php" class="btn btn-sm px-4 py-2 fw-semibold rounded-3 text-white"
                           style="background-color: rgb(210, 10, 40); border: 1px solid rgb(210, 10, 40);">
                            <i class="bi bi-plus-lg me-1"></i>Réserver un trajet
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle small mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="fw-semibold text-uppercase text-secondary small border-0">N° Résa</th>
                                    <th class="fw-semibold text-uppercase text-secondary small border-0">Date</th>
                                    <th class="fw-semibold text-uppercase text-secondary small border-0">Prix</th>
                                    <th class="fw-semibold text-uppercase text-secondary small border-0">Points gagnés</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservations as $r): ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($r['RES_NUM']) ?></td>
                                    <td class="text-secondary"><?= htmlspecialchars($r['RES_DATE']) ?></td>
                                    <td>
                                        <span class="fw-semibold"
                                              style="color: rgb(210,10,40);">
                                            <?= number_format((float)($r['RES_PRIX_TOT'] ?? 0), 2) ?> €
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($r['RES_NB_POINTS']): ?>
                                        <span class="text-success fw-semibold">+<?= htmlspecialchars($r['RES_NB_POINTS']) ?> pts</span>
                                        <?php else: ?>
                                        <span class="text-secondary">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-between align-items-center small text-secondary border-top pt-3">
                        <span><?= count($reservations) ?> réservation(s) au total</span>
                        <a href="reserver.php" class="btn btn-sm btn-outline-secondary rounded-3">
                            <i class="bi bi-plus-lg me-1"></i>Nouvelle réservation
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <?php endif; ?>

    </main>

    <?php include_once("./includes/footer.php"); ?>
    <?php include_once("./includes/jsIncludes.php"); ?>

    <?php
    // Fermeture de la connexion BDD
    if (isset($conn)) {
        $conn = null;
    }
    ?>
</body>

</html>
