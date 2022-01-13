<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $RENTAL PDO connection to rental database
 * @param $DCT    PDO connection to DCT database
 */
declare (strict_types=1);
$payment_fields = [
    'receipt_number',
    'payment_method',
    'check_number',
    'payment_amount',
    'payment_date'
];
$detail_fields = [
    'permit_fee_id',
    'payment_id',
    'paid_amount'
];

$columns = implode(',', $payment_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $payment_fields));
$insert_payment = $DCT->prepare("insert into payment ($columns) values($params)");

$columns = implode(',', $detail_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $detail_fields));
$insert_details = $DCT->prepare("insert into permit_payment_detail ($columns) values($params)");

$fee = $DCT->prepare('select permit_fee_id from permit_fee where legacy_id=? and legacy_data_source_name=?');

$sql = "select bid,
               receipt_no,
               rec_from,
               check_no,
               rec_sum,
               rec_date
        from rental.reg_paid
        where rec_date is not null";
$query  = $RENTAL->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/payment: $percent% $row[bid] => ";

    $insert_payment->execute([
        'receipt_number' => $row['receipt_no'],
        'payment_method' => $row['rec_from'  ],
        'check_number'   => $row['check_no'  ],
        'payment_amount' => $row['rec_sum'   ],
        'payment_date'   => $row['rec_date'  ]
    ]);
    $payment_id = $DCT->lastInsertId();
    echo "$payment_id";

    $fee->execute([$row['bid'], DATASOURCE_RENTAL]);
    $permit_fee_id = $fee->fetchColumn();
    if ($permit_fee_id) {
        $insert_details->execute([
            'permit_fee_id' => $permit_fee_id,
            'payment_id'    => $payment_id,
            'paid_amount'   => $row['rec_sum']
        ]);
    }
}
echo "\n";
