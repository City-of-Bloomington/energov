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
$query  = $RENTAL->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/permit_address: $percent% $row[id] => ";
    echo "$row[street_num] $row[street_dir] $row[street_name] $row[street_type] $row[sud_type] $row[sud_num]";
    $insert->execute([
        'permit_number'     => "rental_$row[id]",
        'main_address'      => $row['subunit_id' ] ? 0 : 1,
        'street_number'     => $row['street_num' ],
        'pre_direction'     => $row['street_dir' ],
        'street_name'       => $row['street_name'],
        'street_type'       => $row['street_type'],
        'post_direction'    => $row['post_dir'   ],
        'unit_suite_number' => trim("$row[sud_type] $row[sud_num]"),
        'country_type'      => 'earth',
    ]);
}
echo "\n";
