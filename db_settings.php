<?php
    // (c) Ian Kirker, 2013
    // Released under the two-clause BSD license, in case it's relevant.

    $settings = "ian-testing";

    if ($settings = "chris-remote") {
      $my_db_hostname = "mysql-server.ucl.ac.uk";
      $my_db_name     = "ucqacpo";
      $my_db_port     = "3306";
      $my_db_username = "ucqacpo";
      $my_db_password = ""; // I am honestly not sure where/how to specify passwords.
      // Putting them here seems like vaguely bad practice.
      // Certainly they shouldn't be put into a public repo.
    } elseif ($settings == "ian-testing") {
      $my_db_hostname = "127.0.0.1";
      $my_db_name     = "test";
      $my_db_port     = "3306";
      $my_db_username = "root";
      $my_db_password = ""; 
    } 
    
?>

