<?php
include '../config/connexion.php';
if(session_status() == PHP_SESSION_NONE) session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'DONNEUR') {
    header('Location: ../auth/login.php');
    exit();
}

$donneur_id = $_SESSION['user_id'];
$urgence_id = (int)($_GET['urgence_id'] ?? 0);
$message    = '';
$type_msg   = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if($action == 'CONFIRMER') {
        // Vérifie règle 56 jours
        $req = $bdd->prepare("
            SELECT dernier_don FROM donneur WHERE id = ?
        ");
        $req->execute([$donneur_id]);
        $d = $req->fetch(PDO::FETCH_ASSOC);

        $bloque = false;
        if($d['dernier_don']) {
            $jours = (int)(new DateTime())->diff(
                new DateTime($d['dernier_don']))->days;
            if($jours < 56) $bloque = true;
        }

        if($bloque) {
            $message  = "Vous ne pouvez pas donner avant 56 jours depuis votre dernier don.";
            $type_msg = 'danger';
        } else {
            // Insert ou update participation
            $req = $bdd->prepare("
                SELECT id FROM participation
                WHERE donneur_id = ? AND urgence_id = ?
            ");
            $req->execute([$donneur_id, $urgence_id]);

            if($req->fetch()) {
                $req = $bdd->prepare("
                    UPDATE participation
                    SET statut = 'CONFIRME', date_reponse = NOW()
                    WHERE donneur_id = ? AND urgence_id = ?
                ");
                $req->execute([$donneur_id, $urgence_id]);
            } else {
                $req = $bdd->prepare("
                    INSERT INTO participation
                    (donneur_id, urgence_id, statut, date_reponse)
                    VALUES (?, ?, 'CONFIRME', NOW())
                ");
                $req->execute([$donneur_id, $urgence_id]);
            }

            // Met à jour statut urgence
            $req = $bdd->prepare("
                UPDATE urgence SET statut = 'EN_COURS'
                WHERE id = ? AND statut = 'OUVERTE'
            ");
            $req->execute([$urgence_id]);

            // ============================================
            // ENVOI EMAIL AUTOMATIQUE À L'HÔPITAL
            // ============================================
            $req = $bdd->prepare("
                SELECT u_hop.email AS email_hop,
                       u_hop.nom  AS nom_hop,
                       h.nom_etablissement,
                       urg.groupe_sanguin_requis,
                       urg.niveau_priorite,
                       u_don.nom AS nom_donneur,
                       u_don.telephone AS tel_donneur
                FROM urgence urg
                JOIN hopital h ON urg.hopital_id = h.id
                JOIN utilisateur u_hop ON h.id = u_hop.id
                JOIN utilisateur u_don ON u_don.id = ?
                WHERE urg.id = ?
            ");
            $req->execute([$donneur_id, $urgence_id]);
            $info = $req->fetch(PDO::FETCH_ASSOC);

            if($info) {
                $sujet = "BloodBridge — Donneur confirmé : groupe "
                       . $info['groupe_sanguin_requis'];

                $corps  = "Bonjour " . $info['nom_hop'] . ",\n\n";
                $corps .= "Un donneur a confirmé sa participation à votre urgence.\n\n";
                $corps .= "═══════════════════════════════\n";
                $corps .= "Donneur    : " . $info['nom_donneur'] . "\n";
                $corps .= "Téléphone  : " . $info['tel_donneur'] . "\n";
                $corps .= "Groupe     : " . $info['groupe_sanguin_requis'] . "\n";
                $corps .= "Priorité   : " . $info['niveau_priorite'] . "\n";
                $corps .= "Hôpital    : " . $info['nom_etablissement'] . "\n";
                $corps .= "═══════════════════════════════\n\n";
                $corps .= "Connectez-vous sur BloodBridge pour valider le don.\n\n";
                $corps .= "BloodBridge — Connecter les donneurs aux hôpitaux\n";

                $headers  = "From: noreply@bloodbridge.tn\r\n";
                $headers .= "Reply-To: noreply@bloodbridge.tn\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                @mail($info['email_hop'], $sujet, $corps, $headers);
            }

            $message  = "Participation confirmée ! L'hôpital a été notifié par email.";
            $type_msg = 'success';
        }

    } elseif($action == 'REFUSER') {
        $req = $bdd->prepare("
            SELECT id FROM participation
            WHERE donneur_id = ? AND urgence_id = ?
        ");
        $req->execute([$donneur_id, $urgence_id]);

        if($req->fetch()) {
            $req = $bdd->prepare("
                UPDATE participation
                SET statut = 'REFUSE', date_reponse = NOW()
                WHERE donneur_id = ? AND urgence_id = ?
            ");
            $req->execute([$donneur_id, $urgence_id]);
        } else {
            $req = $bdd->prepare("
                INSERT INTO participation
                (donneur_id, urgence_id, statut, date_reponse)
                VALUES (?, ?, 'REFUSE', NOW())
            ");
            $req->execute([$donneur_id, $urgence_id]);
        }
        $message  = "Participation refusée.";
        $type_msg = 'warning';
    }
}

// Récupère infos urgence
$req = $bdd->prepare("
    SELECT urg.*, h.nom_etablissement, h.ville, h.adresse
    FROM urgence urg
    JOIN hopital h ON urg.hopital_id = h.id
    WHERE urg.id = ?
");
$req->execute([$urgence_id]);
$urgence = $req->fetch(PDO::FETCH_ASSOC);

// Vérifie participation existante
$statut_actuel = null;
if($urgence) {
    $req = $bdd->prepare("
        SELECT statut FROM participation
        WHERE donneur_id = ? AND urgence_id = ?
    ");
    $req->execute([$donneur_id, $urgence_id]);
    $part = $req->fetch(PDO::FETCH_ASSOC);
    if($part) $statut_actuel = $part['statut'];
}

include '../views/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-6 col-md-8">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h3 class="fw-bold mb-0">
            <i class="bi bi-check-circle-fill text-danger"></i>
            Confirmer ma participation
        </h3>
    </div>

    <?php if($message != ''): ?>
    <div class="alert alert-<?= $type_msg ?> d-flex align-items-center gap-2">
        <i class="bi bi-<?= $type_msg=='success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> fs-5"></i>
        <div>
            <?= htmlspecialchars($message) ?>
            <?php if($type_msg=='success'): ?>
            <a href="dashboard.php" class="fw-bold ms-1">
                → Retour au tableau de bord
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if($urgence): ?>

    <!-- Détails urgence -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pt-3">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-info-circle text-primary"></i>
                Détails de l'urgence
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6">
                    <small class="text-muted d-block">Hôpital</small>
                    <strong><?= htmlspecialchars($urgence['nom_etablissement']) ?></strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Ville</small>
                    <strong><?= htmlspecialchars($urgence['ville']) ?></strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Groupe sanguin requis</small>
                    <span class="badge bg-danger fs-5 py-2 px-3">
                        <?= htmlspecialchars($urgence['groupe_sanguin_requis']) ?>
                    </span>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Priorité</small>
                    <span class="badge bg-<?=
                        $urgence['niveau_priorite']=='CRITIQUE' ? 'danger' :
                        ($urgence['niveau_priorite']=='URGENT'  ? 'warning': 'primary')
                    ?> fs-6 py-2 px-3">
                        <?= htmlspecialchars($urgence['niveau_priorite']) ?>
                    </span>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Quantité requise</small>
                    <strong><?= $urgence['quantite_requise'] ?> poche(s)</strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Adresse</small>
                    <small><?= htmlspecialchars($urgence['adresse'] ?? '—') ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Info email -->
    <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-envelope-fill fs-5"></i>
        <small>
            En confirmant, un <strong>email automatique</strong>
            sera envoyé à l'hôpital avec vos coordonnées.
        </small>
    </div>

    <!-- Boutons si pas encore répondu -->
    <?php if($statut_actuel === null || $statut_actuel === 'EN_ATTENTE'): ?>
    <?php if($message == ''): ?>
    <form method="POST">
        <div class="d-flex gap-3">
            <button type="submit" name="action" value="CONFIRMER"
                    class="btn btn-danger btn-lg flex-fill fw-bold">
                <i class="bi bi-check-circle-fill"></i>
                Je confirme ma participation
            </button>
            <button type="submit" name="action" value="REFUSER"
                    class="btn btn-outline-secondary btn-lg">
                <i class="bi bi-x-circle"></i> Refuser
            </button>
        </div>
    </form>
    <?php endif; ?>

    <?php elseif($statut_actuel === 'CONFIRME'): ?>
    <div class="alert alert-success d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill fs-5"></i>
        Vous avez déjà confirmé cette participation.
    </div>

    <?php elseif($statut_actuel === 'REFUSE'): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2">
        <i class="bi bi-x-circle-fill fs-5"></i>
        Vous avez refusé cette participation.
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        Urgence introuvable.
    </div>
    <?php endif; ?>

</div>
</div>

<?php include '../views/footer.php'; ?>