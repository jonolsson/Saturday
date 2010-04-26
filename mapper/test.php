<?php
// mapper test
$nameOpening;
$nameClosing;

$table = "articles";

$pdo = new PDO('mysql:dbname=mapper;host=localhost', 'mapper', 'mapper' );

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

    switch ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) {
        case 'mysql':
            $nameOpening = $nameClosing = '`';
            break;

        case 'mssql':
            $nameOpening = '[';
            $nameClosing = ']';
            break;

        default:
            $nameOpening = $nameClosing = '"';
            break;
    }


function quoteName( $name ) {
    return $nameOpening
      .str_replace($nameClosing, $nameClosing.$nameClosing, $name)
      .$nameClosing;
}

    $result = $pdo->query("SHOW COLUMNS FROM ".quoteName($table));
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $meta = Array();
    foreach ($result as $row) {
        $meta[$row['field']] = Array(
        'pk' => $row['key'] == 'PRI',
        'type' => $row['type'],
    );
}
$obj = new stdClass();
foreach( $meta as $key => $value ) {
    $obj->$key = $value;
}
print_r( $obj );
//return $meta;
print_r( $meta );

