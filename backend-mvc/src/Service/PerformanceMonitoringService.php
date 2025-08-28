<?php

namespace App\Service;

class PerformanceMonitoringService
{
    private static array $timers = [];
    private static array $counters = [];
    private static array $metrics = [];
    private static float $requestStartTime;
    private static int $memoryStart;
    
    public function __construct()
    {
        if (!isset(self::$requestStartTime)) {
            self::$requestStartTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
            self::$memoryStart = memory_get_usage(true);
        }
    }
    
    /**
     * Démarre un timer
     */
    public static function startTimer(string $name): void
    {
        self::$timers[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true)
        ];
    }
    
    /**
     * Arrête un timer et retourne la durée
     */
    public static function stopTimer(string $name): float
    {
        if (!isset(self::$timers[$name])) {
            return 0.0;
        }
        
        $duration = microtime(true) - self::$timers[$name]['start'];
        $memoryUsed = memory_get_usage(true) - self::$timers[$name]['memory_start'];
        
        self::$metrics[$name] = [
            'duration' => $duration,
            'memory_used' => $memoryUsed,
            'timestamp' => time()
        ];
        
        unset(self::$timers[$name]);
        
        return $duration;
    }
    
    /**
     * Mesure le temps d'exécution d'une fonction
     */
    public static function measureTime(string $name, callable $callback): mixed
    {
        self::startTimer($name);
        $result = $callback();
        self::stopTimer($name);
        
        return $result;
    }
    
    /**
     * Incrémente un compteur
     */
    public static function incrementCounter(string $name, int $value = 1): void
    {
        if (!isset(self::$counters[$name])) {
            self::$counters[$name] = 0;
        }
        self::$counters[$name] += $value;
    }
    
    /**
     * Enregistre une métrique personnalisée
     */
    public static function recordMetric(string $name, mixed $value, string $type = 'gauge'): void
    {
        self::$metrics[$name] = [
            'value' => $value,
            'type' => $type,
            'timestamp' => time()
        ];
    }
    
    /**
     * Surveille l'utilisation de la mémoire
     */
    public static function checkMemoryUsage(string $checkpoint = null): array
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = ini_get('memory_limit');
        
        $memoryData = [
            'current' => $current,
            'current_mb' => round($current / 1024 / 1024, 2),
            'peak' => $peak,
            'peak_mb' => round($peak / 1024 / 1024, 2),
            'limit' => $limit,
            'usage_percentage' => self::calculateMemoryPercentage($current, $limit)
        ];
        
        if ($checkpoint) {
            self::$metrics["memory_$checkpoint"] = $memoryData;
        }
        
        // Alerte si l'utilisation dépasse 80%
        if ($memoryData['usage_percentage'] > 80) {
            self::logPerformanceAlert('high_memory_usage', $memoryData);
        }
        
        return $memoryData;
    }
    
    /**
     * Surveille les requêtes de base de données
     */
    public static function trackDatabaseQuery(string $sql, float $duration, int $rows = 0): void
    {
        self::incrementCounter('db_queries');
        self::recordMetric('db_last_query_time', $duration, 'gauge');
        
        // Stocker les détails de la requête
        if (!isset(self::$metrics['db_queries_details'])) {
            self::$metrics['db_queries_details'] = [];
        }
        
        self::$metrics['db_queries_details'][] = [
            'sql' => substr($sql, 0, 200),
            'duration' => $duration,
            'rows' => $rows,
            'timestamp' => microtime(true)
        ];
        
        // Garder seulement les 50 dernières requêtes
        if (count(self::$metrics['db_queries_details']) > 50) {
            self::$metrics['db_queries_details'] = array_slice(self::$metrics['db_queries_details'], -50);
        }
        
        // Alerte pour les requêtes lentes
        if ($duration > 1.0) {
            self::logPerformanceAlert('slow_query', [
                'sql' => $sql,
                'duration' => $duration,
                'rows' => $rows
            ]);
        }
    }
    
    /**
     * Surveille les hits/miss du cache
     */
    public static function trackCacheOperation(string $operation, string $key, bool $hit = null): void
    {
        self::incrementCounter("cache_{$operation}");
        
        if ($hit !== null) {
            self::incrementCounter($hit ? 'cache_hits' : 'cache_misses');
        }
        
        // Calculer le ratio de hit
        $hits = self::$counters['cache_hits'] ?? 0;
        $misses = self::$counters['cache_misses'] ?? 0;
        $total = $hits + $misses;
        
        if ($total > 0) {
            self::recordMetric('cache_hit_ratio', ($hits / $total) * 100, 'percentage');
        }
    }
    
    /**
     * Génère un rapport de performance pour la requête actuelle
     */
    public static function generateReport(): array
    {
        $now = microtime(true);
        $requestDuration = $now - self::$requestStartTime;
        $memoryUsed = memory_get_usage(true) - self::$memoryStart;
        
        $report = [
            'request' => [
                'duration' => $requestDuration,
                'duration_ms' => round($requestDuration * 1000, 2),
                'memory_used' => $memoryUsed,
                'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'timestamp' => time()
            ],
            'timers' => self::$metrics,
            'counters' => self::$counters,
            'system' => [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'server_load' => sys_getloadavg(),
                'disk_free_space' => disk_free_space('/'),
            ]
        ];
        
        // Ajouter les alertes de performance
        $report['alerts'] = self::generateAlerts($report);
        
        return $report;
    }
    
    /**
     * Génère des alertes basées sur les seuils
     */
    private static function generateAlerts(array $report): array
    {
        $alerts = [];
        
        // Alerte temps de réponse
        if ($report['request']['duration'] > 2.0) {
            $alerts[] = [
                'type' => 'slow_response',
                'message' => 'Temps de réponse lent: ' . $report['request']['duration_ms'] . 'ms',
                'severity' => 'warning'
            ];
        }
        
        // Alerte mémoire
        if ($report['request']['memory_used_mb'] > 50) {
            $alerts[] = [
                'type' => 'high_memory',
                'message' => 'Utilisation mémoire élevée: ' . $report['request']['memory_used_mb'] . 'MB',
                'severity' => 'warning'
            ];
        }
        
        // Alerte requêtes DB
        $dbQueries = self::$counters['db_queries'] ?? 0;
        if ($dbQueries > 50) {
            $alerts[] = [
                'type' => 'too_many_queries',
                'message' => "Trop de requêtes DB: $dbQueries",
                'severity' => 'error'
            ];
        }
        
        // Alerte cache
        $cacheHitRatio = self::$metrics['cache_hit_ratio']['value'] ?? 100;
        if ($cacheHitRatio < 70) {
            $alerts[] = [
                'type' => 'low_cache_hit_ratio',
                'message' => "Ratio de cache faible: {$cacheHitRatio}%",
                'severity' => 'warning'
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Enregistre le rapport dans les logs
     */
    public static function logReport(): void
    {
        $report = self::generateReport();
        
        EnvService::load();
        $logPath = EnvService::get('LOG_PATH', '/Applications/MAMP/htdocs/exemple/backend-mvc/logs/app.log');
        $logDir = dirname($logPath);
        $performanceLogPath = $logDir . '/performance.log';
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $report['request']['url'],
            'method' => $report['request']['method'],
            'duration_ms' => $report['request']['duration_ms'],
            'memory_mb' => $report['request']['memory_used_mb'],
            'db_queries' => self::$counters['db_queries'] ?? 0,
            'alerts' => count($report['alerts'])
        ];
        
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($performanceLogPath, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Analyse les tendances de performance
     */
    public static function analyzeTrends(int $days = 7): array
    {
        EnvService::load();
        $logPath = EnvService::get('LOG_PATH', '/Applications/MAMP/htdocs/exemple/backend-mvc/logs/app.log');
        $logDir = dirname($logPath);
        $performanceLogPath = $logDir . '/performance.log';
        
        if (!file_exists($performanceLogPath)) {
            return ['error' => 'No performance data available'];
        }
        
        $lines = file($performanceLogPath, FILE_IGNORE_NEW_LINES);
        $data = [];
        $cutoff = time() - ($days * 24 * 3600);
        
        foreach (array_reverse($lines) as $line) {
            $entry = json_decode($line, true);
            if ($entry && strtotime($entry['timestamp']) > $cutoff) {
                $data[] = $entry;
            }
        }
        
        if (empty($data)) {
            return ['error' => 'No recent performance data'];
        }
        
        return [
            'total_requests' => count($data),
            'avg_response_time' => array_sum(array_column($data, 'duration_ms')) / count($data),
            'avg_memory_usage' => array_sum(array_column($data, 'memory_mb')) / count($data),
            'avg_db_queries' => array_sum(array_column($data, 'db_queries')) / count($data),
            'slowest_requests' => array_slice(
                array_reverse(array_sort($data, 'duration_ms')), 
                0, 10
            ),
            'most_memory_intensive' => array_slice(
                array_reverse(array_sort($data, 'memory_mb')), 
                0, 10
            ),
            'most_db_queries' => array_slice(
                array_reverse(array_sort($data, 'db_queries')), 
                0, 10
            )
        ];
    }
    
    /**
     * Middleware de monitoring automatique
     */
    public static function middleware(callable $next): mixed
    {
        $monitor = new self();
        
        self::startTimer('total_request');
        self::startTimer('controller_execution');
        
        try {
            $result = $next();
            return $result;
        } finally {
            self::stopTimer('controller_execution');
            self::stopTimer('total_request');
            
            // Log automatique en production
            if (EnvService::get('APP_ENV', 'development') === 'production') {
                self::logReport();
            }
        }
    }
    
    /**
     * Profiler pour les fonctions critiques
     */
    public static function profile(string $name, callable $callback, array $context = []): mixed
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        try {
            $result = $callback();
            
            $duration = microtime(true) - $startTime;
            $memoryUsed = memory_get_usage(true) - $startMemory;
            
            self::recordMetric("profile_$name", [
                'duration' => $duration,
                'memory' => $memoryUsed,
                'context' => $context,
                'timestamp' => time()
            ], 'profile');
            
            return $result;
            
        } catch (\Throwable $e) {
            self::recordMetric("profile_{$name}_error", [
                'error' => $e->getMessage(),
                'context' => $context,
                'timestamp' => time()
            ], 'error');
            
            throw $e;
        }
    }
    
    /**
     * Moniteur de santé système
     */
    public static function getSystemHealth(): array
    {
        return [
            'timestamp' => time(),
            'memory' => self::checkMemoryUsage(),
            'disk_space' => [
                'free_bytes' => disk_free_space('/'),
                'free_gb' => round(disk_free_space('/') / 1024 / 1024 / 1024, 2),
                'total_bytes' => disk_total_space('/'),
                'total_gb' => round(disk_total_space('/') / 1024 / 1024 / 1024, 2),
                'usage_percentage' => round((1 - disk_free_space('/') / disk_total_space('/')) * 100, 2)
            ],
            'server_load' => sys_getloadavg(),
            'php_info' => [
                'version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize')
            ]
        ];
    }
    
    /**
     * Log d'alerte de performance
     */
    private static function logPerformanceAlert(string $type, array $data): void
    {
        $alertData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'data' => $data
        ];
        
        EnvService::load();
        $logPath = EnvService::get('LOG_PATH', '/Applications/MAMP/htdocs/exemple/backend-mvc/logs/app.log');
        $logDir = dirname($logPath);
        $alertLogPath = $logDir . '/performance_alerts.log';
        
        $logLine = json_encode($alertData) . "\n";
        file_put_contents($alertLogPath, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Calcule le pourcentage d'utilisation mémoire
     */
    private static function calculateMemoryPercentage(int $current, string $limit): float
    {
        if ($limit === '-1') {
            return 0; // Pas de limite
        }
        
        $limitBytes = self::parseMemoryLimit($limit);
        if ($limitBytes === 0) {
            return 0;
        }
        
        return ($current / $limitBytes) * 100;
    }
    
    /**
     * Parse la limite mémoire PHP
     */
    private static function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $unit = strtoupper(substr($limit, -1));
        $value = (int)substr($limit, 0, -1);
        
        switch ($unit) {
            case 'G':
                return $value * 1024 * 1024 * 1024;
            case 'M':
                return $value * 1024 * 1024;
            case 'K':
                return $value * 1024;
            default:
                return (int)$limit;
        }
    }
}


