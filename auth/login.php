<?php
include '../config/connexion.php';
include '../views/header.php';

$erreur = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validation des champs
    $email = trim($_POST['email']);
    $mdp   = trim($_POST['mot_de_passe']);

    // Conditions logiques
    if(empty($email) || empty($mdp)) {
        $erreur = "Tous les champs sont obligatoires !";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Format d'email invalide !";
    } elseif(strlen($mdp) < 5) {
        $erreur = "Le mot de passe doit contenir au moins 5 caractères !";
    } else {
        $mdp_md5 = md5($mdp);
        $req = $bdd->prepare("
            SELECT * FROM utilisateur
            WHERE email = ? AND mot_de_passe = ?
        ");
        $req->execute([$email, $mdp_md5]);
        $user = $req->fetch(PDO::FETCH_ASSOC);

        if($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nom']     = $user['nom'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['email']   = $user['email'];

            if($user['role'] == 'DONNEUR')
                header('Location: ../donneur/dashboard.php');
            elseif($user['role'] == 'HOPITAL')
                header('Location: ../hopital/dashboard.php');
            elseif($user['role'] == 'ADMIN')
                header('Location: ../admin/dashboard.php');
            exit();
        } else {
            $erreur = "Email ou mot de passe incorrect !";
        }
    }
}
?>

<div class="row justify-content-center mt-3">
    <div class="col-md-5">
        <div class="card border-0 shadow">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-danger d-inline-flex
                                align-items-center justify-content-center mb-3"
                         style="width:70px;height:70px;">
                        <i class="bi bi-droplet-fill text-white"
                           style="font-size:2rem;"></i>
                    </div>
                    <h4 class="fw-bold">Connexion</h4>
                    <p class="text-muted small">Accédez à votre espace BloodBridge</p>
                </div>

                <?php if($erreur != ''): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?= $erreur ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-envelope"></i> Email
                        </label>
                        <input type="email" name="email"
                               class="form-control form-control-lg"
                               placeholder="votre@email.com"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                               required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="bi bi-lock"></i> Mot de passe
                        </label>
                        <input type="password" name="mot_de_passe"
                               class="form-control form-control-lg"
                               placeholder="••••••••"
                               required>
                    </div>
                    <button type="submit"
                            class="btn btn-danger w-100 btn-lg fw-bold">
                        <i class="bi bi-box-arrow-in-right"></i> Se connecter
                    </button>
                </form>

                <hr class="my-4">

                <div class="bg-light rounded p-3">
                    <p class="fw-bold text-muted small mb-2">
                        <i class="bi bi-info-circle"></i> Comptes de test :
                    </p>
                    <small class="d-block mb-1">
                        👤 <b>Admin :</b> admin@bloodbridge.tn / admin123
                    </small>
                    <small class="d-block mb-1">
                        🩸 <b>Donneur :</b> khouloud@email.com / mdp123
                    </small>
                    <small class="d-block">
                        🏥 <b>Hôpital :</b> charles@hopital.tn / mdp789
                    </small>
                </div>

                <p class="text-center mt-3 mb-0 small">
                    Pas encore inscrit ?
                    <a href="register.php" class="text-danger fw-bold">
                        Créer un compte
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include '../views/footer.php'; ?>