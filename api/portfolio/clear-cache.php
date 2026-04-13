<?php
/**
 * Clear OPcache - Limpiar caché de PHP
 */

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache limpiado exitosamente<br>";
} else {
    echo "ℹ️ OPcache no está disponible<br>";
}

if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    echo "✅ APC Cache limpiado exitosamente<br>";
}

if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "✅ APCu Cache limpiado exitosamente<br>";
}

echo "<br>=== Información ===<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "OPcache enabled: " . (ini_get('opcache.enable') ? 'YES' : 'NO') . "<br>";

?>
