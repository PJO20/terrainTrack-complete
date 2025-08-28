<?php

namespace App\Service;

class ResponseCacheService
{
    private CacheService $cache;
    private bool $enabled;
    private array $config;
    
    public function __construct()
    {
        $this->cache = new CacheService();
        
        EnvService::load();
        $this->enabled = EnvService::getBool('RESPONSE_CACHE_ENABLED', true);
        
        $this->config = [
            'default_ttl' => EnvService::getInt('RESPONSE_CACHE_TTL', 3600),
            'vary_by_user' => EnvService::getBool('RESPONSE_CACHE_VARY_USER', true),
            'compress' => EnvService::getBool('RESPONSE_CACHE_COMPRESS', true),
            'excluded_paths' => explode(',', EnvService::get('RESPONSE_CACHE_EXCLUDE', '/admin,/api,/login,/logout')),
        ];
    }
    
    /**
     * Vérifie si la réponse peut être mise en cache
     */
    public function shouldCache(string $uri, string $method = 'GET', array $headers = []): bool
    {
        if (!$this->enabled || $method !== 'GET') {
            return false;
        }
        
        // Vérifier les chemins exclus
        foreach ($this->config['excluded_paths'] as $excludedPath) {
            if (strpos($uri, trim($excludedPath)) === 0) {
                return false;
            }
        }
        
        // Ne pas cacher si il y a des paramètres de requête sensibles
        $sensitiveParams = ['token', 'password', 'secret', 'key'];
        $queryString = parse_url($uri, PHP_URL_QUERY);
        if ($queryString) {
            parse_str($queryString, $params);
            foreach ($sensitiveParams as $sensitiveParam) {
                if (isset($params[$sensitiveParam])) {
                    return false;
                }
            }
        }
        
        // Ne pas cacher si l'utilisateur est connecté et vary_by_user est désactivé
        if (!$this->config['vary_by_user'] && SessionManager::isLoggedIn()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Génère une clé de cache pour la requête
     */
    public function generateCacheKey(string $uri, array $headers = [], array $extraData = []): string
    {
        $keyParts = [
            'response',
            md5($uri)
        ];
        
        // Varier par utilisateur si configuré
        if ($this->config['vary_by_user']) {
            $user = SessionManager::getCurrentUser();
            $keyParts[] = $user ? $user['id'] : 'guest';
        }
        
        // Varier par certains headers (Accept, Accept-Language, etc.)
        $varyHeaders = ['Accept', 'Accept-Language', 'Accept-Encoding'];
        foreach ($varyHeaders as $header) {
            if (isset($headers[$header])) {
                $keyParts[] = substr(md5($headers[$header]), 0, 8);
            }
        }
        
        // Ajouter des données supplémentaires
        if (!empty($extraData)) {
            $keyParts[] = substr(md5(serialize($extraData)), 0, 8);
        }
        
        return implode('_', $keyParts);
    }
    
    /**
     * Récupère une réponse depuis le cache
     */
    public function getResponse(string $cacheKey): ?array
    {
        if (!$this->enabled) {
            return null;
        }
        
        $cached = $this->cache->get($cacheKey);
        
        if ($cached === null) {
            return null;
        }
        
        // Décompresser si nécessaire
        if ($this->config['compress'] && isset($cached['compressed']) && $cached['compressed']) {
            $cached['content'] = gzuncompress($cached['content']);
        }
        
        return $cached;
    }
    
    /**
     * Stocke une réponse dans le cache
     */
    public function storeResponse(string $cacheKey, string $content, array $headers = [], int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        $ttl = $ttl ?? $this->config['default_ttl'];
        
        $cacheData = [
            'content' => $content,
            'headers' => $headers,
            'timestamp' => time(),
            'compressed' => false
        ];
        
        // Compresser si la taille le justifie et si activé
        if ($this->config['compress'] && strlen($content) > 1024) {
            $compressed = gzcompress($content, 9);
            if ($compressed && strlen($compressed) < strlen($content)) {
                $cacheData['content'] = $compressed;
                $cacheData['compressed'] = true;
            }
        }
        
        return $this->cache->set($cacheKey, $cacheData, $ttl);
    }
    
    /**
     * Middleware de cache pour les réponses
     */
    public function middleware(callable $next): mixed
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $headers = getallheaders() ?: [];
        
        // Vérifier si on peut utiliser le cache
        if (!$this->shouldCache($uri, $method, $headers)) {
            return $next();
        }
        
        // Générer la clé de cache
        $cacheKey = $this->generateCacheKey($uri, $headers);
        
        // Essayer de récupérer depuis le cache
        $cached = $this->getResponse($cacheKey);
        if ($cached !== null) {
            // Ajouter les headers de cache
            $this->addCacheHeaders($cached);
            
            // Retourner la réponse cachée
            echo $cached['content'];
            return null;
        }
        
        // Capturer la sortie
        ob_start();
        $result = $next();
        $content = ob_get_contents();
        ob_end_clean();
        
        // Stocker en cache si la réponse est valide
        if (!empty($content) && http_response_code() === 200) {
            $responseHeaders = $this->getResponseHeaders();
            $this->storeResponse($cacheKey, $content, $responseHeaders);
        }
        
        echo $content;
        return $result;
    }
    
    /**
     * Cache spécifique pour les pages
     */
    public function cachePage(string $pageName, callable $generator, int $ttl = null, array $dependencies = []): string
    {
        $user = SessionManager::getCurrentUser();
        $userId = $user ? $user['id'] : 'guest';
        
        $cacheKey = "page_{$pageName}_{$userId}";
        
        // Ajouter les dépendances à la clé
        if (!empty($dependencies)) {
            $cacheKey .= '_' . md5(serialize($dependencies));
        }
        
        return $this->cache->remember($cacheKey, function() use ($generator) {
            ob_start();
            $generator();
            return ob_get_clean();
        }, $ttl ?? $this->config['default_ttl']);
    }
    
    /**
     * Cache des fragments de page (partials)
     */
    public function cacheFragment(string $fragmentName, callable $generator, int $ttl = 300, array $context = []): string
    {
        $cacheKey = "fragment_{$fragmentName}";
        
        if (!empty($context)) {
            $cacheKey .= '_' . md5(serialize($context));
        }
        
        return $this->cache->remember($cacheKey, function() use ($generator) {
            ob_start();
            $generator();
            return ob_get_clean();
        }, $ttl);
    }
    
    /**
     * Cache conditionnel basé sur l'ETag
     */
    public function handleConditionalCache(string $content, array $extraData = []): void
    {
        $etag = '"' . md5($content . serialize($extraData)) . '"';
        
        header("ETag: $etag");
        header('Cache-Control: private, max-age=0, must-revalidate');
        
        $clientEtag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        
        if ($clientEtag === $etag) {
            http_response_code(304);
            exit;
        }
    }
    
    /**
     * Cache avec Last-Modified
     */
    public function handleLastModified(int $lastModified): bool
    {
        $lastModifiedHeader = gmdate('D, d M Y H:i:s', $lastModified) . ' GMT';
        header("Last-Modified: $lastModifiedHeader");
        
        $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
        
        if ($ifModifiedSince === $lastModifiedHeader) {
            http_response_code(304);
            return true;
        }
        
        return false;
    }
    
    /**
     * Invalide le cache pour un utilisateur
     */
    public function invalidateUserCache(int $userId): int
    {
        return $this->cache->deleteByPattern("*_{$userId}_*");
    }
    
    /**
     * Invalide le cache d'une page spécifique
     */
    public function invalidatePage(string $pageName): int
    {
        return $this->cache->deleteByPattern("page_{$pageName}_*");
    }
    
    /**
     * Invalide les fragments de cache
     */
    public function invalidateFragment(string $fragmentName): int
    {
        return $this->cache->deleteByPattern("fragment_{$fragmentName}_*");
    }
    
    /**
     * Invalide le cache des réponses pour un pattern d'URI
     */
    public function invalidateByUri(string $uriPattern): int
    {
        $pattern = 'response_' . md5($uriPattern) . '*';
        return $this->cache->deleteByPattern($pattern);
    }
    
    /**
     * Purge complète du cache des réponses
     */
    public function purgeAll(): bool
    {
        return $this->cache->deleteByPattern('response_*') > 0;
    }
    
    /**
     * Statistiques du cache des réponses
     */
    public function getStats(): array
    {
        $stats = $this->cache->getStats();
        
        // Compter spécifiquement les réponses cachées
        $responseFiles = 0;
        $fragmentFiles = 0;
        $pageFiles = 0;
        
        if (is_dir($stats['cache_dir'])) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($stats['cache_dir'])
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'cache') {
                    $content = file_get_contents($file->getPathname());
                    if (strpos($content, 'response_') !== false) {
                        $responseFiles++;
                    } elseif (strpos($content, 'fragment_') !== false) {
                        $fragmentFiles++;
                    } elseif (strpos($content, 'page_') !== false) {
                        $pageFiles++;
                    }
                }
            }
        }
        
        return array_merge($stats, [
            'response_files' => $responseFiles,
            'fragment_files' => $fragmentFiles,
            'page_files' => $pageFiles,
            'enabled' => $this->enabled,
            'config' => $this->config
        ]);
    }
    
    /**
     * Configuration du cache par route
     */
    public function configureRoute(string $route, array $config): void
    {
        $routeConfig = [
            'ttl' => $config['ttl'] ?? $this->config['default_ttl'],
            'vary_by_user' => $config['vary_by_user'] ?? $this->config['vary_by_user'],
            'enabled' => $config['enabled'] ?? true
        ];
        
        $this->cache->set("route_config_{$route}", $routeConfig, 86400); // 24h
    }
    
    /**
     * Ajoute les headers de cache à la réponse
     */
    private function addCacheHeaders(array $cached): void
    {
        // Headers de cache
        header('X-Cache: HIT');
        header('X-Cache-Time: ' . date('Y-m-d H:i:s', $cached['timestamp']));
        
        // Headers de la réponse originale
        foreach ($cached['headers'] as $name => $value) {
            header("$name: $value");
        }
        
        // ETag basé sur le contenu
        $etag = '"' . md5($cached['content']) . '"';
        header("ETag: $etag");
    }
    
    /**
     * Récupère les headers de la réponse actuelle
     */
    private function getResponseHeaders(): array
    {
        $headers = [];
        
        foreach (headers_list() as $header) {
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $headers[trim($parts[0])] = trim($parts[1]);
            }
        }
        
        return $headers;
    }
}
