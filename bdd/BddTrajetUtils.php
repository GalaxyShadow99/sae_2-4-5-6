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
