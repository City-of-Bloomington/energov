<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $CITATION  PDO connection to row database
 * @param $DCT       PDO connection to DCT database
 */
declare (strict_types=1);
$fields = [
    'parent_case_number',
    'parent_case_table',
    'attached_by',
    'file_name',
    'doc_comment',
    'doc_date',
    'document_data'
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert into attachment_document ($columns) values($params)");

$sql = "select id,
               cite_id,
               added_by,
               date,
               name,
               notes,
               date_format(date, '%y') as year
        from citation_files";
$query  = $CITATION->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rcitation/attachment_document: $percent% $row[id]";

    $case_number = DATASOURCE_CITATION."_$row[cite_id]";

    $dir  = SITE_HOME.'/citation/depot';
    $file = "$row[year]/$row[name]";
    if (is_file("$dir/$file")) {
        echo " => $file";
        $fp = fopen("$dir/$file", 'rb');

        $insert->bindParam('parent_case_number', $case_number,     \PDO::PARAM_STR);
        $insert->bindValue('parent_case_table' , 'code',           \PDO::PARAM_STR);
        $insert->bindParam('attached_by'       , $row['added_by'], \PDO::PARAM_STR);
        $insert->bindParam('file_name'         , $row['name'    ], \PDO::PARAM_STR);
        $insert->bindParam('doc_comment'       , $row['notes'   ], \PDO::PARAM_STR);
        $insert->bindParam('doc_date'          , $row['date'    ], \PDO::PARAM_STR);
        $insert->bindParam('document_data'     , $fp, \PDO::PARAM_LOB, 0, \PDO::SQLSRV_ENCODING_BINARY);
        $insert->execute();
        fclose($fp);
    }
}
echo "\n";
