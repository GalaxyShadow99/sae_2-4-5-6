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
?>