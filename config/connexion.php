<?php 
try {

    $bdd = new PDO('mysql:host=sql108.byethost6.com;dbname=b6_41848940_bloodbridge;charset=utf8',
        'b6_41848940',
        'Khoukha123');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Si on arrive ici, c'est que ça marche !
} catch(Exception $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}
?>