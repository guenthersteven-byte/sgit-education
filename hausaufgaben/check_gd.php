<?php
/**
 * Quick GD Extension Check
 * Access this file via browser to check if GD is available
 */

header('Content-Type: text/plain; charset=utf-8');

echo "PHP GD Extension Check\n";
echo "======================\n\n";

if (extension_loaded('gd')) {
    echo "✅ GD Extension: LOADED\n\n";

    $info = gd_info();
    echo "Details:\n";
    foreach ($info as $key => $value) {
        $val = is_bool($value) ? ($value ? 'Yes' : 'No') : $value;
        echo "  - $key: $val\n";
    }

    echo "\n✅ READY TO UPLOAD\n";
} else {
    echo "❌ GD Extension: NOT LOADED\n\n";
    echo "This is the root cause of the upload error!\n\n";
    echo "Fix:\n";
    echo "1. Open php.ini\n";
    echo "2. Find: ;extension=gd\n";
    echo "3. Change to: extension=gd\n";
    echo "4. Restart web server\n";
}

echo "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
