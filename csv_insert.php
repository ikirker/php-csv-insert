<?php 
    // (c) Ian Kirker, 2013
    // Released under the two-clause BSD license, in case it's relevant.
    $my_db_hostname = "127.0.0.1";
    $my_db_name     = "test";
    $my_db_port     = "3306";
    $my_db_username = "root";
    $my_db_password = ""; // I am honestly not sure where/how to specify passwords.
                                       // Putting them here seems like vaguely bad practice.

    // These are straight out of the schema.
    $fields_lists = array(
      "Venue"         => array("VenueID", "VenueName", "Add1", "Add2", "Add3", 
                               "LocationID", "SeatCap", "VenueTypeID", 
                               "VenueDescription", "Comments"),
      "VenueType"     => array("VenueTypeID", "VenueTypeName"),
      "VenueResource" => array("UID", "VenueID", "SourceID"),
      "Location"      => array("LocationID", "XCoordinate", "YCoordinate"),
      "CompanyVenue"  => array("UID", "CompanyID", "VenueID", "RoleDescription", 
                               "RoleTypeID", "RoleStartDate", "SourceID", 
                               "SourcePageRed", "Comments"),
      "Company"       => array("CompanyID", "CompanyName", 
                               "CompanyDescription", "Comments"),
      "PersonVenue"   => array("UID", "PersonID", "VenueID", "RoleDescription",
                               "RoleTypeID", "RoleStartDate", "SourceID", 
                               "SourcePageRef", "Comments"),
      "Person"        => array("PersonID", "PSurname", "PMiddleName", "PFname",
                               "PTitle", "Gender", "DoB", "DoD", "PDescription",
                               "Comments"),
      "RoleType"      => array("RoleTypeID", "RoleTypeName"),
      "Events"        => array("EventID", "VenueID", "EventDate", "EventTypeID",
                               "EventDescription", "SourceID", "SourcePageRef",
                               "Comments"),
      "EventType"     => array("EventTypeID", "EventTypeName"),
    );
    $table_list = array_keys($fields_lists);
    
    $allowed_file_extensions = array("csv");
    $allowed_file_types      = array("text/csv");
    $maximum_file_size       = 200 * 1024; // 200kiB
    
    // Nothing lower than this covers specifics.
    
    // Creates the upload form from the above.
    $table_options = "";
    foreach ($table_list as $table_name) {
        $table_options .= "<option value=\"{$table_name}\">{$table_name}</option>\n                    ";
    }

    $post_form = <<<HEREDOC
        <p>
            <form action="csv_insert.php" method="post"
              enctype="multipart/form-data">
                <label for="file">Filename:</label><input type="file" name="file" id="file" /><br />
                <label for="table">Table:</label>
                <select name="table">
                    {$table_options}
                </select>
                <br />
                <input type="submit" name="submit" value="Submit" />
            </form>
        </p>

HEREDOC;

    
    // Start request logic

    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method == "GET") {
        // If this page is just requested with GET, present the upload form straight.
        $body_content =  "<p>Please select a file to insert, and a table to insert to:</p>\n"; 
        $body_content .= $post_form;
    }
    elseif ($method == "POST") {
        // If it's requested with POST, first check whether we have a 
        //   file, and then check it fits the criteria
        
        $requested_table = $_POST['table'];
        
        // A lot of this file logic is just taken from:
        //   http://www.w3schools.com/php/php_file_upload.asp
        // and:
        //   http://www.php.net/manual/en/features.file-upload.errors.php
        
        $file = $_FILES["file"];
        $original_file_name = $file['name'];
        $temp = explode(".", $original_file_name);
        $file_extension = end($temp);
        $file_type      = $file['type'];
        $file_size      = $file['size'];
        $file_error_val = $file['error'];
        
        // Treat an empty file upload as an error.
        define("UPLOAD_ERR_EMPTY",5); 
        if ( ($file_error_val == 0) &&
             ($file_size == 0) ) {
            $file_error_val = UPLOAD_ERR_EMPTY;
        }
        
        // I probably should refactor this into a function. :/
        $upload_errors = array( 
            UPLOAD_ERR_OK         => "No errors.", 
            UPLOAD_ERR_INI_SIZE   => "File was larger than upload_max_filesize set in the php.ini file.", 
            UPLOAD_ERR_FORM_SIZE  => "File was larger than MAX_FILE_SIZE as specified by the submitting form.", 
            UPLOAD_ERR_PARTIAL    => "File was only partially uploaded.", 
            UPLOAD_ERR_NO_FILE    => "No file was sent.", 
            UPLOAD_ERR_NO_TMP_DIR => "The temporary directory to store the file could not be found.", 
            UPLOAD_ERR_CANT_WRITE => "PHP could not write the file to disk.", 
            UPLOAD_ERR_EXTENSION  => "The file upload was automatically stopped due to its extension.", 
            UPLOAD_ERR_EMPTY      => "The uploaded file was empty."
        ); 
        
        if ( $file_error_val != 0 ) {
            $body_content =  "<p>{$upload_errors[$file_error_val]}</p>\n";
            $body_content .= "<p>You may wish to try again, or attempt to remedy the problem.</p>\n";
            $body_content .= $post_form;
        } 
        else
        {
            if ( in_array($file_type,      $allowed_file_types) &&
                 in_array($file_extension, $allowed_file_extensions) &&
                 ($file_size < $maximum_file_size) )
            {
                // The file appears to be valid and present, so do stuff with it.
                $file_name = $file['tmp_name'];
                
                // From https://phpbestpractices.org/
                try{
                    // Create a new connection.
                    // You'll probably want to replace hostname with localhost in the first parameter.
                    // The PDO options we pass do the following:
                    // \PDO::ATTR_ERRMODE enables exceptions for errors.  This is optional but can be handy.
                    // \PDO::ATTR_PERSISTENT disables persistent connections, which can cause concurrency issues in certain cases.  See "Gotchas".
                    // \PDO::MYSQL_ATTR_INIT_COMMAND alerts the connection that we'll be passing UTF-8 data.  This may not be required depending on your configuration, but it'll save you headaches down the road if you're trying to store Unicode strings in your database.  See "Gotchas".
                    $link = new \PDO(   "mysql:host={$my_db_hostname};por={$my_db_port};dbname={$my_db_name}",
                                        $my_db_username,
                                        $my_db_password, 
                                        array(
                                            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, 
                                            \PDO::ATTR_PERSISTENT => false, 
                                            \PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8mb4'
                                        )
                                    );
                                    
                    // Unfortunately you can't bind table and column names in a prepared statement, so we have
                    //   to put them in using string manipulation.

                    $table_fields = $fields_lists[$requested_table];
                    $table_fields_string = implode(',', $table_fields);

                    // This just makes an appropriately long number of ? separated by ,
                    $value_placeholders = implode(',', array_fill(0, count($fields_lists[$requested_table]), '?'));
                    
                    // My danger sense says not to put $requested_table into a string here just as-is, but oh well.
                    $statement_string = "INSERT INTO {$requested_table} (" . $table_fields_string . 
                                        ') VALUES ( ' . $value_placeholders . ')';
                    echo "Debug: \$statement_string: {$statement_string}\n";
                    $db_handle = $link->prepare($statement_string);
                 
                    // PHP bug: if you don't specify PDO::PARAM_INT, PDO may enclose the argument in quotes.  This can mess up some MySQL queries that don't expect integers to be quoted.
                    // See: https://bugs.php.net/bug.php?id=44639
                    // If you're not sure whether the value you're passing is an integer, use the is_int() function.
                    // e.g. $handle->bindValue(1, 100, PDO::PARAM_INT);
                    // but  $handle->bindValue(2, 'Bilbo Baggins');
                    
                    // I *think* the values set above stay bound?
                    // So now we just need to alter and rebind the values we pick up from the CSV file.
                    
                    ini_set('auto_detect_line_endings',TRUE);
                    $csv_handle = fopen($file_name,'r');
                    $rows_added = 0;
                    while ( ($csv_data = fgetcsv($csv_handle) ) !== FALSE ) {
                        if ($rows_added == 0) {
                          $rows_added += 1; // Skip the CSV label row.
                          continue;
                        }
                        $j = 1; 
                        foreach($csv_data as $csv_value) {
                            if ($csv_value == '') $csv_value = NULL;
                            $db_handle->bindValue($j, $csv_value);
                            $j += 1;
                        }
                        $csv_data_as_string = implode(' || ', $csv_data);
                        echo "\n<p>Debug: Current CSV Data: {$csv_data_as_string}</p>\n";
                        $db_handle->execute(); // You might want to wrap this in a separate try/catch
                        $rows_added += 1;
                    }
                    fclose($csv_handle);
                    
                    $body_content = "<p>Added {$rows_added} rows from {$original_file_name} to the '{$requested_table}' table, in the database {$my_db_name} on {$my_db_hostname}.</p>\n";
                    $body_content .= "<p>You may now add another file (note the table selector may have reset):</p>\n";
                    $body_content .= $post_form;

                }
                catch(\PDOException $ex){
                  print("\n<p>" . $ex->getMessage() . "</p>\n");
                }
            } else {
                $body_content =  "<p>Invalid file -- file must be a text CSV file with the .csv extension, under 200 kiB in size.</p>\n";
                $body_content .= "<p>Please try again:</p>\n";
                $body_content .= $post_form;
            }
        }
        
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>CSV SQL Inserter</title>
        <style>
            html,
            button,
            input,
            select,
            textarea {
                color: #222;
            }

            body {
                font-size: 1em;
                line-height: 1.4;
            }

            ::-moz-selection {
                background: #b3d4fc;
                text-shadow: none;
            }

            ::selection {
                background: #b3d4fc;
                text-shadow: none;
            }

            hr {
                display: block;
                height: 1px;
                border: 0;
                border-top: 1px solid #ccc;
                margin: 1em 0;
                padding: 0;
            }

            img {
                vertical-align: middle;
            }

            fieldset {
                border: 0;
                margin: 0;
                padding: 0;
            }

            textarea {
                resize: vertical;
            }

        </style>
    </head>
    <body>

        <?php echo $body_content; ?>

    </body>
</html>
