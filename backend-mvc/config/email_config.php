<?php
/**
 * Configuration pour l'envoi d'emails
 */

return [
    // Configuration SMTP
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'votre-email@gmail.com',
        'password' => 'votre-mot-de-passe-app',
        'encryption' => 'tls', // ou 'ssl' pour le port 465
    ],
    
    // Configuration de l'expéditeur
    'from' => [
        'email' => 'noreply@terraintrack.com',
        'name' => 'TerrainTrack'
    ],
    
    // Configuration pour différents fournisseurs
    'providers' => [
        'gmail' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls'
        ],
        'outlook' => [
            'host' => 'smtp.office365.com',
            'port' => 587,
            'encryption' => 'tls'
        ],
        'yahoo' => [
            'host' => 'smtp.mail.yahoo.com',
            'port' => 587,
            'encryption' => 'tls'
        ]
    ]
];
