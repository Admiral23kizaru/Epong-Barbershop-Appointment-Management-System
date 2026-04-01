<?php
require_once __DIR__ . '/includes/db.php';

try {
    // 1. Create Shops Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS shops (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        address TEXT NULL,
        contact VARCHAR(100) NULL,
        logo VARCHAR(255) NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created shops table.\n";

    // 2. Create System Settings Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT NULL
    )");
    
    // Insert default settings
    $stmt = $pdo->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->execute(['system_name', 'Epong Barbershop']);
    $stmt->execute(['default_currency', 'PHP']);
    $stmt->execute(['timezone', 'Asia/Manila']);
    $stmt->execute(['slot_duration', '30']);
    $stmt->execute(['max_advance_days', '30']);
    $stmt->execute(['maintenance_mode', '0']);
    $stmt->execute(['support_email', 'support@epong.com']);
    echo "Created system_settings table.\n";

    // 3. Alter Admins
    try { $pdo->exec("ALTER TABLE admins ADD COLUMN shop_id INT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE admins ADD COLUMN status VARCHAR(20) DEFAULT 'active'"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE admins ADD COLUMN last_login DATETIME NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE admins ADD COLUMN force_change_password TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
    echo "Altered admins table.\n";

    // 4. Alter Barbers and Services
    try { $pdo->exec("ALTER TABLE barbers ADD COLUMN shop_id INT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE services ADD COLUMN shop_id INT NULL"); } catch (Exception $e) {}
    echo "Altered barbers and services.\n";

    // 5. Alter Audit Logs
    try { $pdo->exec("ALTER TABLE audit_logs ADD COLUMN ip_address VARCHAR(45) NULL"); } catch (Exception $e) {}
    echo "Altered audit_logs.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
