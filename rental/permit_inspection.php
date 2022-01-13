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
$permit  = $DCT->prepare('select permit_number     from permit     where legacy_id=? and legacy_data_source_name=?');
$inspect = $DCT->prepare('select inspection_number from inspection where legacy_id=? and legacy_data_source_name=?');

$sql = "select id,
               insp_id
        from rental.inspections";
$result = $RENTAL->query($sql);
foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    echo "Permit Inspection: $row[id] $row[insp_id]\n";
    $permit ->execute([$row['id'     ], DATASOURCE_RENTAL]);
    $inspect->execute([$row['insp_id'], DATASOURCE_RENTAL]);
    $permit_number     = $permit ->fetchColumn();
    $inspection_number = $inspect->fetchColumn();

    $data = [
        'permit_number'     => $permit_number,
        'inspection_number' => $inspection_number
    ];

    $insert->execute($data);
}
