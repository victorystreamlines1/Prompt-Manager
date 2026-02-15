<?php
/**
 * Scan both databases for ALL existing tables (not just the ones we know about)
 */

$databases = [
    [
        'label' => 'DB1: u419999707_prompt_manager',
        'host' => 'srv1788.hstgr.io',
        'port' => 3306,
        'dbname' => 'u419999707_prompt_manager',
        'username' => 'u419999707_prompt_manager',
        'password' => 'P@master5007',
    ],
    [
        'label' => 'DB2: u419999707_Mohamed',
        'host' => 'srv1788.hstgr.io',
        'port' => 3306,
        'dbname' => 'u419999707_Mohamed',
        'username' => 'u419999707_Abuammar',
        'password' => 'P@master5007',
    ],
];

foreach ($databases as $db) {
    echo "\n=== {$db['label']} ===\n";
    try {
        $pdo = new PDO(
            "mysql:host={$db['host']};port={$db['port']};dbname={$db['dbname']};charset=utf8mb4",
            $db['username'], $db['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables found: " . count($tables) . "\n";
        foreach ($tables as $t) {
            $count = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
            echo "  • {$t}: {$count} rows\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
