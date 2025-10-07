<?php
// 检查users表结构
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=data_collection', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Users table structure:\n";
    $stmt = $pdo->query('DESCRIBE users');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Field: {$row['Field']}, Type: {$row['Type']}, Null: {$row['Null']}, Key: {$row['Key']}\n";
    }
    
    echo "\nUsers table indexes:\n";
    $stmt = $pdo->query('SHOW INDEX FROM users');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Key_name: {$row['Key_name']}, Column_name: {$row['Column_name']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>