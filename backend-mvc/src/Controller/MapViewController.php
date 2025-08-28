<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\SessionManager;
use App\Repository\VehicleRepository;
use App\Repository\InterventionRepository;
use App\Repository\TeamRepository;

class MapViewController
{
    private TwigService $twig;
    private VehicleRepository $vehicleRepository;
    private InterventionRepository $interventionRepository;
    private TeamRepository $teamRepository;

    public function __construct(TwigService $twig, VehicleRepository $vehicleRepository, InterventionRepository $interventionRepository, TeamRepository $teamRepository)
    {
        $this->twig = $twig;
        $this->vehicleRepository = $vehicleRepository;
        $this->interventionRepository = $interventionRepository;
        $this->teamRepository = $teamRepository;
    }

    public function index()
    {
        SessionManager::requireLogin();
        
        // Récupérer les données pour l'initialisation
        $vehicles = $this->vehicleRepository->findAll();
        $interventions = $this->interventionRepository->findAll();
        $teams = $this->teamRepository->findAll();

        // Ajouter des coordonnées simulées aux véhicules s'ils n'en ont pas
        $vehicles = $this->addSimulatedLocations($vehicles);
        
        // Ajouter des coordonnées aux interventions si nécessaire
        $interventions = $this->addSimulatedInterventionLocations($interventions);
        
        // Filtrer les interventions actives pour l'affichage
        $activeInterventions = $this->getActiveInterventions($interventions);
        
        // Calculer les statistiques
        $stats = $this->calculateStats($vehicles, $interventions);

        return $this->twig->render('map-view.html.twig', [
            'title' => 'Map View',
            'vehicles' => $vehicles,
            'interventions' => $interventions,
            'active_interventions' => $activeInterventions,
            'teams' => $teams,
            'stats' => $stats
        ]);
    }

    /**
     * API endpoint pour récupérer les données en temps réel
     */
    public function apiData()
    {
        SessionManager::requireLogin();
        
        header('Content-Type: application/json');
        
        $vehicles = $this->vehicleRepository->findAll();
        $interventions = $this->interventionRepository->findAll();
        
        // Ajouter des coordonnées simulées
        $vehicles = $this->addSimulatedLocations($vehicles);
        $interventions = $this->addSimulatedInterventionLocations($interventions);
        
        // Simuler des mises à jour de position pour les véhicules en cours d'intervention
        $vehicles = $this->simulateMovement($vehicles);
        
        echo json_encode([
            'vehicles' => $vehicles,
            'interventions' => $interventions,
            'active_interventions' => $this->getActiveInterventions($interventions),
            'last_updated' => date('H:i:s'),
            'stats' => $this->calculateStats($vehicles, $interventions)
        ]);
    }

    private function addSimulatedLocations($vehicles)
    {
        // Centre autour de Paris avec variations
        $baseLat = 48.8566;
        $baseLng = 2.3522;
        
        foreach ($vehicles as &$vehicle) {
            // Si pas de coordonnées, en générer
            if (empty($vehicle['latitude']) || empty($vehicle['longitude'])) {
                $vehicle['latitude'] = $baseLat + (mt_rand(-50, 50) / 1000);
                $vehicle['longitude'] = $baseLng + (mt_rand(-50, 50) / 1000);
            }
            
            // Ajouter des emoji selon le type
            $vehicle['emoji'] = $this->getVehicleEmoji(
                $vehicle['name'] ?? '',
                $vehicle['brand'] ?? '',
                $vehicle['model'] ?? '',
                $vehicle['type'] ?? 'autre'
            );
            
            // Normaliser le statut
            $vehicle['normalized_status'] = $this->normalizeStatus($vehicle['status'] ?? 'disponible');
        }
        
        return $vehicles;
    }

    private function addSimulatedInterventionLocations($interventions)
    {
        // Centre autour de Paris avec variations
        $baseLat = 48.8566;
        $baseLng = 2.3522;
        
        foreach ($interventions as &$intervention) {
            // Si pas de coordonnées, en générer
            if (empty($intervention['latitude']) || empty($intervention['longitude'])) {
                $intervention['latitude'] = $baseLat + (mt_rand(-30, 30) / 1000);
                $intervention['longitude'] = $baseLng + (mt_rand(-30, 30) / 1000);
            }
            
            // Normaliser le statut
            $intervention['normalized_status'] = $this->normalizeInterventionStatus($intervention['status'] ?? 'planifiée');
        }
        
        return $interventions;
    }

    private function getActiveInterventions($interventions)
    {
        return array_filter($interventions, function($intervention) {
            $status = strtolower(trim($intervention['status'] ?? ''));
            return in_array($status, [
                'en cours', 'in progress', 'active', 'planifiée', 'pending', 
                'assignée', 'assigned', 'démarrée', 'started'
            ]);
        });
    }

    private function simulateMovement($vehicles)
    {
        foreach ($vehicles as &$vehicle) {
            // Simuler léger mouvement pour véhicules en cours
            if (in_array(strtolower($vehicle['status']), ['en cours', 'intervention', 'en intervention'])) {
                $vehicle['latitude'] += (mt_rand(-5, 5) / 10000);
                $vehicle['longitude'] += (mt_rand(-5, 5) / 10000);
            }
        }
        
        return $vehicles;
    }

    private function getVehicleEmoji($name, $brand, $model, $type)
    {
        // Normaliser les chaînes pour comparaison
        $nameLower = strtolower($name ?? '');
        $brandLower = strtolower($brand ?? '');
        $modelLower = strtolower($model ?? '');
        $typeLower = strtolower($type ?? '');
        
        // Combinaisons spécifiques par nom et marque
        $fullName = $nameLower . ' ' . $brandLower . ' ' . $modelLower;
        
        // Quads - 🏍️
        if (strpos($nameLower, 'quad') !== false || 
            strpos($fullName, 'quad') !== false || 
            strpos($nameLower, 'explorer') !== false ||
            strpos($nameLower, 'sport') !== false && strpos($typeLower, 'quad') !== false) {
            return '🏍️';
        }
        
        // Tracteurs - 🚜
        if (strpos($nameLower, 'jd') !== false || 
            strpos($brandLower, 'john deere') !== false ||
            strpos($modelLower, '6120r') !== false ||
            strpos($typeLower, 'tracteur') !== false ||
            strpos($typeLower, 'tractor') !== false) {
            return '🚜';
        }
        
        // Fourgons utilitaires - 🚐
        if (strpos($brandLower, 'mercedes') !== false && strpos($modelLower, 'sprinter') !== false ||
            strpos($brandLower, 'renault') !== false && strpos($modelLower, 'master') !== false ||
            strpos($modelLower, 'sprinter') !== false ||
            strpos($modelLower, 'master') !== false ||
            strpos($typeLower, 'fourgon') !== false ||
            strpos($typeLower, 'utilitaire') !== false) {
            return '🚐';
        }
        
        // Camions - 🚛
        if (strpos($nameLower, 'camion') !== false ||
            strpos($brandLower, 'daf') !== false ||
            strpos($brandLower, 'scania') !== false ||
            strpos($modelLower, 'r730') !== false ||
            strpos($modelLower, 'kerax') !== false ||
            strpos($modelLower, 'r') !== false && strpos($brandLower, 'scania') !== false ||
            strpos($typeLower, 'camion') !== false ||
            strpos($typeLower, 'truck') !== false) {
            return '🚛';
        }
        
        // Véhicules de transport général - 🚚
        if (strpos($nameLower, 'transport') !== false) {
            return '🚚';
        }
        
        // Fallback selon le type
        switch ($typeLower) {
            case 'quad':
            case 'atv':
                return '🏍️';
            case 'tracteur':
            case 'tractor':
                return '🚜';
            case 'camion':
            case 'truck':
                return '🚛';
            case 'fourgon':
            case 'van':
            case 'utilitaire':
                return '🚐';
            default:
                return '🚗'; // Véhicule générique
        }
    }

    private function normalizeStatus($status)
    {
        $status = strtolower(trim($status));
        
        if (in_array($status, ['disponible', 'available'])) {
            return 'available';
        } elseif (in_array($status, ['en cours', 'intervention', 'en intervention', 'inprogress'])) {
            return 'inprogress';
        } elseif (in_array($status, ['maintenance', 'en maintenance'])) {
            return 'maintenance';
        } elseif (in_array($status, ['hors service', 'out of service', 'issue'])) {
            return 'issue';
        }
        
        return 'available';
    }

    private function normalizeInterventionStatus($status)
    {
        $status = strtolower(trim($status));
        
        if (in_array($status, ['en cours', 'in progress', 'active', 'démarrée', 'started'])) {
            return 'active';
        } elseif (in_array($status, ['planifiée', 'pending', 'assignée', 'assigned'])) {
            return 'pending';
        } elseif (in_array($status, ['terminée', 'completed', 'finie', 'done'])) {
            return 'completed';
        } elseif (in_array($status, ['annulée', 'cancelled', 'canceled'])) {
            return 'cancelled';
        }
        
        return 'pending';
    }

    private function calculateStats($vehicles, $interventions)
    {
        $stats = [
            'total_vehicles' => count($vehicles),
            'available' => 0,
            'inprogress' => 0,
            'maintenance' => 0,
            'issue' => 0,
            'active_interventions' => 0,
            'pending_interventions' => 0
        ];

        foreach ($vehicles as $vehicle) {
            $status = strtolower(trim($vehicle['status'] ?? 'disponible'));
            
            // Comptage selon les vraies valeurs dans la base
            if (in_array($status, ['disponible', 'available'])) {
                $stats['available']++;
            } elseif (in_array($status, ['en intervention', 'en cours', 'intervention', 'inprogress', 'in progress', 'occupé', 'occupied'])) {
                $stats['inprogress']++;
            } elseif (in_array($status, ['maintenance', 'en maintenance', 'under maintenance'])) {
                $stats['maintenance']++;
            } elseif (in_array($status, ['hors service', 'out of service', 'issue', 'problème', 'panne', 'broken'])) {
                $stats['issue']++;
            } else {
                // Statuts inconnus comptés comme disponibles par défaut
                $stats['available']++;
            }
        }

        foreach ($interventions as $intervention) {
            $status = strtolower(trim($intervention['status'] ?? 'planifiée'));
            
            if (in_array($status, ['en cours', 'in progress', 'active', 'démarrée', 'started', 'ongoing'])) {
                $stats['active_interventions']++;
            } elseif (in_array($status, ['planifiée', 'pending', 'assignée', 'assigned', 'scheduled'])) {
                $stats['pending_interventions']++;
            }
        }

        return $stats;
    }
} 