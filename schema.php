<?php
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
      "Source"        => array("SourceID", "SourceTypeID", "SourceName", "AuthSurname", "AuthFName", "SourceYear", "SourcePlPub", "SourcePub", "SourceLocation", "Comments"),
      "SourceType"    => array("SourceTypeID", "SourceTypeName"),
    );
    
    // ID fields autoincrement, so remove them.
    foreach ($fields_lists as $key => $value) {
      array_shift($fields_lists[$key]);
    } 

    $table_list = array_keys($fields_lists);
    
?>
