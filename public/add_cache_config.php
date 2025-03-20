<?php
// Save as public/add_cache_config.php

// Load existing config
$configFile = __DIR__ . '/../config/app.php';
if (!file_exists($configFile)) {
    die("Config file not found at: $configFile");
}

$config = require $configFile;

// Add cache configuration if it doesn't exist
if (!isset($config['cache'])) {
    $config['cache'] = [
        // Disable Redis by default since it's not available
        'enabled' => false,
        'duration' => 300, // 5 minutes
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null
        ]
    ];
    
    // Write the updated config back to the file
    $configContent = "<?php\nreturn " . var_export($config, true) . ";\n";
    file_put_contents($configFile, $configContent);
    
    echo "Cache configuration added to config file. Redis caching is disabled.";
} else {
    // Update existing config to disable Redis
    $config['cache']['enabled'] = false;
    
    // Write the updated config back to the file
    $configContent = "<?php\nreturn " . var_export($config, true) . ";\n";
    file_put_contents($configFile, $configContent);
    
    echo "Cache configuration updated. Redis caching is disabled.";
}