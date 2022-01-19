<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $ROW  PDO connection to row database
 * @param $DCT  PDO connection to DCT database
 */
declare (strict_types=1);
$fields = [
    'legacy_id',
    'permit_number',
    'permit_type',
    'permit_sub_type',
    'permit_status',
    'permit_description',
    'apply_date',
    'issue_date',
    'assigned_to',
    'legacy_data_source_name'
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert into permit ($columns) values($params)");

$sql = "select p.id,
               p.permit_num,
               p.permit_type,
               p.status,
               p.date,
               p.project,
               p.start_date,
               i.first_name,
               i.last_name
        from row.excavpermits p
        left join inspectors  i on p.reviewer_id=i.user_id";
$query  = $ROW->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrow/permit: $percent% $row[id]";

    $insert->execute([
        'permit_type'             => 'Excavation',
        'legacy_id'               => $row['id'         ],
        'permit_number'           => $row['permit_num' ],
        'permit_sub_type'         => $row['permit_type'],
        'permit_status'           => $row['status'     ],
        'permit_description'      => $row['project'    ],
        'apply_date'              => $row['date'       ],
        'issue_date'              => $row['start_date' ],
        'assigned_to'             => "$row[first_name] $row[last_name]",
        'legacy_data_source_name' => DATASOURCE_ROW
    ]);
}
echo "\n";
