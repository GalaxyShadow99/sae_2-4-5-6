<?php
require_once __DIR__ . '/BddConnexionUtils.php';

function ListeNoeudsLigne($conn, $lig_num) {
    $sql = "SELECT n.COM_CODE_INSEE_ARRET, c.COM_NOM,
                   n.COM_CODE_INSEE_SUIVANT,
                   TO_CHAR(n.NOE_HEURE_PASSAGE, 'HH24:MI') AS NOE_HEURE_PASSAGE
            FROM vik_noeud n
            JOIN vik_commune c ON c.COM_CODE_INSEE = n.COM_CODE_INSEE_ARRET
            WHERE TRIM(n.LIG_NUM) = :lig_num1
            UNION ALL
            SELECT DISTINCT n.COM_CODE_INSEE_SUIVANT, c.COM_NOM, NULL, NULL
            FROM vik_noeud n
            JOIN vik_commune c ON c.COM_CODE_INSEE = n.COM_CODE_INSEE_SUIVANT
            WHERE TRIM(n.LIG_NUM) = :lig_num2
            AND n.COM_CODE_INSEE_SUIVANT NOT IN (
                SELECT COM_CODE_INSEE_ARRET FROM vik_noeud WHERE TRIM(lig_num) = :lig_num3
            )
            ORDER BY NOE_HEURE_PASSAGE NULLS LAST";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['lig_num1' => $lig_num, 'lig_num2' => $lig_num, 'lig_num3' => $lig_num]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ModifierHoraire($conn, $lig_num, $com_arret, $ancienne, $nouvelle) {
    try {
        $sql = "UPDATE vik_noeud
                SET noe_heure_passage = TRUNC(noe_heure_passage)
                    + (TO_NUMBER(SUBSTR(:nouvelle, 1, 2)) * 60 + TO_NUMBER(SUBSTR(:nouvelle, 4, 2))) / 1440
                WHERE TRIM(lig_num) = :lig_num
                AND com_code_insee_arret = :arret
                AND TO_CHAR(noe_heure_passage, 'HH24:MI') = :ancienne";
        $stmt = preparerRequetePDO($conn, $sql);
        return $stmt->execute([
            'nouvelle' => $nouvelle,
            'lig_num'  => $lig_num,
            'arret'    => $com_arret,
            'ancienne' => $ancienne
        ]);
    } catch (PDOException $e) { return false; }
}

function AjouterHoraire($conn, $lig_num, $com_arret, $com_suivant, $heure) {
    try {
        $sql = "INSERT INTO vik_noeud (lig_num, com_code_insee_arret, com_code_insee_suivant, noe_heure_passage)
                SELECT :lig_num, :arret, :suivant,
                       COALESCE(TRUNC(MIN(noe_heure_passage)), TO_DATE('01/01/1970', 'DD/MM/YYYY'))
                       + (TO_NUMBER(SUBSTR(:heure, 1, 2)) * 60 + TO_NUMBER(SUBSTR(:heure, 4, 2))) / 1440
                FROM vik_noeud
                WHERE TRIM(lig_num) = :lig_num2";
        $stmt = preparerRequetePDO($conn, $sql);
        return $stmt->execute([
            'lig_num'  => $lig_num,
            'lig_num2' => $lig_num,
            'arret'    => $com_arret,
            'suivant'  => $com_suivant ?: null,
            'heure'    => $heure
        ]);
    } catch (PDOException $e) { return false; }
}

function SupprimerHoraire($conn, $lig_num, $com_arret, $heure) {
    try {
        $sql = "DELETE FROM vik_noeud
                WHERE TRIM(lig_num) = :lig_num
                AND com_code_insee_arret = :arret
                AND TO_CHAR(noe_heure_passage, 'HH24:MI') = :heure";
        $stmt = preparerRequetePDO($conn, $sql);
        return $stmt->execute(['lig_num' => $lig_num, 'arret' => $com_arret, 'heure' => $heure]);
    } catch (PDOException $e) { return false; }
}

function SupprimerArret($conn, $lig_num, $com_arret) {
    try {
        $sql = "DELETE FROM vik_noeud
                WHERE TRIM(lig_num) = :lig_num
                AND com_code_insee_arret = :arret";
        $stmt = preparerRequetePDO($conn, $sql);
        return $stmt->execute(['lig_num' => $lig_num, 'arret' => $com_arret]);
    } catch (PDOException $e) { return false; }
}

function ModifierLigne($conn, $lig_num, $com_debu, $com_term) {
    try {
        $sql = "UPDATE vik_ligne
                SET com_code_insee_debu = :debu, com_code_insee_term = :term
                WHERE TRIM(lig_num) = :num";
        $stmt = preparerRequetePDO($conn, $sql);
        return $stmt->execute(['debu' => $com_debu, 'term' => $com_term, 'num' => $lig_num]);
    } catch (PDOException $e) { return false; }
}

function ListeCommunes($conn) {
    $sql = "SELECT COM_CODE_INSEE, COM_NOM FROM vik_commune ORDER BY COM_NOM";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}