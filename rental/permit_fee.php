<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $RENTAL PDO connection to rental database
 * @param $DCT    PDO connection to DCT database
 */
declare (strict_types=1);
$fields = [
    'permit_fee_id',
    'permit_number',
    'fee_amount',
    'fee_date',
    'legacy_data_source_name'
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert into permit_fee ($columns) values($params)");

$query   = $RENTAL->query("select * from rental.reg_bills");
$result  = $query->fetchAll(\PDO::FETCH_ASSOC);
$total   = count($result);
$c       = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/permit_fee: $percent% $row[bid]";

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
        'permit_fee_id'           => DATASOURCE_RENTAL."_$row[bid]",
        'permit_number'           => DATASOURCE_RENTAL."_$row[id]",
        'fee_amount'              => $fee_amount,
        'fee_date'                => $row['issue_date'],
        'legacy_data_source_name' => DATASOURCE_RENTAL
    ];
    $insert->execute($data);
}
echo "\n";
