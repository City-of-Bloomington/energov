<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $RENTAL PDO connection to rental database
 * @param $DCT    PDO connection to DCT database
 */
declare (strict_types=1);
$fee_fields = [
    'permit_fee_id',
    'permit_number',
    'fee_amount',
    'fee_date'
];

$adjustment_fields = [
    'permit_fee_adjustment_id',
    'permit_fee_id',
    'adjustment_type',
    'adjustment_amount'
];

$columns = implode(',', $fee_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fee_fields));
$insert_fee = $DCT->prepare("insert into permit_fee ($columns) values($params)");

$columns = implode(',', $adjustment_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $adjustment_fields));
$insert_adjustment = $DCT->prepare("insert into permit_fee_adjustment ($columns) values($params)");

$sql     = "select b.*
            from rental.registr   r
            join rental.reg_bills b on r.id=b.id
            left join (
                select p.rental_id, min(p.pull_date) as earliest_pull
                from rental.pull_history p
                group by p.rental_id
            ) pulls on pulls.rental_id=r.id
            where (r.registered_date is not null or earliest_pull is not null)";
$query   = $RENTAL->query($sql);
$result  = $query->fetchAll(\PDO::FETCH_ASSOC);
$total   = count($result);
$c       = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/permit_fee: $percent% $row[bid]";

    $permit_fee_id = DATASOURCE_RENTAL."_$row[bid]";
    $permit_number = DATASOURCE_RENTAL."_$row[id]";

    $fee_amount = (((int)$row[    'bul_rate'] * (int)$row[    'bul_cnt'])
                 + ((int)$row[   'unit_rate'] * (int)$row[   'unit_cnt'])
                 + ((int)$row[   'bath_rate'] * (int)$row[   'bath_cnt'])
                 + ((int)$row[ 'noshow_rate'] * (int)$row[ 'noshow_cnt'])
                 + ((int)$row[ 'reinsp_rate'] * (int)$row[ 'reinsp_cnt'])
                 + ((int)$row['summary_rate'] * (int)$row['summary_cnt'])
                 + ((int)$row[    'idl_rate'] * (int)$row[    'idl_cnt'])
                 +  (int)$row[ 'bhqa_fine'  ]
                 +  (int)$row[ 'other_fee'  ]
                 +  (int)$row['other_fee2'  ]);

    $insert_fee->execute([
        'permit_fee_id' => $permit_fee_id,
        'permit_number' => $permit_number,
        'fee_amount'    => $fee_amount,
        'fee_date'      => $row['issue_date']
    ]);

    $credit = (int)$row['credit'];
    if ($credit) {
        $adjustment_id = $permit_fee_id;

        $insert_adjustment->execute([
            'permit_fee_adjustment_id' => $adjustment_id,
            'permit_fee_id'            => $permit_fee_id,
            'adjustment_type'          => 'credit',
            'adjustment_amount'        => $credit,
        ]);
    }
}
echo "\n";
