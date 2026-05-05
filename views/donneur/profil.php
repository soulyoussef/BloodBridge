
<?php
include '../../config/connexion.php';
include '../../views/header.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'DONNEUR') {
    header('Location: ../auth/login.php');
    exit();
}

$id = $_SESSION['user_id'];
$message = '';

$villes = [
    'Tunis','Sfax','Sousse','Bizerte','Gabès',
    'Kairouan','Monastir','Nabeul','Gafsa','Médenine',
    'Kasserine','Sidi Bouzid','Jendouba','Béja','Mahdia',
    'Le Kef','Siliana','Zaghouan','Tataouine','Tozeur','Kébili'
];
$regions = [
    'Grand Tunis','Nord-Est','Nord-Ouest','Centre-Est',
    'Centre-Ouest','Sud-Est','Sud-Ouest','Sahel','Cap Bon'
];

// Mise à jour profil
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $telephone  = trim($_POST['telephone']);
    $ville      = $_POST['ville'];
    $region     = $_POST['region'];
    $disponible = isset($_POST['disponible']) ? 1 : 0;

    if(!preg_match('/^[0-9]{8}$/', $telephone)) {
        $message = "error:Le numéro doit contenir 8 chiffres !";
    } else {
        $bdd->prepare("
            UPDATE utilisateur SET telephone = ? WHERE id = ?
        ")->execute([$telephone, $id]);

        $bdd->prepare("
            UPDATE donneur
            SET ville = ?, region = ?, disponible = ?
            WHERE id = ?
        ")->execute([$ville, $region, $disponible, $id]);

        $message = "success:Profil mis à jour avec succès !";
    }
}

// Récupère les données
$req = $bdd->prepare("
    SELECT u.nom, u.email, u.telephone,
           d.groupe_sanguin, d.ville, d.region,
           d.disponible, d.dernier_don,
           d.nb_dons_totaux, d.score
    FROM utilisateur u
    JOIN donneur d ON u.id = d.id
    WHERE u.id = ?
");
$req->execute([$id]);
$donneur = $req->fetch(PDO::FETCH_ASSOC);
?>

<div class="row justify-content-center">
    <div class="col-md-7">

        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <h4 class="fw-bold mb-0">
                <i class="bi bi-person-circle text-danger"></i>
                Mon Profil
            </h4>
        </div>

        <!-- Carte infos fixes -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted">Nom</small>
                        <p class="fw-bold mb-0">
                            <?= htmlspecialchars($donneur['nom']) ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Email</small>
                        <p class="fw-bold mb-0">
                            <?= htmlspecialchars($donneur['email']) ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Groupe sanguin</small>
                        <p class="mb-0">
                            <span class="badge bg-danger fs-6">
                                <?= $donneur['groupe_sanguin'] ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Dons effectués</small>
                        <p class="fw-bold text-danger mb-0">
                            <?= $donneur['nb_dons_totaux'] ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Dernier don</small>
                        <p class="fw-bold mb-0">
                            <?= $donneur['dernier_don']
                                ? date('d/m/Y', strtotime($donneur['dernier_don']))
                                : 'Jamais' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulaire modification -->
        <?php if($message != ''): ?>
        <?php $parts = explode(':', $message, 2); ?>
        <div class="alert alert-<?= $parts[0]=='success' ? 'success' : 'danger' ?>">
            <i class="bi bi-<?= $parts[0]=='success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <?= $parts[1] ?>
        </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-pencil text-danger"></i>
                    Modifier mes informations
                </h6>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Téléphone</label>
                            <input type="text" name="telephone"
                                   class="form-control"
                                   value="<?= htmlspecialchars($donneur['telephone']) ?>"
                                   maxlength="8">
                            <small class="text-muted">8 chiffres exactement</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ville</label>
                            <select name="ville" class="form-select">
                                <?php foreach($villes as $v): ?>
                                <option value="<?= $v ?>"
                                    <?= $donneur['ville']==$v ? 'selected' : '' ?>>
                                    <?= $v ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Région</label>
                            <select name="region" class="form-select">
                                <?php foreach($regions as $r): ?>
                                <option value="<?= $r ?>"
                                    <?= $donneur['region']==$r ? 'selected' : '' ?>>
                                    <?= $r ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       name="disponible" id="disponible"
                                       <?= $donneur['disponible'] ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold"
                                       for="disponible">
                                    Je suis disponible pour donner
                                </label>
                            </div>
                        </div>
                    </div>
                    <button type="submit"
                            class="btn btn-danger fw-bold mt-3">
                        <i class="bi bi-save"></i> Enregistrer
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php include '../../views/footer.php'; ?>