<?php
require_once __DIR__ . '/BddConnexionUtils.php';

function MeilleursClients($conn) {
    $sql = "SELECT c.cli_nom, c.cli_prenom, c.cli_nb_points_tot, COUNT(r.res_num) as nb_reservations
            FROM vik_client c
            JOIN vik_reservation r USING(cli_num)
            GROUP BY c.cli_num, c.cli_nom, c.cli_prenom, c.cli_nb_points_tot
            ORDER BY nb_reservations DESC
            FETCH FIRST 10 ROWS ONLY";
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
            ORDER BY MIN(res_date) ASC";
    LireDonneesPDO3($conn, $sql, $tab);
    return $tab;
}