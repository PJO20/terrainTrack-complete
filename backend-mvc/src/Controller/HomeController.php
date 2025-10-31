<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\SessionManager;

class HomeController
{
    private TwigService $twig;

    public function __construct(TwigService $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Page d'accueil publique (vitrine)
     */
    public function index(): string
    {
        // Vérifier si l'utilisateur est vraiment authentifié (pas seulement si la session existe)
        $isAuthenticated = SessionManager::isAuthenticated();
        $user = $isAuthenticated ? SessionManager::getUser() : null;
        
        // Permettre de voir la vitrine même si connecté (utile pour le marketing)
        // L'utilisateur peut toujours accéder au dashboard via le menu
        
        return $this->twig->render('public/home.html.twig', [
            'title' => 'Accueil - TerrainTrack',
            'isPublic' => true,
            'user' => $user
        ]);
    }
} 