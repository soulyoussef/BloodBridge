
<?php
if(session_status() == PHP_SESSION_NONE) session_start();

// Détecte si on est à la racine ou dans un sous-dossier
$profondeur = substr_count($_SERVER['PHP_SELF'], '/') - 1;
$racine = str_repeat('../', $profondeur - 1);
if($profondeur <= 1) $racine = '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BloodBridge — Don de Sang Intelligent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background-color: #C0392B !important; }
        .navbar-brand { font-size: 1.3rem; letter-spacing: 1px; }
        .nav-link { color: rgba(255,255,255,0.92) !important; font-weight: 500; }
        .nav-link:hover { color: #fff !important; }
        .card { border-radius: 12px; }
        .btn-danger { background-color: #C0392B; border-color: #C0392B; }
        .btn-danger:hover { background-color: #a93226; border-color: #a93226; }
        footer { background-color: #1a1a2e; }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .stat-icon {
            width: 56px; height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin: 0 auto 12px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-white"
           href="<?= $racine ?>index.php">
            <i class="bi bi-droplet-fill"></i> BloodBridge
        </a>
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= $racine ?>index.php">
                        <i class="bi bi-house"></i> Accueil
                    </a>
                </li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['role'] == 'DONNEUR'): ?>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="<?= $racine ?>donneur/dashboard.php">
                            <i class="bi bi-person-heart"></i> Mon Espace
                        </a>
                    </li>
                    <?php elseif($_SESSION['role'] == 'HOPITAL'): ?>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="<?= $racine ?>hopital/dashboard.php">
                            <i class="bi bi-hospital"></i> Mon Espace
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="<?= $racine ?>hopital/declarer_urgence.php">
                            <i class="bi bi-plus-circle"></i> Déclarer urgence
                        </a>
                    </li>
                    <?php elseif($_SESSION['role'] == 'ADMIN'): ?>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="<?= $racine ?>admin/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Administration
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="<?= $racine ?>admin/utilisateurs.php">
                            <i class="bi bi-people"></i> Utilisateurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="<?= $racine ?>admin/statistiques.php">
                            <i class="bi bi-bar-chart"></i> Statistiques
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link text-warning fw-bold"
                           href="<?= $racine ?>auth/logout.php">
                            <i class="bi bi-box-arrow-right"></i>
                            Déconnexion (<?= htmlspecialchars($_SESSION['nom']) ?>)
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="<?= $racine ?>auth/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="<?= $racine ?>auth/register.php">
                            <i class="bi bi-person-plus"></i> Inscription
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">