<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Test avec l'IP directe pour éviter le problème de "No such file or directory"
    $bdd = new PDO("mysql:host=185.27.134.10;port=3306;dbname=b6_41848940_bloodbridge;charset=utf8", "b6_41848940", "Khoukha123");
    echo "✅ BRAVO : La connexion fonctionne enfin !";
} catch (Exception $e) {
    echo "❌ L'erreur est : " . $e->getMessage();
}
?>