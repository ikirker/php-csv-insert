<?php 
    // (c) Ian Kirker, 2013
    // Released under the two-clause BSD license, in case it's relevant.

    include("db_settings.php");

    include("schema.php");
    $fields_lists = $fields_lists;
    $table_list   = $table_list;
    // Just to remind you what's defined in the file above.

    // Nothing lower than this covers specifics.
    
    // Creates the upload form from the above.
    $table_options = "";
    $table_inputs = "";
    $js_table_array = "[";
    foreach ($table_list as $table_name) {
        $table_options .= "<option value=\"{$table_name}\">{$table_name}</option>\n                    ";
        $table_forms   .= "<div id=\"{$table_name}\" style=\"visibility: hidden;display: none;\">\n" .
                          "<h5>Manual Input for table \"{$table_name}\":</h5>\n" .
                          "<br />\n";
        foreach ($fields_lists[$table_name] as $column_name) {
          $table_forms .= "<label for=\"$column_name\">$column_name</label>\n" .
                          "<input type=\"text\" default=\"NULL\" name=\"form_data[{$table_name}][{$column_name}]\"><br />\n";
        }
        $table_forms .= "</div>\n";
        $js_table_array .= " \"{$table_name}\", ";
    }
    $js_table_array .= "]";

    $post_form = <<<HEREDOC
        <p>
            <form action="insert.php" method="post"
              enctype="multipart/form-data"
              id="input_form">
                <script>
                  function showOneForm() {
                    var f = document.getElementById("table_selector");
                    var g = document.getElementById(f.value);
                    var table_array = {$js_table_array};
                    for (var i = 0; i < table_array.length; i++) {
                        var j = document.getElementById(table_array[i]);
                        j.style.visibility="hidden";
                        j.style.display="none";
                    }
                    g.style.visibility="visible";
                    g.style.display="inline";
                  }
                </script>
                <h5>Select a table to change available column fields.</h5>
                <label for="file">Filename:</label><input type="file" name="file" id="file" /><br />
                <label for="table">Table:</label>
                <select name="table" id="table_selector" onchange="showOneForm();">
                    {$table_options}
                </select>
                <br />
                <input type="submit" name="submit" value="Submit File" />
                <br />
                <br />
                <hr />
                {$table_forms}
                <br />
                <input type="submit" name="submit" value="Submit Manual Input" />
                <script>
                    showOneForm();
                </script>
            </form>
        </p>

HEREDOC;
    
    // Treat an empty file upload as an error.
    define("UPLOAD_ERR_EMPTY",5);
    define("UPLOAD_ERR_TOO_LARGE",6);
    define("UPLOAD_ERR_DISALLOWED_TYPE",7);
    class UploadedFile {
        
        private $original_file_name;
        private $current_name;
        private $file_type;
        private $file_size;
        private $file_error_val;
        private $file_handle;
        private $on_first_line;
         
        public function __construct( $afile ) {
            $this->original_file_name = $afile['name'];
            $this->current_name   = $afile['tmp_name'];
            $this->file_type      = $afile['type'];
            $this->file_size      = $afile['size'];
            $this->file_error_val = $afile['error'];
            $this->file_handle    = 0;
            $this->on_first_line  = TRUE; 
        }
        
        public function original_file_name() {
            return $this->original_file_name;
        }
        
        public function current_name() {
            return $this->current_name;
        }
        
        public function get_handle() {
            if ($this->file_handle == 0) {
                $this->file_handle = fopen($this->current_name, 'r');
            }
            return $this->file_handle;
        }
        
        public function close() {
            if ($this->file_handle != 0) {
                if (fclose($this->file_handle) == TRUE) {
                    $this->file_handle = 0;
                } else {
                    echo "Error: Could not close file!";
                }
            }
        }
        
        public function __destruct() {
            $this->close();
        }
        
        public function get_dataline() {
            ini_set('auto_detect_line_endings',TRUE);
            $file = $this->get_handle();
            $return_val = fgetcsv($file); //File handle stores position state
            
            if ($this->on_first_line == TRUE) { // Skip the first line, since it contains column labels
                $return_val = fgetcsv($file);
                $this->on_first_line = FALSE;
            }
            
            if ($return_val == FALSE) {
                $this->close();
            } else {
                array_shift($return_val); // The first element is an ID which MySQL gens automatically, so remove it
            }
            return $return_val;
        }
        
        public function is_invalid() {
            // A lot of this file logic is just taken from:
            //   http://www.w3schools.com/php/php_file_upload.asp
            // and:
            //   http://www.php.net/manual/en/features.file-upload.errors.php
            
            $allowed_file_extensions = array("csv");
            $allowed_file_types      = array("text/csv");
            $maximum_file_size       = 200 * 1024; // 200kiB
    
            $temp = explode(".", $this->original_file_name);
            $file_extension = end($temp);
            
            if ( ($file_error_val == 0) &&
                 ($file_size == 0) ) {
                $this->file_error_val = UPLOAD_ERR_EMPTY;
            }
            
            if ( ! ( in_array($file_type,      $allowed_file_types) &&
                     in_array($file_extension, $allowed_file_extensions) ))
            {
                $this->file_error_val = UPLOAD_ERR_DISALLOWED_TYPE;
            }
            
            if ($file_size < $maximum_file_size) {
                $this->file_error_val = UPLOAD_ERR_TOO_LARGE;
            }
                          
            return $file_error_val;                
        }
        
        public function upload_error_string($code = NULL) {
            if ($code == NULL) {
                $code = $this->$file_error_val;
            }
            $upload_errors = array( 
                        UPLOAD_ERR_OK         => "No errors.", 
                        UPLOAD_ERR_INI_SIZE   => "File was larger than upload_max_filesize set in the php.ini file.", 
                        UPLOAD_ERR_FORM_SIZE  => "File was larger than MAX_FILE_SIZE as specified by the submitting form.", 
                        UPLOAD_ERR_PARTIAL    => "File was only partially uploaded.", 
                        UPLOAD_ERR_NO_FILE    => "No file was sent.", 
                        UPLOAD_ERR_NO_TMP_DIR => "The temporary directory to store the file could not be found.", 
                        UPLOAD_ERR_CANT_WRITE => "PHP could not write the file to disk.", 
                        UPLOAD_ERR_EXTENSION  => "The file upload was automatically stopped due to its extension.", 
                        UPLOAD_ERR_EMPTY      => "The uploaded file was empty.",
                        UPLOAD_ERR_TOO_LARGE  => "The file uploaded was larger than this script allows.",
                        UPLOAD_ERR_DISALLOWED_TYPE => "The file uploaded was of a type not allowed by this script.",
                    );
            return $upload_errors[$code];
        }
    } // End of UploadedFile class

    // This is mostly to match abstract data access with the UploadedFile get_dataline method
    // This is just to handle a single line's worth
    class FormCSVSource {
        private $data_array;
        private $has_remaining_data;

        public function __construct( $data_source ) {
            $this->data_array = $data_source;
            $this->has_remaining_data = TRUE;
        }

        public function get_dataline() {
            if ($this->has_remaining_data == TRUE) {
                $this->has_remaining_data = FALSE;
                return $this->data_array;
            } else {
                return FALSE;
            }
        }
    } // End of FormCSVSource class


    
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
        
        if ( ($_POST['submit'] == "Submit File") &&
             (isset($_FILES['file'])           ) )  
        {
            $file = new UploadedFile($_FILES["file"]);
            $source = $file;
        } else {
            $file = FALSE;
            foreach ($_POST['form_data'][$requested_table] as $form_element_name => $form_element_value) {
              echo "<p>({$form_element_name}:{$form_element_value})</p>";
            }
            $source = new FormCSVSource($_POST['form_data'][$requested_table]); 
        }

        if ( ($file != FALSE ) &&
             ($file->is_invalid() != 0 ) ) 
        {
            $body_content =  "<p>{$file->upload_error_string()}</p>\n";
            $body_content .= "<p>You may wish to try again, or attempt to remedy the problem.</p>\n";
            $body_content .= $post_form;
        } 
        else
        {
            // PDO code from https://phpbestpractices.org/
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
                //echo "Debug: \$statement_string: {$statement_string}\n";
                $db_handle = $link->prepare($statement_string);
             
                // PHP bug: if you don't specify PDO::PARAM_INT, PDO may enclose the argument in quotes.  This can mess up some MySQL queries that don't expect integers to be quoted.
                // See: https://bugs.php.net/bug.php?id=44639
                // If you're not sure whether the value you're passing is an integer, use the is_int() function.
                // e.g. $handle->bindValue(1, 100, PDO::PARAM_INT);
                // but  $handle->bindValue(2, 'Bilbo Baggins');
                

                $rows_added = 0;
                while ( ($data = $source->get_dataline() ) !== FALSE ) {
                    $j = 1; 
                    foreach($data as $element) {
                        if ($element == '') $element = NULL;
                        $db_handle->bindValue($j, $element);
                        $j += 1;
                    }
                    $data_as_string = implode(' || ', $data);
                    echo "\n<p>Current Data Line: {$data_as_string}</p>\n";
                    
                    $execute_result = $db_handle->execute();
                    if ( $execute_result == FALSE ) {
                        echo "\n<p><em>This data insertion failed. Please check output.</p>\n";
                    } else {
                        $rows_added += 1;
                    }
                }
                
                if ($file != FALSE) {
                    $source_name = $file->original_file_name();
                } else {
                    $source_name = "form input";
                }
                $body_content = "<p>Added {$rows_added} rows from {$source_name} to the '{$requested_table}' table, in the database {$my_db_name} on {$my_db_hostname}.</p>\n";
                $body_content .= "<p>You may now add more data (note the table selector may have reset):</p>\n";
                $body_content .= $post_form;

            }
            catch(\PDOException $ex){
              print("\n<p>" . $ex->getMessage() . "</p>\n");
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
