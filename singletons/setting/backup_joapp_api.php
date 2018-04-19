<?php

if (!defined('ABSPATH')) {
    exit('error');
}

if (!function_exists('is_admin') || !is_admin()) {
    exit('error');
}

@ob_clean();
global $wpdb;

$table_name = $wpdb->prefix . "options";
$row = $wpdb->get_results("SELECT * FROM $table_name where option_name LIKE '%joapp%'");

echo json_encode($row, TRUE);

header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
$time = date("Y-m-d h_i");
header("Content-disposition: attachment; filename=\"backup-joapp-api-$time.txt\"");
exit();
?>