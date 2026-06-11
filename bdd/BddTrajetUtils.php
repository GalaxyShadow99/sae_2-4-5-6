<?php

require_once __DIR__ . '/BddConnexionUtils.php';

function TrajetsEtHorairesMemeLigne($conn, $code_insee_depart, $code_insee_arrivee) {
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
            FETCH FIRST 1 ROWS ONLY";

    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['depart' => $code_insee_depart, 'arrivee' => $code_insee_arrivee]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function ListeToutesCommunes($conn) {
  $sql = "SELECT COM_CODE_INSEE, COM_NOM FROM vik_commune ORDER BY COM_NOM ASC";
  $stmt = preparerRequetePDO($conn, $sql);
  $stmt->execute(['depart' ]);
  return $stmt->fetch(PDO::FETCH_ASSOC);
}

/*=== POUR ETRE SUR ===*/

function ListeNoeuds($conn) {
  $cur = $conn->query("select com_code_insee_arret, 
                      com_code_insee_suivant, 
                      lig_num, 
                      noe_heure_passage,
                      noe_distance_prochain, 
                      noe_duree_prochain from vik_noeud");
  return $cur->fetchAll(PDO::FETCH_ASSOC);
}

function RecupHoraireSup($conn, $ligne, $commune, $heure) {
  $sql = "SELECT TO_CHAR(noe_heure_passage, 'HH24:MI') AS heure_passage
          FROM vik_noeud
          WHERE lig_num = :ligne
            AND com_code_insee_arret = :commune
            AND noe_heure_passage > TO_DATE(:heure, 'HH24:MI')";
  $stmt = preparerRequetePDO($conn, $sql);
  $stmt->execute(['ligne' => $ligne, 'commune' => $commune, 'heure' => $heure]);
  return $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
