<?php
/**
 * Page 2FA directe sans conteneur
 */

require_once '../../vendor/autoload.php';

use App\Service\SessionManager;

// Démarrer la session
SessionManager::start();

// Vérifier l'authentification
if (!SessionManager::isAuthenticated()) {
    header('Location: /login');
    exit;
}

$user = SessionManager::getUser();
$userId = $user['id'];

// Test simple sans dépendances
$twoFactorEnabled = false;
$twoFactorRequired = ($user['role'] === 'admin');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Authentification à deux facteurs</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 600px; margin: 0 auto; }
        .status { padding: 20px; margin: 20px 0; border-radius: 8px; }
        .enabled { background: #f0fdf4; border: 1px solid #22c55e; }
        .disabled { background: #fef2f2; border: 1px solid #ef4444; }
        .required { background: #fef3c7; border: 1px solid #f59e0b; }
        button { padding: 10px 20px; margin: 10px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-danger { background: #ef4444; color: white; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔐 Authentification à deux facteurs</h1>
        <p>Utilisateur: <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)</p>
        
        <div class='status <?= $twoFactorEnabled ? 'enabled' : 'disabled' ?>'>
            <h3>État actuel</h3>
            <p>2FA <?= $twoFactorEnabled ? 'Activé' : 'Désactivé' ?></p>
        </div>
        
        <?php if ($twoFactorRequired): ?>
        <div class='status required'>
            <h3>⚠️ Obligatoire</h3>
            <p>L'authentification à deux facteurs est obligatoire pour votre rôle d'administrateur.</p>
        </div>
        <?php endif; ?>
        
        <div>
            <button class='btn-primary' onclick='testActivation()'>Activer la 2FA</button>
            <?php if ($twoFactorEnabled): ?>
            <button class='btn-danger' onclick='testDeactivation()'>Désactiver</button>
            <?php endif; ?>
        </div>
        
        <div id='result' style='margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; display: none;'></div>
    </div>
    
    <script>
    function testActivation() {
        console.log('Test activation 2FA...');
        fetch('/security/two-factor-api.php?action=enable', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Réponse:', data);
            document.getElementById('result').style.display = 'block';
            
            if (data.success) {
                document.getElementById('result').innerHTML = 
                    '<strong>✅ Succès:</strong> ' + data.message + 
                    (data.debug_code ? '<br><strong>Code de test:</strong> ' + data.debug_code : '') +
                    '<br><br><input type="text" id="verification-code" placeholder="Entrez le code" maxlength="6">' +
                    '<button onclick="verifyCode()" style="margin-left: 10px;">Vérifier</button>';
            } else {
                document.getElementById('result').innerHTML = '<strong>❌ Erreur:</strong> ' + data.error;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('result').style.display = 'block';
            document.getElementById('result').innerHTML = '<strong>❌ Erreur réseau:</strong> ' + error.message;
        });
    }
    
    function verifyCode() {
        const code = document.getElementById('verification-code').value;
        if (!code) {
            alert('Veuillez entrer le code');
            return;
        }
        
        console.log('Vérification du code:', code);
        
        const formData = new FormData();
        formData.append('code', code);
        
        fetch('/security/two-factor-api.php?action=verify', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Réponse vérification:', data);
            
            if (data.success) {
                document.getElementById('result').innerHTML = 
                    '<strong>✅ 2FA Activée !</strong><br>' + data.message +
                    '<br><br><strong>Codes de récupération:</strong><br>' +
                    data.backup_codes.map(code => '<code>' + code + '</code>').join(' ') +
                    '<br><br><button onclick="location.reload()">Recharger la page</button>';
            } else {
                document.getElementById('result').innerHTML = '<strong>❌ Erreur:</strong> ' + data.error;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('result').innerHTML = '<strong>❌ Erreur réseau:</strong> ' + error.message;
        });
    }
    
    function testDeactivation() {
        if (!confirm('Êtes-vous sûr de vouloir désactiver la 2FA ?')) {
            return;
        }
        
        console.log('Test désactivation 2FA...');
        fetch('/security/two-factor-api.php?action=disable', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Réponse:', data);
            document.getElementById('result').style.display = 'block';
            
            if (data.success) {
                document.getElementById('result').innerHTML = '<strong>✅ Succès:</strong> ' + data.message;
                setTimeout(() => location.reload(), 2000);
            } else {
                document.getElementById('result').innerHTML = '<strong>❌ Erreur:</strong> ' + data.error;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('result').style.display = 'block';
            document.getElementById('result').innerHTML = '<strong>❌ Erreur réseau:</strong> ' + error.message;
        });
    }
    </script>
</body>
</html>
