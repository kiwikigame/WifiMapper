<?php

$db_host = "";
$db_user = "";
$db_password = "";
$db_name = "";

$connexion = mysqli_connect($db_host, $db_user, $db_password, $db_name);

if (!$connexion) {
    die("La connexion à la base de données a échoué : " . mysqli_connect_error());
}

function escapeString($value) {
    global $connexion;
    return mysqli_real_escape_string($connexion, $value);
}

$requeteSecurite = "SELECT DISTINCT Securite FROM wifitrg";
$resultatSecurite = mysqli_query($connexion, $requeteSecurite);

if (!$resultatSecurite) {
    die("La requête a échoué : " . mysqli_error($connexion));
}

?>
