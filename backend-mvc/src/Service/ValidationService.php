<?php

namespace App\Service;

class ValidationService
{
    private array $errors = [];
    
    public function __construct()
    {
        $this->errors = [];
    }
    
    /**
     * Valide un email
     */
    public function validateEmail(string $email, string $fieldName = 'email'): bool
    {
        if (empty($email)) {
            $this->errors[$fieldName] = "L'email est requis.";
            return false;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$fieldName] = "L'email n'est pas valide.";
            return false;
        }
        
        if (strlen($email) > 255) {
            $this->errors[$fieldName] = "L'email ne peut pas dépasser 255 caractères.";
            return false;
        }
        
        return true;
    }
    
    /**
     * Valide un mot de passe
     */
    public function validatePassword(string $password, string $fieldName = 'password'): bool
    {
        if (empty($password)) {
            $this->errors[$fieldName] = "Le mot de passe est requis.";
            return false;
        }
        
        if (strlen($password) < 8) {
            $this->errors[$fieldName] = "Le mot de passe doit contenir au moins 8 caractères.";
            return false;
        }
        
        if (strlen($password) > 255) {
            $this->errors[$fieldName] = "Le mot de passe ne peut pas dépasser 255 caractères.";
            return false;
        }
        
        // Vérifier la complexité
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            $this->errors[$fieldName] = "Le mot de passe doit contenir au moins une minuscule, une majuscule et un chiffre.";
            return false;
        }
        
        return true;
    }
    
    /**
     * Valide une chaîne requise
     */
    public function validateRequired(string $value, string $fieldName, int $maxLength = 255): bool
    {
        if (empty(trim($value))) {
            $this->errors[$fieldName] = "Le champ {$fieldName} est requis.";
            return false;
        }
        
        if (strlen($value) > $maxLength) {
            $this->errors[$fieldName] = "Le champ {$fieldName} ne peut pas dépasser {$maxLength} caractères.";
            return false;
        }
        
        return true;
    }
    
    /**
     * Valide un numéro de téléphone
     */
    public function validatePhone(string $phone, string $fieldName = 'phone'): bool
    {
        if (empty($phone)) {
            return true; // Optionnel
        }
        
        // Format français : 0x xx xx xx xx ou +33 x xx xx xx xx
        if (!preg_match('/^(?:(?:\+33|0)[1-9](?:[0-9]{8}))$/', str_replace(' ', '', $phone))) {
            $this->errors[$fieldName] = "Le numéro de téléphone n'est pas valide.";
            return false;
        }
        
        return true;
    }
    
    /**
     * Valide un entier dans une plage
     */
    public function validateInteger(mixed $value, string $fieldName, int $min = null, int $max = null): bool
    {
        if (!is_numeric($value) || (int)$value != $value) {
            $this->errors[$fieldName] = "Le champ {$fieldName} doit être un nombre entier.";
            return false;
        }
        
        $intValue = (int)$value;
        
        if ($min !== null && $intValue < $min) {
            $this->errors[$fieldName] = "Le champ {$fieldName} doit être supérieur ou égal à {$min}.";
            return false;
        }
        
        if ($max !== null && $intValue > $max) {
            $this->errors[$fieldName] = "Le champ {$fieldName} doit être inférieur ou égal à {$max}.";
            return false;
        }
        
        return true;
    }
    
    /**
     * Valide une date
     */
    public function validateDate(string $date, string $fieldName = 'date', string $format = 'Y-m-d'): bool
    {
        if (empty($date)) {
            $this->errors[$fieldName] = "La date est requise.";
            return false;
        }
        
        $dateTime = \DateTime::createFromFormat($format, $date);
        if (!$dateTime || $dateTime->format($format) !== $date) {
            $this->errors[$fieldName] = "La date n'est pas valide.";
            return false;
        }
        
        return true;
    }
    
    /**
     * Valide un fichier uploadé
     */
    public function validateFile(array $file, string $fieldName, array $allowedTypes = [], int $maxSize = 5242880): bool
    {
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return true; // Pas de fichier, optionnel
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[$fieldName] = "Erreur lors de l'upload du fichier.";
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            $sizeMB = round($maxSize / 1024 / 1024, 1);
            $this->errors[$fieldName] = "Le fichier ne peut pas dépasser {$sizeMB} MB.";
            return false;
        }
        
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $this->errors[$fieldName] = "Type de fichier non autorisé.";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Sanitise une chaîne pour éviter les XSS
     */
    public function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitise pour la base de données
     */
    public function sanitizeForDb(string $input): string
    {
        return trim($input);
    }
    
    /**
     * Récupère toutes les erreurs
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Vérifie s'il y a des erreurs
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
    
    /**
     * Ajoute une erreur personnalisée
     */
    public function addError(string $fieldName, string $message): void
    {
        $this->errors[$fieldName] = $message;
    }
    
    /**
     * Efface toutes les erreurs
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }
    
    /**
     * Validation d'un tableau de données
     */
    public function validateData(array $data, array $rules): bool
    {
        $this->clearErrors();
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? '';
            
            switch ($rule['type']) {
                case 'email':
                    $this->validateEmail($value, $field);
                    break;
                    
                case 'password':
                    $this->validatePassword($value, $field);
                    break;
                    
                case 'required':
                    $maxLength = $rule['max_length'] ?? 255;
                    $this->validateRequired($value, $field, $maxLength);
                    break;
                    
                case 'phone':
                    $this->validatePhone($value, $field);
                    break;
                    
                case 'integer':
                    $min = $rule['min'] ?? null;
                    $max = $rule['max'] ?? null;
                    $this->validateInteger($value, $field, $min, $max);
                    break;
                    
                case 'date':
                    $format = $rule['format'] ?? 'Y-m-d';
                    $this->validateDate($value, $field, $format);
                    break;
            }
        }
        
        return !$this->hasErrors();
    }
}


