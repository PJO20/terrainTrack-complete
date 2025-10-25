<?php
/**
 * Page de vérification 2FA lors de la connexion
 */

require_once '../../vendor/autoload.php';

use App\Service\SessionManager;
use App\Service\TwoFactorService;

session_start();

// Vérifier qu'il y a un utilisateur en attente de vérification 2FA
if (!isset($_SESSION['pending_2fa_user'])) {
    header('Location: /login');
    exit;
}

$user = $_SESSION['pending_2fa_user'];
$error = $_GET['error'] ?? '';

// Traitement du formulaire de vérification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    
    if (empty($code)) {
        header('Location: /auth/verify-2fa.php?error=Code requis');
        exit;
    }
    
    try {
        $twoFactorService = new TwoFactorService();
        
        if ($twoFactorService->verifyOtpCode($user['id'], $code)) {
            // Code valide - créer la session utilisateur
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role'],
                'is_admin' => $user['is_admin']
            ];
            $_SESSION['last_activity'] = time();
            $_SESSION['authenticated'] = true;
            
            // Nettoyer la session temporaire
            unset($_SESSION['pending_2fa_user']);
            
            // Si c'est un admin et que la 2FA n'est pas encore activée, l'activer automatiquement
            if ($user['role'] === 'admin' && !$twoFactorService->isTwoFactorEnabled($user['id'])) {
                $backupCodes = $twoFactorService->generateRecoveryCodes();
                $twoFactorService->enableTwoFactor($user['id'], $backupCodes);
            }
            
            // Rediriger vers le dashboard
            header('Location: /dashboard');
            exit;
        } else {
            header('Location: /auth/verify-2fa.php?error=Code invalide ou expiré');
            exit;
        }
    } catch (Exception $e) {
        error_log("Erreur vérification 2FA: " . $e->getMessage());
        header('Location: /auth/verify-2fa.php?error=Erreur lors de la vérification');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification 2FA - TerrainTrack</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .verify-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        .verify-header {
            margin-bottom: 2rem;
        }
        
        .verify-header h1 {
            color: #2d3748;
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
        }
        
        .verify-header p {
            color: #718096;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .user-info {
            background: #f7fafc;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #4299e1;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        
        .form-label {
            display: block;
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1.1rem;
            text-align: center;
            letter-spacing: 0.2em;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: #4299e1;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn:hover {
            background: #3182ce;
        }
        
        .error {
            background: #fed7d7;
            color: #c53030;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .help-text {
            color: #718096;
            font-size: 0.8rem;
            margin-top: 1rem;
        }
        
        .resend-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .resend-btn {
            background: #68d391;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .resend-btn:hover {
            background: #48bb78;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-header">
            <h1>🔐 Vérification 2FA</h1>
            <p>Authentification à deux facteurs requise</p>
        </div>
        
        <div class="user-info">
            <strong><?= htmlspecialchars($user['name'] ?? $user['email']) ?></strong><br>
            <small><?= htmlspecialchars($user['notification_email'] ?? $user['email']) ?></small>
            <?php if ($user['role'] === 'admin'): ?>
            <br><small style="color: #e53e3e;">⚠️ 2FA obligatoire pour les administrateurs</small>
            <?php endif; ?>
        </div>
        
        <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="code">Code de vérification</label>
                <input type="text" 
                       id="code" 
                       name="code" 
                       class="form-input" 
                       placeholder="123456" 
                       maxlength="6" 
                       pattern="[0-9]{6}" 
                       required 
                       autofocus>
            </div>
            
            <button type="submit" class="btn">
                Vérifier le code
            </button>
        </form>
        
        <div class="help-text">
            Un code à 6 chiffres a été envoyé à votre adresse email.<br>
            Le code expire dans 10 minutes.
        </div>
        
        <div class="resend-section">
            <p style="color: #718096; font-size: 0.9rem; margin-bottom: 0.5rem;">
                Vous n'avez pas reçu le code ?
            </p>
            <button type="button" class="resend-btn" onclick="resendCode()">
                Renvoyer le code
            </button>
        </div>
    </div>
    
    <script>
    // Auto-focus sur le champ de code
    document.getElementById('code').focus();
    
    // Fonction pour renvoyer le code
    function resendCode() {
        fetch('/auth/resend-2fa-code.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Nouveau code envoyé !');
            } else {
                alert('❌ Erreur: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('❌ Erreur de connexion');
        });
    }
    
    // Validation du formulaire
    document.querySelector('form').addEventListener('submit', function(e) {
        const code = document.getElementById('code').value;
        if (!/^\d{6}$/.test(code)) {
            e.preventDefault();
            alert('Veuillez entrer un code à 6 chiffres');
        }
    });
    </script>
</body>
</html>
