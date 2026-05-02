<?php
include '../config/connexion.php';
if(session_status() == PHP_SESSION_NONE) session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'HOPITAL') {
    header('Location: ../auth/login.php');
    exit();
}

$id      = $_SESSION['user_id'];
$erreur  = '';
$message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $groupe   = $_POST['groupe_sanguin'];
    $priorite = $_POST['niveau_priorite'];
    $quantite = (int)$_POST['quantite_requise'];

    $groupes_ok   = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
    $priorites_ok = ['FAIBLE','NORMAL','URGENT','CRITIQUE'];

    if(!in_array($groupe, $groupes_ok)) {
        $erreur = "Groupe sanguin invalide.";
    } elseif(!in_array($priorite, $priorites_ok)) {
        $erreur = "Priorité invalide.";
    } elseif($quantite < 1 || $quantite > 10) {
        $erreur = "La quantité doit être entre 1 et 10.";
    } else {
        $req = $bdd->prepare("
            INSERT INTO urgence
            (groupe_sanguin_requis, niveau_priorite,
             quantite_requise, statut, date_declaration, hopital_id)
            VALUES (?, ?, ?, 'OUVERTE', NOW(), ?)
        ");
        $req->execute([$groupe, $priorite, $quantite, $id]);
        $message = "Urgence déclarée avec succès !";
    }
}

include '../views/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-6 col-md-8">

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h3 class="fw-bold mb-0">
            <i class="bi bi-plus-circle-fill text-danger"></i>
            Déclarer une urgence sanguine
        </h3>
        <small class="text-muted">
            Les donneurs compatibles seront alertés automatiquement
        </small>
    </div>
</div>

<?php if($message != ''): ?>
<div class="alert alert-success d-flex align-items-center gap-2">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div>
        <?= htmlspecialchars($message) ?>
        <a href="dashboard.php" class="fw-bold ms-1 text-success">
            → Voir mes urgences
        </a>
    </div>
</div>
<?php endif; ?>

<?php if($erreur != ''): ?>
<div class="alert alert-danger d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <?= htmlspecialchars($erreur) ?>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
<div class="card-body p-4">
<form method="POST">

    <!-- Groupe sanguin -->
    <div class="mb-4">
        <label class="form-label fw-semibold">
            <i class="bi bi-droplet-fill text-danger"></i>
            Groupe sanguin requis
        </label>
        <div class="row g-2">
            <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
            <div class="col-3">
                <input type="radio" class="btn-check"
                       name="groupe_sanguin"
                       id="g_<?= $g ?>" value="<?= $g ?>" required>
                <label class="btn btn-outline-danger w-100 fw-bold"
                       for="g_<?= $g ?>">
                    <?= $g ?>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Priorité -->
    <div class="mb-4">
        <label class="form-label fw-semibold">
            <i class="bi bi-exclamation-triangle-fill text-warning"></i>
            Niveau de priorité
        </label>
        <div class="row g-2">
            <?php
            $priorites = [
                'FAIBLE'   => ['success','bi-arrow-down-circle'],
                'NORMAL'   => ['primary','bi-dash-circle'],
                'URGENT'   => ['warning','bi-arrow-up-circle'],
                'CRITIQUE' => ['danger', 'bi-exclamation-triangle-fill'],
            ];
            foreach($priorites as $p => $info): ?>
            <div class="col-3">
                <input type="radio" class="btn-check"
                       name="niveau_priorite"
                       id="p_<?= $p ?>" value="<?= $p ?>"
                       <?= $p=='NORMAL' ? 'checked' : '' ?>>
                <label class="btn btn-outline-<?= $info[0] ?> w-100"
                       for="p_<?= $p ?>">
                    <i class="bi <?= $info[1] ?> d-block mb-1"></i>
                    <small class="fw-bold"><?= $p ?></small>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Quantité -->
    <div class="mb-4">
        <label class="form-label fw-semibold">
            <i class="bi bi-bag-plus text-primary"></i>
            Quantité requise (poches)
        </label>
        <div class="d-flex align-items-center gap-3">
            <input type="range" class="form-range flex-grow-1"
                   name="quantite_requise" id="quantite"
                   min="1" max="10" value="1"
                   oninput="document.getElementById('val_q').textContent=this.value">
            <span id="val_q"
                  class="badge bg-danger fs-5 px-3 py-2">1</span>
        </div>
        <div class="d-flex justify-content-between">
            <small class="text-muted">1</small>
            <small class="text-muted">10</small>
        </div>
    </div>

    <button type="submit" class="btn btn-danger w-100 py-3 fw-bold">
        <i class="bi bi-send-fill"></i> Déclarer l'urgence
    </button>
</form>
</div>
</div>

</div>
</div>

<?php include '../views/footer.php'; ?>