<?php
include '../config/connexion.php';
if(session_status() == PHP_SESSION_NONE) session_start();

$erreur  = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom       = trim($_POST['nom']);
    $email     = trim($_POST['email']);
    $mdp       = $_POST['mot_de_passe'];
    $mdp2      = $_POST['mdp_confirm'];
    $telephone = trim($_POST['telephone']);
    $role      = $_POST['role'];

    // Validation commune
    if(strlen($nom) < 3) {
        $erreur = "Le nom doit contenir au moins 3 caractères.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Email invalide.";
    } elseif(strlen($mdp) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif($mdp != $mdp2) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif(!preg_match('/^[0-9]{8}$/', $telephone)) {
        $erreur = "Le téléphone doit contenir exactement 8 chiffres.";
    } else {
        // Vérifie email existant
        $req = $bdd->prepare("SELECT id FROM utilisateur WHERE email = ?");
        $req->execute([$email]);
        if($req->fetch()) {
            $erreur = "Cet email est déjà utilisé.";
        } else {
            // Insère dans utilisateur
            $req = $bdd->prepare("
                INSERT INTO utilisateur
                (nom, email, mot_de_passe, telephone, role)
                VALUES (?, ?, ?, ?, ?)
            ");
            $req->execute([$nom, $email, md5($mdp), $telephone, $role]);
            $new_id = $bdd->lastInsertId();

            if($role == 'DONNEUR') {
                $groupe = $_POST['groupe_sanguin'] ?? 'O+';
                $ville  = trim($_POST['ville']      ?? '');
                $region = trim($_POST['region_don'] ?? '');

                if(empty($ville) || empty($region)) {
                    $erreur = "Gouvernorat et délégation obligatoires !";
                    $bdd->prepare("DELETE FROM utilisateur WHERE id = ?")
                        ->execute([$new_id]);
                } else {
                    $req = $bdd->prepare("
                        INSERT INTO donneur
                        (id, groupe_sanguin, ville, region)
                        VALUES (?, ?, ?, ?)
                    ");
                    $req->execute([$new_id, $groupe, $ville, $region]);
                    $success = "Inscription réussie ! Vous pouvez vous connecter.";
                }

            } elseif($role == 'HOPITAL') {
                $adresse   = trim($_POST['adresse']           ?? '');
                $ville     = trim($_POST['ville_hop']         ?? '');
                $region    = trim($_POST['region_hop']        ?? '');
                $type_etab = $_POST['type_etablissement']     ?? 'PUBLIC';

                if(empty($adresse) || empty($ville) || empty($region)) {
                    $erreur = "Adresse, gouvernorat et délégation obligatoires !";
                    $bdd->prepare("DELETE FROM utilisateur WHERE id = ?")
                        ->execute([$new_id]);
                } else {
                    $req = $bdd->prepare("
                        INSERT INTO hopital
                        (id, adresse, ville, region,
                         telephone, type_etablissement)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $req->execute([
                        $new_id, $adresse, $ville,
                        $region, $telephone, $type_etab
                    ]);
                    $success = "Inscription réussie ! Vous pouvez vous connecter.";
                }
            }
        }
    }
}

include '../views/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-7 col-md-9">
<div class="card border-0 shadow-sm">
<div class="card-body p-5">

    <div class="text-center mb-4">
        <div class="rounded-circle bg-danger bg-opacity-10
                    d-inline-flex align-items-center justify-content-center mb-3"
             style="width:70px;height:70px;font-size:2rem;">
            <i class="bi bi-person-plus-fill text-danger"></i>
        </div>
        <h3 class="fw-bold">Inscription</h3>
        <p class="text-muted">Rejoignez BloodBridge</p>
    </div>

    <?php if($erreur != ''): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <?= htmlspecialchars($erreur) ?>
    </div>
    <?php endif; ?>

    <?php if($success != ''): ?>
    <div class="alert alert-success d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div>
            <?= htmlspecialchars($success) ?>
            <a href="login.php" class="fw-bold ms-1">Se connecter</a>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST">

        <!-- Choix rôle -->
        <div class="mb-4">
            <label class="form-label fw-semibold">Je suis :</label>
            <div class="d-flex gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio"
                           name="role" value="DONNEUR" id="rDon"
                           checked onchange="toggleRole()">
                    <label class="form-check-label fw-semibold" for="rDon">
                        <i class="bi bi-droplet-fill text-danger"></i>
                        Donneur de sang
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio"
                           name="role" value="HOPITAL" id="rHop"
                           onchange="toggleRole()">
                    <label class="form-check-label fw-semibold" for="rHop">
                        <i class="bi bi-hospital-fill text-primary"></i>
                        Établissement hospitalier
                    </label>
                </div>
            </div>
        </div>

        <!-- Infos communes -->
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Nom complet <span class="text-danger">*</span>
                </label>
                <input type="text" name="nom" class="form-control"
                       value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                       placeholder="Ex: Youssef Souli" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Email <span class="text-danger">*</span>
                </label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="votre@email.com" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Mot de passe <span class="text-danger">*</span>
                </label>
                <input type="password" name="mot_de_passe"
                       class="form-control"
                       placeholder="Min. 6 caractères" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Confirmer mot de passe <span class="text-danger">*</span>
                </label>
                <input type="password" name="mdp_confirm"
                       class="form-control"
                       placeholder="Répétez le mot de passe" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Téléphone <span class="text-danger">*</span>
                </label>
                <input type="text" name="telephone" class="form-control"
                       value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>"
                       placeholder="8 chiffres ex: 22345678"
                       maxlength="8" required>
            </div>
        </div>

        <!-- ========== SECTION DONNEUR ========== -->
        <div id="secDonneur">
            <hr>
            <h6 class="fw-bold text-danger mb-3">
                <i class="bi bi-droplet-fill"></i> Informations Donneur
            </h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Groupe sanguin <span class="text-danger">*</span>
                    </label>
                    <select name="groupe_sanguin" class="form-select">
                        <?php
                        foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g)
                            echo "<option value='$g'>$g</option>";
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Gouvernorat <span class="text-danger">*</span>
                    </label>
                    <select name="ville" id="ville_don" class="form-select"
                            onchange="majRegions('ville_don','region_don')">
                        <option value="">-- Choisir --</option>
                        <?php
                        $villes = ['Tunis','Ariana','Ben Arous','Manouba',
                                   'Sfax','Sousse','Monastir','Bizerte',
                                   'Nabeul','Kairouan','Gabès','Gafsa',
                                   'Médenine','Kasserine','Sidi Bouzid',
                                   'Jendouba','Béja','Le Kef','Siliana',
                                   'Zaghouan','Tataouine','Tozeur','Kébili'];
                        foreach($villes as $v)
                            echo "<option value='$v'>$v</option>";
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Délégation <span class="text-danger">*</span>
                    </label>
                    <select name="region_don" id="region_don" class="form-select">
                        <option value="">-- Choisir d'abord --</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- ========== SECTION HÔPITAL ========== -->
        <div id="secHopital" style="display:none;">
            <hr>
            <h6 class="fw-bold text-primary mb-3">
                <i class="bi bi-hospital-fill"></i> Informations Hôpital
            </h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Adresse <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="adresse" class="form-control"
                           placeholder="Ex: Bd 9 Avril, Tunis">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Type d'établissement <span class="text-danger">*</span>
                    </label>
                    <select name="type_etablissement" class="form-select">
                        <option value="PUBLIC">🏛️ Public</option>
                        <option value="PRIVE">🏢 Privé</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Gouvernorat <span class="text-danger">*</span>
                    </label>
                    <select name="ville_hop" id="ville_hop" class="form-select"
                            onchange="majRegions('ville_hop','region_hop')">
                        <option value="">-- Choisir --</option>
                        <?php
                        foreach($villes as $v)
                            echo "<option value='$v'>$v</option>";
                        ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Délégation <span class="text-danger">*</span>
                    </label>
                    <select name="region_hop" id="region_hop" class="form-select">
                        <option value="">-- Choisir d'abord --</option>
                    </select>
                </div>
            </div>
        </div>

        <button type="submit"
                class="btn btn-danger w-100 fw-bold py-3 mt-4">
            <i class="bi bi-person-check-fill"></i> S'inscrire
        </button>

        <div class="text-center mt-3">
            <small class="text-muted">
                Déjà inscrit ?
                <a href="login.php" class="text-danger fw-bold">
                    Se connecter
                </a>
            </small>
        </div>

    </form>
</div>
</div>
</div>
</div>

<script>
// Délégations par gouvernorat
const delegations = {
    'Tunis': ['La Médina','Bab El Bhar','Bab Souika','El Omrane',
              'El Omrane Supérieur','El Menzah','El Manar 1','El Manar 2',
              'Cité El Khadra','La Marsa','Carthage','Le Bardo',
              'La Goulette','Le Kram','Séjoumi','Sidi Hassine',
              'Kabaria','Hrairia','Ettahrir','Djebel Jelloud'],
    'Ariana': ['Ariana Ville','Soukra','Raoued',
               'Kalâat el-Andalous','Sidi Thabet',
               'Mnihla','Ettadhamen','Borj Louzir'],
    'Ben Arous': ['Ben Arous','Bou Mhel el-Bassatine',
                  'El Mourouj','Ezzahra','Fouchana',
                  'Hammam Lif','Hammam Chott','Mégrine',
                  'Mohamedia','Radès','Nouvelle Médina'],
    'Manouba': ['Manouba','Den Den','Douar Hicher',
                'Oued Ellil','Tebourba','El Battan',
                'Borj El Amri','Djedeida'],
    'Sfax': ['Sfax Ville','Sakiet Ezzit','Sakiet Eddaïer',
             'Sfax Ouest','Sfax Sud','Thyna','Agareb',
             'Bir Ali Ben Khalifa','Skhira','Graïba',
             'El Hencha','Menzel Chaker'],
    'Sousse': ['Sousse Ville','Sousse Riadh','Sousse Jawhara',
               'Sousse Sidi Abdelhamid','Hammam Sousse',
               'Akouda','Kalaa Kebira','Msaken',
               'Enfidha','Hergla','Sidi Bou Ali'],
    'Monastir': ['Monastir','Ksibet el-Médiouni','Zeramdine',
                 'Bembla','Jammel','Moknine','Ksar Hellal',
                 'Teboulba','Bekalta','Sahline','Ouerdanine'],
    'Bizerte': ['Bizerte Nord','Bizerte Sud','Bizerte Ville',
                'Menzel Bourguiba','Mateur','Sejnane',
                'Ras Jebel','Ghar el-Melh','Utique',
                'El Alia','Tinja','Joumine'],
    'Nabeul': ['Nabeul','Hammamet','Kelibia','Grombalia',
               'Soliman','Menzel Temime','Korba',
               'Dar Chaabane','El Haouaria','Takelsa',
               'Beni Khalled','Menzel Bouzelfa'],
    'Kairouan': ['Kairouan Nord','Kairouan Sud','Sbikha',
                 'Oueslatia','Haffouz','Hajeb El Ayoun',
                 'Nasrallah','El Alaa','Chebika'],
    'Gabès': ['Gabès Ville','Gabès Ouest','Gabès Sud',
              'Mareth','El Hamma','Metouia',
              'Nouvelle Matmata','Matmata','Menzel El Habib'],
    'Gafsa': ['Gafsa Nord','Gafsa Sud','Métlaoui',
              'El Ksar','Redeyef','Moulares',
              'Sened','Belkhir','Sidi Aïch'],
    'Médenine': ['Médenine Nord','Médenine Sud','Ben Gardane',
                 'Zarzis','Houmt Souk','Midoun',
                 'Beni Khedache','Smar'],
    'Kasserine': ['Kasserine Nord','Kasserine Sud','Ezzouhour',
                  'Hassi El Frid','Sbeitla','Sbiba',
                  'Djedeliane','El Ayoun','Foussana'],
    'Sidi Bouzid': ['Sidi Bouzid Est','Sidi Bouzid Ouest',
                    'Jelma','Cebbala Ouled Asker','Bir El Hafey',
                    'Sidi Ali Ben Aoun','Menzel Bouzaïane',
                    'Meknassy','Souk Jedid'],
    'Jendouba': ['Jendouba','Jendouba Nord','Bou Salem',
                 'Tabarka','Aïn Draham','Fernana',
                 'Ghardimaou','Oued Meliz','Balta Bou Aouane'],
    'Béja': ['Béja Nord','Béja Sud','Amdoun',
             'Nefza','Téboursouk','Thibar',
             'Testour','Goubellat','Medjez el-Bab'],
    'Le Kef': ['Le Kef Ouest','Le Kef Est','Nebeur',
               'Sakiet Sidi Youssef','Tajerouine','Kalaat Senan',
               'Kalaat Khasba','Jerid','Dahmani','Sers'],
    'Siliana': ['Siliana Nord','Siliana Sud','Bou Arada',
                'Gaâfour','El Krib','Sidi Bou Rouis',
                'Makthar','Rouhia','Kesra','Bargou'],
    'Zaghouan': ['Zaghouan','Zriba','Djebel Oust',
                 'El Fahs','Nadhour','Saouaf'],
    'Tataouine': ['Tataouine Nord','Tataouine Sud',
                  'Dehiba','Ghomrassen','Bir Lahmar',
                  'Remada','Smâr'],
    'Tozeur': ['Tozeur','Degache','Tamerza',
               'Hazoua','Nefta'],
    'Kébili': ['Kébili Nord','Kébili Sud','Souk Lahad',
               'Douz Nord','Douz Sud','El Faouar']
};

function majRegions(idVille, idRegion) {
    const ville  = document.getElementById(idVille).value;
    const selReg = document.getElementById(idRegion);
    selReg.innerHTML = '';

    if(ville && delegations[ville]) {
        selReg.innerHTML = '<option value="">-- Choisir --</option>';
        delegations[ville].forEach(function(r) {
            const opt = document.createElement('option');
            opt.value = r;
            opt.textContent = r;
            selReg.appendChild(opt);
        });
    } else {
        selReg.innerHTML = '<option value="">-- Choisir d\'abord --</option>';
    }
}

function toggleRole() {
    const role = document.querySelector('input[name="role"]:checked').value;
    document.getElementById('secDonneur').style.display =
        role == 'DONNEUR' ? 'block' : 'none';
    document.getElementById('secHopital').style.display =
        role == 'HOPITAL' ? 'block' : 'none';
}
</script>

<?php include '../views/footer.php'; ?>