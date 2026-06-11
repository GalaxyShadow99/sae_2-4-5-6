<?php

require_once __DIR__ . '/BddConnexionUtils.php';

function HistoriqueReservationsClient($conn, $cli_num) {
    $sql = "SELECT * FROM vik_reservation JOIN vik_client USING (cli_num) WHERE cli_num = :num order by res_num DESC";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['num' => $cli_num]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ListeInfosClient($conn, $cli_num) {
    $sql = "SELECT * FROM vik_client WHERE cli_num = :num";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['num' => $cli_num]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function userAllowed($conn, $adresseMailClient, $userPassword){
    $sql = "SELECT * FROM vik_client WHERE CLI_COURRIEL = :email";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['email' => $adresseMailClient]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) return false;

    if ($client['CLI_MDP'] === $userPassword){
        return $client;
    }
    return false;
}
