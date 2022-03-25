<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $PLANNING  PDO connection to staging database
 * @param $DCT       PDO connection to DCT database
 */
declare (strict_types=1);

$fields = [
    'plan_number',
    'plan_type',
    'apply_date',
    'completed_date',
    'assigned_to',
    'legacy_data_source_name'
];

$address_fields = [
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert into plan_case ($columns) values($params)");

$sql    = "select * from permits where permit_type='Zoning Verification Letter'";
$query  = $PLANNING->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rplanning/plan_case: $percent% $row[id]";

    $insert->execute([
        'plan_number'    => $row['permit_number'],
        'plan_type'      => $row['permit_type'  ],
        'apply_date'     => $row['apply_date'   ],
        'completed_date' => $row['issue_date'   ],
        'assigned_to'    => $row['assigned_to'  ],
        'legacy_data_source_name' => DATASOURCE_PLANNING
    ]);
}
echo "\n";
