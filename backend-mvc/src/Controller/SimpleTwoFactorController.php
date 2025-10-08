<?php

namespace App\Controller;

use App\Service\SessionManager;

class SimpleTwoFactorController
{
    /**
     * Page de gestion de la 2FA
     */
    public function index(): void
    {
        SessionManager::start();
        
        if (!SessionManager::isAuthenticated()) {
            header('Location: /login');
            exit;
        }

        $user = SessionManager::getUser();
        $userId = $user['id'];

        // Test simple sans dépendances
        $twoFactorEnabled = false;
        $twoFactorRequired = ($user['role'] === 'admin');

        echo "<!DOCTYPE html>
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
        <p>Utilisateur: {$user['name']} ({$user['email']})</p>
        
        <div class='status " . ($twoFactorEnabled ? 'enabled' : 'disabled') . "'>
            <h3>État actuel</h3>
            <p>2FA " . ($twoFactorEnabled ? 'Activé' : 'Désactivé') . "</p>
        </div>
        
        " . ($twoFactorRequired ? "<div class='status required'>
            <h3>⚠️ Obligatoire</h3>
            <p>L'authentification à deux facteurs est obligatoire pour votre rôle d'administrateur.</p>
        </div>" : "") . "
        
        <div>
            <button class='btn-primary' onclick='testActivation()'>Activer la 2FA</button>
            " . ($twoFactorEnabled ? "<button class='btn-danger' onclick='testDeactivation()'>Désactiver</button>" : "") . "
        </div>
        
        <div id='result' style='margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; display: none;'></div>
    </div>
    
    <script>
    function testActivation() {
        console.log('Test activation 2FA...');
        fetch('/security/two-factor/enable', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Réponse:', data);
            document.getElementById('result').style.display = 'block';
            document.getElementById('result').innerHTML = '<strong>Réponse:</strong> ' + JSON.stringify(data, null, 2);
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('result').style.display = 'block';
            document.getElementById('result').innerHTML = '<strong>Erreur:</strong> ' + error.message;
        });
    }
    
    function testDeactivation() {
        console.log('Test désactivation 2FA...');
        fetch('/security/two-factor/disable', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Réponse:', data);
            document.getElementById('result').style.display = 'block';
            document.getElementById('result').innerHTML = '<strong>Réponse:</strong> ' + JSON.stringify(data, null, 2);
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('result').style.display = 'block';
            document.getElementById('result').innerHTML = '<strong>Erreur:</strong> ' + error.message;
        });
    }
    </script>
</body>
</html>";
    }
}
