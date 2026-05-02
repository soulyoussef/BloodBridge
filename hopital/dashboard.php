<?php
include '../config/connexion.php';
if(session_status() == PHP_SESSION_NONE) session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'HOPITAL') {
    header('Location: ../auth/login.php');
    exit();
}

$id = $_SESSION['user_id'];

// Infos hôpital
$req = $bdd->prepare("
    SELECT u.nom, u.email, u.telephone,
           h.nom_etablissement, h.ville,
           h.adresse, h.statut, h.region
    FROM utilisateur u
    JOIN hopital h ON u.id = h.id
    WHERE u.id = ?
");
$req->execute([$id]);
$hopital = $req->fetch(PDO::FETCH_ASSOC);

// Statistiques
$req = $bdd->prepare("
    SELECT COUNT(*) as total FROM urgence WHERE hopital_id = ?
");
$req->execute([$id]);
$nb_total = $req->fetch(PDO::FETCH_ASSOC)['total'];

$req = $bdd->prepare("
    SELECT COUNT(*) as total FROM urgence
    WHERE hopital_id = ? AND statut = 'OUVERTE'
");
$req->execute([$id]);
$nb_ouvertes = $req->fetch(PDO::FETCH_ASSOC)['total'];

$req = $bdd->prepare("
    SELECT COUNT(*) as total FROM urgence
    WHERE hopital_id = ? AND statut = 'EN_COURS'
");
$req->execute([$id]);
$nb_en_cours = $req->fetch(PDO::FETCH_ASSOC)['total'];

$req = $bdd->prepare("
    SELECT COUNT(*) as total FROM urgence
    WHERE hopital_id = ? AND statut = 'CLOTUREE'
");
$req->execute([$id]);
$nb_cloturees = $req->fetch(PDO::FETCH_ASSOC)['total'];

// Nombre de dons en attente de validation
$req = $bdd->prepare("
    SELECT COUNT(*) as total
    FROM participation p
    JOIN urgence u ON p.urgence_id = u.id
    WHERE u.hopital_id = ?
    AND p.statut = 'CONFIRME'
    AND p.don_valide = 0
");
$req->execute([$id]);
$nb_a_valider = $req->fetch(PDO::FETCH_ASSOC)['total'];

// Liste de toutes les urgences avec nb participants
$req = $bdd->prepare("
    SELECT u.id, u.groupe_sanguin_requis,
           u.niveau_priorite, u.quantite_requise,
           u.statut, u.date_declaration,
           COUNT(p.id) as nb_participants
    FROM urgence u
    LEFT JOIN participation p ON u.id = p.urgence_id
    WHERE u.hopital_id = ?
    GROUP BY u.id, u.groupe_sanguin_requis,
             u.niveau_priorite, u.quantite_requise,
             u.statut, u.date_declaration
    ORDER BY u.date_declaration DESC
");
$req->execute([$id]);
$urgences = $req->fetchAll(PDO::FETCH_ASSOC);

include '../views/header.php';
?>

<!-- En-tête -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-hospital-fill text-primary"></i>
            <?= htmlspecialchars($hopital['nom_etablissement']) ?>
        </h4>
        <small class="text-muted">
            <i class="bi bi-geo-alt-fill text-danger"></i>
            <?= htmlspecialchars($hopital['ville']) ?> —
            <?= htmlspecialchars($hopital['adresse']) ?>
        </small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="declarer_urgence.php" class="btn btn-danger fw-bold">
            <i class="bi bi-plus-circle"></i> Déclarer une urgence
        </a>
        <a href="valider_don.php" class="btn btn-success fw-bold">
            <i class="bi bi-check2-circle"></i>
            Valider les dons
            <?php if($nb_a_valider > 0): ?>
            <span class="badge bg-white text-success ms-1">
                <?= $nb_a_valider ?>
            </span>
            <?php endif; ?>
        </a>
    </div>
</div>

<!-- Statistiques -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <i class="bi bi-list-ul text-primary"
                   style="font-size:1.8rem;"></i>
                <h3 class="fw-bold text-primary mt-1"><?= $nb_total ?></h3>
                <small class="text-muted">Total urgences</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <i class="bi bi-exclamation-circle-fill text-danger"
                   style="font-size:1.8rem;"></i>
                <h3 class="fw-bold text-danger mt-1"><?= $nb_ouvertes ?></h3>
                <small class="text-muted">Ouvertes</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <i class="bi bi-hourglass-split text-warning"
                   style="font-size:1.8rem;"></i>
                <h3 class="fw-bold text-warning mt-1"><?= $nb_en_cours ?></h3>
                <small class="text-muted">En cours</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <i class="bi bi-check-circle-fill text-success"
                   style="font-size:1.8rem;"></i>
                <h3 class="fw-bold text-success mt-1"><?= $nb_cloturees ?></h3>
                <small class="text-muted">Clôturées</small>
            </div>
        </div>
    </div>
</div>

<!-- Alerte dons à valider -->
<?php if($nb_a_valider > 0): ?>
<div class="alert alert-success d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-check-circle-fill fs-3"></i>
    <div>
        <strong><?= $nb_a_valider ?> don(s) en attente de validation !</strong>
        <br>
        <small>
            Des donneurs ont confirmé leur participation.
            <a href="valider_don.php" class="fw-bold">
                Valider maintenant →
            </a>
        </small>
    </div>
</div>
<?php endif; ?>

<!-- Tableau des urgences -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 pb-2">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-list-ul text-danger"></i>
            Mes urgences déclarées
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if(count($urgences) == 0): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            Aucune urgence déclarée pour le moment.
        </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Groupe</th>
                    <th>Priorité</th>
                    <th>Quantité</th>
                    <th>Participants</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($urgences as $u):
                    $c = 'secondary';
                    if($u['niveau_priorite']=='CRITIQUE') $c='danger';
                    if($u['niveau_priorite']=='URGENT')   $c='warning';
                    if($u['niveau_priorite']=='NORMAL')   $c='success';
                    $sc = 'secondary';
                    if($u['statut']=='OUVERTE')   $sc='success';
                    if($u['statut']=='EN_COURS')  $sc='warning';
                    if($u['statut']=='CLOTUREE')  $sc='secondary';
                ?>
                <tr>
                    <td>
                        <span class="badge bg-danger fs-6">
                            <?= htmlspecialchars($u['groupe_sanguin_requis']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $c ?>">
                            <?= htmlspecialchars($u['niveau_priorite']) ?>
                        </span>
                    </td>
                    <td><?= $u['quantite_requise'] ?> poche(s)</td>
                    <td>
                        <span class="badge bg-primary rounded-pill">
                            <i class="bi bi-people-fill"></i>
                            <?= $u['nb_participants'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $sc ?>">
                            <?= htmlspecialchars($u['statut']) ?>
                        </span>
                    </td>
                    <td>
                        <small class="text-muted">
                            <?= date('d/m/Y',
                                strtotime($u['date_declaration'])) ?>
                        </small>
                    </td>
                    <td>
                        <?php if($u['statut'] == 'OUVERTE'
                              || $u['statut'] == 'EN_COURS'): ?>
                        <a href="scoring.php?urgence_id=<?= $u['id'] ?>"
                           class="btn btn-sm btn-outline-primary fw-bold">
                            <i class="bi bi-robot"></i> IA Scoring
                        </a>
                        <?php else: ?>
                        <span class="text-muted small">Clôturée</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../views/footer.php'; ?>