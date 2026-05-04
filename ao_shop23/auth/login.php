<?php

session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lang.php';

// Redirection si déjà connecté
if (isset($_SESSION['id_user'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../client/index.php');
    }
    exit;
}

$error = '';
$active_tab = 'login'; // 'login' ou 'register'

//  Traitement du formulaire 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    //  Connexion 
    if ($action === 'login') {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || empty($password)) {
            $error = 'Veuillez remplir tous les champs.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                // Vérification rétrocompatible pour les hash de test
                if (str_starts_with($user['mot_de_passe'], '$2y$')) {
                    $_SESSION['id_user'] = $user['id_user'];
                    $_SESSION['nom'] = $user['nom'];
                    $_SESSION['prenom'] = $user['prenom'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['solde'] = $user['solde'];

                    if ($user['role'] === 'admin') {
                        header('Location: ../admin/dashboard.php');
                    } else {
                        header('Location: ../client/index.php');
                    }
                    exit;
                }
            }
            $error = __t('invalid_credentials');
        }
    }

    //  Inscription 
    if ($action === 'register') {
        $active_tab = 'register';
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // Validation côté serveur
        if (empty($nom) || empty($prenom) || !$email || empty($password) || empty($confirm)) {
            $error = 'Veuillez remplir tous les champs.';
        } elseif ($password !== $confirm) {
            $error = __t('password_mismatch');
        } elseif (strlen($password) < 6) {
            $error = 'Le mot de passe doit contenir au moins 6 caractères.';
        } else {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id_user FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = __t('email_exists');
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare(
                    "INSERT INTO users (nom, prenom, email, mot_de_passe, role, solde) VALUES (?, ?, ?, ?, 'client', 100000.00)"
                );
                $stmt->execute([$nom, $prenom, $email, $hash]);

                // Connecter automatiquement après inscription
                $newId = $pdo->lastInsertId();
                $_SESSION['id_user'] = $newId;
                $_SESSION['nom'] = $nom;
                $_SESSION['prenom'] = $prenom;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'client';
                $_SESSION['solde'] = 100000.00;

                header('Location: ../client/index.php');
                exit;
            }
        }
    }
}

$lang_url_fr = '?' . http_build_query(array_merge($_GET, ['lang' => 'fr']));
$lang_url_en = '?' . http_build_query(array_merge($_GET, ['lang' => 'en']));
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __t('login_title'); ?> — AO Shop</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<script src="../assets/theme.js"></script>
</head>
<body style="min-height:100vh; display:flex; flex-direction:column;">

    <!-- Glow atmosphérique en arrière-plan -->
    <div class="fancy-top-gradient"></div>

    <!-- Bouton de bascule de langue et de thème -->
    <div style="position:fixed; top:1rem; right:1rem; z-index:100; display:flex; align-items:center; gap:0.5rem;">
        <button onclick="cycleTheme()" class="btn-ghost" style="padding:0.5rem 0.75rem; font-size:1.1rem; cursor:pointer;" title="Change Theme">
            🎨
        </button>
        <div class="view-toggle">
            <a href="<?php echo $lang_url_fr; ?>" class="<?php echo $lang === 'fr' ? 'active' : ''; ?>"
               style="text-decoration:none; display:inline-flex; align-items:center; gap:0.375rem; padding:0.5rem 0.75rem;">
                🇫🇷 FR
            </a>
            <a href="<?php echo $lang_url_en; ?>" class="<?php echo $lang === 'en' ? 'active' : ''; ?>"
               style="text-decoration:none; display:inline-flex; align-items:center; gap:0.375rem; padding:0.5rem 0.75rem;">
                🇬🇧 EN
            </a>
        </div>
    </div>

    <div class="auth-wrapper">
        <div class="auth-card animate-pop">
            <div class="auth-logo">AO Shop</div>

            <?php if ($error): ?>
                <div class="toast error" style="margin-bottom:1.5rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Onglets de bascule -->
            <div class="auth-tabs">
                <button type="button" class="auth-tab <?php echo $active_tab === 'login' ? 'active' : ''; ?>"
                        onclick="showTab('login')">
                    <?php echo __t('login_title'); ?>
                </button>
                <button type="button" class="auth-tab <?php echo $active_tab === 'register' ? 'active' : ''; ?>"
                        onclick="showTab('register')">
                    <?php echo __t('register_title'); ?>
                </button>
            </div>

            <!-- Formulaire de connexion -->
            <form method="POST" action="" id="login-form"
                  style="display:<?php echo $active_tab === 'login' ? 'block' : 'none'; ?>;">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label class="form-label"><?php echo __t('email'); ?></label>
                    <input type="email" name="email" class="form-input" required
                           placeholder="exemple@email.tn"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo __t('password'); ?></label>
                    <input type="password" name="password" class="form-input" required
                           placeholder="••••••••">
                </div>
                <button type="submit" class="btn-primary" style="width:100%; justify-content:center;">
                    <?php echo __t('login_btn'); ?>
                </button>
            </form>

            <!-- Formulaire d'inscription -->
            <form method="POST" action="" id="register-form"
                  style="display:<?php echo $active_tab === 'register' ? 'block' : 'none'; ?>;">
                <input type="hidden" name="action" value="register">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="form-group" style="margin-bottom:0.75rem;">
                        <label class="form-label"><?php echo __t('last_name'); ?></label>
                        <input type="text" name="nom" class="form-input" required
                               value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0.75rem;">
                        <label class="form-label"><?php echo __t('first_name'); ?></label>
                        <input type="text" name="prenom" class="form-input" required
                               value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo __t('email'); ?></label>
                    <input type="email" name="email" class="form-input" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo __t('password'); ?></label>
                    <input type="password" name="password" class="form-input" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo __t('confirm_password'); ?></label>
                    <input type="password" name="confirm_password" class="form-input" required minlength="6">
                </div>
                <button type="submit" class="btn-primary" style="width:100%; justify-content:center;">
                    <?php echo __t('register_btn'); ?>
                </button>
            </form>
        </div>
    </div>

    <script>
    function showTab(tab) {
        document.getElementById('login-form').style.display = tab === 'login' ? 'block' : 'none';
        document.getElementById('register-form').style.display = tab === 'register' ? 'block' : 'none';
        document.querySelectorAll('.auth-tab').forEach(function(el) {
            el.classList.toggle('active', el.textContent.trim().toLowerCase().includes(tab));
        });
    }
    </script>

</body>
</html>
