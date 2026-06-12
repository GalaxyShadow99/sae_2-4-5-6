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
    $sqlDepart = "SELECT COM_CODE_INSEE_DEBU FROM vik_ligne WHERE TRIM(LIG_NUM) = :lig";
    $stmtDepart = preparerRequetePDO($conn, $sqlDepart);
    $stmtDepart->execute(['lig' => trim($lig_num)]);
    $arretCourant = $stmtDepart->fetchColumn();

    if (!$arretCourant) return [];

    $sqlNoeuds = "SELECT n.COM_CODE_INSEE_ARRET, n.COM_CODE_INSEE_SUIVANT, c.COM_NOM 
                  FROM vik_noeud n
                  JOIN vik_commune c ON n.COM_CODE_INSEE_ARRET = c.COM_CODE_INSEE
                  WHERE TRIM(n.LIG_NUM) = :lig";
    $stmtNoeuds = preparerRequetePDO($conn, $sqlNoeuds);
    $stmtNoeuds->execute(['lig' => trim($lig_num)]);
    $noeuds = $stmtNoeuds->fetchAll(PDO::FETCH_ASSOC);

    $dicoNoeuds = [];
    foreach ($noeuds as $noeud) {
        $dicoNoeuds[$noeud['COM_CODE_INSEE_ARRET']] = $noeud;
    }

    $villes_ordonnees = [];
    $visites = [];

    while ($arretCourant && !isset($visites[$arretCourant])) {
        $visites[$arretCourant] = true;

        if (isset($dicoNoeuds[$arretCourant])) {
            $etape = $dicoNoeuds[$arretCourant];
            $villes_ordonnees[] = [
                'COM_CODE_INSEE' => $arretCourant,
                'COM_NOM' => $etape['COM_NOM']
            ];
            $arretCourant = $etape['COM_CODE_INSEE_SUIVANT'];
        } else {

            $sqlTerminus = "SELECT COM_NOM FROM vik_commune WHERE COM_CODE_INSEE = :code";
            $stmtTerm = preparerRequetePDO($conn, $sqlTerminus);
            $stmtTerm->execute(['code' => $arretCourant]);
            $nomTerm = $stmtTerm->fetchColumn();

            $villes_ordonnees[] = [
                'COM_CODE_INSEE' => $arretCourant,
                'COM_NOM' => $nomTerm ?: "Terminus"
            ];
            break;
        }
    }

    return $villes_ordonnees;
}
 
function RecupereVille($conn,$code_insee){
    $sql = "SELECT com_nom FROM vik_commune WHERE com_code_insee = :num";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['num' => $code_insee]);
    return $stmt->fetchColumn();
}

function ObtenirTrajetComplet($conn, $lig_num, $code_insee_depart, $heure_depart) {
    // 1. On force Oracle à renvoyer l'heure au format HH:MM avec TO_CHAR
    $sql = "SELECT TRIM(COM_CODE_INSEE_ARRET) AS COM_CODE_INSEE_ARRET, 
                   TRIM(COM_CODE_INSEE_SUIVANT) AS COM_CODE_INSEE_SUIVANT, 
                   TO_CHAR(NOE_HEURE_PASSAGE, 'HH24:MI') AS HEURE_FORMATTEE, 
                   NOE_DUREE_PROCHAIN 
            FROM vik_noeud 
            WHERE TRIM(LIG_NUM) = :lig";
            
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['lig' => trim($lig_num)]);
    $noeuds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. On organise les nœuds
    $mapTrajets = [];
    foreach ($noeuds as $n) {
        $arret = $n['COM_CODE_INSEE_ARRET'];
        // L'heure est déjà parfaite grâce à Oracle, plus besoin de strtotime() !
        $hKey = $n['HEURE_FORMATTEE']; 
        $mapTrajets[$arret][$hKey] = $n;
    }

    $trajet = [];
    $arretActuel = trim($code_insee_depart);
    $heureActuelle = trim($heure_depart);

    // Sécurité pour éviter que le PHP tourne en boucle si les données BDD sont mal formatées
    $max_iterations = 50; 
    $i = 0;

    // 3. On remonte le fil des arrêts
    while (isset($mapTrajets[$arretActuel][$heureActuelle]) && $i < $max_iterations) {
        $noeud = $mapTrajets[$arretActuel][$heureActuelle];
        $i++;
        
        $sqlNom = "SELECT COM_NOM FROM vik_commune WHERE TRIM(COM_CODE_INSEE) = :c";
        $stmtNom = preparerRequetePDO($conn, $sqlNom);
        $stmtNom->execute(['c' => $arretActuel]);
        $nomCommune = $stmtNom->fetchColumn();

        $trajet[] = [
            'COM_CODE_INSEE' => $arretActuel,
            'COM_NOM' => $nomCommune ?: "Arrêt inconnu",
            'HEURE' => $heureActuelle
        ];

        // Calcul de l'heure du prochain arrêt
        $minutesAajouter = intval($noeud['NOE_DUREE_PROCHAIN']);
        $timestampProchain = strtotime("+".$minutesAajouter." minutes", strtotime("2026-01-01 ".$heureActuelle));
        
        // On avance à l'arrêt suivant
        $arretActuel = $noeud['COM_CODE_INSEE_SUIVANT'];
        $heureActuelle = date('H:i', $timestampProchain);
    }

    // 4. Ajouter le tout dernier arrêt (le terminus)
    if (!empty($trajet)) {
        $sqlNom = "SELECT COM_NOM FROM vik_commune WHERE TRIM(COM_CODE_INSEE) = :c";
        $stmtNom = preparerRequetePDO($conn, $sqlNom);
        $stmtNom->execute(['c' => $arretActuel]);
        $nomTerminus = $stmtNom->fetchColumn();

        $trajet[] = [
            'COM_CODE_INSEE' => $arretActuel,
            'COM_NOM' => $nomTerminus ?: "Terminus",
            'HEURE' => $heureActuelle
        ];
    }

    return $trajet;
}