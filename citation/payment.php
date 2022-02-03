<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $CITATION  PDO connection to row database
 * @param $DCT       PDO connection to DCT database
 */
declare (strict_types=1);
$payment_fields = [
    'payment_id',
    'receipt_number',
    'payment_method',
    'check_number',
    'payment_amount',
    'payment_date'
];

$detail_fields = [
    'code_case_violation_fee_id',
    'payment_id',
    'paid_amount'
];

$columns = implode(',', $payment_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $payment_fields));
$insert_payment = $DCT->prepare("insert into payment ($columns) values($params)");

$columns = implode(',', $detail_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $detail_fields));
$insert_detail  = $DCT->prepare("insert into code_case_violation_payment_detail ($columns) values($params)");

// This will filter out many checks we received from people, but we have
// no record of how much their fine was.
$sql    = "select r.*
           from receipts  r
           join citations c on c.id=r.cite_id
           where c.amount>0";
$query  = $CITATION->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rcitation/payment: $percent% $row[id]";

    $payment_id = DATASOURCE_CITATION."_$row[id]";
    $fee_id     = DATASOURCE_CITATION."_$row[cite_id]";

    $insert_payment->execute([
        'payment_id'     => $payment_id,
        'receipt_number' => $row['receipt_no'],
        'payment_method' => $row['paid_by'   ],
        'check_number'   => $row['check_no'  ],
        'payment_amount' => $row['rec_sum'   ],
        'payment_date'   => $row['rec_date'  ]
    ]);

    $insert_detail->execute([
        'code_case_violation_fee_id' => $fee_id,
        'payment_id'                 => $payment_id,
        'paid_amount'                => $row['rec_sum']
    ]);
}
echo "\n";
