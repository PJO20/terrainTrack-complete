<?php

namespace App\Controller;

use App\Service\TwigService;

class HomeController
{
    private TwigService $twig;

    public function __construct(TwigService $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Page d'accueil
     */
    public function index(): string
    {
        return $this->twig->render('home.html.twig', [
            'title' => 'Accueil - TerrainTrack',
            'message' => 'Bienvenue sur TerrainTrack'
        ]);
    }
} 