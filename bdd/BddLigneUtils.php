<?php

require_once __DIR__ . '/BddConnexionUtils.php';

function VillesParLigne($conn, $num_ligne) {
    $sql = "SELECT DISTINCT c.com_code_insee, c.com_nom 
            FROM vik_commune c
            JOIN vik_noeud n ON c.com_code_insee = n.com_code_insee_arret
            WHERE TRIM(n.lig_num) = :ligne
            ORDER BY c.com_nom ASC";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['ligne' => $num_ligne]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ListeLignes($conn){
    $cur = $conn->query("SELECT l.LIG_NUM,
                                l.COM_CODE_INSEE_DEBU,
                                l.COM_CODE_INSEE_TERM,
                                d.COM_NOM AS COM_NOM_DEBU,
                                t.COM_NOM AS COM_NOM_TERM
                         FROM vik_ligne l
                         LEFT JOIN vik_commune d ON d.COM_CODE_INSEE = l.COM_CODE_INSEE_DEBU
                         LEFT JOIN vik_commune t ON t.COM_CODE_INSEE = l.COM_CODE_INSEE_TERM");
    
    $lignes = $cur->fetchAll(PDO::FETCH_ASSOC);

    usort($lignes, function($a, $b) {
        return strnatcasecmp($a['LIG_NUM'], $b['LIG_NUM']);
    });

    return $lignes;
}

function ListeHorairesLigne($conn, $lig_num){
    $cur = $conn->query("
                        SELECT COM_CODE_INSEE_ARRET, TO_CHAR(NOE_HEURE_PASSAGE, 'HH24:MI') AS NOE_HEURE_PASSAGE 
                         FROM VIK_NOEUD 
                         WHERE TRIM(LIG_NUM) = '$lig_num'
                         order by NOE_HEURE_PASSAGE
    ");
    return $cur->fetchAll(PDO::FETCH_ASSOC);
}

function ProchainArret($conn,$lig_num){
    $sql ="select noe1.com_code_insee_arret, noe1.noe_heure_passage from vik_noeud noe1
            join vik_noeud noe2 using(lig_num)
            where noe1.com_code_insee_suivant = noe2.com_code_insee_arret and lig_num = :X;";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['X' => $lig_num]);
    return $stmt->fetchColumn();
}

function ObtenirVillesOrdonnees($conn, $lig_num) {
    $sql = "SELECT c.COM_CODE_INSEE, c.COM_NOM
            FROM vik_commune c
            JOIN (
                SELECT COM_CODE_INSEE_ARRET as code, MIN(NOE_HEURE_PASSAGE) as PREMIERE_HEURE
                FROM vik_noeud
                WHERE TRIM(LIG_NUM) = :lig_num1
                GROUP BY COM_CODE_INSEE_ARRET
                UNION
                SELECT COM_CODE_INSEE_SUIVANT, MAX(NOE_HEURE_PASSAGE)
                FROM vik_noeud
                WHERE TRIM(LIG_NUM) = :lig_num2
                AND COM_CODE_INSEE_SUIVANT NOT IN (
                    SELECT COM_CODE_INSEE_ARRET FROM vik_noeud WHERE TRIM(LIG_NUM) = :lig_num3
                )
                GROUP BY COM_CODE_INSEE_SUIVANT
            ) n ON c.COM_CODE_INSEE = n.code
            ORDER BY n.PREMIERE_HEURE ASC";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute([
        'lig_num1' => $lig_num,
        'lig_num2' => $lig_num,
        'lig_num3' => $lig_num
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
 
function RecupereVille($conn,$code_insee){
    $sql = "SELECT com_nom FROM vik_commune WHERE com_code_insee = :num";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['num' => $code_insee]);
    return $stmt->fetchColumn();
}


