<?php
// BDDUtils.php
function OuvrirConnexionPDO($db, $db_username, $db_password) {
    try {
        $conn = new PDO($db, $db_username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $erreur) {
        echo "Erreur connexion : " . $erreur->getMessage();
        $conn = null;
    }
    return $conn;
}

function majDonneesPDO($conn, $sql) {
    return $conn->exec($sql);
}

function preparerRequetePDO($conn, $sql) {
    return $conn->prepare($sql);
}

function LireDonneesPDO3($conn, $sql, &$tab) {
    $cur = $conn->query($sql);
    $tab = $cur->fetchAll(PDO::FETCH_ASSOC);
    return count($tab);
}

// Retourne toutes les lignes avec leur code INSEE de début et de fin
// Colonnes disponibles : LIG_NUM, COM_CODE_INSEE_DEBU, COM_CODE_INSEE_TERM
function ListeLignes($conn) {
    $cur = $conn->query("SELECT LIG_NUM, COM_CODE_INSEE_DEBU, COM_CODE_INSEE_TERM FROM sae.vik_ligne ORDER BY LIG_NUM");
    return $cur->fetchAll(PDO::FETCH_ASSOC);
}

// Retourne tous les arrêts par ligne
// Colonnes disponibles : LIG_NUM, COM_CODE_INSEE_ARRET
// Note : la BDD ne stocke pas de nom textuel pour les arrêts, seulement le code INSEE
function ListeCommunesLignes($conn) {
    $cur = $conn->query("SELECT LIG_NUM, COM_CODE_INSEE_ARRET FROM sae.vik_noeud ORDER BY LIG_NUM, COM_CODE_INSEE_ARRET");
    return $cur->fetchAll(PDO::FETCH_ASSOC);
}

// Insère une réservation en base
// À compléter selon la structure exacte de vik_reservation et vik_client
function reserverLigne($conn, $nom, $prenom, $email, $numLigne, $comDepart, $comArrivee) {
    // TODO : implémenter l'INSERT dans vik_reservation une fois la structure connue
    // Exemple de structure probable :
    // INSERT INTO sae.vik_reservation (cli_num, lig_num, com_code_insee_depart, com_code_insee_arrivee, res_date)
    // VALUES (:cli_num, :lig_num, :depart, :arrivee, SYSDATE)
    return false;
}

// Récupère toutes les réservations d'un client
function HistoriqueReservationsClient($conn, $cli_num) {
    $sql = "SELECT * FROM sae.vik_reservation JOIN sae.vik_client USING (cli_num) WHERE cli_num = :num";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['num' => $cli_num]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupère les infos d'un client par son numéro
function ListeInfosClient($conn, $cli_num) {
    $sql = "SELECT * FROM sae.vik_client WHERE cli_num = :num";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['num' => $cli_num]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

//////////////////////////////////////
// Ces fonctions nécessitent 2 villes SUR LA MÊME LIGNE
//////////////////////////////////////

function TrajetsEtHorairesMemeLigne($conn, $code_insee_depart, $code_insee_arrivee) {
    $sql = "SELECT LIG_NUM,
                   TO_CHAR(ETA_HEURE, 'DD/MM/YYYY HH24:MI:SS') AS heure_depart,
                   ETA_DISTANCE AS distance
            FROM sae.VIK_ETAPE
            WHERE COM_CODE_INSEE_DEPART = :depart
              AND COM_CODE_INSEE_ARRIVEE = :arrivee
            ORDER BY ETA_HEURE ASC";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['depart' => $code_insee_depart, 'arrivee' => $code_insee_arrivee]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function TrajetPlusCourtMemeLigne($conn, $code_insee_depart, $code_insee_arrivee) {
    $sql = "SELECT LIG_NUM,
                   ETA_DISTANCE,
                   TO_CHAR(ETA_HEURE, 'DD/MM/YYYY HH24:MI:SS') AS heure_voyage
            FROM sae.VIK_ETAPE
            WHERE COM_CODE_INSEE_DEPART = :depart
              AND COM_CODE_INSEE_ARRIVEE = :arrivee
            ORDER BY ETA_DISTANCE ASC
            FETCH FIRST 1 ROWS ONLY";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['depart' => $code_insee_depart, 'arrivee' => $code_insee_arrivee]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function TrajetPlusRapideMemeLigne($conn, $code_insee_depart, $code_insee_arrivee) {
    $sql = "SELECT LIG_NUM,
                   TO_CHAR(ETA_HEURE, 'DD/MM/YYYY HH24:MI:SS') AS heure_voyage,
                   ETA_DISTANCE
            FROM sae.VIK_ETAPE
            WHERE COM_CODE_INSEE_DEPART = :depart
              AND COM_CODE_INSEE_ARRIVEE = :arrivee
            ORDER BY ETA_HEURE ASC
            FETCH FIRST 1 ROWS ONLY";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['depart' => $code_insee_depart, 'arrivee' => $code_insee_arrivee]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- Fonctions en cours de développement ---

function ListeHorairesLigne($conn) {
    // TODO : requête à corriger, actuellement retourne vik_ligne au lieu des horaires
    $cur = $conn->query("SELECT * FROM sae.vik_ligne");
    return $cur->fetchAll(PDO::FETCH_ASSOC);
}

function userAllowed($conn, $adresseMailClient, $userPassword) {
    // TODO : implémenter la vraie vérification d'authentification
    $sql = "SELECT * FROM sae.vik_client WHERE cli_courriel = :email";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['email' => $adresseMailClient]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


function reserver_ligne($conn, $client_num, $res_num, $tar_num_tranche, $res_date, $res_nb_points, $res_prix_tot) {
    $sql = "INSERT INTO sae.vik_reservation 
                (cli_num, res_num, tar_num, res_date, res_nb_points, res_prix_tot) 
            VALUES 
                (:cli_num, :res_num, :tar_num, :res_date, 0, :res_prix_tot)";

    $stmt = preparerRequetePDO($conn, $sql);
    return $stmt->execute([
        'cli_num'       => $client_num,
        'res_num'       => $res_num,
        'tar_num'       => $tar_num_tranche,
        'res_date'      => $res_date, 
        'res_prix_tot'  => $res_prix_tot
    ]);
}


?>
