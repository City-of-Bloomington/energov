<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
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
$insert_payment = $energov->prepare("insert into payment ($columns) values($params)");

$columns = implode(',', $detail_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $detail_fields));
$insert_details = $energov->prepare("insert into permit_payment_detail ($columns) values($params)");

$fee = $energov->prepare('select permit_fee_id from permit_fee where legacy_id=? and legacy_data_source_name=?');

$sql = "select bid,
               receipt_no,
               rec_from,
               check_no,
               rec_sum,
               rec_date
        from rental.reg_paid
        where rec_date is not null";
$result = $rental->query($sql);
foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    echo "Payment: $row[bid] => ";

    $insert_payment->execute([
        'receipt_number' => $row['receipt_no'],
        'payment_method' => $row['rec_from'  ],
        'check_number'   => $row['check_no'  ],
        'payment_amount' => $row['rec_sum'   ],
        'payment_date'   => $row['rec_date'  ]
    ]);
    $payment_id = $energov->lastInsertId();

    $fee->execute([$row['bid'], DATASOURCE_RENTAL]);
    $permit_fee_id = $fee->fetchColumn();
    if ($permit_fee_id) {
        $insert_details->execute([
            'permit_fee_id' => $permit_fee_id,
            'payment_id'    => $payment_id,
            'paid_amount'   => $row['rec_sum']
        ]);
    }
    echo "$payment_id\n";
}
