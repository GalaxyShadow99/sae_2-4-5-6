<?php 

function GetAllClients($conn) {
    $sql = "SELECT * FROM vik_client ORDER BY cli_date_connec desc";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function isUserAdmin($conn, $userId) {
    $sql = "SELECT 1 
            FROM vik_client c
            WHERE c.cli_num = :id
              AND EXISTS (
                  SELECT 1 
                  FROM vik_administrateur a 
                  WHERE UPPER(a.cli_courriel) = UPPER(c.cli_courriel)
              )";
              
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['id' => $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si la requête renvoie une ligne, le client est admin (true), sinon (false)
    return (bool)$result;
}

function deleteClient($conn, $cliNum) {
    try {
        $conn->beginTransaction();

        $sqlDeleteReservations2 = "DELETE FROM vik_etape WHERE cli_num = :num";
        $stmtReservations2 = preparerRequetePDO($conn, $sqlDeleteReservations2);
        $stmtReservations2->execute(['num' => $cliNum]);

        // suppr éléments fils, les résa du client
        $sqlDeleteReservations = "DELETE FROM vik_reservation WHERE cli_num = :num";
        $stmtReservations = preparerRequetePDO($conn, $sqlDeleteReservations);
        $stmtReservations->execute(['num' => $cliNum]);

        // on suppr le client lui-même
        $sqlDeleteClient = "DELETE FROM vik_client WHERE cli_num = :num";
        $stmtClient = preparerRequetePDO($conn, $sqlDeleteClient);
        $ok = $stmtClient->execute(['num' => $cliNum]);

        // Si la suppression a fonctionné, commmit
        if ($ok) {
            $conn->commit();
        } else {
            $conn->rollBack();
        }

        return $ok;

    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        // echo "Erreur lors de la suppression du client : " . $e->getMessage();
        return false;
    }
}

function updateClientInfos($conn, $cliNum, $nom, $prenom, $ville, $tel, $mail) {
    try {
        $sql = "UPDATE vik_client SET 
                    cli_nom = :nom,
                    cli_prenom = :prenom,
                    cli_ville = :ville,
                    cli_telephone = :tel,
                    cli_courriel = :mail
                WHERE cli_num = :num";
                
        $stmt = preparerRequetePDO($conn, $sql);
        return $stmt->execute([
            'nom'    => strtoupper($nom),
            'prenom' => ucfirst(strtolower($prenom)),
            'ville'  => $ville,
            'tel'    => $tel,
            'mail'   => $mail,
            'num'    => $cliNum
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function popUpClientNotAdmin() {
    return '
    <div class="modal fade" id="accessDeniedModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header text-white border-0 py-3" style="background: linear-gradient(135deg, #dc3545, #f25c6c);">
                    <h5 class="modal-title fw-bold mx-auto"><i class="bi bi-shield-x me-2"></i>Accès refusé</h5>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="text-danger display-1 mb-3">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <p class="mb-0 fs-5 fw-medium text-secondary">Privilèges insuffisants.</p>
                    <small class="text-muted d-block mt-2">Vous n\'avez pas les droits d\'administrateur pour consulter cette page.</small>
                </div>
                <div class="modal-footer justify-content-center border-0 pb-4">
                    <a href="index.php" class="btn btn-danger px-4 rounded-3 fw-semibold shadow-sm">
                        Retour à l\'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener(\'DOMContentLoaded\', function () {
            const modalElement = document.getElementById(\'accessDeniedModal\');
            const modal = new bootstrap.Modal(modalElement);
            modal.show();

            modalElement.addEventListener(\'hidden.bs.modal\', function () {
                window.location.href = \'index.php\';
            });

            setTimeout(function () {
                modal.hide();
            }, 3000);
        });
    </script>
    ';
}

?>