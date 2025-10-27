<?php
/**
 * Test rapide après correction
 */

session_start();

echo "🔒 TEST RAPIDE APRÈS CORRECTION\n";
echo "===============================\n\n";

// Test avec curl POST
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/settings/security/update-session-timeout');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'session_timeout=75');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
echo "Réponse: $response\n";

$json = json_decode($response, true);
if ($json) {
    echo "JSON valide: ✅ OUI\n";
    echo "Succès: " . ($json['success'] ? '✅ OUI' : '❌ NON') . "\n";
} else {
    echo "JSON valide: ❌ NON\n";
    echo "Première ligne: " . substr($response, 0, 100) . "\n";
}
?>

