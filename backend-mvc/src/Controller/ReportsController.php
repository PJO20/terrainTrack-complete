<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\SessionManager;

class ReportsController
{
    private TwigService $twig;

    public function __construct(TwigService $twig)
    {
        $this->twig = $twig;
    }

    public function index()
    {
        SessionManager::requireLogin();
        
        return $this->twig->render('reports/index.html.twig');
    }

    public function generate()
    {
        // Logique pour générer des rapports
        return $this->twig->render('reports/generate.html.twig', [
            'title' => 'Generate Report'
        ]);
    }

    public function view($id)
    {
        // Logique pour voir un rapport spécifique
        return $this->twig->render('reports/view.html.twig', [
            'title' => 'View Report',
            'report_id' => $id
        ]);
    }
} 