<?php
include '../../config/connexion.php';
if(session_status() == PHP_SESSION_NONE) session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
    header('Location: ../auth/login.php');
    exit();
}

// Statistiques globales
$req = $bdd->query("SELECT COUNT(*) as t FROM donneur");
$nb_donneurs = $req->fetch(PDO::FETCH_ASSOC)['t'];

$req = $bdd->query("SELECT COUNT(*) as t FROM hopital");
$nb_hopitaux = $req->fetch(PDO::FETCH_ASSOC)['t'];

$req = $bdd->query("
    SELECT COUNT(*) as t FROM urgence WHERE statut='OUVERTE'
");
$nb_urgences = $req->fetch(PDO::FETCH_ASSOC)['t'];

$req = $bdd->query("
    SELECT COALESCE(SUM(nb_dons_totaux),0) as t FROM donneur
");
$nb_dons = $req->fetch(PDO::FETCH_ASSOC)['t'];

$req = $bdd->query("SELECT COUNT(*) as t FROM utilisateur");
$nb_users = $req->fetch(PDO::FETCH_ASSOC)['t'];

$req = $bdd->query("
    SELECT COUNT(*) as t FROM participation
    WHERE statut='CONFIRME' AND don_valide=0
");
$nb_a_valider = $req->fetch(PDO::FETCH_ASSOC)['t'];

// Dernières urgences
$req = $bdd->query("
    SELECT u.groupe_sanguin_requis, u.niveau_priorite,
           u.statut, u.date_declaration,
           h.nom_etablissement, h.ville
    FROM urgence u
    JOIN hopital h ON u.hopital_id = h.id
    ORDER BY u.date_declaration DESC
    LIMIT 6
");
$urgences_recentes = $req->fetchAll(PDO::FETCH_ASSOC);

// Derniers inscrits
$req = $bdd->query("
    SELECT id, nom, email, role
    FROM utilisateur
    ORDER BY id DESC
    LIMIT 5
");
$derniers_users = $req->fetchAll(PDO::FETCH_ASSOC);

// Stats par groupe sanguin
$req = $bdd->query("
    SELECT groupe_sanguin, COUNT(*) as nb
    FROM donneur
    GROUP BY groupe_sanguin
    ORDER BY nb DESC
");
$stats_groupes = $req->fetchAll(PDO::FETCH_ASSOC);

include '../../views/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-speedometer2 text-danger"></i>
        Tableau de bord Administrateur
    </h4>
    <div class="d-flex gap-2 flex-wrap">
        <a href="utilisateurs.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-people"></i> Utilisateurs
        </a>
        <a href="statistiques.php" class="btn btn-outline-success btn-sm">
            <i class="bi bi-bar-chart"></i> Statistiques
        </a>
    </div>
</div>

<!-- Alerte dons à valider -->
<?php if($nb_a_valider > 0): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
    <div>
        <strong><?= $nb_a_valider ?> don(s)</strong>
        en attente de validation par les hôpitaux.
    </div>
</div>
<?php endif; ?>

<!-- Statistiques globales -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['val'=>$nb_donneurs,'label'=>'Donneurs',
         'icon'=>'people-fill','color'=>'danger'],
        ['val'=>$nb_hopitaux,'label'=>'Hôpitaux',
         'icon'=>'hospital-fill','color'=>'primary'],
        ['val'=>$nb_urgences,'label'=>'Urgences ouvertes',
         'icon'=>'exclamation-triangle-fill','color'=>'warning'],
        ['val'=>$nb_dons,'label'=>'Dons effectués',
         'icon'=>'droplet-fill','color'=>'success'],
        ['val'=>$nb_users,'label'=>'Utilisateurs',
         'icon'=>'person-check-fill','color'=>'info'],
    ];
    foreach($cards as $c):
    ?>
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <i class="bi bi-<?= $c['icon'] ?> text-<?= $c['color'] ?>"
                   style="font-size:1.8rem;"></i>
                <h3 class="fw-bold text-<?= $c['color'] ?> mt-1">
                    <?= $c['val'] ?>
                </h3>
                <small class="text-muted"><?= $c['label'] ?></small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">

    <!-- Urgences récentes -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-clock-history text-danger"></i>
                    Dernières urgences
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Hôpital</th>
                            <th>Groupe</th>
                            <th>Priorité</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($urgences_recentes as $u):
                            $c = 'secondary';
                            if($u['niveau_priorite']=='CRITIQUE') $c='danger';
                            if($u['niveau_priorite']=='URGENT')   $c='warning';
                            if($u['niveau_priorite']=='NORMAL')   $c='success';
                        ?>
                        <tr>
                            <td>
                                <small class="fw-bold">
                                    <?= htmlspecialchars($u['nom_etablissement']) ?>
                                </small>
                                <br>
                                <small class="text-muted">
                                    <?= htmlspecialchars($u['ville']) ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-danger">
                                    <?= htmlspecialchars(
                                        $u['groupe_sanguin_requis']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $c ?>">
                                    <?= htmlspecialchars($u['niveau_priorite']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?=
                                    $u['statut']=='OUVERTE'  ? 'success' :
                                    ($u['statut']=='EN_COURS'? 'warning' : 'secondary')
                                ?>">
                                    <?= htmlspecialchars($u['statut']) ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= date('d/m/Y',
                                        strtotime($u['date_declaration'])) ?>
                                </small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne droite -->
    <div class="col-md-5">

        <!-- Derniers inscrits -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-person-plus text-primary"></i>
                    Derniers inscrits
                </h5>
            </div>
            <div class="card-body">
                <?php foreach($derniers_users as $u): ?>
                <div class="d-flex justify-content-between
                            align-items-center mb-2 pb-2 border-bottom">
                    <div>
                        <strong class="d-block">
                            <?= htmlspecialchars($u['nom']) ?>
                        </strong>
                        <small class="text-muted">
                            <?= htmlspecialchars($u['email']) ?>
                        </small>
                    </div>
                    <span class="badge bg-<?=
                        $u['role']=='ADMIN'    ? 'danger' :
                        ($u['role']=='HOPITAL' ? 'primary': 'success')
                    ?>">
                        <?= $u['role'] ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <a href="utilisateurs.php"
                   class="btn btn-outline-primary btn-sm w-100 mt-2">
                    Voir tous les utilisateurs →
                </a>
            </div>
        </div>

        <!-- Stats groupes sanguins -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-droplet-fill text-danger"></i>
                    Donneurs par groupe sanguin
                </h5>
            </div>
            <div class="card-body">
                <?php foreach($stats_groupes as $g): ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-danger"
                          style="min-width:40px;">
                        <?= htmlspecialchars($g['groupe_sanguin']) ?>
                    </span>
                    <div class="progress flex-grow-1" style="height:10px;">
                        <div class="progress-bar bg-danger"
                             style="width:<?=
                                $nb_donneurs > 0
                                ? ($g['nb']/$nb_donneurs)*100 : 0
                             ?>%">
                        </div>
                    </div>
                    <small class="fw-bold text-muted">
                        <?= $g['nb'] ?>
                    </small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?php include '../../views/footer.php'; ?>