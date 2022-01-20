<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $RENTAL PDO connection to rental database
 * @param $DCT    PDO connection to DCT database
 */
declare (strict_types=1);
$fields = [
    'permit_number',
    'inspection_number'
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert into permit_inspection ($columns) values($params)");

$query   = $RENTAL->query("select id, insp_id from rental.inspections");
$result  = $query->fetchAll(\PDO::FETCH_ASSOC);
$total   = count($result);
$c       = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/permit_inspection: $percent% $row[insp_id]";

    $insert->execute([
        'permit_number'     => DATASOURCE_RENTAL."_$row[id]",
        'inspection_number' => DATASOURCE_RENTAL."_$row[insp_id]"
    ]);
}
echo "\n";
