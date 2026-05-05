<?php
include '../../config/connexion.php';
if(session_status() == PHP_SESSION_NONE) session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
    header('Location: ../auth/login.php');
    exit();
}

// Stats groupes sanguins
$req = $bdd->query("
    SELECT groupe_sanguin, COUNT(*) as nb
    FROM donneur GROUP BY groupe_sanguin ORDER BY nb DESC
");
$stats_groupes = $req->fetchAll(PDO::FETCH_ASSOC);

// Stats urgences par priorité
$req = $bdd->query("
    SELECT niveau_priorite, COUNT(*) as nb
    FROM urgence GROUP BY niveau_priorite ORDER BY nb DESC
");
$stats_priorites = $req->fetchAll(PDO::FETCH_ASSOC);

// Stats urgences par statut
$req = $bdd->query("
    SELECT statut, COUNT(*) as nb
    FROM urgence GROUP BY statut
");
$stats_statuts = $req->fetchAll(PDO::FETCH_ASSOC);

// Top 5 donneurs
$req = $bdd->query("
    SELECT u.nom, d.groupe_sanguin,
           d.nb_dons_totaux, d.ville
    FROM donneur d
    JOIN utilisateur u ON d.id = u.id
    ORDER BY d.nb_dons_totaux DESC
    LIMIT 5
");
$top_donneurs = $req->fetchAll(PDO::FETCH_ASSOC);

// Stats par hôpital
$req = $bdd->query("
    SELECT h.nom_etablissement, h.ville,
           COUNT(u.id) as nb_urgences
    FROM hopital h
    LEFT JOIN urgence u ON h.id = u.hopital_id
    GROUP BY h.id, h.nom_etablissement, h.ville
    ORDER BY nb_urgences DESC
");
$stats_hopitaux = $req->fetchAll(PDO::FETCH_ASSOC);

// Totaux pour les barres de progression
$req = $bdd->query("SELECT COUNT(*) as t FROM donneur");
$total_donneurs = $req->fetch(PDO::FETCH_ASSOC)['t'];

$req = $bdd->query("SELECT COUNT(*) as t FROM urgence");
$total_urgences = $req->fetch(PDO::FETCH_ASSOC)['t'];

include '../../views/header.php';
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="fw-bold mb-0">
        <i class="bi bi-bar-chart-fill text-success"></i>
        Statistiques & Rapports
    </h4>
</div>

<div class="row g-4">

    <!-- Groupes sanguins -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-droplet-fill text-danger"></i>
                    Donneurs par groupe sanguin
                </h5>
            </div>
            <div class="card-body">
                <?php foreach($stats_groupes as $g): ?>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-danger fs-6"
                          style="min-width:45px;">
                        <?= htmlspecialchars($g['groupe_sanguin']) ?>
                    </span>
                    <div class="progress flex-grow-1" style="height:12px;">
                        <div class="progress-bar bg-danger"
                             style="width:<?=
                                $total_donneurs > 0
                                ? ($g['nb']/$total_donneurs)*100 : 0
                             ?>%">
                        </div>
                    </div>
                    <strong class="text-muted" style="min-width:20px;">
                        <?= $g['nb'] ?>
                    </strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Urgences par priorité -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-exclamation-triangle text-warning"></i>
                    Urgences par priorité
                </h5>
            </div>
            <div class="card-body">
                <?php foreach($stats_priorites as $p):
                    $c = 'secondary';
                    if($p['niveau_priorite']=='CRITIQUE') $c='danger';
                    if($p['niveau_priorite']=='URGENT')   $c='warning';
                    if($p['niveau_priorite']=='NORMAL')   $c='success';
                ?>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-<?= $c ?>"
                          style="min-width:80px;">
                        <?= htmlspecialchars($p['niveau_priorite']) ?>
                    </span>
                    <div class="progress flex-grow-1" style="height:12px;">
                        <div class="progress-bar bg-<?= $c ?>"
                             style="width:<?=
                                $total_urgences > 0
                                ? ($p['nb']/$total_urgences)*100 : 0
                             ?>%">
                        </div>
                    </div>
                    <strong class="text-muted" style="min-width:20px;">
                        <?= $p['nb'] ?>
                    </strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Top 5 donneurs -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-trophy-fill text-warning"></i>
                    Top 5 Donneurs
                </h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Rang</th>
                            <th>Nom</th>
                            <th>Groupe</th>
                            <th>Ville</th>
                            <th>Dons</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($top_donneurs as $i => $d): ?>
                        <tr class="<?= $i==0 ? 'table-warning' : '' ?>">
                            <td class="text-center">
                                <?php if($i==0): ?>
                                <i class="bi bi-trophy-fill text-warning"></i>
                                <?php else: ?>
                                <span class="text-muted"><?= $i+1 ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>
                                    <?= htmlspecialchars($d['nom']) ?>
                                </strong>
                            </td>
                            <td>
                                <span class="badge bg-danger">
                                    <?= htmlspecialchars($d['groupe_sanguin']) ?>
                                </span>
                            </td>
                            <td>
                                <small>
                                    <?= htmlspecialchars($d['ville']) ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-primary rounded-pill">
                                    <?= $d['nb_dons_totaux'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Stats hôpitaux -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-hospital-fill text-primary"></i>
                    Urgences par hôpital
                </h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Hôpital</th>
                            <th>Ville</th>
                            <th>Urgences</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stats_hopitaux as $h): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?= htmlspecialchars(
                                        $h['nom_etablissement']) ?>
                                </strong>
                            </td>
                            <td>
                                <small>
                                    <?= htmlspecialchars($h['ville']) ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-primary rounded-pill">
                                    <?= $h['nb_urgences'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Stats par statut urgences -->
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-pie-chart-fill text-info"></i>
                    Urgences par statut
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <?php foreach($stats_statuts as $s):
                        $c = 'secondary';
                        if($s['statut']=='OUVERTE')   $c='success';
                        if($s['statut']=='EN_COURS')  $c='warning';
                        if($s['statut']=='CLOTUREE')  $c='secondary';
                        if($s['statut']=='SATISFAITE')$c='primary';
                    ?>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded bg-<?= $c ?> bg-opacity-10">
                            <h3 class="fw-bold text-<?= $c ?>">
                                <?= $s['nb'] ?>
                            </h3>
                            <span class="badge bg-<?= $c ?>">
                                <?= htmlspecialchars($s['statut']) ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include '../../views/footer.php'; ?>