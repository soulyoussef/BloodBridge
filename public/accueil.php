
<?php
// Inclure la connexion BD
include '../config/connexion.php';
// Inclure le header
include '../views/header.php';

// ---- Statistiques depuis la BD ----

// Nombre de donneurs
$req = $bdd->query("SELECT COUNT(*) as total FROM donneur");
$nb_donneurs = $req->fetch(PDO::FETCH_ASSOC)['total'];

// Nombre de dons
$req = $bdd->query("SELECT SUM(nb_dons_totaux) as total FROM donneur");
$nb_dons = $req->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Nombre d'hôpitaux
$req = $bdd->query("SELECT COUNT(*) as total FROM hopital");
$nb_hopitaux = $req->fetch(PDO::FETCH_ASSOC)['total'];

// Urgences ouvertes
$req = $bdd->query("SELECT COUNT(*) as total FROM urgence WHERE statut='OUVERTE'");
$nb_urgences = $req->fetch(PDO::FETCH_ASSOC)['total'];

// Urgences récentes
$req = $bdd->query("
    SELECT u.groupe_sanguin_requis, u.niveau_priorite,
           u.quantite_requise, h.nom_etablissement, h.ville
    FROM urgence u
    JOIN hopital h ON u.hopital_id = h.id
    WHERE u.statut = 'OUVERTE'
    ORDER BY CASE u.niveau_priorite
        WHEN 'CRITIQUE' THEN 1
        WHEN 'URGENT'   THEN 2
        WHEN 'NORMAL'   THEN 3
        ELSE 4 END
    LIMIT 6
");
$urgences = $req->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Hero -->
<div class="p-5 mb-4 rounded text-white"
     style="background: linear-gradient(135deg,#1a1a2e,#C0392B);">
    <h1 class="fw-bold">
        <i class="bi bi-droplet-fill"></i> BloodBridge
    </h1>
    <p class="lead">
        Plateforme intelligente de don de sang.<br>
        Connecter les donneurs aux hôpitaux, <strong>sauver des vies.</strong>
    </p>
    <?php if(!isset($_SESSION['user_id'])): ?>
    <a href="auth/register.php" class="btn btn-light btn-lg fw-bold me-2">
        <i class="bi bi-person-plus"></i> Devenir Donneur
    </a>
    <a href="auth/login.php" class="btn btn-outline-light btn-lg">
        <i class="bi bi-box-arrow-in-right"></i> Connexion
    </a>
    <?php endif; ?>
</div>

<!-- Statistiques -->
<div class="row g-3 mb-5">
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0">
            <div class="card-body py-4">
                <i class="bi bi-people-fill text-danger" style="font-size:2.5rem;"></i>
                <h2 class="fw-bold text-danger"><?= $nb_donneurs ?></h2>
                <p class="text-muted mb-0">Donneurs inscrits</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0">
            <div class="card-body py-4">
                <i class="bi bi-droplet-fill text-success" style="font-size:2.5rem;"></i>
                <h2 class="fw-bold text-success"><?= $nb_dons ?></h2>
                <p class="text-muted mb-0">Dons effectués</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0">
            <div class="card-body py-4">
                <i class="bi bi-hospital-fill text-primary" style="font-size:2.5rem;"></i>
                <h2 class="fw-bold text-primary"><?= $nb_hopitaux ?></h2>
                <p class="text-muted mb-0">Hôpitaux partenaires</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0">
            <div class="card-body py-4">
                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size:2.5rem;"></i>
                <h2 class="fw-bold text-warning"><?= $nb_urgences ?></h2>
                <p class="text-muted mb-0">Urgences en cours</p>
            </div>
        </div>
    </div>
</div>

<!-- Urgences récentes -->
<h3 class="fw-bold mb-4">
    <i class="bi bi-exclamation-circle-fill text-danger"></i>
    Urgences Sanguines en Cours
</h3>
<div class="row g-3 mb-5">
    <?php foreach($urgences as $urg): ?>
    <?php
        $couleur = 'secondary';
        if($urg['niveau_priorite'] == 'CRITIQUE') $couleur = 'danger';
        if($urg['niveau_priorite'] == 'URGENT')   $couleur = 'warning';
        if($urg['niveau_priorite'] == 'NORMAL')   $couleur = 'success';
    ?>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-<?= $couleur ?> border-4">
            <div class="card-body">
                <span class="badge bg-<?= $couleur ?> fs-6 mb-2">
                    Groupe <?= $urg['groupe_sanguin_requis'] ?>
                </span>
                <h6 class="fw-bold"><?= $urg['nom_etablissement'] ?></h6>
                <small class="text-muted">
                    <i class="bi bi-geo-alt"></i> <?= $urg['ville'] ?>
                </small>
                <hr>
                <small class="text-muted">
                    <i class="bi bi-droplet"></i>
                    <?= $urg['quantite_requise'] ?> poche(s) de sang requise(s)
                </small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Comment ça marche -->
<div class="card border-0 shadow-sm mb-5">
    <div class="card-body p-5">
        <h3 class="fw-bold text-center mb-4">
            <i class="bi bi-question-circle"></i> Comment ça marche ?
        </h3>
        <div class="row g-4 text-center">
            <div class="col-md-3">
                <div class="rounded-circle bg-danger text-white d-inline-flex
                            align-items-center justify-content-center mb-3"
                     style="width:70px;height:70px;font-size:1.8rem;">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h5 class="fw-bold">1. Inscription</h5>
                <p class="text-muted small">Le donneur s'inscrit avec son groupe sanguin.</p>
            </div>
            <div class="col-md-3">
                <div class="rounded-circle bg-warning text-white d-inline-flex
                            align-items-center justify-content-center mb-3"
                     style="width:70px;height:70px;font-size:1.8rem;">
                    <i class="bi bi-hospital"></i>
                </div>
                <h5 class="fw-bold">2. Urgence</h5>
                <p class="text-muted small">L'hôpital déclare une urgence sanguine.</p>
            </div>
            <div class="col-md-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex
                            align-items-center justify-content-center mb-3"
                     style="width:70px;height:70px;font-size:1.8rem;">
                    <i class="bi bi-robot"></i>
                </div>
                <h5 class="fw-bold">3. IA Scoring</h5>
                <p class="text-muted small">L'algorithme trouve le meilleur donneur.</p>
            </div>
            <div class="col-md-3">
                <div class="rounded-circle bg-success text-white d-inline-flex
                            align-items-center justify-content-center mb-3"
                     style="width:70px;height:70px;font-size:1.8rem;">
                    <i class="bi bi-heart-fill"></i>
                </div>
                <h5 class="fw-bold">4. Don effectué</h5>
                <p class="text-muted small">Le donneur confirme et effectue le don.</p>
            </div>
        </div>
    </div>
</div>

<?php include '../views/footer.php'; ?>