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

// template du TD
function LireDonneesPDO3($conn, $sql, &$tab) {
    $cur = $conn->query($sql);
    $tab = $cur->fetchAll(PDO::FETCH_ASSOC);
    return count($tab);
}

// la liste de toute les lignes de transports
function ListeLignes($conn){
    $cur = $conn->query("select * from vik_ligne");
    $tab = $cur->fetchAll(PDO::FETCH_ASSOC);
    return $tab;
}

function RecupereVille($conn,$code_insee){
    $sql = "SELECT * FROM vik_commune WHERE com_code_insee = :num";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['num' => $code_insee]);
    return $stmt->fetchColumn();
}

// récupère toutes les réservations associées à un cli_num
function HistoriqueReservationsClient($conn, $cli_num) {
 
    $sql = "SELECT * FROM vik_reservation JOIN vik_client USING (cli_num) WHERE cli_num = :num";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['num' => $cli_num]);
    $tab = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $tab;
}

// récupère toutes les info d'un client à l'aide d'un cli_num
function ListeInfosClient($conn, $cli_num) {
    $sql = "SELECT * FROM vik_client WHERE cli_num = :num";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['num' => $cli_num]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $client;
}

//////////////////////////////////////
// Fonction qui marchent avec 2 villes SUR LA MEME LIGNE, sans ça renverra tableau vide...
//////////////////////////////////////
function TrajetsEtHorairesMemeLigne($conn, $code_insee_depart, $code_insee_arrivee) {
    // On sélectionne la ligne et heure de étape
    $sql = "SELECT LIG_NUM, 
                   TO_CHAR(ETA_HEURE, 'DD/MM/YYYY HH24:MI:SS') AS heure_depart,
                   ETA_DISTANCE AS distance
            FROM VIK_ETAPE
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
            FROM VIK_ETAPE
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
            FROM VIK_ETAPE
            WHERE COM_CODE_INSEE_DEPART = :depart 
              AND COM_CODE_INSEE_ARRIVEE = :arrivee
            ORDER BY ETA_HEURE ASC
            FETCH FIRST 1 ROWS ONLY"; // Remplace par LIMIT 1 si vous êtes sur MySQL/PostgreSQL
            
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['depart' => $code_insee_depart, 'arrivee' => $code_insee_arrivee]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

//
// fonctions en cours de dev, ne pas encore utiliser
//
function ListeHorairesLigne($conn, $lig_num){
    $cur = $conn->query("
                        SELECT COM_CODE_INSEE_DEPART, COM_CODE_INSEE_ARRIVEE, 
                        ETA_HEURE, ETA_DISTANCE 
                         FROM VIK_ETAPE 
                         WHERE LIG_NUM = '$lig_num' 
                         ORDER BY ETA_HEURE ASC
    ");
    $tab = $cur->fetchAll(PDO::FETCH_ASSOC);
    return $tab;
}

function userAllowed($conn,$adresseMailClient , $userPassword){

    $cur = $conn->query("select * from vik_ligne");
    $tab = $cur->fetchAll(PDO::FETCH_ASSOC);
    
    return $tab;
}

?>