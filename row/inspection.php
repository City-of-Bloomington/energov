<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $ROW  PDO connection to row database
 * @param $DCT  PDO connection to DCT database
 */
declare (strict_types=1);
$fields = [
    'inspection_number',
    'inspection_type',
    'inspection_status',
    'completed',
    'inspector',
    'inspected_date_start',
    'inspected_date_end',
    'comment',
    'legacy_data_source_name'
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert into inspection ($columns) values($params)");

$sql = "select i.id,
               i.status,
               i.inspector_id,
               i.date,
               i.date,
               i.notes,
               u.first_name,
               u.last_name
        from row.inspections i
        left join inspectors u on i.inspector_id=u.user_id";
$query   = $ROW->query($sql);
$result  = $query->fetchAll(\PDO::FETCH_ASSOC);
$total   = count($result);
$c       = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrow/inspection: $percent% $row[id]";

    $insert->execute([
        'inspection_number'       => DATASOURCE_ROW."_$row[id]",
        'inspection_type'         => 'Excavation',
        'inspection_status'       => $row['status'],
        'completed'               => $row['status']=='Completed' ? 1 : 0,
        'inspected_date_start'    => $row['date'  ],
        'inspected_date_end'      => $row['date'  ],
        'comment'                 => $row['notes' ],
        'inspector'               => "$row[first_name] $row[last_name]",
        'legacy_data_source_name' => DATASOURCE_ROW,
    ]);
}
echo "\n";
