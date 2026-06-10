<?php

require_once __DIR__ . '/BddConnexionUtils.php';

function Today($conn) {
    return $conn->query("select sysdate from dual");
}

function DernierClient($conn) {
    return $conn->query("select cli_num from vik_client
                        where cli_num >= all(
                            select cli_num from vik_client)");
}

function DepPourVille($conn, $code_insee){
    $sql = "select dep_nom from vik_departement
            join vik_commune using(dep_num)
            where com_code_insee = :code";
    $stmt = preparerRequetePDO($conn, $sql);
    $tab = $stmt->execute(['code' => $code_insee]);

    return $tab;
}

function AjouteClient($conn, $cli_nom, $cli_prenom, $cli_ville, $cli_tel, $cli_mail, $cli_mdp) {
    $sql = "insert into vik_client
            values(:num, 1, :dep, :nom, :prenom, :ville, :tel, :mail, :mdp, 0, 0, :date)";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['num' => DernierClient($conn),
                    'dep' => DepPourVille($conn, $cli_ville),
                    'nom' => $cli_nom,
                    'prenom' => $cli_prenom,
                    'ville' => $cli_ville,
                    'tel' => $cli_tel,
                    'mail' => $cli_mail,
                    'mdp' => $cli_mdp,
                    'date' => Today($conn)]);
}

?>