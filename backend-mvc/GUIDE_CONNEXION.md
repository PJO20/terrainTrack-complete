# 🔐 GUIDE DE CONNEXION - TerrainTrack

## ✅ **STATUT : CONNEXION PRÊTE !**

### 🎯 **IDENTIFIANTS DE CONNEXION :**

```
URL: http://localhost:8888/login
Email: momo@gmail.com
Mot de passe: 123456789
Rôle: Administrateur
```

---

## 🧪 **TESTS EFFECTUÉS :**

### ✅ **Backend :**
- ✅ EnvService fonctionne
- ✅ Base de données connectée
- ✅ Utilisateur `momo@gmail.com` trouvé
- ✅ Mot de passe `123456789` vérifié
- ✅ Rate limiting opérationnel
- ✅ AuthController initialisé
- ✅ Validation des données OK

### ✅ **Sécurité :**
- ✅ Headers de sécurité appliqués
- ✅ CSRF protection active
- ✅ Sessions sécurisées
- ✅ Mots de passe hashés (bcrypt)
- ✅ Rate limiting configuré

---

## 🌐 **PROCÉDURE DE CONNEXION :**

### **Étape 1 : Accéder à la page**
1. Ouvrez votre navigateur
2. Allez sur `http://localhost:8888/login`
3. Vérifiez que la page se charge correctement

### **Étape 2 : Saisir les identifiants**
1. **Email** : `momo@gmail.com`
2. **Mot de passe** : `123456789`
3. Cliquez sur "Se connecter"

### **Étape 3 : Vérification**
- ✅ Redirection vers `/dashboard`
- ✅ Menu utilisateur visible (coin supérieur droit)
- ✅ Nom "PJ" affiché
- ✅ Rôle "Administrateur"

---

## 🚨 **EN CAS DE PROBLÈME :**

### **Si la page ne se charge pas :**
```bash
# Vérifier que MAMP est démarré
curl -I http://localhost:8888/login

# Vérifier les logs
tail -f logs/app.log
```

### **Si "Identifiants incorrects" :**
```bash
# Réinitialiser le mot de passe
php -r "
require_once 'vendor/autoload.php';
\$repo = new \App\Repository\UserRepository();
\$repo->updatePassword(7, password_hash('123456789', PASSWORD_DEFAULT));
echo 'Mot de passe réinitialisé\n';
"
```

### **Si erreur CSRF :**
1. Actualisez la page (F5)
2. Réessayez la connexion
3. Vérifiez que JavaScript est activé

### **Si rate limiting :**
```bash
# Réinitialiser le rate limiting
php -r "
\$service = new \App\Service\RateLimitService();
\$service->reset('login_127.0.0.1', 'login');
echo 'Rate limiting réinitialisé\n';
"
```

---

## 🔧 **DIAGNOSTIC RAPIDE :**

### **Test complet :**
```bash
php test_login_complete.php
```

### **Vérifier la base de données :**
```bash
php -r "
require_once 'vendor/autoload.php';
\$repo = new \App\Repository\UserRepository();
\$user = \$repo->findByEmail('momo@gmail.com');
echo 'Utilisateur: ' . \$user->getEmail() . '\n';
echo 'Rôle: ' . \$user->getRole() . '\n';
echo 'Test mot de passe: ' . (password_verify('123456789', \$user->getPassword()) ? 'OK' : 'KO') . '\n';
"
```

### **Vérifier les services :**
```bash
php test_security_fixes.php
```

---

## 📊 **AUTRES UTILISATEURS DISPONIBLES :**

Si vous voulez tester avec d'autres comptes, voici comment les lister :

```bash
php -r "
require_once 'vendor/autoload.php';
\$pdo = \App\Service\Database::connect();
\$stmt = \$pdo->query('SELECT email, name, role FROM users LIMIT 10');
while (\$user = \$stmt->fetch()) {
    echo \$user['email'] . ' (' . \$user['role'] . ') - ' . \$user['name'] . '\n';
}
"
```

---

## 🎉 **CONNEXION RÉUSSIE :**

Une fois connecté, vous devriez voir :
- 🏠 **Dashboard** avec les statistiques
- 👤 **Profil utilisateur** en haut à droite
- 🛡️ **Menu administrateur** (si admin)
- 📊 **Toutes les fonctionnalités** disponibles

---

## 🔒 **SÉCURITÉ ACTIVE :**

Votre application est maintenant protégée par :
- 🛡️ **Rate limiting** (5 tentatives / 15 min)
- 🔐 **Sessions sécurisées** (HTTPS ready)
- 🚫 **Protection CSRF**
- 🔒 **Headers de sécurité** complets
- 🔑 **Mots de passe hashés** (bcrypt)

**Votre application TerrainTrack est prête et sécurisée !** 🚀

