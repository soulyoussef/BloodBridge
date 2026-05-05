<?php
include '../../config/connexion.php';
if(session_status() == PHP_SESSION_NONE) session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'DONNEUR') {
    header('Location: ../auth/login.php');
    exit();
}

$id = $_SESSION['user_id'];

// Infos donneur
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

// Notifications non lues — sans urgence_id
$req = $bdd->prepare("
    SELECT n.contenu, n.date_envoi, n.canal
    FROM notification n
    WHERE n.destinataire_id = ? AND n.est_lue = 0
    ORDER BY n.date_envoi DESC
");
$req->execute([$id]);
$notifications = $req->fetchAll(PDO::FETCH_ASSOC);

// Historique participations
$req = $bdd->prepare("
    SELECT p.statut, p.score_compatibilite,
           p.date_reponse, p.don_valide,
           urg.groupe_sanguin_requis,
           urg.niveau_priorite,
           u.nom AS nom_etablissement
    FROM participation p
    JOIN urgence urg ON p.urgence_id = urg.id
    JOIN hopital h ON urg.hopital_id = h.id
    JOIN utilisateur u ON h.id = u.id
    WHERE p.donneur_id = ?
    ORDER BY p.date_reponse DESC
    LIMIT 5
");
$req->execute([$id]);
$participations = $req->fetchAll(PDO::FETCH_ASSOC);

// Badges
$req = $bdd->prepare("
    SELECT b.nom, b.niveau, b.description,
           db.date_obtention
    FROM donneur_badge db
    JOIN badge b ON db.badge_id = b.id
    WHERE db.donneur_id = ?
");
$req->execute([$id]);
$badges = $req->fetchAll(PDO::FETCH_ASSOC);

// Urgences compatibles
$req = $bdd->prepare("
    SELECT urg.id,
           urg.groupe_sanguin_requis,
           urg.niveau_priorite,
           urg.quantite_requise,
           urg.date_declaration,
           u.nom AS nom_etablissement,
           h.ville
    FROM urgence urg
    JOIN hopital h ON urg.hopital_id = h.id
    JOIN utilisateur u ON h.id = u.id
    WHERE urg.groupe_sanguin_requis = ?
    AND urg.statut = 'OUVERTE'
    ORDER BY CASE urg.niveau_priorite
        WHEN 'CRITIQUE' THEN 1
        WHEN 'URGENT'   THEN 2
        ELSE 3 END
");
$req->execute([$donneur['groupe_sanguin']]);
$urgences = $req->fetchAll(PDO::FETCH_ASSOC);

// Vérification éligibilité 56 jours
$eligible    = true;
$jours_reste = 0;
if($donneur['dernier_don'] != null) {
    $jours_passes = (int)(new DateTime())->diff(
        new DateTime($donneur['dernier_don']))->days;
    if($jours_passes < 56) {
        $eligible    = false;
        $jours_reste = 56 - $jours_passes;
    }
}

include '../../views/header.php';
?>

<!-- En-tête -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-person-heart text-danger"></i>
            Bonjour, <?= htmlspecialchars($donneur['nom']) ?> !
        </h4>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-danger fs-6">
                <?= $donneur['groupe_sanguin'] ?>
            </span>
            <small class="text-muted">
                <i class="bi bi-geo-alt-fill text-danger"></i>
                <?= htmlspecialchars($donneur['ville']) ?> —
                <?= htmlspecialchars($donneur['region']) ?>
            </small>
            <?php if($eligible && $donneur['disponible']): ?>
            <span class="badge bg-success">
                <i class="bi bi-check-circle"></i> Éligible au don
            </span>
            <?php elseif(!$eligible): ?>
            <span class="badge bg-warning text-dark">
                <i class="bi bi-clock"></i>
                Éligible dans <?= $jours_reste ?> jours
            </span>
            <?php else: ?>
            <span class="badge bg-secondary">
                <i class="bi bi-pause-circle"></i> Non disponible
            </span>
            <?php endif; ?>
        </div>
    </div>
    <a href="profil.php" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-pencil"></i> Mon Profil
    </a>
</div>

<!-- Cartes résumé -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <i class="bi bi-droplet-fill text-danger"
                   style="font-size:1.8rem;"></i>
                <h3 class="fw-bold text-danger mt-1">
                    <?= $donneur['nb_dons_totaux'] ?>
                </h3>
                <small class="text-muted">Dons effectués</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <i class="bi bi-award-fill text-warning"
                   style="font-size:1.8rem;"></i>
                <h3 class="fw-bold text-warning mt-1">
                    <?= count($badges) ?>
                </h3>
                <small class="text-muted">Badges</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <i class="bi bi-bell-fill text-primary"
                   style="font-size:1.8rem;"></i>
                <h3 class="fw-bold text-primary mt-1">
                    <?= count($notifications) ?>
                </h3>
                <small class="text-muted">Notifications</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <i class="bi bi-graph-up text-success"
                   style="font-size:1.8rem;"></i>
                <h3 class="fw-bold text-success mt-1">
                    <?= $donneur['score'] ?>
                </h3>
                <small class="text-muted">Score IA</small>
            </div>
        </div>
    </div>
</div>

<!-- Alerte non éligible -->
<?php if(!$eligible): ?>
<div class="alert alert-warning mb-4">
    <i class="bi bi-clock-history"></i>
    <strong>Règle médicale :</strong> Votre dernier don date du
    <?= date('d/m/Y', strtotime($donneur['dernier_don'])) ?>.
    Vous pourrez donner à nouveau dans
    <strong><?= $jours_reste ?> jours</strong>
    (minimum 56 jours entre deux dons).
</div>
<?php endif; ?>

<div class="row g-4">

    <!-- Colonne gauche -->
    <div class="col-md-8">

        <!-- Urgences compatibles -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h5 class="fw-bold">
                    <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                    Urgences compatibles — Groupe
                    <span class="badge bg-danger">
                        <?= $donneur['groupe_sanguin'] ?>
                    </span>
                </h5>
            </div>
            <div class="card-body">
                <?php if(count($urgences) == 0): ?>
                <div class="text-center py-3 text-muted">
                    <i class="bi bi-check-circle-fill text-success"
                       style="font-size:2rem;"></i>
                    <p class="mt-2 mb-0">
                        Aucune urgence compatible pour le moment.
                    </p>
                </div>
                <?php else: ?>
                <?php foreach($urgences as $urg):
                    $c = 'secondary';
                    if($urg['niveau_priorite']=='CRITIQUE') $c='danger';
                    if($urg['niveau_priorite']=='URGENT')   $c='warning';
                    if($urg['niveau_priorite']=='NORMAL')   $c='success';
                ?>
                <div class="d-flex justify-content-between align-items-center
                            p-3 mb-2 bg-light rounded
                            border-start border-<?= $c ?> border-4">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-<?= $c ?>">
                                <?= htmlspecialchars($urg['niveau_priorite']) ?>
                            </span>
                            <strong>
                                <?= htmlspecialchars($urg['nom_etablissement']) ?>
                            </strong>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-geo-alt"></i>
                            <?= htmlspecialchars($urg['ville']) ?> |
                            <i class="bi bi-droplet"></i>
                            <?= $urg['quantite_requise'] ?> poche(s)
                        </small>
                    </div>
                    <?php if($eligible && $donneur['disponible']): ?>
                    <a href="confirmer.php?urgence_id=<?= $urg['id'] ?>"
                       class="btn btn-danger btn-sm fw-bold ms-2">
                        <i class="bi bi-check-circle"></i> Participer
                    </a>
                    <?php else: ?>
                    <button class="btn btn-secondary btn-sm ms-2" disabled>
                        <i class="bi bi-lock"></i> Non éligible
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historique -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h5 class="fw-bold">
                    <i class="bi bi-clock-history text-primary"></i>
                    Historique de mes participations
                </h5>
            </div>
            <div class="card-body">
                <?php if(count($participations) == 0): ?>
                <p class="text-muted text-center py-2">
                    Aucune participation pour le moment.
                </p>
                <?php else: ?>
                <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Hôpital</th>
                            <th>Groupe</th>
                            <th>Statut</th>
                            <th>Don validé</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($participations as $p):
                            $sc = 'secondary';
                            if($p['statut']=='CONFIRME')   $sc='success';
                            if($p['statut']=='REFUSE')     $sc='danger';
                            if($p['statut']=='DON_VALIDE') $sc='primary';
                        ?>
                        <tr>
                            <td>
                                <small class="fw-bold">
                                    <?= htmlspecialchars($p['nom_etablissement']) ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-danger">
                                    <?= htmlspecialchars($p['groupe_sanguin_requis']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $sc ?>">
                                    <?= htmlspecialchars($p['statut']) ?>
                                </span>
                            </td>
                            <td>
                                <?= $p['don_valide']
                                    ? '<i class="bi bi-check-circle-fill text-success"></i>'
                                    : '<i class="bi bi-x-circle-fill text-muted"></i>'
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Colonne droite -->
    <div class="col-md-4">

        <!-- Notifications -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h5 class="fw-bold">
                    <i class="bi bi-bell-fill text-primary"></i>
                    Notifications
                    <?php if(count($notifications) > 0): ?>
                    <span class="badge bg-danger">
                        <?= count($notifications) ?>
                    </span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if(count($notifications) == 0): ?>
                <p class="text-muted text-center small py-2">
                    Aucune notification.
                </p>
                <?php else: ?>
                <?php foreach($notifications as $n): ?>
                <div class="alert alert-info py-2 px-3 mb-2 small">
                    <?= htmlspecialchars($n['contenu']) ?>
                    <small class="text-muted d-block mt-1">
                        <i class="bi bi-clock"></i>
                        <?= date('d/m/Y H:i', strtotime($n['date_envoi'])) ?>
                    </small>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Badges -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h5 class="fw-bold">
                    <i class="bi bi-award-fill text-warning"></i>
                    Mes Badges
                </h5>
            </div>
            <div class="card-body">
                <?php if(count($badges) == 0): ?>
                <div class="text-center py-2">
                    <i class="bi bi-award text-muted"
                       style="font-size:2.5rem;"></i>
                    <p class="text-muted small mt-2">
                        Effectuez votre premier don<br>
                        pour gagner un badge !
                    </p>
                </div>
                <?php else: ?>
                <?php foreach($badges as $b):
                    $bc = 'secondary';
                    if($b['niveau']=='BRONZE') $bc='warning';
                    if($b['niveau']=='OR')     $bc='warning';
                ?>
                <div class="d-flex align-items-center gap-3 mb-3
                            p-2 bg-light rounded">
                    <i class="bi bi-award-fill text-<?= $bc ?>"
                       style="font-size:2rem;"></i>
                    <div>
                        <strong class="d-block">
                            <?= htmlspecialchars($b['nom']) ?>
                        </strong>
                        <small class="text-muted">
                            <?= htmlspecialchars($b['description']) ?>
                        </small>
                        <small class="text-muted d-block">
                            <?= date('d/m/Y', strtotime($b['date_obtention'])) ?>
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <!-- Progression prochain badge -->
                <?php
                $req = $bdd->prepare("
                    SELECT MIN(seuil_dons) as prochain
                    FROM badge
                    WHERE seuil_dons > ?
                ");
                $req->execute([$donneur['nb_dons_totaux']]);
                $prochain = $req->fetch(PDO::FETCH_ASSOC)['prochain'];
                if($prochain): ?>
                <hr>
                <small class="text-muted d-block mb-1">
                    <i class="bi bi-trophy"></i>
                    Prochain badge dans
                    <?= $prochain - $donneur['nb_dons_totaux'] ?> don(s)
                </small>
                <div class="progress" style="height:8px;">
                    <div class="progress-bar bg-warning"
                         style="width:<?=
                            ($donneur['nb_dons_totaux']/$prochain)*100
                         ?>%">
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include '../../views/footer.php'; ?>