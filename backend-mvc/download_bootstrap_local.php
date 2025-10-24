<?php
/**
 * Script pour télécharger Bootstrap localement
 * Usage: php download_bootstrap_local.php
 */

class BootstrapDownloader
{
    private string $assetsDir;
    private string $bootstrapVersion;
    
    public function __construct()
    {
        $this->assetsDir = __DIR__ . '/assets';
        $this->bootstrapVersion = '5.1.3';
    }
    
    /**
     * Télécharge Bootstrap localement
     */
    public function downloadBootstrap(): bool
    {
        echo "🔄 Téléchargement de Bootstrap {$this->bootstrapVersion}...\n";
        
        // Créer les dossiers nécessaires
        $this->createDirectories();
        
        // URLs Bootstrap CDN
        $files = [
            'css' => "https://cdn.jsdelivr.net/npm/bootstrap@{$this->bootstrapVersion}/dist/css/bootstrap.min.css",
            'js' => "https://cdn.jsdelivr.net/npm/bootstrap@{$this->bootstrapVersion}/dist/js/bootstrap.bundle.min.js"
        ];
        
        $success = true;
        
        foreach ($files as $type => $url) {
            echo "📥 Téléchargement du fichier $type...\n";
            
            $content = $this->downloadFile($url);
            if ($content === false) {
                echo "❌ Erreur lors du téléchargement de $type\n";
                $success = false;
                continue;
            }
            
            $filename = "bootstrap.min.$type";
            $filepath = $this->assetsDir . "/$type/$filename";
            
            if (file_put_contents($filepath, $content) === false) {
                echo "❌ Erreur lors de l'écriture du fichier $filename\n";
                $success = false;
                continue;
            }
            
            $size = round(strlen($content) / 1024, 2);
            echo "✅ $filename téléchargé ($size KB)\n";
        }
        
        if ($success) {
            echo "\n🎉 Bootstrap téléchargé avec succès !\n";
            $this->createFallbackTemplate();
        }
        
        return $success;
    }
    
    /**
     * Télécharge un fichier depuis une URL
     */
    private function downloadFile(string $url): string|false
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (compatible; Bootstrap Downloader)'
            ]
        ]);
        
        $content = file_get_contents($url, false, $context);
        
        if ($content === false) {
            // Essayer avec cURL si file_get_contents échoue
            return $this->downloadWithCurl($url);
        }
        
        return $content;
    }
    
    /**
     * Télécharge avec cURL (fallback)
     */
    private function downloadWithCurl(string $url): string|false
    {
        if (!function_exists('curl_init')) {
            return false;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Bootstrap Downloader)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || $content === false) {
            return false;
        }
        
        return $content;
    }
    
    /**
     * Crée les dossiers nécessaires
     */
    private function createDirectories(): void
    {
        $dirs = [
            $this->assetsDir . '/css',
            $this->assetsDir . '/js'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                echo "📁 Dossier créé: $dir\n";
            }
        }
    }
    
    /**
     * Crée un template avec fallback Bootstrap
     */
    private function createFallbackTemplate(): void
    {
        echo "\n📝 Création du template avec fallback...\n";
        
        $fallbackTemplate = '{% extends "base.html.twig" %}

{% block head %}
    {{ parent() }}
    
    <!-- Fallback Bootstrap local -->
    <script>
        // Vérifier si Bootstrap CSS est chargé
        function checkBootstrapCSS() {
            const bootstrapCSS = document.querySelector(\'link[href*="bootstrap"]\');
            if (!bootstrapCSS || !bootstrapCSS.sheet) {
                console.warn(\'Bootstrap CSS non chargé, utilisation du fallback local\');
                loadLocalBootstrapCSS();
            }
        }
        
        // Vérifier si Bootstrap JS est chargé
        function checkBootstrapJS() {
            if (typeof bootstrap === \'undefined\') {
                console.warn(\'Bootstrap JS non chargé, utilisation du fallback local\');
                loadLocalBootstrapJS();
            }
        }
        
        // Charger Bootstrap CSS local
        function loadLocalBootstrapCSS() {
            const link = document.createElement(\'link\');
            link.rel = \'stylesheet\';
            link.href = \'/assets/css/bootstrap.min.css\';
            link.onerror = function() {
                console.error(\'Impossible de charger Bootstrap CSS local\');
                // CSS de secours minimal
                const fallbackCSS = `
                    .container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }
                    .row { display: flex; flex-wrap: wrap; margin: 0 -15px; }
                    .col { flex: 1; padding: 0 15px; }
                    .btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
                    .btn:hover { background: #0056b3; }
                    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1050; }
                    .modal.show { display: block; }
                    .modal-dialog { position: relative; width: auto; margin: 10px; }
                    .modal-content { background: white; border-radius: 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                    .modal-header { padding: 15px; border-bottom: 1px solid #dee2e6; }
                    .modal-body { padding: 15px; }
                    .modal-footer { padding: 15px; border-top: 1px solid #dee2e6; }
                    .close { float: right; font-size: 1.5rem; font-weight: 700; line-height: 1; color: #000; background: transparent; border: 0; cursor: pointer; }
                `;
                const style = document.createElement(\'style\');
                style.textContent = fallbackCSS;
                document.head.appendChild(style);
            };
            document.head.appendChild(link);
        }
        
        // Charger Bootstrap JS local
        function loadLocalBootstrapJS() {
            const script = document.createElement(\'script\');
            script.src = \'/assets/js/bootstrap.min.js\';
            script.onerror = function() {
                console.error(\'Impossible de charger Bootstrap JS local\');
                // JS de secours minimal
                window.bootstrap = {
                    Modal: function(element) {
                        this.element = element;
                        this.show = function() { element.classList.add(\'show\'); };
                        this.hide = function() { element.classList.remove(\'show\'); };
                    },
                    Toast: function(element) {
                        this.element = element;
                        this.show = function() { 
                            element.style.display = \'block\';
                            setTimeout(() => element.style.display = \'none\', 5000);
                        };
                    }
                };
            };
            document.head.appendChild(script);
        }
        
        // Vérifications au chargement
        document.addEventListener(\'DOMContentLoaded\', function() {
            checkBootstrapCSS();
            checkBootstrapJS();
        });
    </script>
{% endblock %}';
        
        $templatePath = __DIR__ . '/template/base_fallback.html.twig';
        file_put_contents($templatePath, $fallbackTemplate);
        
        echo "✅ Template de fallback créé: base_fallback.html.twig\n";
    }
    
    /**
     * Vérifie si Bootstrap local est disponible
     */
    public function checkLocalBootstrap(): bool
    {
        $cssFile = $this->assetsDir . '/css/bootstrap.min.css';
        $jsFile = $this->assetsDir . '/js/bootstrap.min.js';
        
        $cssExists = file_exists($cssFile);
        $jsExists = file_exists($jsFile);
        
        echo "📋 VÉRIFICATION BOOTSTRAP LOCAL :\n";
        echo "================================\n";
        echo "CSS: " . ($cssExists ? "✅ Disponible" : "❌ Manquant") . "\n";
        echo "JS: " . ($jsExists ? "✅ Disponible" : "❌ Manquant") . "\n";
        
        if ($cssExists && $jsExists) {
            $cssSize = round(filesize($cssFile) / 1024, 2);
            $jsSize = round(filesize($jsFile) / 1024, 2);
            echo "\n📊 TAILLES :\n";
            echo "CSS: {$cssSize} KB\n";
            echo "JS: {$jsSize} KB\n";
        }
        
        return $cssExists && $jsExists;
    }
}

// Script principal
if (php_sapi_name() === 'cli') {
    $downloader = new BootstrapDownloader();
    
    $options = getopt('', ['check', 'download']);
    
    if (isset($options['check'])) {
        $downloader->checkLocalBootstrap();
    } else {
        // Téléchargement par défaut
        $downloader->downloadBootstrap();
    }
}
?>


