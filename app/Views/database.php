<?php

use Config\Database;
use Config\Database\Forge;

// Connect to the database
$db = Database::connect();

$forge = Database::forge();


$tablesToCreate = [
    "assets" => [
        "id" => [
            'name' => 'asset',
            'type' => 'INT',
            'constraint' => 5,
            'unsigned' => true,
            'auto_increment' => true,
        ],
    ],
];

// Define an array for the columns and tables to be created
$columnsToCreate = [
    ["column" => "user_id", "table" => "bot_follow_up", "type" => "VARCHAR", "constraint" => '250'],
    ["column" => "location_id", "table" => "bot_follow_up", "type" => "VARCHAR", "constraint" => '250'],
    ["column" => "start_date", "table" => "bot_follow_up", "type" => "DATETIME", "constraint" => '6'],
    ["column" => "end_date", "table" => "bot_follow_up", "type" => "DATETIME", "constraint" => '6'],
    ["column" => "adult", "table" => "bot_follow_up", "type" => "VARCHAR", "constraint" => '250'],
    ["column" => "child", "table" => "bot_follow_up", "type" => "VARCHAR", "constraint" => '250'],
    ["column" => "status", "table" => "bot_follow_up", "default" => "active", "type" => "VARCHAR", "constraint" => '250'],
    ["column" => "created_at", "table" => "bot_follow_up", "type" => "DATETIME", "constraint" => '6'],
    ["column" => "updated_at", "table" => "bot_follow_up", "type" => "DATETIME", "constraint" => '6'],
];



// Iterate through the array and create columns if they don't exist
foreach ($columnsToCreate as $item) {
    $column = $item["column"];
    $table = $item["table"];
    $type = $item["type"];
    $constraint = $item["constraint"] ?? null;
    $auto_increment = $item["auto_increment"] ?? false;

    if (!$db->tableExists($table)) {
        $fields = [
            'id' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true,
            ],
        ];
        $forge->addField($fields);
        $forge->addKey('id', true);
        $forge->createTable($table, true);
    }

    if (!$db->fieldExists($column, $table)) {
        $fields = [
            $column => [
                'type' => $type,
            ],
        ];

        if ($constraint !== null) {
            $fields[$column]['constraint'] = $constraint;
        }

        if ($auto_increment !== false) {
            $fields[$column]['auto_increment'] = $auto_increment;
        }

        if (isset($item["null"])) {
            $fields[$column]['null'] = true;
        }

        if (isset($item["default"])) {
            $fields[$column]['default'] = $item["default"];
        }

        $forge->addColumn($table, $fields);
        echo "$column created <hr/>";
    } else {
        echo "$column already created <hr/>";
    }
}





// Additional code if needed
echo "Database update completed!";
