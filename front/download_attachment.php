<?php
// Endpoint para download de anexo
if (!isset($_GET['file'])) {
    die('Arquivo não especificado.');
}
$file = basename($_GET['file']);
$path = realpath(__DIR__ . '/../logs/attachments/' . $file);
if (!$path || strpos($path, realpath(__DIR__ . '/../logs/attachments/')) !== 0 || !file_exists($path)) {
    die('Arquivo não encontrado.');
}
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file . '"');
readfile($path);
exit;
