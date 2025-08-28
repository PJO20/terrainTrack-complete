<?php

namespace App\Service;

class DatabaseOptimizedService
{
    private static ?\PDO $connection = null;
    private static CacheService $cache;
    private static array $queryStats = [];
    
    public static function connect(): \PDO
    {
        if (self::$connection === null) {
            self::$cache = new CacheService();
            
            EnvService::load();
            
            $host = EnvService::get('DB_HOST', 'localhost');
            $dbname = EnvService::get('DB_NAME', 'exemple');
            $username = EnvService::get('DB_USER', 'root');
            $password = EnvService::get('DB_PASS', 'root');
            $port = EnvService::getInt('DB_PORT', 8889);
            
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            
            try {
                self::$connection = new \PDO($dsn, $username, $password, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::ATTR_PERSISTENT => false,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false, // Pour économiser la mémoire
                ]);
                
                // Optimisations MySQL
                self::$connection->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
                self::$connection->exec("SET SESSION optimizer_search_depth = 62");
                
            } catch (\PDOException $e) {
                error_log("Database connection error: " . $e->getMessage());
                
                if (EnvService::getBool('APP_DEBUG', false)) {
                    throw $e;
                } else {
                    throw new \Exception("Erreur de connexion à la base de données");
                }
            }
        }
        
        return self::$connection;
    }
    
    /**
     * Exécute une requête avec cache automatique
     */
    public static function cachedQuery(string $sql, array $params = [], int $ttl = 300): array
    {
        $cacheKey = 'query_' . md5($sql . serialize($params));
        
        return self::$cache->remember($cacheKey, function() use ($sql, $params) {
            $start = microtime(true);
            
            $pdo = self::connect();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            
            self::recordQueryStats($sql, microtime(true) - $start, count($result));
            
            return $result;
        }, $ttl);
    }
    
    /**
     * Requête optimisée pour un seul résultat
     */
    public static function fetchOne(string $sql, array $params = [], int $ttl = 300): ?array
    {
        $cacheKey = 'query_one_' . md5($sql . serialize($params));
        
        return self::$cache->remember($cacheKey, function() use ($sql, $params) {
            $start = microtime(true);
            
            $pdo = self::connect();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            self::recordQueryStats($sql, microtime(true) - $start, $result ? 1 : 0);
            
            return $result ?: null;
        }, $ttl);
    }
    
    /**
     * Requête optimisée pour une seule colonne
     */
    public static function fetchColumn(string $sql, array $params = [], int $column = 0, int $ttl = 300): mixed
    {
        $cacheKey = 'query_col_' . md5($sql . serialize($params) . $column);
        
        return self::$cache->remember($cacheKey, function() use ($sql, $params, $column) {
            $start = microtime(true);
            
            $pdo = self::connect();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchColumn($column);
            
            self::recordQueryStats($sql, microtime(true) - $start, 1);
            
            return $result;
        }, $ttl);
    }
    
    /**
     * Requête avec pagination optimisée
     */
    public static function paginate(string $sql, array $params = [], int $page = 1, int $perPage = 20, int $ttl = 300): array
    {
        $offset = ($page - 1) * $perPage;
        
        // Requête pour compter le total
        $countSql = "SELECT COUNT(*) FROM ($sql) as count_query";
        $total = self::fetchColumn($countSql, $params, 0, $ttl);
        
        // Requête paginée
        $paginatedSql = $sql . " LIMIT $perPage OFFSET $offset";
        $data = self::cachedQuery($paginatedSql, $params, $ttl);
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }
    
    /**
     * Requête de modification (INSERT/UPDATE/DELETE) avec invalidation cache
     */
    public static function execute(string $sql, array $params = [], array $cacheKeysToInvalidate = []): int
    {
        $start = microtime(true);
        
        $pdo = self::connect();
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        $rowCount = $stmt->rowCount();
        
        self::recordQueryStats($sql, microtime(true) - $start, $rowCount);
        
        // Invalider les caches spécifiés
        foreach ($cacheKeysToInvalidate as $key) {
            self::$cache->delete($key);
        }
        
        // Auto-invalidation basée sur les tables modifiées
        self::autoInvalidateCache($sql);
        
        return $rowCount;
    }
    
    /**
     * Transaction optimisée avec rollback automatique
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connect();
        
        try {
            $pdo->beginTransaction();
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Batch insert optimisé
     */
    public static function batchInsert(string $table, array $data, int $batchSize = 1000): int
    {
        if (empty($data)) {
            return 0;
        }
        
        $pdo = self::connect();
        $totalInserted = 0;
        
        // Préparer la requête
        $columns = array_keys($data[0]);
        $placeholders = '(' . str_repeat('?,', count($columns) - 1) . '?)';
        
        foreach (array_chunk($data, $batchSize) as $batch) {
            $values = [];
            $params = [];
            
            foreach ($batch as $row) {
                $values[] = $placeholders;
                $params = array_merge($params, array_values($row));
            }
            
            $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES " . implode(',', $values);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $totalInserted += $stmt->rowCount();
        }
        
        // Invalider les caches liés à cette table
        self::$cache->deleteByPattern("query_*{$table}*");
        
        return $totalInserted;
    }
    
    /**
     * Upsert optimisé (INSERT ON DUPLICATE KEY UPDATE)
     */
    public static function upsert(string $table, array $data, array $updateColumns = []): int
    {
        if (empty($data)) {
            return 0;
        }
        
        $pdo = self::connect();
        $columns = array_keys($data);
        $placeholders = ':' . implode(', :', $columns);
        
        $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES ($placeholders)";
        
        if (!empty($updateColumns)) {
            $updates = [];
            foreach ($updateColumns as $col) {
                $updates[] = "$col = VALUES($col)";
            }
            $sql .= " ON DUPLICATE KEY UPDATE " . implode(', ', $updates);
        }
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($data);
        
        // Invalider les caches
        self::$cache->deleteByPattern("query_*{$table}*");
        
        return $stmt->rowCount();
    }
    
    /**
     * Recherche avec index full-text
     */
    public static function fullTextSearch(string $table, array $columns, string $searchTerm, array $conditions = [], int $ttl = 300): array
    {
        $columnsStr = implode(',', $columns);
        $whereClause = '';
        $params = [];
        
        if (!empty($conditions)) {
            $whereConditions = [];
            foreach ($conditions as $column => $value) {
                $whereConditions[] = "$column = ?";
                $params[] = $value;
            }
            $whereClause = " AND " . implode(' AND ', $whereConditions);
        }
        
        $sql = "SELECT *, MATCH($columnsStr) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance 
                FROM $table 
                WHERE MATCH($columnsStr) AGAINST(? IN NATURAL LANGUAGE MODE) $whereClause
                ORDER BY relevance DESC";
        
        array_unshift($params, $searchTerm, $searchTerm);
        
        return self::cachedQuery($sql, $params, $ttl);
    }
    
    /**
     * Statistiques de performance des requêtes
     */
    public static function getQueryStats(): array
    {
        return [
            'total_queries' => count(self::$queryStats),
            'average_time' => count(self::$queryStats) > 0 ? array_sum(array_column(self::$queryStats, 'time')) / count(self::$queryStats) : 0,
            'slowest_queries' => array_slice(
                array_reverse(array_sort(self::$queryStats, 'time')), 
                0, 10
            ),
            'most_frequent' => array_count_values(array_column(self::$queryStats, 'sql'))
        ];
    }
    
    /**
     * Optimisation automatique des tables
     */
    public static function optimizeTables(array $tables = []): array
    {
        $pdo = self::connect();
        $results = [];
        
        if (empty($tables)) {
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("OPTIMIZE TABLE $table");
            $results[$table] = $stmt->fetch();
        }
        
        return $results;
    }
    
    /**
     * Analyse des index manquants
     */
    public static function analyzeIndexes(string $table): array
    {
        $pdo = self::connect();
        
        // Récupérer les index existants
        $stmt = $pdo->query("SHOW INDEXES FROM $table");
        $existingIndexes = $stmt->fetchAll();
        
        // Analyser les requêtes lentes
        $stmt = $pdo->query("
            SELECT sql_text, rows_examined, rows_sent 
            FROM performance_schema.events_statements_history 
            WHERE object_name = '$table' 
            AND rows_examined > rows_sent * 10
            ORDER BY rows_examined DESC 
            LIMIT 10
        ");
        $slowQueries = $stmt->fetchAll();
        
        return [
            'existing_indexes' => $existingIndexes,
            'slow_queries' => $slowQueries,
            'suggestions' => $this->suggestIndexes($table, $slowQueries)
        ];
    }
    
    /**
     * Connection pooling simple
     */
    public static function closeConnection(): void
    {
        self::$connection = null;
    }
    
    /**
     * Enregistre les statistiques de requête
     */
    private static function recordQueryStats(string $sql, float $time, int $rowCount): void
    {
        self::$queryStats[] = [
            'sql' => substr($sql, 0, 100), // Truncate pour le stockage
            'time' => $time,
            'rows' => $rowCount,
            'timestamp' => time()
        ];
        
        // Garder seulement les 1000 dernières requêtes
        if (count(self::$queryStats) > 1000) {
            self::$queryStats = array_slice(self::$queryStats, -1000);
        }
        
        // Log les requêtes lentes
        if ($time > 1.0) { // Plus d'1 seconde
            error_log("Slow query detected: {$time}s - " . substr($sql, 0, 200));
        }
    }
    
    /**
     * Auto-invalidation du cache basée sur les tables modifiées
     */
    private static function autoInvalidateCache(string $sql): void
    {
        // Extraire les noms de tables de la requête
        preg_match_all('/(?:FROM|JOIN|UPDATE|INSERT INTO|DELETE FROM)\s+`?(\w+)`?/i', $sql, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $table) {
                self::$cache->deleteByPattern("query_*{$table}*");
                
                // Invalidations spécifiques par table
                switch ($table) {
                    case 'users':
                        self::$cache->deleteByPattern('user_*');
                        self::$cache->deleteByPattern('permissions_*');
                        break;
                    case 'interventions':
                        self::$cache->deleteByPattern('dashboard_stats_*');
                        break;
                }
            }
        }
    }
}


