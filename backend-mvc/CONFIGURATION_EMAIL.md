# 📧 Configuration de l'envoi d'emails

## 🎯 Objectif
Configurer l'envoi d'emails réels pour la fonctionnalité "Mot de passe oublié".

## 🔧 Configuration Gmail (Recommandé)

### 1. Préparer votre compte Gmail
1. **Activez l'authentification à 2 facteurs** sur votre compte Google
2. **Générez un mot de passe d'application** :
   - Allez sur [myaccount.google.com](https://myaccount.google.com)
   - Sécurité → Authentification à 2 facteurs
   - Mots de passe des applications
   - Générez un mot de passe pour "Mail"

### 2. Configurer le fichier
Modifiez le fichier `config/email_config.php` :

```php
'smtp' => [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'votre-email@gmail.com',        // ← Remplacez par votre email
    'password' => 'votre-mot-de-passe-app',       // ← Remplacez par le mot de passe d'application
    'encryption' => 'tls',
],
```

### 3. Tester la configuration
```bash
php test_email_config.php
```

## 🔧 Configuration Outlook/Office 365

```php
'smtp' => [
    'host' => 'smtp.office365.com',
    'port' => 587,
    'username' => 'votre-email@outlook.com',
    'password' => 'votre-mot-de-passe',
    'encryption' => 'tls',
],
```

## 🔧 Configuration Yahoo

```php
'smtp' => [
    'host' => 'smtp.mail.yahoo.com',
    'port' => 587,
    'username' => 'votre-email@yahoo.com',
    'password' => 'votre-mot-de-passe-app',
    'encryption' => 'tls',
],
```

## 🧪 Test de la fonctionnalité

1. **Configurez vos identifiants** dans `config/email_config.php`
2. **Testez la configuration** : `php test_email_config.php`
3. **Testez le formulaire** :
   - Allez sur `http://localhost:8888/forgot-password`
   - Saisissez un email d'utilisateur existant
   - Vérifiez que l'email arrive dans votre boîte de réception

## 📋 Vérifications

- ✅ Configuration SMTP correcte
- ✅ Identifiants valides
- ✅ Test d'envoi réussi
- ✅ Email reçu dans la boîte de réception
- ✅ Lien de réinitialisation fonctionnel

## 🚨 Sécurité

- **Ne commitez jamais** vos identifiants dans Git
- **Utilisez des mots de passe d'application** (pas votre mot de passe principal)
- **Limitez les permissions** de votre compte email
- **Surveillez les logs** d'envoi d'emails

## 🆘 Dépannage

### Erreur d'authentification
- Vérifiez que l'authentification à 2 facteurs est activée
- Utilisez un mot de passe d'application (pas votre mot de passe principal)

### Erreur de connexion SMTP
- Vérifiez le host et le port
- Vérifiez que votre fournisseur autorise les connexions SMTP

### Email non reçu
- Vérifiez vos spams
- Vérifiez que l'adresse de destination est correcte
- Consultez les logs dans `logs/emails/`

## 📞 Support

Si vous rencontrez des problèmes :
1. Consultez les logs dans `logs/app.log`
2. Vérifiez la configuration avec `php test_email_config.php`
3. Testez avec un autre fournisseur d'email
