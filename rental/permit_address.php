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
    'main_address',
    'street_number',
    'pre_direction',
    'street_name',
    'street_type',
    'post_direction',
    'unit_suite_number',
    'country_type'
];
$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert permit_address ($columns) values($params)");
$permit  = $DCT->prepare('select permit_number from permit where legacy_id=? and legacy_data_source_name=?');

$sql = "select r.id,
               a.street_num,
               a.street_dir,
               a.street_name,
               a.street_type,
               a.post_dir,
               a.subunit_id,
               a.sud_type,
               a.sud_num
        from rental.registr  r
        join rental.address2 a on r.id=a.registr_id";
$result = $RENTAL->query($sql);
foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    echo "Permit Address: $row[id] => ";
    $permit->execute([$row['id'], DATASOURCE_RENTAL]);
    $permit_number = $permit->fetchColumn();

    if ($permit_number) {
        echo "$permit_number | $row[street_num] $row[street_dir] $row[street_name] $row[street_type] $row[sud_type] $row[sud_num]\n";
        $data = [
            'permit_number'     => $permit_number,
            'main_address'      => $row['subunit_id' ] ? 0 : 1,
            'street_number'     => $row['street_num' ],
            'pre_direction'     => $row['street_dir' ],
            'street_name'       => $row['street_name'],
            'street_type'       => $row['street_type'],
            'post_direction'    => $row['post_dir'   ],
            'unit_suite_number' => trim("$row[sud_type] $row[sud_num]"),
            'country_type'      => 'unknown',
        ];
        $insert->execute($data);
    }
    else {
        echo "Could not find permit for $row[id]\n";
        exit();
    }
}
