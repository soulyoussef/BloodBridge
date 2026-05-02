
<?php
include 'config/connexion.php';
include 'views/header.php';

$req = $bdd->query("SELECT COUNT(*) as t FROM donneur");
$nb_donneurs = $req->fetch(PDO::FETCH_ASSOC)['t'];

$req = $bdd->query("SELECT COALESCE(SUM(nb_dons_totaux),0) as t FROM donneur");
$nb_dons = $req->fetch(PDO::FETCH_ASSOC)['t'];

$req = $bdd->query("SELECT COUNT(*) as t FROM hopital");
$nb_hopitaux = $req->fetch(PDO::FETCH_ASSOC)['t'];

$req = $bdd->query("SELECT COUNT(*) as t FROM urgence WHERE statut='OUVERTE'");
$nb_urgences = $req->fetch(PDO::FETCH_ASSOC)['t'];

// Urgences ouvertes — ORDER BY priorité
// Remplace l'ancienne requête par celle-ci
$req = $bdd->query("
    SELECT urg.id, urg.groupe_sanguin_requis,
           urg.niveau_priorite, urg.quantite_requise,
           urg.date_declaration,
           h.nom_etablissement, h.ville
    FROM urgence urg
    JOIN hopital h ON urg.hopital_id = h.id
    WHERE urg.statut = 'OUVERTE'
    ORDER BY CASE urg.niveau_priorite
        WHEN 'CRITIQUE' THEN 1
        WHEN 'URGENT'   THEN 2
        WHEN 'NORMAL'   THEN 3
        ELSE 4 END
    LIMIT 6
");
$urgences = $req->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- HERO -->
<div class="p-5 mb-4 rounded-4 text-white"
     style="background:linear-gradient(135deg,#1a1a2e 0%,#C0392B 100%);">
    <div class="row align-items-center">
        <div class="col-md-8">
            <span class="badge bg-white text-danger mb-3 py-2 px-3">
                <i class="bi bi-heart-pulse-fill"></i>
                ODD 3 — Bonne santé et bien-être
            </span>
            <h1 class="fw-bold display-5 mb-3">
                <i class="bi bi-droplet-fill"></i> BloodBridge
            </h1>
            <p class="lead mb-4" style="color:rgba(255,255,255,0.85);">
                Plateforme intelligente de don de sang en Tunisie.<br>
                <strong>1 don de sang peut sauver jusqu'à 3 vies.</strong>
            </p>
            <?php if(!isset($_SESSION['user_id'])): ?>
            <div class="d-flex gap-3 flex-wrap">
                <a href="auth/register.php"
                   class="btn btn-light btn-lg fw-bold text-danger">
                    <i class="bi bi-person-plus-fill"></i> Devenir Donneur
                </a>
                <a href="auth/login.php"
                   class="btn btn-outline-light btn-lg">
                    <i class="bi bi-hospital"></i> Espace Hôpital
                </a>
            </div>
            <?php endif; ?>
        </div>
        <div class="col-md-4 text-center d-none d-md-block">
            <i class="bi bi-droplet-fill"
               style="font-size:8rem;opacity:0.15;"></i>
        </div>
    </div>
</div>

<!-- STATISTIQUES -->
<div class="row g-3 mb-5">
    <?php
    $cards = [
        ['val'=>$nb_donneurs,'label'=>'Donneurs inscrits',
         'icon'=>'people-fill','color'=>'danger'],
        ['val'=>$nb_dons,'label'=>'Dons effectués',
         'icon'=>'droplet-fill','color'=>'success'],
        ['val'=>$nb_hopitaux,'label'=>'Hôpitaux partenaires',
         'icon'=>'hospital-fill','color'=>'primary'],
        ['val'=>$nb_urgences,'label'=>'Urgences en cours',
         'icon'=>'exclamation-triangle-fill','color'=>'warning'],
    ];
    foreach($cards as $c):
    ?>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-<?= $c['color'] ?> bg-opacity-10 mx-auto">
                <i class="bi bi-<?= $c['icon'] ?> text-<?= $c['color'] ?>"></i>
            </div>
            <h2 class="fw-bold text-<?= $c['color'] ?>"><?= $c['val'] ?></h2>
            <p class="text-muted small mb-0"><?= $c['label'] ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- URGENCES EN COURS -->
<?php if(count($urgences) > 0): ?>
<div class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">
            <i class="bi bi-exclamation-circle-fill text-danger"></i>
            Urgences Sanguines en Cours
        </h4>
        <?php if(!isset($_SESSION['user_id'])): ?>
        <a href="auth/register.php" class="btn btn-danger btn-sm">
            <i class="bi bi-person-plus"></i> Devenir donneur
        </a>
        <?php endif; ?>
    </div>
    <div class="row g-3">
        <?php foreach($urgences as $u):
        $cp = 'secondary';
        if($u['niveau_priorite']=='CRITIQUE')     $cp='danger';
        elseif($u['niveau_priorite']=='URGENT')   $cp='warning';
        elseif($u['niveau_priorite']=='NORMAL')   $cp='primary';
        ?>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-<?= $cp ?> border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-danger fs-5 py-2 px-3">
                            <?= htmlspecialchars($u['groupe_sanguin_requis']) ?>
                        </span>
                        <span class="badge bg-<?= $cp ?>">
                            <?= htmlspecialchars($u['niveau_priorite']) ?>
                        </span>
                    </div>
                    <h6 class="fw-bold mb-1">
                        <?= htmlspecialchars($u['nom_etablissement']) ?>
                    </h6>
                    <small class="text-muted d-block mb-2">
                        <i class="bi bi-geo-alt-fill text-danger"></i>
                        <?= htmlspecialchars($u['ville']) ?>
                    </small>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="bi bi-droplet text-danger"></i>
                            <?= $u['quantite_requise'] ?> poche(s)
                        </small>
                        <small class="text-muted">
                            <i class="bi bi-clock"></i>
                            <?= date('d/m/Y', strtotime($u['date_declaration'])) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- COMMENT ÇA MARCHE -->
<div class="card border-0 shadow-sm mb-5">
    <div class="card-body p-5">
        <h4 class="fw-bold text-center mb-4">
            <i class="bi bi-question-circle-fill text-danger"></i>
            Comment fonctionne BloodBridge ?
        </h4>
        <div class="row g-4 text-center">
            <?php
            $etapes = [
                ['icon'=>'person-plus-fill','color'=>'danger',
                 'titre'=>'Inscription',
                 'desc'=>'Le donneur s\'inscrit avec son groupe sanguin et sa localisation.'],
                ['icon'=>'hospital-fill','color'=>'primary',
                 'titre'=>'Urgence déclarée',
                 'desc'=>'L\'hôpital déclare une urgence sanguine avec le groupe requis.'],
                ['icon'=>'robot','color'=>'success',
                 'titre'=>'IA Scoring',
                 'desc'=>'L\'algorithme identifie les meilleurs donneurs compatibles.'],
                ['icon'=>'check-circle-fill','color'=>'warning',
                 'titre'=>'Don effectué',
                 'desc'=>'Le donneur confirme et se rend à l\'hôpital pour donner.'],
            ];
            foreach($etapes as $i => $e):
            ?>
            <div class="col-6 col-md-3">
                <div class="rounded-circle bg-<?= $e['color'] ?> bg-opacity-10
                            d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:70px;height:70px;font-size:1.8rem;">
                    <i class="bi bi-<?= $e['icon'] ?> text-<?= $e['color'] ?>"></i>
                </div>
                <div class="badge bg-<?= $e['color'] ?> rounded-pill mb-2">
                    Étape <?= $i+1 ?>
                </div>
                <h6 class="fw-bold"><?= $e['titre'] ?></h6>
                <p class="text-muted small mb-0"><?= $e['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- INFOS RÉELLES DON DE SANG -->
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-danger text-white border-0 p-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-person-check-fill"></i> Qui peut donner ?
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                        Âge entre <strong>18 et 65 ans</strong>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                        Poids <strong>≥ 50 kg</strong>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                        En bonne santé générale
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                        Délai <strong>56 jours</strong> depuis dernier don
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                        Hémoglobine <strong>≥ 12,5 g/dL</strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-warning text-dark border-0 p-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Contre-indications
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-x-circle-fill text-danger me-2"></i>
                        Femme <strong>enceinte ou allaitante</strong>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-x-circle-fill text-danger me-2"></i>
                        Maladie chronique non contrôlée
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-x-circle-fill text-danger me-2"></i>
                        Prise d'antibiotiques récente
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-x-circle-fill text-danger me-2"></i>
                        Tatouage / piercing <strong>récent (6 mois)</strong>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-x-circle-fill text-danger me-2"></i>
                        Fièvre ou infection active
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-success text-white border-0 p-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-shield-check-fill"></i>
                    Conseils avant de donner
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-droplet-fill text-primary me-2"></i>
                        Bien <strong>s'hydrater</strong> avant le don
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-egg-fried text-warning me-2"></i>
                        Manger un <strong>repas léger</strong> avant
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-moon-stars-fill text-info me-2"></i>
                        Avoir <strong>bien dormi</strong> la nuit précédente
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-cup-straw text-danger me-2"></i>
                        Éviter l'<strong>alcool</strong> 24h avant
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-bicycle text-success me-2"></i>
                        Éviter le <strong>sport intense</strong> après
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- COMPATIBILITÉ GROUPES SANGUINS -->
<div class="card border-0 shadow-sm mb-5">
    <div class="card-header bg-white border-0 pt-4 pb-2 text-center">
        <h4 class="fw-bold">
            <i class="bi bi-arrow-left-right text-danger"></i>
            Compatibilité des Groupes Sanguins
        </h4>
        <p class="text-muted small mb-0">
            <span class="badge bg-dark me-1">O-</span> donneur universel |
            <span class="badge bg-danger ms-1">AB+</span> receveur universel
        </p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-bordered text-center mb-0">
            <thead class="table-danger">
                <tr>
                    <th class="text-start">Donneur ↓</th>
                    <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                    <th><span class="badge bg-danger"><?= $g ?></span></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $compat = [
                    'O-' =>['O-'=>1,'O+'=>1,'A-'=>1,'A+'=>1,'B-'=>1,'B+'=>1,'AB-'=>1,'AB+'=>1],
                    'O+' =>['O-'=>0,'O+'=>1,'A-'=>0,'A+'=>1,'B-'=>0,'B+'=>1,'AB-'=>0,'AB+'=>1],
                    'A-' =>['O-'=>0,'O+'=>0,'A-'=>1,'A+'=>1,'B-'=>0,'B+'=>0,'AB-'=>1,'AB+'=>1],
                    'A+' =>['O-'=>0,'O+'=>0,'A-'=>0,'A+'=>1,'B-'=>0,'B+'=>0,'AB-'=>0,'AB+'=>1],
                    'B-' =>['O-'=>0,'O+'=>0,'A-'=>0,'A+'=>0,'B-'=>1,'B+'=>1,'AB-'=>1,'AB+'=>1],
                    'B+' =>['O-'=>0,'O+'=>0,'A-'=>0,'A+'=>0,'B-'=>0,'B+'=>1,'AB-'=>0,'AB+'=>1],
                    'AB-'=>['O-'=>0,'O+'=>0,'A-'=>0,'A+'=>0,'B-'=>0,'B+'=>0,'AB-'=>1,'AB+'=>1],
                    'AB+'=>['O-'=>0,'O+'=>0,'A-'=>0,'A+'=>0,'B-'=>0,'B+'=>0,'AB-'=>0,'AB+'=>1],
                ];
                foreach($compat as $don => $rec):
                ?>
                <tr>
                    <td class="text-start">
                        <span class="badge bg-dark"><?= $don ?></span>
                    </td>
                    <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                    <td>
                        <?php if($rec[$g] ?? 0): ?>
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <?php else: ?>
                        <i class="bi bi-x-circle-fill text-danger opacity-25"></i>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- CHIFFRES CLÉS TUNISIE -->
<div class="card border-0 shadow-sm mb-5"
     style="background:linear-gradient(135deg,#fff5f5,#fff);">
    <div class="card-body p-4 text-center">
        <h5 class="fw-bold mb-4">
            <i class="bi bi-graph-up-arrow text-danger"></i>
            Le Don de Sang en Tunisie
        </h5>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <h3 class="fw-bold text-danger">250 000</h3>
                <small class="text-muted">Dons collectés / an</small>
            </div>
            <div class="col-6 col-md-3">
                <h3 class="fw-bold text-warning">40 000</h3>
                <small class="text-muted">Déficit annuel</small>
            </div>
            <div class="col-6 col-md-3">
                <h3 class="fw-bold text-primary">6.25%</h3>
                <small class="text-muted">Donneurs réguliers</small>
            </div>
            <div class="col-6 col-md-3">
                <h3 class="fw-bold text-success">56 j</h3>
                <small class="text-muted">Délai min entre dons</small>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>