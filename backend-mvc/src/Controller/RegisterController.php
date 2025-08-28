<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Twig\Environment;

class RegisterController
{
    private Environment $twig;
    private UserRepository $userRepository;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
        $this->userRepository = new UserRepository();
    }

    // Affiche le formulaire d'inscription
    public function showForm()
    {
        echo $this->twig->render('register.html.twig');
    }

    // Traite le formulaire d'inscription
    public function register()
    {
        $errors = [];
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // Validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse email invalide.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
        }
        if ($password !== $confirm) {
            $errors[] = 'La confirmation du mot de passe ne correspond pas.';
        }
        if ($this->userRepository->findByEmail($email)) {
            $errors[] = 'Cet email est déjà utilisé.';
        }

        if (!empty($errors)) {
            echo $this->twig->render('register.html.twig', [
                'errors' => $errors,
                'email' => $email
            ]);
            return;
        }

        // Hash du mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Création et sauvegarde de l'utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hashedPassword);
        $this->userRepository->save($user);

        // Redirection automatique vers le login avec message de succès
        header('Location: /login?registered=1');
        exit;
    }
}
