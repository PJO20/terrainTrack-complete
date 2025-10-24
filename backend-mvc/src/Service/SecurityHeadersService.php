<?php

namespace App\Service;

class SecurityHeadersService
{
    /**
     * Applique tous les headers de sécurité recommandés
     */
    public static function applySecurityHeaders(): void
    {
        // Éviter les en-têtes en double
        if (headers_sent()) {
            return;
        }
        
        EnvService::load();
        $isProduction = EnvService::get('APP_ENV', 'development') === 'production';
        $isHttps = EnvService::getBool('SESSION_SECURE', false);
        
        // Protection XSS
        header('X-XSS-Protection: 1; mode=block');
        
        // Empêche le MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Empêche l'affichage dans une frame (Clickjacking)
        header('X-Frame-Options: DENY');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy renforcée
        $csp = self::getContentSecurityPolicy($isProduction);
        header('Content-Security-Policy: ' . $csp);
        
        // Cross-Origin Embedder Policy - Désactivé pour permettre les tuiles OpenStreetMap
        // header('Cross-Origin-Embedder-Policy: require-corp');
        
        // Cross-Origin Opener Policy  
        header('Cross-Origin-Opener-Policy: same-origin');
        
        // Cross-Origin Resource Policy - Désactivé pour permettre les tuiles OpenStreetMap
        // header('Cross-Origin-Resource-Policy: same-origin');
        
        // HTTP Strict Transport Security (HTTPS uniquement)
        if ($isHttps && $isProduction) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Permissions Policy (anciennement Feature Policy)
        $permissionsPolicy = self::getPermissionsPolicy();
        header('Permissions-Policy: ' . $permissionsPolicy);
        
        // Cache control pour les pages sensibles
        if (self::isSensitivePage()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
        
        // Headers personnalisés pour l'application
        header('X-Powered-By: TerrainTrack');
        
        // En développement, ajouter des headers utiles
        if (!$isProduction) {
            header('X-Debug-Mode: enabled');
        }
    }
    
    /**
     * Génère la Content Security Policy
     */
    private static function getContentSecurityPolicy(bool $isProduction = false): string
    {
        $policies = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "font-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.gstatic.com data:",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https://*.openstreetmap.org https://*.tile.openstreetmap.org",
            "media-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'"
        ];
        
        // En production, politique plus stricte
        if ($isProduction) {
            $policies = array_map(function($policy) {
                // Retirer 'unsafe-inline' et 'unsafe-eval' en production
                return str_replace(["'unsafe-inline'", "'unsafe-eval'"], '', $policy);
            }, $policies);
        }
        
        return implode('; ', $policies);
    }
    
    /**
     * Génère la Permissions Policy
     */
    private static function getPermissionsPolicy(): string
    {
        $policies = [
            'geolocation=(self)',
            'microphone=()',
            'camera=()',
            'payment=()',
            'usb=()',
            'magnetometer=()',
            'gyroscope=()',
            'speaker=(self)',
            'vibrate=()',
            'fullscreen=(self)',
            'sync-xhr=(self)'
        ];
        
        return implode(', ', $policies);
    }
    
    /**
     * Vérifie si la page actuelle est sensible (nécessite no-cache)
     */
    private static function isSensitivePage(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $sensitivePaths = [
            '/login',
            '/logout', 
            '/profile',
            '/settings',
            '/admin',
            '/dashboard'
        ];
        
        foreach ($sensitivePaths as $path) {
            if (strpos($uri, $path) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Headers spécifiques pour les API JSON
     */
    public static function applyJsonApiHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
    
    /**
     * Headers pour les fichiers téléchargés
     */
    public static function applyDownloadHeaders(string $filename, string $contentType = 'application/octet-stream'): void
    {
        if (headers_sent()) {
            return;
        }
        
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
    
    /**
     * Headers pour les uploads de fichiers
     */
    public static function applyUploadHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        
        // Limite de taille d'upload
        $maxSize = EnvService::get('MAX_UPLOAD_SIZE', '10M');
        header('X-Upload-Max-Size: ' . $maxSize);
    }
    
    /**
     * Headers CORS pour les API (si nécessaire)
     */
    public static function applyCorsHeaders(array $allowedOrigins = ['*'], array $allowedMethods = ['GET', 'POST']): void
    {
        if (headers_sent()) {
            return;
        }
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
        }
        
        header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }
    
    /**
     * Supprime les headers qui révèlent des informations sensibles
     */
    public static function removeServerHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        
        // Supprimer/Remplacer les headers révélateurs
        header_remove('X-Powered-By');
        header_remove('Server');
        
        // Headers personnalisés neutres
        header('Server: WebServer');
    }
    
    /**
     * Headers pour les erreurs (404, 500, etc.)
     */
    public static function applyErrorPageHeaders(int $statusCode): void
    {
        if (headers_sent()) {
            return;
        }
        
        http_response_code($statusCode);
        
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        
        // Log les erreurs 404 et 500 pour monitoring
        if (in_array($statusCode, [404, 500])) {
            self::logSecurityEvent('http_error', [
                'status_code' => $statusCode,
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
            ]);
        }
    }
    
    /**
     * Log des événements de sécurité
     */
    private static function logSecurityEvent(string $event, array $data = []): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => 'security_headers_' . $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'data' => $data
        ];
        
        EnvService::load();
        $logPath = EnvService::get('LOG_PATH', '/Applications/MAMP/htdocs/exemple/backend-mvc/logs/app.log');
        $logDir = dirname($logPath);
        $securityLogPath = $logDir . '/security.log';
        
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($securityLogPath, $logLine, FILE_APPEND | LOCK_EX);
    }
}


