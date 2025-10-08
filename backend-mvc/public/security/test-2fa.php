<?php
/**
 * Test 2FA simple avec session basique
 */

session_start();

// Simuler une session utilisateur pour les tests
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 7,
        'name' => 'Momo',
        'email' => 'momo@gmail.com',
        'role' => 'admin'
    ];
    $_SESSION['authenticated'] = true;
}

$user = $_SESSION['user'];

// Si c'est une requête POST (API)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        require_once '../../vendor/autoload.php';
        
        $action = $_GET['action'] ?? 'enable';
        
        switch ($action) {
            case 'enable':
                // Simuler la génération d'un code
                $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                
                // Stocker le code en session pour le test
                $_SESSION['2fa_code'] = $code;
                $_SESSION['2fa_time'] = time();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Code de vérification généré',
                    'debug_code' => $code
                ]);
                exit;
                
            case 'verify':
                $inputCode = $_POST['code'] ?? '';
                $storedCode = $_SESSION['2fa_code'] ?? '';
                $codeTime = $_SESSION['2fa_time'] ?? 0;
                
                // Vérifier le code (valide 10 minutes)
                if ($inputCode === $storedCode && (time() - $codeTime) < 600) {
                    // Marquer la 2FA comme activée
                    $_SESSION['2fa_enabled'] = true;
                    
                    // Générer des codes de récupération
                    $backupCodes = [];
                    for ($i = 0; $i < 8; $i++) {
                        $backupCodes[] = strtoupper(substr(md5(uniqid()), 0, 8));
                    }
                    
                    $_SESSION['backup_codes'] = $backupCodes;
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Authentification à deux facteurs activée !',
                        'backup_codes' => $backupCodes
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Code invalide ou expiré'
                    ]);
                }
                exit;
                
            case 'disable':
                $_SESSION['2fa_enabled'] = false;
                unset($_SESSION['2fa_code'], $_SESSION['2fa_time'], $_SESSION['backup_codes']);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Authentification à deux facteurs désactivée'
                ]);
                exit;
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Erreur: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Interface HTML
$twoFactorEnabled = $_SESSION['2fa_enabled'] ?? false;
$twoFactorRequired = ($user['role'] === 'admin');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Authentification à deux facteurs</title>
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
        .result { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; display: none; }
        code { background: #e9ecef; padding: 2px 4px; border-radius: 3px; margin: 2px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔐 Test Authentification à deux facteurs</h1>
        <p>Utilisateur: <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)</p>
        
        <div class='status <?= $twoFactorEnabled ? 'enabled' : 'disabled' ?>'>
            <h3>État actuel</h3>
            <p>2FA <?= $twoFactorEnabled ? 'Activé ✅' : 'Désactivé ❌' ?></p>
        </div>
        
        <?php if ($twoFactorRequired): ?>
        <div class='status required'>
            <h3>⚠️ Obligatoire</h3>
            <p>L'authentification à deux facteurs est obligatoire pour votre rôle d'administrateur.</p>
        </div>
        <?php endif; ?>
        
        <div>
            <?php if (!$twoFactorEnabled): ?>
            <button class='btn-primary' onclick='activateTwoFA()'>Activer la 2FA</button>
            <?php else: ?>
            <button class='btn-danger' onclick='deactivateTwoFA()'>Désactiver la 2FA</button>
            <?php endif; ?>
        </div>
        
        <div id='result' class='result'></div>
    </div>
    
    <script>
    function activateTwoFA() {
        console.log('🔐 Activation de la 2FA...');
        
        fetch('?action=enable', {
            method: 'POST'
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text();
        })
        .then(text => {
            console.log('Response text:', text);
            try {
                const data = JSON.parse(text);
                console.log('Parsed data:', data);
                
                document.getElementById('result').style.display = 'block';
                
                if (data.success) {
                    document.getElementById('result').innerHTML = 
                        '<strong>✅ Succès:</strong> ' + data.message + 
                        '<br><strong>Code de test:</strong> <code>' + data.debug_code + '</code>' +
                        '<br><br><label>Entrez le code:</label><br>' +
                        '<input type="text" id="verification-code" placeholder="123456" maxlength="6" style="padding: 8px; font-size: 16px;">' +
                        '<button onclick="verifyCode()" style="margin-left: 10px; padding: 8px 15px;">Vérifier</button>';
                } else {
                    document.getElementById('result').innerHTML = '<strong>❌ Erreur:</strong> ' + data.error;
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                document.getElementById('result').style.display = 'block';
                document.getElementById('result').innerHTML = '<strong>❌ Erreur de parsing:</strong> ' + text;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
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
        
        console.log('🔍 Vérification du code:', code);
        
        const formData = new FormData();
        formData.append('code', code);
        
        fetch('?action=verify', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Réponse vérification:', data);
            
            if (data.success) {
                document.getElementById('result').innerHTML = 
                    '<strong>🎉 2FA Activée avec succès !</strong><br>' + data.message +
                    '<br><br><strong>📋 Codes de récupération:</strong><br>' +
                    '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 5px; margin: 10px 0;">' +
                    data.backup_codes.map(code => '<code>' + code + '</code>').join('') +
                    '</div>' +
                    '<p><small>⚠️ Conservez ces codes en lieu sûr !</small></p>' +
                    '<button onclick="location.reload()" style="padding: 8px 15px;">Recharger la page</button>';
            } else {
                document.getElementById('result').innerHTML = '<strong>❌ Erreur:</strong> ' + data.error;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('result').innerHTML = '<strong>❌ Erreur réseau:</strong> ' + error.message;
        });
    }
    
    function deactivateTwoFA() {
        if (!confirm('Êtes-vous sûr de vouloir désactiver la 2FA ?')) {
            return;
        }
        
        console.log('🔓 Désactivation de la 2FA...');
        
        fetch('?action=disable', {
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
