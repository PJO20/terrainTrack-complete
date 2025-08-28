<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic des Pictogrammes</title>
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        .diagnostic { background: #f5f5f5; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .icon-test { display: inline-block; margin: 10px; padding: 10px; border: 1px solid #ddd; }
        .icon-test i { font-size: 2rem; }
    </style>
</head>
<body>
    <h1>Diagnostic des Pictogrammes TerrainTrack</h1>
    
    <div class="diagnostic">
        <h2>1. Test de chargement des CSS</h2>
        <?php
        $css_urls = [
            'https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            '/assets/css/style.css'
        ];
        
        foreach ($css_urls as $url) {
            $full_url = strpos($url, 'http') === 0 ? $url : 'http://localhost:8888' . $url;
            $headers = @get_headers($full_url);
            $status = $headers ? substr($headers[0], 9, 3) : 'Error';
            echo "<p>$url : ";
            echo $status == '200' ? "<span class='success'>✅ OK ($status)</span>" : "<span class='error'>❌ Erreur ($status)</span>";
            echo "</p>";
        }
        ?>
    </div>
    
    <div class="diagnostic">
        <h2>2. Test des fonts Boxicons</h2>
        <?php
        $font_urls = [
            'https://cdn.jsdelivr.net/npm/boxicons@2.1.4/fonts/boxicons.woff2',
            'https://cdn.jsdelivr.net/npm/boxicons@2.1.4/fonts/boxicons.woff',
            'https://cdn.jsdelivr.net/npm/boxicons@2.1.4/fonts/boxicons.ttf'
        ];
        
        foreach ($font_urls as $url) {
            $headers = @get_headers($url);
            $status = $headers ? substr($headers[0], 9, 3) : 'Error';
            echo "<p>" . basename($url) . " : ";
            echo $status == '200' ? "<span class='success'>✅ OK</span>" : "<span class='error'>❌ Erreur</span>";
            echo "</p>";
        }
        ?>
    </div>
    
    <div class="diagnostic">
        <h2>3. Headers de sécurité</h2>
        <?php
        $response_headers = headers_list();
        $csp_found = false;
        foreach ($response_headers as $header) {
            if (stripos($header, 'Content-Security-Policy') !== false) {
                $csp_found = true;
                echo "<p><strong>CSP:</strong> " . htmlspecialchars($header) . "</p>";
                
                // Vérifier si cdn.jsdelivr.net est autorisé
                if (strpos($header, 'cdn.jsdelivr.net') !== false) {
                    echo "<p class='success'>✅ cdn.jsdelivr.net est autorisé dans la CSP</p>";
                } else {
                    echo "<p class='error'>❌ cdn.jsdelivr.net n'est PAS autorisé dans la CSP</p>";
                }
            }
        }
        if (!$csp_found) {
            echo "<p>Aucune politique CSP détectée</p>";
        }
        ?>
    </div>
    
    <div class="diagnostic">
        <h2>4. Test visuel des icônes</h2>
        <div>
            <div class="icon-test">
                <i class='bx bx-user'></i>
                <p>bx-user</p>
            </div>
            <div class="icon-test">
                <i class='bx bxs-dashboard'></i>
                <p>bxs-dashboard</p>
            </div>
            <div class="icon-test">
                <i class='bx bx-cog'></i>
                <p>bx-cog</p>
            </div>
            <div class="icon-test">
                <i class='bx bx-help-circle'></i>
                <p>bx-help-circle</p>
            </div>
            <div class="icon-test">
                <i class='bx bx-log-out'></i>
                <p>bx-log-out</p>
            </div>
        </div>
    </div>
    
    <div class="diagnostic">
        <h2>5. Test JavaScript</h2>
        <div id="js-test"></div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const testDiv = document.getElementById('js-test');
            const icons = document.querySelectorAll('i[class*="bx"]');
            let report = '';
            
            // Test 1: Vérifier le chargement de la font
            const testIcon = document.createElement('i');
            testIcon.className = 'bx bx-check';
            document.body.appendChild(testIcon);
            
            setTimeout(function() {
                const styles = window.getComputedStyle(testIcon, ':before');
                const fontFamily = styles.getPropertyValue('font-family');
                const content = styles.getPropertyValue('content');
                
                report += '<p><strong>Font Family:</strong> ' + (fontFamily || 'Non détecté') + '</p>';
                report += '<p><strong>Test icon content:</strong> ' + (content || 'Non détecté') + '</p>';
                
                // Test 2: Vérifier chaque icône
                let visibleCount = 0;
                let totalCount = icons.length;
                
                icons.forEach(function(icon) {
                    if (icon.offsetWidth > 0 && icon.offsetHeight > 0) {
                        visibleCount++;
                    } else {
                        console.error('Icône invisible:', icon.className);
                    }
                });
                
                report += '<p><strong>Icônes visibles:</strong> ' + visibleCount + '/' + totalCount + '</p>';
                
                if (visibleCount === totalCount) {
                    report += '<p class="success">✅ Toutes les icônes sont visibles !</p>';
                } else {
                    report += '<p class="error">❌ Certaines icônes ne sont pas visibles</p>';
                }
                
                testDiv.innerHTML = report;
                document.body.removeChild(testIcon);
            }, 1000);
        });
    </script>
</body>
</html>
