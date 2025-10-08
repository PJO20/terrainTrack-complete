<?php

require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "🔍 Debug Gmail SMTP Configuration\n";
echo "==================================\n\n";

try {
    $mail = new PHPMailer(true);
    
    // Activer le debug SMTP
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        echo "DEBUG ($level): $str\n";
    };
    
    // Configuration SMTP Gmail
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'pjorsini20@gmail.com';
    $mail->Password = 'gmqncgtfunpfnkjh'; // App password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    // Options SSL
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    echo "📧 Configuration SMTP :\n";
    echo "   Host: smtp.gmail.com\n";
    echo "   Port: 587\n";
    echo "   Username: pjorsini20@gmail.com\n";
    echo "   Password: gmqn... (masqué)\n\n";
    
    // Configuration de l'email
    $mail->setFrom('pjorsini20@gmail.com', 'TerrainTrack Test');
    $mail->addAddress('pjorsini20@gmail.com');
    $mail->isHTML(true);
    $mail->Subject = '🧪 Test SMTP Direct - ' . date('H:i:s');
    $mail->Body = '
        <div style="font-family: Arial, sans-serif; padding: 20px;">
            <h2 style="color: #2563eb;">🧪 Test SMTP Direct</h2>
            <p>Ceci est un test d\'envoi direct via PHPMailer SMTP.</p>
            <p><strong>Heure d\'envoi :</strong> ' . date('d/m/Y à H:i:s') . '</p>
            <p><strong>Configuration :</strong> Gmail SMTP avec App Password</p>
        </div>
    ';
    
    echo "📤 Tentative d'envoi d'email de test...\n";
    echo "=====================================\n";
    
    $result = $mail->send();
    
    if ($result) {
        echo "\n✅ EMAIL ENVOYÉ AVEC SUCCÈS !\n";
        echo "📧 Vérifiez votre boîte mail : pjorsini20@gmail.com\n";
        echo "📁 Vérifiez aussi les spams/courrier indésirable\n";
    } else {
        echo "\n❌ ÉCHEC DE L'ENVOI\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERREUR PHPMailer : " . $e->getMessage() . "\n";
    echo "📍 Code d'erreur : " . $e->getCode() . "\n";
}

echo "\n🏁 Test terminé\n";

?>
