<?php 
try {
    $host = 'sql108.byethost6.com';
    $db   = 'b6_41848940_bloodbridge';
    $user = 'b6_41848940';
    $pass = 'Khoukha123'; // <--- Vérifie bien celui-ci

    $bdd = new PDO("mysql:host=$host;port=3306;dbname=$db;charset=utf8", $user, $pass);
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Si on arrive ici, c'est que ça marche !
} catch(Exception $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}
?>