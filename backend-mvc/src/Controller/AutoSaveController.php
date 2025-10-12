<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AutoSaveService;
use App\Service\SessionManager;

class AutoSaveController
{
    public function handleRequest(): void
    {
        // Inclure le fichier API existant
        include __DIR__ . '/../../public/api/autosave.php';
    }
}

