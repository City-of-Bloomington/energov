<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $PLANNING  PDO connection to staging database
 * @param $DCT       PDO connection to DCT database
 */
declare (strict_types=1);
$fields = [
    'permit_number',
    'street_number',
    'pre_direction',
    'street_name',
    'street_type',
    'unit_suite_number',
    'city',
    'state_code',
    'country_type'
];
$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert permit_address ($columns) values($params)");

$sql    = 'select * from permit_addresses';
$query  = $PLANNING->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rplanning/permit_address: $percent% $row[permit_id]";

    $insert->execute([
        'permit_number'     => $row['permit_number'],
        'street_number'     => $row['street_number'],
        'pre_direction'     => $row['pre_direction'],
        'street_name'       => $row['street_name'  ],
        'street_type'       => $row['street_type'  ],
        'unit_suite_number' => $row['unit_suite_number'],
        'city'              => 'Bloomington',
        'state_code'        => 'IN',
        'country_type'      => COUNTRY_TYPE,
    ]);
}
echo "\n";
