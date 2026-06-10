<?php

function RecupereVille($conn,$code_insee){
    $sql = "SELECT com_nom FROM vik_commune WHERE com_code_insee = :num";
    $stmt = preparerRequetePDO($conn, $sql);
    $stmt->execute(['num' => $code_insee]);
    return $stmt->fetchColumn();
}

?>