<?php
include '../config/connexion.php';
if(session_status() == PHP_SESSION_NONE) session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
    header('Location: ../auth/login.php');
    exit();
}

$search      = trim($_GET['search'] ?? '');
$par_page    = 10;
$page_actuel = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($page_actuel - 1) * $par_page;

// Compte total
if($search != '') {
    $like = "%$search%";
    $req  = $bdd->prepare("
        SELECT COUNT(*) as total FROM utilisateur
        WHERE nom LIKE ? OR email LIKE ? OR role LIKE ?
    ");
    $req->execute([$like, $like, $like]);
} else {
    $req = $bdd->query("SELECT COUNT(*) as total FROM utilisateur");
}
$total_lignes = (int)$req->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages  = max(1, ceil($total_lignes / $par_page));

// Récupère les utilisateurs — LIMIT et OFFSET en PARAM_INT
if($search != '') {
    $like = "%$search%";
    $req  = $bdd->prepare("
        SELECT * FROM utilisateur
        WHERE nom LIKE ? OR email LIKE ? OR role LIKE ?
        ORDER BY id DESC
        LIMIT :limite OFFSET :offset
    ");
    $req->bindValue(':limite', $par_page, PDO::PARAM_INT);
    $req->bindValue(':offset', $offset,   PDO::PARAM_INT);
    $req->bindValue(1, $like);
    $req->bindValue(2, $like);
    $req->bindValue(3, $like);
    $req->execute();
} else {
    $req = $bdd->prepare("
        SELECT * FROM utilisateur
        ORDER BY id DESC
        LIMIT :limite OFFSET :offset
    ");
    $req->bindValue(':limite', $par_page, PDO::PARAM_INT);
    $req->bindValue(':offset', $offset,   PDO::PARAM_INT);
    $req->execute();
}
$utilisateurs = $req->fetchAll(PDO::FETCH_ASSOC);

include '../views/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="fw-bold mb-0">
                <i class="bi bi-people-fill text-primary"></i>
                Gestion des utilisateurs
            </h4>
            <small class="text-muted">
                Total : <?= $total_lignes ?> utilisateur(s)
            </small>
        </div>
    </div>
</div>

<!-- Recherche -->
<form method="GET" class="mb-4">
    <div class="input-group shadow-sm">
        <span class="input-group-text bg-white border-end-0">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text" name="search"
               class="form-control border-start-0"
               placeholder="Rechercher par nom, email ou rôle..."
               value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-danger px-4">
            Rechercher
        </button>
        <?php if($search != ''): ?>
        <a href="utilisateurs.php" class="btn btn-outline-secondary">
            <i class="bi bi-x-lg"></i>
        </a>
        <?php endif; ?>
    </div>
</form>

<!-- Tableau -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">#</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Rôle</th>
                    <th>Ville</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($utilisateurs) == 0): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-search"
                           style="font-size:2.5rem;"></i>
                        <p class="mt-2 mb-0">Aucun utilisateur trouvé.</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach($utilisateurs as $i => $u): ?>
                <tr>
                    <td class="ps-4 text-muted fw-bold">
                        <?= $offset + $i + 1 ?>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle d-inline-flex
                                        align-items-center justify-content-center
                                        text-white fw-bold flex-shrink-0"
                                 style="width:38px;height:38px;
                                        font-size:0.9rem;
                                        background:<?=
                                            $u['role']=='ADMIN'    ? '#C0392B' :
                                            ($u['role']=='HOPITAL' ? '#0d6efd' : '#198754')
                                        ?>;">
                                <?= strtoupper(substr($u['nom'], 0, 1)) ?>
                            </div>
                            <strong>
                                <?= htmlspecialchars($u['nom']) ?>
                            </strong>
                        </div>
                    </td>
                    <td>
                        <small class="text-muted">
                            <?= htmlspecialchars($u['email']) ?>
                        </small>
                    </td>
                    <td>
                        <small>
                            <?= htmlspecialchars($u['telephone'] ?? '—') ?>
                        </small>
                    </td>
                    <td>
                        <span class="badge py-2 px-3 bg-<?=
                            $u['role']=='ADMIN'    ? 'danger'  :
                            ($u['role']=='HOPITAL' ? 'primary' : 'success')
                        ?>">
                            <i class="bi bi-<?=
                                $u['role']=='ADMIN'    ? 'shield-fill'   :
                                ($u['role']=='HOPITAL' ? 'hospital-fill' : 'person-heart')
                            ?> me-1"></i>
                            <?= $u['role'] ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        if($u['role'] == 'DONNEUR') {
                            $rv = $bdd->prepare(
                                "SELECT ville FROM donneur WHERE id = ?"
                            );
                            $rv->execute([$u['id']]);
                            $row = $rv->fetch(PDO::FETCH_ASSOC);
                            echo '<small class="text-muted">'
                                . htmlspecialchars($row['ville'] ?? '—')
                                . '</small>';
                        } elseif($u['role'] == 'HOPITAL') {
                            $rv = $bdd->prepare(
                                "SELECT ville FROM hopital WHERE id = ?"
                            );
                            $rv->execute([$u['id']]);
                            $row = $rv->fetch(PDO::FETCH_ASSOC);
                            echo '<small class="text-muted">'
                                . htmlspecialchars($row['ville'] ?? '—')
                                . '</small>';
                        } else {
                            echo '<small class="text-muted">—</small>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if($total_pages > 1): ?>
    <div class="card-footer bg-white border-0 py-3">
        <div class="d-flex justify-content-between
                    align-items-center flex-wrap gap-2">

            <small class="text-muted">
                Affichage
                <?= $offset + 1 ?> –
                <?= min($offset + $par_page, $total_lignes) ?>
                sur <?= $total_lignes ?> utilisateur(s)
            </small>

            <nav>
                <ul class="pagination pagination-sm mb-0 gap-1">

                    <!-- Précédent -->
                    <li class="page-item
                        <?= $page_actuel == 1 ? 'disabled' : '' ?>">
                        <a class="page-link rounded"
                           href="?page=<?= $page_actuel - 1 ?>&search=<?= urlencode($search) ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <!-- Numéros de pages -->
                    <?php for($p = 1; $p <= $total_pages; $p++): ?>
                    <?php if(
                        $p == 1
                        || $p == $total_pages
                        || abs($p - $page_actuel) <= 2
                    ): ?>
                    <li class="page-item
                        <?= $p == $page_actuel ? 'active' : '' ?>">
                        <a class="page-link rounded"
                           style="<?= $p == $page_actuel
                               ? 'background:#C0392B;border-color:#C0392B;color:white;'
                               : '' ?>"
                           href="?page=<?= $p ?>&search=<?= urlencode($search) ?>">
                            <?= $p ?>
                        </a>
                    </li>
                    <?php elseif(abs($p - $page_actuel) == 3): ?>
                    <li class="page-item disabled">
                        <span class="page-link border-0">…</span>
                    </li>
                    <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Suivant -->
                    <li class="page-item
                        <?= $page_actuel == $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link rounded"
                           href="?page=<?= $page_actuel + 1 ?>&search=<?= urlencode($search) ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>

                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>

    <div class="card-footer bg-white border-top-0 pb-3 pt-0">
        <small class="text-muted fst-italic">
            <i class="bi bi-info-circle text-primary"></i>
            Les suppressions de comptes ne sont pas autorisées
            depuis cette interface.
        </small>
    </div>
</div>

<?php include '../views/footer.php'; ?>