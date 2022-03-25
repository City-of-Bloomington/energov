<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $PLANNING  PDO connection to staging database
 * @param $DCT       PDO connection to DCT database
 */
declare (strict_types=1);

$permit_fields = [
    'permit_number',
    'permit_type',
    'permit_sub_type',
    'permit_description',
    'apply_date',
    'issue_date',
    'assigned_to',
    'legacy_data_source_name'
];

$columns = implode(',', $permit_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $permit_fields));
$insert_permit = $DCT->prepare("insert into permit ($columns) values($params)");

$sql    = "select * from permits where permit_type!='Zoning Verification Letter'";
$query  = $PLANNING->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rplanning/permit: $percent% $row[id]";

    $insert_permit->execute([
        'permit_type'             => 'CZC',
        'permit_number'           => $row['permit_number'     ],
        'permit_sub_type'         => $row['permit_type'       ],
        'permit_description'      => $row['permit_description'],
        'apply_date'              => $row['apply_date'        ],
        'issue_date'              => $row['issue_date'        ],
        'assigned_to'             => $row['assigned_to'       ],
        'legacy_data_source_name' => DATASOURCE_PLANNING
    ]);
}
echo "\n";
