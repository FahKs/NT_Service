<?php
// ajax/get_thambons.php
header('Content-Type: application/json');
require_once '../config/condb.php';
require_once '../functions/customer_functions.php';

if (isset($_GET['amphure_id'])) {
    $amphure_id = intval($_GET['amphure_id']);
    $thambons = getThambons($pdo, $amphure_id);
    echo json_encode($thambons);
} else {
    echo json_encode([]);
}
?>
