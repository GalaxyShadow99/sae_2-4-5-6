<?php
require_once __DIR__ . '/BddConnexionUtils.php';

function MeilleursClients($conn) {
    // CORRECTION: supprimé FETCH FIRST 10 ROWS ONLY
    $sql = "SELECT c.cli_nom, c.cli_prenom, c.cli_nb_points_tot, COUNT(r.res_num) as nb_reservations
            FROM vik_client c
            JOIN vik_reservation r ON c.cli_num = r.cli_num
            GROUP BY c.cli_num, c.cli_nom, c.cli_prenom, c.cli_nb_points_tot
            ORDER BY nb_reservations DESC";
    LireDonneesPDO3($conn, $sql, $tab);
    return $tab;
}

function LignesPlusUtilisees($conn) {
    $sql = "SELECT lig_num, COUNT(*) as nb_utilisations
            FROM vik_etape
            GROUP BY lig_num
            ORDER BY nb_utilisations DESC";
    LireDonneesPDO3($conn, $sql, $tab);
    return $tab;
}

function ReservationsParPeriode($conn) {
    $sql = "SELECT TO_CHAR(res_date, 'MM/YYYY') as periode, COUNT(*) as nb_reservations
            FROM vik_reservation
            GROUP BY TO_CHAR(res_date, 'MM/YYYY')
            ORDER BY MIN(res_date) DESC";
    LireDonneesPDO3($conn, $sql, $tab);
    return $tab;
}

function ChiffreAffairesTotal($conn) {
    $sql = "SELECT SUM(res_prix_tot) as total FROM vik_reservation";
    LireDonneesPDO3($conn, $sql, $tab);
    return $tab[0]['TOTAL'];
}

function ChiffreAffairesParLigne($conn) {
    // CORRECTION: sous-requête DISTINCT pour éviter double comptage
    $sql = "SELECT e.lig_num, SUM(r.res_prix_tot) as ca_total
            FROM vik_reservation r
            JOIN (SELECT DISTINCT cli_num, res_num, lig_num FROM vik_etape) e
              ON r.cli_num = e.cli_num AND r.res_num = e.res_num
            GROUP BY e.lig_num
            ORDER BY ca_total DESC";
    LireDonneesPDO3($conn, $sql, $tab);
    return $tab;
}

function ClientsPlusDePoints($conn) {
    // CORRECTION: supprimé FETCH FIRST 10 ROWS ONLY
    $sql = "SELECT cli_nom, cli_prenom, cli_nb_points_ec, cli_nb_points_tot
            FROM vik_client
            ORDER BY cli_nb_points_tot DESC";
    LireDonneesPDO3($conn, $sql, $tab);
    return $tab;
}

function HeureDePointe($conn) {
    $sql = "SELECT TO_CHAR(noe_heure_passage, 'HH24') as heure, COUNT(*) as nb
            FROM vik_noeud
            GROUP BY TO_CHAR(noe_heure_passage, 'HH24')
            ORDER BY nb DESC
            FETCH FIRST 5 ROWS ONLY";
    LireDonneesPDO3($conn, $sql, $tab);
    return $tab;
}

function TrajetsPopulaires($conn) {
    // CORRECTION: supprimé FETCH FIRST 10 ROWS ONLY
    $sql = "SELECT c1.com_nom as depart, c2.com_nom as arrivee, COUNT(*) as nb
            FROM vik_etape e
            JOIN vik_commune c1 ON c1.com_code_insee = e.com_code_insee_depart
            JOIN vik_commune c2 ON c2.com_code_insee = e.com_code_insee_arrivee
            GROUP BY c1.com_nom, c2.com_nom
            ORDER BY nb DESC";
    LireDonneesPDO3($conn, $sql, $tab);
    return $tab;
}