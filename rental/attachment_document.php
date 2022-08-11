<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $RENTAL PDO connection to rental database
 * @param $DCT    PDO connection to DCT database
 */
declare (strict_types=1);
$ATTACHMENT_PATH = "S:\\legacy\\rental";

$fields = [
    'parent_case_number',
    'parent_case_table',
    'file_path',
    'file_name',
    'doc_comment',
    'doc_date'
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert into attachment_document ($columns) values($params)");

$sql    = "select rid,
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
    echo chr(27)."[2K\rrental/attachment image: $percent% $row[rid]";

    $permit_number = DATASOURCE_RENTAL."_$row[rid]";

    $dir  = SITE_HOME.'/rental/files';
    $file = "$row[year]/$row[image_file]";
    if (is_file("$dir/$file")) {
        echo " => $file";
        $insert->execute([
            'parent_case_number' => $permit_number,
            'parent_case_table'  => 'permit',
            'file_path'          => "$ATTACHMENT_PATH\\files\\$row[year]",
            'file_name'          => $row['image_file'],
            'doc_comment'        => $row['notes'     ],
            'doc_date'           => $row['image_date']
        ]);
    }
}
echo "\n";

$sql    = "select id,
                  insp_id,
                  inspection_date,
                  insp_file
           from rental.inspections
           where insp_file is not null";
$query  = $RENTAL->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/attachment inspection: $percent% $row[insp_id]";

    $inspection_number = DATASOURCE_RENTAL."_$row[insp_id]";

    $dir  = SITE_HOME.'/rental/inspections';
    $file = str_replace("\\", '/', $row['insp_file']);
    $ext  = extension($file);
    $date = new \DateTime($row['inspection_date']);
    $name = 'inspection_'.$date->format('Y-m-d').$ext;


    if (is_file("$dir/$file")) {
        echo " => $file";
        $file_path = str_replace('/', '\\', dirname($file));
        $file_name = basename($file);

        $insert->execute([
            'parent_case_number' => $inspection_number,
            'parent_case_table'  => 'inspection',
            'file_path'          => "$ATTACHMENT_PATH\\inspections\\$file_path",
            'file_name'          => $file_name,
            'doc_comment'        => null,
            'doc_date'           => $row['inspection_date']
        ]);
    }
}
echo "\n";

function extension(string $filename): string
{
    $matches = [];
    preg_match('|\.[^\.]+$|', $filename, $matches);
    return $matches[0];
}
