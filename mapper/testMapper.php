<?php
include "Mapper.php";
include "MapperCollection.php";
include "Articles.php";
$map = new Articles( "articles" );

// Find
echo "Find\n";
$result = $map->find(2);

print_r( $result );

// insert

echo "\nInsert\n";
$obj = new stdClass();
$obj->name = "Ny artikel";
$obj->body = "Blah blah yada yada";
$obj->author_id = 2;

$map->insert( $obj );

echo "Inserted object:\n";
print_r( $obj );

echo "delete\n";
$map->delete($obj);

//update
echo "\nUpdate:\n";
$result->name = "Modified";
$map->update( $result );
