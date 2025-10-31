<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\SessionManager;

class LegalController
{
    private TwigService $twig;

    public function __construct(TwigService $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Page mentions légales
     */
    public function mentionsLegales(): string
    {
        $isAuthenticated = SessionManager::isAuthenticated();
        $user = $isAuthenticated ? SessionManager::getUser() : null;

        return $this->twig->render('public/mentions-legales.html.twig', [
            'title' => 'Mentions Légales - TerrainTrack',
            'isPublic' => true,
            'user' => $user
        ]);
    }

    /**
     * Page politique de confidentialité
     */
    public function politiqueConfidentialite(): string
    {
        $isAuthenticated = SessionManager::isAuthenticated();
        $user = $isAuthenticated ? SessionManager::getUser() : null;

        return $this->twig->render('public/politique-confidentialite.html.twig', [
            'title' => 'Politique de Confidentialité - TerrainTrack',
            'isPublic' => true,
            'user' => $user
        ]);
    }

    /**
     * Page CGU
     */
    public function cgu(): string
    {
        $isAuthenticated = SessionManager::isAuthenticated();
        $user = $isAuthenticated ? SessionManager::getUser() : null;

        return $this->twig->render('public/cgu.html.twig', [
            'title' => 'CGU - TerrainTrack',
            'isPublic' => true,
            'user' => $user
        ]);
    }
}
