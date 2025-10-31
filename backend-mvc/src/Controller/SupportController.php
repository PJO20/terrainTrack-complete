<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\SessionManager;
use App\Service\EmailService;
use App\Repository\UserRepository;
use PDO;

class SupportController
{
    private TwigService $twig;
    private EmailService $emailService;
    private UserRepository $userRepository;
    private PDO $pdo;

    public function __construct(TwigService $twig, EmailService $emailService, UserRepository $userRepository, PDO $pdo)
    {
        $this->twig = $twig;
        $this->emailService = $emailService;
        $this->userRepository = $userRepository;
        $this->pdo = $pdo;
    }

    /**
     * Affiche le formulaire de support
     */
    public function index(): string
    {
        // Vérifier si l'utilisateur est vraiment authentifié
        $isAuthenticated = SessionManager::isAuthenticated();
        $user = $isAuthenticated ? SessionManager::getUser() : null;
        
        return $this->twig->render('public/support.html.twig', [
            'title' => 'Support Client - TerrainTrack',
            'isPublic' => true,
            'user' => $user
        ]);
    }

    /**
     * Traite la soumission du formulaire de support
     */
    public function submit(): string
    {
        // Vérifier si l'utilisateur est vraiment authentifié
        $isAuthenticated = SessionManager::isAuthenticated();
        $user = $isAuthenticated ? SessionManager::getUser() : null;

        $error = null;
        $success = false;
        $ticketId = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $subject = $_POST['subject'] ?? '';
            $message = $_POST['message'] ?? '';

            // Validation
            if (empty($name) || empty($email) || empty($subject) || empty($message)) {
                $error = 'Tous les champs sont requis.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Adresse email invalide.';
            } else {
                try {
                    // Récupérer l'ID utilisateur si connecté
                    $userId = $user ? $user['id'] : null;

                    // Enregistrer le ticket dans la base de données
                    $stmt = $this->pdo->prepare("
                        INSERT INTO support_tickets (user_id, name, email, subject, message, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, 'open', NOW())
                    ");
                    $stmt->execute([$userId, $name, $email, $subject, $message]);
                    $ticketId = $this->pdo->lastInsertId();

                    // Envoyer l'email au support
                    $this->emailService->send(
                        'support@tondomaine.fr',
                        'Nouveau ticket de support : ' . $subject,
                        "Un nouveau ticket de support a été créé.\n\n" .
                        "Numéro de ticket: #$ticketId\n" .
                        "Nom: $name\n" .
                        "Email: $email\n" .
                        "Sujet: $subject\n\n" .
                        "Message:\n$message"
                    );

                    // Envoyer l'email de confirmation à l'utilisateur
                    $this->emailService->send(
                        $email,
                        'Confirmation de votre demande de support - Ticket #' . $ticketId,
                        "Bonjour $name,\n\n" .
                        "Nous avons bien reçu votre demande de support.\n\n" .
                        "Numéro de ticket: #$ticketId\n" .
                        "Sujet: $subject\n\n" .
                        "Notre équipe traitera votre demande dans les plus brefs délais.\n\n" .
                        "Cordialement,\n" .
                        "L'équipe TerrainTrack"
                    );

                    $success = true;
                } catch (\Exception $e) {
                    error_log("Erreur lors de l'enregistrement du ticket: " . $e->getMessage());
                    $error = 'Une erreur est survenue lors de l\'envoi de votre demande. Veuillez réessayer.';
                }
            }
        }

        return $this->twig->render('public/support.html.twig', [
            'title' => 'Support Client - TerrainTrack',
            'isPublic' => true,
            'user' => $user,
            'error' => $error,
            'success' => $success,
            'ticketId' => $ticketId
        ]);
    }
}
