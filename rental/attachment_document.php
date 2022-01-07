<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $rental   PDO connection to rental database
 * @param $energov  PDO connection to DCT database
 */
declare (strict_types=1);

$fields = [
    'parent_case_number',
    'parent_case_table',
    'file_name',
    'doc_comment',
    'doc_date',
    'document_data'
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $energov->prepare("insert into attachment_document ($columns) values($params)");
$permit  = $energov->prepare('select permit_number from permit where legacy_id=? and legacy_data_source_name=?');

$sql = "select rid,
               image_file,
               image_date,
               notes,
               to_char(image_date, 'YY') as year
        from rental.rental_images";
$result = $rental->query($sql);
foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    echo "Permit Images: $row[rid] => ";
    $permit->execute([$row['rid'], DATASOURCE_RENTAL]);
    $permit_number = $permit ->fetchColumn();

    $dir  = SITE_HOME.'/rental/files';
    $file = "$row[year]/$row[image_file]";
    if (is_file("$dir/$file")) {
        echo " $permit_number";
        $fp = fopen("$dir/$file", 'rb');
        $insert->bindParam('parent_case_number', $permit_number,     \PDO::PARAM_INT);
        $insert->bindValue('parent_case_table' , 'permit',           \PDO::PARAM_STR);
        $insert->bindParam('file_name'         , $row['image_file'], \PDO::PARAM_STR);
        $insert->bindParam('doc_comment'       , $row['notes'     ], \PDO::PARAM_STR);
        $insert->bindParam('doc_date'          , $row['image_date'], \PDO::PARAM_STR);
        $insert->bindParam('document_data'     , $fp, \PDO::PARAM_LOB, 0, \PDO::SQLSRV_ENCODING_BINARY);
        $insert->execute();
        fclose($fp);
        echo " => $file";
    }
    echo "\n";
}
