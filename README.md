php-csv-insert
==============

A basic PHP script for inserting CSV or manually entered form data into a MySQL db.

Remember to tweak db_settings.php before you try and actually use it.

The schema is stored as an associative array of arrays named as the table:

    $fields_lists = array(
      "A_Table_name"     => array("The_Field_For_Table_ID", "Some_Other_Field", "Another_Field),
    );

The insertion script makes some assumptions about the schema:

  * That the first element in a table's array is the primary key, is numerical, and autoincrements
  * That any field other than the primary key ending in "ID" is a the primary key for another table whose name is the rest of that field name, so for example, a field called "RocketShipID" would be the key for a table called "RocketShip". (This fact is used to populate the dropdowns for the insertion form.)


