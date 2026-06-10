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

// Retourne toutes les lignes : LIG_NUM, COM_CODE_INSEE_DEBU, COM_CODE_INSEE_TERM
function ListeLignes($conn) {
    $cur = $conn->query("SELECT LIG_NUM, COM_CODE_INSEE_DEBU, COM_CODE_INSEE_TERM FROM sae.vik_ligne ORDER BY LIG_NUM");
    return $cur->fetchAll(PDO::FETCH_ASSOC);
}

// Retourne tous les arrêts par ligne : LIG_NUM, COM_CODE_INSEE_ARRET
function ListeCommunesLignes($conn) {
    $cur = $conn->query("SELECT LIG_NUM, COM_CODE_INSEE_ARRET FROM sae.vik_noeud ORDER BY LIG_NUM, COM_CODE_INSEE_ARRET");
    return $cur->fetchAll(PDO::FETCH_ASSOC);
}

// US6 — Récupère le tarif applicable pour un segment (ligne + départ + arrivée)
function GetTarifSegment($conn, $numLigne, $comDepart, $comArrivee) {
    try {
        $sqlDist = "SELECT ETA_DISTANCE FROM sae.vik_etape
                    WHERE LIG_NUM = :ligne
                      AND COM_CODE_INSEE_DEPART  = :depart
                      AND COM_CODE_INSEE_ARRIVEE = :arrivee
                    FETCH FIRST 1 ROWS ONLY";
        $stmt = preparerRequetePDO($conn, $sqlDist);
        $stmt->execute(['ligne' => $numLigne, 'depart' => $comDepart, 'arrivee' => $comArrivee]);
        $etape = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$etape) return false;
        $distance = $etape['ETA_DISTANCE'];

        $sqlTarif = "SELECT TAR_NUM_TRANCHE, TAR_PRIX AS PRIX
                     FROM sae.vik_tarif
                     WHERE TAR_KM_MIN <= :distance
                       AND TAR_KM_MAX >= :distance
                     FETCH FIRST 1 ROWS ONLY";
        $stmt = preparerRequetePDO($conn, $sqlTarif);
        $stmt->execute(['distance' => $distance]);
        $tarif = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tarif) return false;
        return $tarif;

    } catch (Exception $e) {
        return false;
    }
}

// US4 — Réserve un trajet pour un client non inscrit (nom/prénom/email uniquement)
// Utilise une transaction pour garantir l'intégrité : si l'insertion de la réservation
// échoue après la création du client, tout est annulé.
function reserverSansCompte($conn, $nom, $prenom, $email, $numLigne, $comDepart, $comArrivee, $tarNum = null, $prix = null) {
    try {
        $conn->beginTransaction();

        // Chercher si ce client existe déjà par email
        $sqlClient = "SELECT CLI_NUM FROM sae.vik_client WHERE CLI_COURRIEL = :email";
        $stmt = preparerRequetePDO($conn, $sqlClient);
        $stmt->execute(['email' => $email]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($client) {
            $cliNum = $client['CLI_NUM'];
        } else {
            // Créer le client — TYP_NUM = 1 (non inscrit), points = 0
            $sqlInsertClient = "INSERT INTO sae.vik_client
                                    (CLI_NUM, TYP_NUM, CLI_NOM, CLI_PRENOM, CLI_COURRIEL, CLI_NB_POINTS_EC, CLI_NB_POINTS_TOT)
                                VALUES
                                    (sae.seq_client.NEXTVAL, 1, :nom, :prenom, :email, 0, 0)";
            $stmt = preparerRequetePDO($conn, $sqlInsertClient);
            $stmt->execute(['nom' => strtoupper($nom), 'prenom' => $prenom, 'email' => $email]);

            $cur    = $conn->query("SELECT sae.seq_client.CURRVAL AS CLI_NUM FROM dual");
            $row    = $cur->fetch(PDO::FETCH_ASSOC);
            $cliNum = $row['CLI_NUM'];
        }

        // Insérer la réservation
        $sqlRes = "INSERT INTO sae.vik_reservation
                       (RES_NUM, CLI_NUM, TAR_NUM_TRANCHE, RES_DATE, RES_NB_POINTS, RES_PRIX_TOT)
                   VALUES
                       (sae.seq_reservation.NEXTVAL, :cli_num, :tar_num, SYSDATE, 0, :prix)";
        $stmt = preparerRequetePDO($conn, $sqlRes);
        $stmt->execute([
            'cli_num' => $cliNum,
            'tar_num' => $tarNum,
            'prix'    => $prix,
        ]);

        $conn->commit();
        return true;

    } catch (Exception $e) {
        $conn->rollBack();
        return false;
    }
}

// Alias conservé pour compatibilité avec l'ancien code
function reserverLigne($conn, $nom, $prenom, $email, $numLigne, $comDepart, $comArrivee) {
    return reserverSansCompte($conn, $nom, $prenom, $email, $numLigne, $comDepart, $comArrivee);
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
            WHERE COM_CODE_INSEE_DEPART  = :depart
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
            WHERE COM_CODE_INSEE_DEPART  = :depart
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
            WHERE COM_CODE_INSEE_DEPART  = :depart
              AND COM_CODE_INSEE_ARRIVEE = :arrivee
            ORDER BY ETA_HEURE ASC
            FETCH FIRST 1 ROWS ONLY";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['depart' => $code_insee_depart, 'arrivee' => $code_insee_arrivee]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- Fonctions en cours de développement ---

// TODO : implémenter la vraie requête sur les horaires
function ListeHorairesLigne($conn, $numLigne) {
    $sql = "SELECT LIG_NUM,
                   COM_CODE_INSEE_DEPART,
                   COM_CODE_INSEE_ARRIVEE,
                   TO_CHAR(ETA_HEURE, 'DD/MM/YYYY HH24:MI:SS') AS heure_depart,
                   ETA_DISTANCE
            FROM sae.vik_etape
            WHERE LIG_NUM = :ligne
            ORDER BY ETA_HEURE ASC";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['ligne' => $numLigne]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// TODO : ajouter la vérification du mot de passe (hash) quand la colonne sera disponible
function userAllowed($conn, $adresseMailClient, $userPassword) {
    $sql = "SELECT CLI_NUM, CLI_NOM, CLI_PRENOM, CLI_COURRIEL, CLI_MOT_DE_PASSE
            FROM sae.vik_client
            WHERE CLI_COURRIEL = :email";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['email' => $adresseMailClient]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) return false;

    // Vérification du mot de passe hashé
    if (!password_verify($userPassword, $client['CLI_MOT_DE_PASSE'])) return false;

    // Ne pas retourner le hash dans la suite de l'application
    unset($client['CLI_MOT_DE_PASSE']);
    return $client;
}

?>