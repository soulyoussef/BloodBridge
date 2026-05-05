
<?php
include '../../config/connexion.php';
if(session_status() == PHP_SESSION_NONE) session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'HOPITAL') {
    header('Location: ../auth/login.php');
    exit();
}

$hopital_id = $_SESSION['user_id'];
$message    = '';
$type_msg   = '';

// Traitement validation
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $participation_id = (int)$_POST['participation_id'];
    $donneur_id       = (int)$_POST['donneur_id'];
    $urgence_id       = (int)$_POST['urgence_id'];

    // 1. Valide la participation
    $req = $bdd->prepare("
        UPDATE participation
        SET don_valide = 1,
            statut = 'DON_VALIDE',
            date_confirmation = NOW()
        WHERE id = ?
    ");
    $req->execute([$participation_id]);

    // 2. Met à jour le profil du donneur
    $req = $bdd->prepare("
        UPDATE donneur
        SET dernier_don    = NOW(),
            nb_dons_totaux = nb_dons_totaux + 1,
            disponible     = 0
        WHERE id = ?
    ");
    $req->execute([$donneur_id]);

    // 3. Clôture l'urgence
    $req = $bdd->prepare("
        UPDATE urgence
        SET statut = 'CLOTUREE', date_cloture = NOW()
        WHERE id = ?
    ");
    $req->execute([$urgence_id]);

    // 4. Attribue un badge si nécessaire
    $req = $bdd->prepare("
        SELECT nb_dons_totaux FROM donneur WHERE id = ?
    ");
    $req->execute([$donneur_id]);
    $nb_dons = $req->fetch(PDO::FETCH_ASSOC)['nb_dons_totaux'];

    $req = $bdd->prepare("
        SELECT id FROM badge WHERE seuil_dons = ? LIMIT 1
    ");
    $req->execute([$nb_dons]);
    $badge = $req->fetch(PDO::FETCH_ASSOC);

    if($badge) {
        // Vérifie qu'il n'a pas déjà ce badge
        $req = $bdd->prepare("
            SELECT id FROM donneur_badge
            WHERE donneur_id = ? AND badge_id = ?
        ");
        $req->execute([$donneur_id, $badge['id']]);
        if(!$req->fetch()) {
            $req = $bdd->prepare("
                INSERT INTO donneur_badge
                (donneur_id, badge_id, date_obtention)
                VALUES (?, ?, NOW())
            ");
            $req->execute([$donneur_id, $badge['id']]);
        }
    }

    // 5. Envoie email de confirmation au donneur
    $req = $bdd->prepare("
        SELECT u.email, u.nom,
               h.nom_etablissement
        FROM utilisateur u
        JOIN utilisateur uh ON uh.id = ?
        JOIN hopital h ON h.id = uh.id
        WHERE u.id = ?
    ");
    $req->execute([$hopital_id, $donneur_id]);
    $info = $req->fetch(PDO::FETCH_ASSOC);

    if($info) {
        $sujet = "BloodBridge — Merci pour votre don !";
        $corps  = "Bonjour " . $info['nom'] . ",\n\n";
        $corps .= "Votre don de sang a été validé avec succès !\n\n";
        $corps .= "Hôpital : " . $info['nom_etablissement'] . "\n";
        $corps .= "Date    : " . date('d/m/Y H:i') . "\n\n";
        $corps .= "Grâce à vous, des vies ont été sauvées.\n";
        $corps .= "Merci de votre générosité !\n\n";
        $corps .= "BloodBridge — Connecter les donneurs aux hôpitaux\n";
        $headers = "From: noreply@bloodbridge.tn\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        @mail($info['email_hop'], $sujet, $corps, $headers);
    }

    $message  = "Don validé avec succès ! Le donneur a été notifié.";
    $type_msg = 'success';
}

// Récupère les participations confirmées pour cet hôpital
$req = $bdd->prepare("
    SELECT p.id AS participation_id,
           p.donneur_id, p.urgence_id,
           p.score_compatibilite, p.date_reponse,
           u.nom AS nom_donneur,
           u.telephone AS tel_donneur,
           d.groupe_sanguin,
           urg.groupe_sanguin_requis,
           urg.niveau_priorite,
           urg.id AS urgence_id
    FROM participation p
    JOIN donneur d ON p.donneur_id = d.id
    JOIN utilisateur u ON d.id = u.id
    JOIN urgence urg ON p.urgence_id = urg.id
    WHERE urg.hopital_id = ?
    AND p.statut = 'CONFIRME'
    AND p.don_valide = 0
    ORDER BY p.date_reponse DESC
");
$req->execute([$hopital_id]);
$participations = $req->fetchAll(PDO::FETCH_ASSOC);

include '../../views/header.php';
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
    <h4 class="fw-bold mb-0">
        <i class="bi bi-check-circle-fill text-success"></i>
        Valider les dons
    </h4>
</div>

<?php if($message != ''): ?>
<div class="alert alert-<?= $type_msg ?> d-flex align-items-center gap-2">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<?php if(count($participations) == 0): ?>
<div class="alert alert-info text-center py-4">
    <i class="bi bi-info-circle-fill fs-3 d-block mb-2"></i>
    Aucun don en attente de validation pour le moment.
    <br><small class="text-muted">
        Les dons apparaissent ici quand un donneur confirme sa participation.
    </small>
</div>
<?php else: ?>

<div class="row g-3">
    <?php foreach($participations as $p): ?>
    <?php
        $c = 'secondary';
        if($p['niveau_priorite']=='CRITIQUE') $c='danger';
        if($p['niveau_priorite']=='URGENT')   $c='warning';
        if($p['niveau_priorite']=='NORMAL')   $c='success';
    ?>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm border-start border-<?= $c ?> border-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="fw-bold mb-1">
                            <?= htmlspecialchars($p['nom_donneur']) ?>
                        </h5>
                        <span class="badge bg-danger me-1">
                            <?= $p['groupe_sanguin'] ?>
                        </span>
                        <span class="badge bg-<?= $c ?>">
                            <?= $p['niveau_priorite'] ?>
                        </span>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-primary fs-5">
                            <?= $p['score_compatibilite'] ?>
                            <small class="text-muted fs-6">/100</small>
                        </div>
                        <small class="text-muted">Score IA</small>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Téléphone</small>
                        <strong>
                            <i class="bi bi-telephone text-success"></i>
                            <?= htmlspecialchars($p['tel_donneur']) ?>
                        </strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Groupe requis</small>
                        <span class="badge bg-danger fs-6">
                            <?= $p['groupe_sanguin_requis'] ?>
                        </span>
                    </div>
                    <div class="col-12">
                        <small class="text-muted d-block">Confirmé le</small>
                        <small>
                            <?= date('d/m/Y à H:i',
                                strtotime($p['date_reponse'])) ?>
                        </small>
                    </div>
                </div>

                <!-- Bouton validation -->
                <form method="POST">
                    <input type="hidden" name="participation_id"
                           value="<?= $p['participation_id'] ?>">
                    <input type="hidden" name="donneur_id"
                           value="<?= $p['donneur_id'] ?>">
                    <input type="hidden" name="urgence_id"
                           value="<?= $p['urgence_id'] ?>">
                    <button type="submit"
                            class="btn btn-success w-100 fw-bold"
                            onclick="return confirm(
                                'Confirmer que le don a bien été effectué ?'
                            )">
                        <i class="bi bi-check2-circle"></i>
                        Valider le don effectué
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include '../../views/footer.php'; ?>