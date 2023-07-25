<?php

require_once 'vendor/autoload.php';

use App\Plugins;
use App\Plugins\Di\Factory;

$di = Factory::getDi();

$pdo = $di->get('db')->getConnection();

try {
    // Create the Location table
    $sql = "CREATE TABLE Location (
        id INT AUTO_INCREMENT PRIMARY KEY,
        city VARCHAR(255) NULL,
        address VARCHAR(255) NULL,
        zip_code INT NULL,
        country_code VARCHAR(2) NULL,
        phone_number VARCHAR(20) NULL
    )";
    $pdo->exec($sql);

    // Create the Facility table
    $sql = "CREATE TABLE Facility (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NULL,
        creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP,
        location_id INT NULL,
        FOREIGN KEY (location_id) REFERENCES Location (id)
    )";
    $pdo->exec($sql);

    // Create index on Facility table
    $sql = "CREATE INDEX location_id ON Facility (location_id)";
    $pdo->exec($sql);

    // Create the Tag table
    $sql = "CREATE TABLE Tag (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NULL,
        CONSTRAINT name UNIQUE (name)
    )";
    $pdo->exec($sql);

    // Create the Facility_Tag table
    $sql = "CREATE TABLE Facility_Tag (
        facility_id INT NOT NULL,
        tag_id INT NOT NULL,
        PRIMARY KEY (facility_id, tag_id),
        FOREIGN KEY (facility_id) REFERENCES Facility (id),
        FOREIGN KEY (tag_id) REFERENCES Tag (id)
    )";
    $pdo->exec($sql);

    // Create index on Facility_Tag table
    $sql = "CREATE INDEX tag_id ON Facility_Tag (tag_id)";
    $pdo->exec($sql);

    echo "Database tables created successfully.\n";
} catch (PDOException $e) {
    echo "Database creation failed: " . $e->getMessage();
}

