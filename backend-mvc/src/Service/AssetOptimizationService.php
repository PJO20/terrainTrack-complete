<?php

namespace App\Service;

class AssetOptimizationService
{
    private string $publicDir;
    private string $cacheDir;
    private bool $isProduction;
    
    public function __construct()
    {
        EnvService::load();
        $this->publicDir = EnvService::get('PUBLIC_DIR', '/Applications/MAMP/htdocs/exemple/backend-mvc/public');
        $this->cacheDir = EnvService::get('CACHE_DIR', '/Applications/MAMP/htdocs/exemple/backend-mvc/var/cache');
        $this->isProduction = EnvService::get('APP_ENV', 'development') === 'production';
        
        // Créer les dossiers nécessaires
        $assetsDir = $this->cacheDir . '/assets';
        if (!is_dir($assetsDir)) {
            mkdir($assetsDir, 0755, true);
        }
    }
    
    /**
     * Compile et minifie les fichiers CSS
     */
    public function compileCSS(array $files, string $outputName = 'app.min.css'): string
    {
        $cacheKey = 'css_' . md5(serialize($files) . $outputName);
        $outputPath = $this->cacheDir . '/assets/' . $outputName;
        $publicPath = '/assets/cache/' . $outputName;
        
        // Vérifier si le fichier compilé existe et est à jour
        if (file_exists($outputPath) && !$this->needsRecompilation($files, $outputPath)) {
            return $publicPath;
        }
        
        $combinedCSS = '';
        
        foreach ($files as $file) {
            $filePath = $this->resolveAssetPath($file);
            
            if (file_exists($filePath)) {
                $css = file_get_contents($filePath);
                
                // Traitement des imports CSS
                $css = $this->processImports($css, dirname($filePath));
                
                // Traitement des URLs relatives
                $css = $this->processUrls($css, dirname($file));
                
                $combinedCSS .= "/* Source: $file */\n" . $css . "\n\n";
            } else {
                error_log("Asset not found: $filePath");
            }
        }
        
        // Minification en production
        if ($this->isProduction) {
            $combinedCSS = $this->minifyCSS($combinedCSS);
        }
        
        // Ajouter l'en-tête de cache busting
        $combinedCSS = "/* Generated: " . date('Y-m-d H:i:s') . " */\n" . $combinedCSS;
        
        // Sauvegarder le fichier compilé
        file_put_contents($outputPath, $combinedCSS);
        
        // Copier vers le dossier public
        $publicOutputPath = $this->publicDir . '/assets/cache/' . $outputName;
        $publicCacheDir = dirname($publicOutputPath);
        
        if (!is_dir($publicCacheDir)) {
            mkdir($publicCacheDir, 0755, true);
        }
        
        copy($outputPath, $publicOutputPath);
        
        return $publicPath;
    }
    
    /**
     * Compile et minifie les fichiers JavaScript
     */
    public function compileJS(array $files, string $outputName = 'app.min.js'): string
    {
        $cacheKey = 'js_' . md5(serialize($files) . $outputName);
        $outputPath = $this->cacheDir . '/assets/' . $outputName;
        $publicPath = '/assets/cache/' . $outputName;
        
        // Vérifier si le fichier compilé existe et est à jour
        if (file_exists($outputPath) && !$this->needsRecompilation($files, $outputPath)) {
            return $publicPath;
        }
        
        $combinedJS = '';
        
        foreach ($files as $file) {
            $filePath = $this->resolveAssetPath($file);
            
            if (file_exists($filePath)) {
                $js = file_get_contents($filePath);
                
                // Validation syntaxique basique
                if ($this->validateJS($js)) {
                    $combinedJS .= "/* Source: $file */\n" . $js . "\n;\n\n";
                } else {
                    error_log("JavaScript syntax error in: $filePath");
                }
            } else {
                error_log("Asset not found: $filePath");
            }
        }
        
        // Minification en production
        if ($this->isProduction) {
            $combinedJS = $this->minifyJS($combinedJS);
        }
        
        // Ajouter l'en-tête
        $combinedJS = "/* Generated: " . date('Y-m-d H:i:s') . " */\n" . $combinedJS;
        
        // Sauvegarder le fichier compilé
        file_put_contents($outputPath, $combinedJS);
        
        // Copier vers le dossier public
        $publicOutputPath = $this->publicDir . '/assets/cache/' . $outputName;
        $publicCacheDir = dirname($publicOutputPath);
        
        if (!is_dir($publicCacheDir)) {
            mkdir($publicCacheDir, 0755, true);
        }
        
        copy($outputPath, $publicOutputPath);
        
        return $publicPath;
    }
    
    /**
     * Optimise les images (redimensionnement et compression)
     */
    public function optimizeImage(string $sourcePath, string $outputPath = null, array $options = []): string
    {
        $defaultOptions = [
            'max_width' => 1920,
            'max_height' => 1080,
            'quality' => 85,
            'format' => null // auto-detect
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        if (!file_exists($sourcePath)) {
            throw new \Exception("Image source not found: $sourcePath");
        }
        
        // Générer le chemin de sortie si non spécifié
        if ($outputPath === null) {
            $info = pathinfo($sourcePath);
            $outputPath = $this->cacheDir . '/assets/images/' . $info['filename'] . '_optimized.' . $info['extension'];
        }
        
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Vérifier si l'image optimisée existe déjà et est à jour
        if (file_exists($outputPath) && filemtime($outputPath) > filemtime($sourcePath)) {
            return $outputPath;
        }
        
        // Obtenir les informations de l'image
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            throw new \Exception("Invalid image: $sourcePath");
        }
        
        [$width, $height, $type] = $imageInfo;
        
        // Calculer les nouvelles dimensions
        $newDimensions = $this->calculateDimensions($width, $height, $options['max_width'], $options['max_height']);
        
        // Créer l'image source
        $sourceImage = $this->createImageFromType($sourcePath, $type);
        if (!$sourceImage) {
            throw new \Exception("Cannot create image resource from: $sourcePath");
        }
        
        // Créer l'image de destination
        $destImage = imagecreatetruecolor($newDimensions['width'], $newDimensions['height']);
        
        // Préserver la transparence pour PNG et GIF
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($destImage, false);
            imagesavealpha($destImage, true);
            $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
            imagefill($destImage, 0, 0, $transparent);
        }
        
        // Redimensionner
        imagecopyresampled(
            $destImage, $sourceImage,
            0, 0, 0, 0,
            $newDimensions['width'], $newDimensions['height'],
            $width, $height
        );
        
        // Sauvegarder selon le format
        $format = $options['format'] ?: $this->getImageFormat($type);
        $this->saveOptimizedImage($destImage, $outputPath, $format, $options['quality']);
        
        // Libérer la mémoire
        imagedestroy($sourceImage);
        imagedestroy($destImage);
        
        return $outputPath;
    }
    
    /**
     * Génère un manifest des assets avec hash pour cache busting
     */
    public function generateManifest(): array
    {
        $manifest = [];
        $assetsDir = $this->publicDir . '/assets';
        
        if (is_dir($assetsDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($assetsDir)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = str_replace($this->publicDir, '', $file->getPathname());
                    $hash = substr(md5_file($file->getPathname()), 0, 8);
                    $manifest[$relativePath] = $relativePath . '?v=' . $hash;
                }
            }
        }
        
        // Sauvegarder le manifest
        $manifestPath = $this->publicDir . '/assets/manifest.json';
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
        
        return $manifest;
    }
    
    /**
     * Précharge les assets critiques
     */
    public function generatePreloadHeaders(array $criticalAssets): string
    {
        $headers = [];
        
        foreach ($criticalAssets as $asset => $type) {
            $assetPath = $this->resolvePublicPath($asset);
            $headers[] = "<$assetPath>; rel=preload; as=$type";
        }
        
        return implode(', ', $headers);
    }
    
    /**
     * Génère les tags HTML pour les assets optimisés
     */
    public function generateAssetTags(array $assets, string $type = 'css'): string
    {
        $tags = [];
        
        if ($type === 'css') {
            $compiledPath = $this->compileCSS($assets);
            $tags[] = "<link rel=\"stylesheet\" href=\"$compiledPath\">";
        } elseif ($type === 'js') {
            $compiledPath = $this->compileJS($assets);
            $tags[] = "<script src=\"$compiledPath\"></script>";
        }
        
        return implode("\n", $tags);
    }
    
    /**
     * Minification CSS
     */
    private function minifyCSS(string $css): string
    {
        // Supprimer les commentaires
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);
        
        // Supprimer les espaces inutiles
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Supprimer les espaces autour des caractères spéciaux
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
        
        // Supprimer le dernier point-virgule des blocs
        $css = preg_replace('/;}/', '}', $css);
        
        return trim($css);
    }
    
    /**
     * Minification JavaScript basique
     */
    private function minifyJS(string $js): string
    {
        // Supprimer les commentaires sur une ligne
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Supprimer les commentaires multi-lignes
        $js = preg_replace('/\/\*.*?\*\//s', '', $js);
        
        // Supprimer les espaces inutiles (attention aux chaînes)
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Supprimer les espaces autour des opérateurs
        $js = preg_replace('/\s*([{}();,=+\-*\/])\s*/', '$1', $js);
        
        return trim($js);
    }
    
    /**
     * Traite les imports CSS (@import)
     */
    private function processImports(string $css, string $basePath): string
    {
        return preg_replace_callback('/@import\s+["\']([^"\']+)["\'];?/', function($matches) use ($basePath) {
            $importPath = $basePath . '/' . $matches[1];
            if (file_exists($importPath)) {
                return file_get_contents($importPath);
            }
            return $matches[0];
        }, $css);
    }
    
    /**
     * Traite les URLs relatives dans le CSS
     */
    private function processUrls(string $css, string $basePath): string
    {
        return preg_replace_callback('/url\(["\']?([^"\'()]+)["\']?\)/', function($matches) use ($basePath) {
            $url = $matches[1];
            
            // Ignorer les URLs absolues
            if (preg_match('/^(https?:\/\/|\/|data:)/', $url)) {
                return $matches[0];
            }
            
            // Convertir en chemin relatif depuis le dossier public
            $absolutePath = realpath($basePath . '/' . $url);
            if ($absolutePath && strpos($absolutePath, $this->publicDir) === 0) {
                $relativePath = str_replace($this->publicDir, '', $absolutePath);
                return "url('$relativePath')";
            }
            
            return $matches[0];
        }, $css);
    }
    
    /**
     * Validation syntaxique JavaScript basique
     */
    private function validateJS(string $js): bool
    {
        // Vérifications basiques
        $opens = substr_count($js, '{');
        $closes = substr_count($js, '}');
        
        if ($opens !== $closes) {
            return false;
        }
        
        $opens = substr_count($js, '(');
        $closes = substr_count($js, ')');
        
        if ($opens !== $closes) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Vérifie si une recompilation est nécessaire
     */
    private function needsRecompilation(array $sourceFiles, string $outputFile): bool
    {
        if (!file_exists($outputFile)) {
            return true;
        }
        
        $outputTime = filemtime($outputFile);
        
        foreach ($sourceFiles as $file) {
            $filePath = $this->resolveAssetPath($file);
            if (file_exists($filePath) && filemtime($filePath) > $outputTime) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Résout le chemin complet d'un asset
     */
    private function resolveAssetPath(string $path): string
    {
        if (strpos($path, '/') === 0) {
            return $this->publicDir . $path;
        }
        
        return $this->publicDir . '/assets/' . $path;
    }
    
    /**
     * Résout le chemin public d'un asset
     */
    private function resolvePublicPath(string $path): string
    {
        if (strpos($path, '/') === 0) {
            return $path;
        }
        
        return '/assets/' . $path;
    }
    
    /**
     * Calcule les nouvelles dimensions pour le redimensionnement
     */
    private function calculateDimensions(int $width, int $height, int $maxWidth, int $maxHeight): array
    {
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        
        if ($ratio >= 1) {
            return ['width' => $width, 'height' => $height];
        }
        
        return [
            'width' => (int)($width * $ratio),
            'height' => (int)($height * $ratio)
        ];
    }
    
    /**
     * Crée une resource image selon le type
     */
    private function createImageFromType(string $path, int $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }
    
    /**
     * Obtient le format d'image recommandé
     */
    private function getImageFormat(int $type): string
    {
        switch ($type) {
            case IMAGETYPE_PNG:
                return 'png';
            case IMAGETYPE_GIF:
                return 'gif';
            case IMAGETYPE_WEBP:
                return 'webp';
            default:
                return 'jpeg';
        }
    }
    
    /**
     * Sauvegarde l'image optimisée
     */
    private function saveOptimizedImage($image, string $path, string $format, int $quality): void
    {
        switch ($format) {
            case 'png':
                imagepng($image, $path, 9);
                break;
            case 'gif':
                imagegif($image, $path);
                break;
            case 'webp':
                imagewebp($image, $path, $quality);
                break;
            default:
                imagejpeg($image, $path, $quality);
        }
    }
}


