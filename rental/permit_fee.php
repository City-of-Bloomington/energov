<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $rental   PDO connection to rental database
 * @param $energov  PDO connection to DCT database
 */
declare (strict_types=1);
$fields = [
    'permit_number',
    'fee_amount',
    'fee_date',
    'legacy_data_source_name',
    'legacy_id'
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $energov->prepare("insert into permit_fee ($columns) values($params)");
$permit  = $energov->prepare('select permit_number from permit  where legacy_id=? and legacy_data_source_name=?');

$sql    = "select * from rental.reg_bills";
$result = $rental->query($sql);
foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    echo "Permt Fee: $row[bid] => ";
    $permit->execute([$row['bid'], DATASOURCE_RENTAL]);
    $fee_amount = (((int)$row[    'bul_rate'] * (int)$row[    'bul_cnt'])
                 + ((int)$row[   'unit_rate'] * (int)$row[   'unit_cnt'])
                 + ((int)$row[   'bath_rate'] * (int)$row[   'bath_cnt'])
                 + ((int)$row[ 'noshow_rate'] * (int)$row[ 'noshow_cnt'])
                 + ((int)$row[ 'reinsp_rate'] * (int)$row[ 'reinsp_cnt'])
                 + ((int)$row['summary_rate'] * (int)$row['summary_cnt'])
                 + ((int)$row[    'idl_rate'] * (int)$row[    'idl_cnt'])
                 +  (int)$row[ 'bhqa_fine']
                 +  (int)$row[ 'other_fee']
                 +  (int)$row['other_fee2']
                 -  (int)$row['credit']);
    $data = [
        'permit_number'           => $permit_number,
        'fee_amount'              => $fee_amount,
        'fee_date'                => $row['issue_date'],
        'legacy_data_source_name' => DATASOURCE_RENTAL,
        'legacy_id'               => $row['bid']
    ];
    $insert->execute($data);
    $permit_fee_id = $energov->lastInsertId();
    echo "$permit_fee_id\n";
}
