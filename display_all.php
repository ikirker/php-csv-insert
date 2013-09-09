<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>CSV SQL Inserter Table Dump</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>

<?php
// (c) Ian Kirker, 2013
// Released under the two-clause BSD license, in case it's relevant.
include("db_settings.php");
$strip_id_fields = FALSE;
include("schema.php");
$fields_lists = $fields_lists;
$table_list   = $table_list;
// Defined in schema.php

function get_table_contents($table, $fields) {
  global $my_db_hostname, $my_db_name, $my_db_port, $my_db_username, $my_db_password;
  try {
    $link = new \PDO(   
      "mysql:host={$my_db_hostname};" . 
      "por={$my_db_port};" . 
      "dbname={$my_db_name};",
      $my_db_username,
      $my_db_password,
      array(
          \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, 
          \PDO::ATTR_PERSISTENT => false, 
          \PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8mb4'
      )
    );

    $handle = $link->prepare("select " . implode(",", $fields) . " from {$table}");
    $handle->execute();
    return $handle->fetchAll(\PDO::FETCH_NUM);
  } catch(\PDOException $ex){
    print($ex->getMessage());
  }
}

echo "<h3>Contents</h3><ul>";
foreach ($table_list as $table_name) {
  echo "<li><a href=\"#{$table_name}\">{$table_name}</a></li>\n";
}

foreach ($table_list as $table_name) {
  echo "<h3><a name=\"{$table_name}\" />{$table_name}</h3>\n";

  echo "<table>\n  <tr>\n";
  $table_fields = $fields_lists[$table_name];
  $num_cols = count($table_fields);
  foreach ($table_fields as $table_field) {
    echo "    <th>{$table_field}</th>\n";
  }
  echo "  </tr>\n";
  
  foreach (get_table_contents($table_name, $table_fields) as $table_row) {
    echo "  <tr>\n";
    foreach ($table_row as $single_value) {
      echo "    <td>{$single_value}</td>\n";
    }
    echo "  </tr>\n";
  }
  echo "</table>";

}

?>
</body>
</html>

