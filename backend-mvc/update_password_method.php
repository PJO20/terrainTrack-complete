<?php
/**
 * Script pour mettre à jour la méthode updatePassword
 */

$filePath = '/Applications/MAMP/htdocs/exemple/backend-mvc/src/Repository/UserRepository.php';

// Lire le contenu du fichier
$content = file_get_contents($filePath);

// Ancien code à remplacer
$oldCode = '    // Met à jour le mot de passe d\'un utilisateur
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $sql = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            \'password\' => $hashedPassword,
            \'id\' => $userId
        ]);
    }';

// Nouveau code avec la date de mise à jour
$newCode = '    // Met à jour le mot de passe d\'un utilisateur
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $sql = "UPDATE users SET password = :password, password_updated_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            \'password\' => $hashedPassword,
            \'id\' => $userId
        ]);
    }';

// Effectuer le remplacement
$newContent = str_replace($oldCode, $newCode, $content);

// Vérifier si le remplacement a eu lieu
if ($newContent !== $content) {
    // Sauvegarder le fichier modifié
    file_put_contents($filePath, $newContent);
    echo "✅ Méthode updatePassword mise à jour avec succès !\n";
    echo "📝 La date de modification du mot de passe sera maintenant enregistrée.\n";
} else {
    echo "❌ Aucun changement détecté. Le code pourrait déjà être mis à jour.\n";
}

echo "\n🔍 Vérification du fichier...\n";
$verification = file_get_contents($filePath);
if (strpos($verification, 'password_updated_at = NOW()') !== false) {
    echo "✅ La mise à jour est bien présente dans le fichier.\n";
} else {
    echo "❌ La mise à jour n'a pas été appliquée correctement.\n";
}
?>
