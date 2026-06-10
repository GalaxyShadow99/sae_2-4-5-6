<?php

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
