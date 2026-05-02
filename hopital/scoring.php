
<?php
include '../config/connexion.php';
if(session_status() == PHP_SESSION_NONE) session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'HOPITAL') {
    header('Location: ../auth/login.php');
    exit();
}

// Récupère l'ID urgence depuis l'URL
$urgence_id = (int)($_GET['urgence_id'] ?? 0);

if($urgence_id == 0) {
    header('Location: dashboard.php');
    exit();
}

// Récupère les infos de l'urgence
$req = $bdd->prepare("
    SELECT u.*, h.nom_etablissement, h.ville
    FROM urgence u
    JOIN hopital h ON u.hopital_id = h.id
    WHERE u.id = ?
");
$req->execute([$urgence_id]);
$urgence = $req->fetch(PDO::FETCH_ASSOC);

if(!$urgence) {
    header('Location: dashboard.php');
    exit();
}

// =============================================
// TABLE DE COMPATIBILITÉ GROUPES SANGUINS
// Qui peut donner à qui ?
// =============================================
$compatibilite = [
    'O-'  => ['O-','O+','A-','A+','B-','B+','AB-','AB+'],
    'O+'  => ['O+','A+','B+','AB+'],
    'A-'  => ['A-','A+','AB-','AB+'],
    'A+'  => ['A+','AB+'],
    'B-'  => ['B-','B+','AB-','AB+'],
    'B+'  => ['B+','AB+'],
    'AB-' => ['AB-','AB+'],
    'AB+' => ['AB+'],
];

// Récupère tous les donneurs disponibles
$req = $bdd->query("
    SELECT u.id, u.nom, u.telephone,
           d.groupe_sanguin, d.ville, d.region,
           d.disponible, d.dernier_don,
           d.nb_dons_totaux
    FROM donneur d
    JOIN utilisateur u ON d.id = u.id
    WHERE d.disponible = 1
");
$tous_donneurs = $req->fetchAll(PDO::FETCH_ASSOC);

// =============================================
// ALGORITHME IA — CALCUL DU SCORE
// Score total = 100 points répartis sur 4 critères
// =============================================
$resultats = [];

foreach($tous_donneurs as $d) {

    // Critère 1 — Compatibilité groupe sanguin (40 points)
    // On vérifie si le donneur peut donner au groupe requis
    $groupes_recevables = $compatibilite[$d['groupe_sanguin']] ?? [];
    if(!in_array($urgence['groupe_sanguin_requis'], $groupes_recevables)) {
        continue; // Pas compatible → on ignore ce donneur
    }
    $score_groupe = 40;

    // Critère 2 — Disponibilité (20 points)
    $score_dispo = ($d['disponible'] == 1) ? 20 : 0;

    // Critère 3 — Délai depuis dernier don (20 points)
    // Règle médicale : minimum 56 jours entre deux dons
    if($d['dernier_don'] == null) {
        $score_delai = 20; // Jamais donné → disponible immédiatement
    } else {
        $jours = (int)(new DateTime())->diff(
            new DateTime($d['dernier_don'])
        )->days;
        if($jours >= 90)     $score_delai = 20; // Plus de 3 mois
        elseif($jours >= 56) $score_delai = 10; // Entre 56 et 90 jours
        else                 $score_delai = 0;  // Moins de 56 jours — inéligible
    }

    // Critère 4 — Expérience / nb dons passés (20 points)
    if($d['nb_dons_totaux'] >= 10)    $score_exp = 20;
    elseif($d['nb_dons_totaux'] >= 5) $score_exp = 15;
    elseif($d['nb_dons_totaux'] >= 1) $score_exp = 10;
    else                              $score_exp = 5;

    // Score final
    $score_total = $score_groupe + $score_dispo
                 + $score_delai + $score_exp;

    // Sauvegarde le score dans la BD
    $req = $bdd->prepare("
        UPDATE donneur SET score = ? WHERE id = ?
    ");
    $req->execute([$score_total, $d['id']]);

    // Ajoute à la liste des résultats
    $d['score_total']  = $score_total;
    $d['score_groupe'] = $score_groupe;
    $d['score_dispo']  = $score_dispo;
    $d['score_delai']  = $score_delai;
    $d['score_exp']    = $score_exp;
    $d['jours_depuis'] = ($d['dernier_don'] != null)
        ? (int)(new DateTime())->diff(
            new DateTime($d['dernier_don']))->days
        : null;

    $resultats[] = $d;
}

// Trie par score décroissant
usort($resultats, function($a, $b) {
    return $b['score_total'] - $a['score_total'];
});

include '../views/header.php';
?>

<!-- En-tête -->
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
    <h4 class="fw-bold mb-0">
        <i class="bi bi-robot text-primary"></i>
        Algorithme IA — Scoring des Donneurs
    </h4>
</div>

<!-- Infos urgence -->
<?php
    $c = 'secondary';
    if($urgence['niveau_priorite']=='CRITIQUE') $c='danger';
    if($urgence['niveau_priorite']=='URGENT')   $c='warning';
    if($urgence['niveau_priorite']=='NORMAL')   $c='success';
?>
<div class="alert alert-<?= $c ?> d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
    <div>
        <strong>Urgence <?= htmlspecialchars($urgence['niveau_priorite']) ?></strong>
        — Groupe <strong><?= htmlspecialchars($urgence['groupe_sanguin_requis']) ?></strong>
        — <?= $urgence['quantite_requise'] ?> poche(s) requise(s)
        <br>
        <small>
            <?= htmlspecialchars($urgence['nom_etablissement']) ?> —
            <?= htmlspecialchars($urgence['ville']) ?>
        </small>
    </div>
</div>

<!-- Explication algorithme -->
<div class="card border-0 shadow-sm mb-4"
     style="background:linear-gradient(135deg,#f0f4ff,#fff);">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-3">
            <i class="bi bi-info-circle-fill text-primary"></i>
            Comment fonctionne l'algorithme ?
        </h6>
        <div class="row g-2">
            <div class="col-6 col-md-3">
                <div class="p-2 rounded text-center bg-danger bg-opacity-10">
                    <strong class="text-danger d-block">40 pts</strong>
                    <small>Groupe sanguin compatible</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 rounded text-center bg-success bg-opacity-10">
                    <strong class="text-success d-block">20 pts</strong>
                    <small>Disponibilité confirmée</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 rounded text-center bg-warning bg-opacity-10">
                    <strong class="text-warning d-block">20 pts</strong>
                    <small>Délai depuis dernier don</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 rounded text-center bg-primary bg-opacity-10">
                    <strong class="text-primary d-block">20 pts</strong>
                    <small>Expérience (nb dons)</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Résultats -->
<?php if(count($resultats) == 0): ?>
<div class="alert alert-warning text-center py-4">
    <i class="bi bi-exclamation-triangle-fill fs-2 d-block mb-2"></i>
    <strong>Aucun donneur compatible trouvé !</strong>
    <br>
    <small class="text-muted">
        Aucun donneur disponible avec un groupe sanguin
        compatible pour cette urgence.
    </small>
</div>
<?php else: ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 pb-2">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-trophy-fill text-warning"></i>
            <?= count($resultats) ?> donneur(s) compatible(s) — trié(s) par score
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Rang</th>
                    <th>Donneur</th>
                    <th>Groupe</th>
                    <th>Ville</th>
                    <th>Dons</th>
                    <th>Dernier don</th>
                    <th>Score IA</th>
                    <th>Téléphone</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($resultats as $i => $d): ?>
                <tr class="<?= $i == 0 ? 'table-success' : '' ?>">
                    <td class="text-center fw-bold">
                        <?php if($i == 0): ?>
                        <i class="bi bi-trophy-fill text-warning fs-5"></i>
                        <?php elseif($i == 1): ?>
                        <span class="text-secondary fw-bold">2</span>
                        <?php elseif($i == 2): ?>
                        <span class="text-danger fw-bold">3</span>
                        <?php else: ?>
                        <span class="text-muted"><?= $i+1 ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong>
                            <?= htmlspecialchars($d['nom']) ?>
                        </strong>
                        <?php if($i == 0): ?>
                        <span class="badge bg-success ms-1">
                            Meilleur match
                        </span>
                        <?php endif; ?>
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
                    <td>
                        <small class="text-muted">
                            <?php if($d['dernier_don']): ?>
                            <?= date('d/m/Y', strtotime($d['dernier_don'])) ?>
                            <br>
                            <span class="text-<?=
                                $d['jours_depuis'] >= 56 ? 'success' : 'danger'
                            ?>">
                                <?= $d['jours_depuis'] ?> jours
                            </span>
                            <?php else: ?>
                            <span class="text-success">
                                Jamais donné
                            </span>
                            <?php endif; ?>
                        </small>
                    </td>
                    <td>
                        <!-- Barre de score -->
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1"
                                 style="height:10px; min-width:80px;">
                                <div class="progress-bar bg-<?=
                                    $d['score_total'] >= 80 ? 'success' :
                                    ($d['score_total'] >= 60 ? 'warning' : 'danger')
                                ?>"
                                style="width:<?= $d['score_total'] ?>%">
                                </div>
                            </div>
                            <strong class="text-<?=
                                $d['score_total'] >= 80 ? 'success' :
                                ($d['score_total'] >= 60 ? 'warning' : 'danger')
                            ?>">
                                <?= $d['score_total'] ?>/100
                            </strong>
                        </div>
                        <!-- Détail des scores -->
                        <small class="text-muted d-block mt-1">
                            Grp:<?= $d['score_groupe'] ?>
                            | Dispo:<?= $d['score_dispo'] ?>
                            | Délai:<?= $d['score_delai'] ?>
                            | Exp:<?= $d['score_exp'] ?>
                        </small>
                    </td>
                    <td>
                        <a href="tel:<?= htmlspecialchars($d['telephone']) ?>"
                           class="btn btn-sm btn-outline-success">
                            <i class="bi bi-telephone-fill"></i>
                            <?= htmlspecialchars($d['telephone']) ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../views/footer.php'; ?>