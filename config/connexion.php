<?php
// Connexion PDO MySQL
try {
    $bdd = new PDO(
        'mysql:host=localhost;dbname=bloodbridge;charset=utf8',
        'root',
        ''
    );
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Exception $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}
?>