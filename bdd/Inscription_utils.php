<?php

function Today($conn) {
    $stmt = $conn->query("select sysdate from dual");
    return $stmt->fetchColumn();
}

function DernierClient($conn) {
    $stmt = $conn->query("select cli_num from vik_client
                        where cli_num >= all(
                            select cli_num from vik_client)");

    return $stmt->fetchColumn();
}

function DepPourVille($conn, $code_insee){
    $sql = "select dep_num from vik_departement
            join vik_commune using(dep_num)
            where com_code_insee = :code";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['code' => $code_insee]);

    return $stmt->fetchColumn();
}

function AjouteClient($conn, $cli_nom, $cli_prenom, $cli_ville, $cli_tel, $cli_mail, $cli_mdp) {
    try {
        $sql = "insert into vik_client
            values(:num, 1, :dep, :nom, :prenom, :ville, :tel, :mail, :mdp, 0, 0, SYSDATE)";
        $stmt = preparerRequetePDO($conn, $sql);
        $stmt->execute([
            'num' => DernierClient($conn) + 1,
            'dep' => DepPourVille($conn, $cli_ville),
            'nom' => $cli_nom,
            'prenom' => $cli_prenom,
            'ville' => $cli_ville,
            'tel' => $cli_tel,
            'mail' => $cli_mail,
            'mdp' => $cli_mdp,
        ]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

?>