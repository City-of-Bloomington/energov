<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $RENTAL PDO connection to rental database
 * @param $DCT    PDO connection to DCT database
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
$insert  = $DCT->prepare("insert into attachment_document ($columns) values($params)");

$sql = "select rid,
               image_file,
               image_date,
               notes,
               to_char(image_date, 'YY') as year
        from rental.rental_images";
$query  = $RENTAL->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/attachment_document: $percent% $row[rid]";

    $permit_number = "rental_$row[rid]";

    $dir  = SITE_HOME.'/rental/files';
    $file = "$row[year]/$row[image_file]";
    if (is_file("$dir/$file")) {
        echo " => $file";
        $fp = fopen("$dir/$file", 'rb');
        $insert->bindParam('parent_case_number', $permit_number,     \PDO::PARAM_STR);
        $insert->bindValue('parent_case_table' , 'permit',           \PDO::PARAM_STR);
        $insert->bindParam('file_name'         , $row['image_file'], \PDO::PARAM_STR);
        $insert->bindParam('doc_comment'       , $row['notes'     ], \PDO::PARAM_STR);
        $insert->bindParam('doc_date'          , $row['image_date'], \PDO::PARAM_STR);
        $insert->bindParam('document_data'     , $fp, \PDO::PARAM_LOB, 0, \PDO::SQLSRV_ENCODING_BINARY);
        $insert->execute();
        fclose($fp);
    }
}
echo "\n";
