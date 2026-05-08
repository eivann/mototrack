<?php

function convert_values($table, $values) {
    // Remove backticks
    $values = str_replace('`', '', $values);
    // Fix single quote escaping
    $values = str_replace("\\'", "''", $values);
    
    // Handle booleans based on table structure
    if ($table === 'inventory_items') {
        // last column is low_stock_notified (bool)
        $values = preg_replace('/, 0\)([,;])/', ', false)\1', $values);
        $values = preg_replace('/, 1\)([,;])/', ', true)\1', $values);
    } elseif ($table === 'repairs') {
        // 9th column is is_paid (bool), 10th is customer_id (int/null)
        // Example: ..., '2026-05-06 07:31:59', 0, 1)
        $values = preg_replace_callback('/, (0|1), (\d+|NULL)\)([,;])/', function($m) {
            $bool = ($m[1] === '0') ? 'false' : 'true';
            return ", $bool, {$m[2]}){$m[3]}";
        }, $values);
    } elseif ($table === 'pre_orders') {
        // is_paid is 13th column.
        // It's followed by xendit_invoice_id (varchar/null)
        // heuristic: , (0|1), (NULL|'...') , (NULL|'...') )
        $values = preg_replace_callback('/, (0|1), (NULL|\'[^\']*\'), (NULL|\'[^\']*\')\)([,;])/', function($m) {
            $bool = ($m[1] === '0') ? 'false' : 'true';
            return ", $bool, {$m[2]}, {$m[3]}){$m[4]}";
        }, $values);
    }
    
    return $values;
}

$tables = ['inventory_items', 'users', 'repairs', 'pre_orders', 'sales'];
$dump_file = 'mototrack_dump.sql';
$output_file = 'supabase_import.sql';

$content = file_get_contents($dump_file);
$all_sql = [];
$all_sql[] = "SET session_replication_role = 'replica';";

foreach ($tables as $table) {
    echo "Extracting $table...\n";
    
    $pattern = '/INSERT INTO `' . preg_quote($table) . '` \((.*?)\) VALUES\s+(.*?);/s';
    if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
        $all_sql[] = "-- Data for $table";
        $all_sql[] = "DELETE FROM $table;";
        
        foreach ($matches as $match) {
            $columns = str_replace('`', '', $match[1]);
            $values = convert_values($table, $match[2]);
            $all_sql[] = "INSERT INTO $table ($columns) VALUES\n$values;";
        }
    } else {
        echo "No data found for $table\n";
    }
}

$all_sql[] = "SET session_replication_role = 'origin';";

file_put_contents($output_file, implode("\n\n", $all_sql));
echo "Done! SQL saved to $output_file\n";
