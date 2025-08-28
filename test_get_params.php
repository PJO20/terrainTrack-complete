<?php
// Test simple pour voir les paramètres GET
if (isset($_GET["success"]) && $_GET["success"] === "deleted") {
    echo "SUCCESS: deleted parameter found";
} else {
    echo "No deleted success parameter found";
    echo "GET parameters: " . print_r($_GET, true);
}
?>
