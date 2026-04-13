<?php
/**
 * Verificar configuración de logs de PHP
 */

echo "=== CONFIGURACIÓN DE LOGS PHP ===\n\n";

echo "error_log: " . ini_get('error_log') . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "log_errors: " . ini_get('log_errors') . "\n";
echo "error_reporting: " . error_reporting() . "\n\n";

// Test de escritura
error_log("TEST LOG: Probando escritura de logs desde check-logs.php");

echo "✅ Log de prueba escrito. Revisa el archivo indicado arriba.\n";
echo "\nPosibles ubicaciones:\n";
echo "- C:\\xampp\\apache\\logs\\error.log\n";
echo "- C:\\wamp\\logs\\php_error.log\n";
echo "- " . __DIR__ . "\\..\\..\\logs\\\n";
