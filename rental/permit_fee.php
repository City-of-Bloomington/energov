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
    'fee_type',
    'fee_amount',
    'fee_date'
];

$adjustment_fields = [
    'permit_fee_adjustment_id',
    'permit_fee_id',
    'adjustment_type',
    'adjustment_amount',
    'adjustment_date',
    'adjustment_note'
];
$columns = implode(',', $fee_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fee_fields));
$insert_fee = $DCT->prepare("insert into permit_fee ($columns) values($params)");

$columns = implode(',', $adjustment_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $adjustment_fields));
$insert_adjustment = $DCT->prepare("insert into permit_fee_adjustment ($columns) values($params)");

$sql = "select r.id,
               b.bid,
               r.prop_type,
               r.building_type,
               b.issue_date,
               b.due_date,
               b.bul_cnt,
               b.bul_rate,
               b.bath_cnt,
               b.bath_rate,
               b.unit_cnt,
               b.unit_rate,
               b.noshow_cnt,
               b.noshow_rate,
               b.reinsp_cnt,
               b.reinsp_rate,
               b.bhqa_fine,
               b.summary_flag,
               b.summary_cnt,
               b.summary_rate,
               b.idl_flag,
               b.idl_cnt,
               b.idl_rate,
               b.other_fee,
               b.credit
        from rental.registr   r
        join rental.reg_bills b on b.bid=(select max(bid) from rental.reg_bills where id=r.id)
        where r.inactive is null
        and b.status!='Paid'";
$query   = $RENTAL->query($sql);
$result  = $query->fetchAll(\PDO::FETCH_ASSOC);
$total   = count($result);
$c       = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/permit_fee: $percent% $row[bid]";

    $permit_number = DATASOURCE_RENTAL."_$row[id]";
    // Insert the inspection fees
    if ($row['prop_type'] == 'Condo') {
        $fee_id     = DATASOURCE_RENTAL."_condo_$row[bid]";
        $fee_amount = (int)$row['bul_rate'] + (int)$row['unit_rate'];
        $insert_fee->execute([
            'permit_fee_id' => $fee_id,
            'permit_number' => $permit_number,
            'fee_type'      => 'Condo',
            'fee_amount'    => $fee_amount,
            'fee_date'      => $row['issue_date']
        ]);
    }
    elseif ($row['prop_type'] == 'Rooming House') {
        $fee_id     = DATASOURCE_RENTAL."_bul_$row[bid]";
        $fee_amount = (int)$row['bul_cnt'] * (int)$row['bul_rate'];
        $insert_fee->execute([
            'permit_fee_id' => $fee_id,
            'permit_number' => $permit_number,
            'fee_type'      => 'Rooming House - Buildings',
            'fee_amount'    => $fee_amount,
            'fee_date'      => $row['issue_date']
        ]);

        $fee_id     = DATASOURCE_RENTAL."_unit_$row[bid]";
        $fee_amount = (int)$row['bath_cnt'] * (int)$row['bath_rate'];
        $insert_fee->execute([
            'permit_fee_id' => $fee_id,
            'permit_number' => $permit_number,
            'fee_type'      => 'Rooming House - Bathrooms',
            'fee_amount'    => $fee_amount,
            'fee_date'      => $row['issue_date']
        ]);
    }
    elseif ($row['building_type'] == 'Single-Family') {
        $fee_id     = DATASOURCE_RENTAL."_single_$row[bid]";
        $fee_amount = (int)$row['bul_rate'] + (int)$row['unit_rate'];
        $insert_fee->execute([
            'permit_fee_id' => $fee_id,
            'permit_number' => $permit_number,
            'fee_type'      => 'Single Family Home',
            'fee_amount'    => $fee_amount,
            'fee_date'      => $row['issue_date']
        ]);
    }
    else {
        // Everything left over is treated as Multi-Family
        $fee_id     = DATASOURCE_RENTAL."_bul_$row[bid]";
        $fee_amount = (int)$row['bul_cnt'] * $row['bul_rate'];
        $insert_fee->execute([
            'permit_fee_id' => $fee_id,
            'permit_number' => $permit_number,
            'fee_type'      => 'Multi-Family - Buildings',
            'fee_amount'    => $fee_amount,
            'fee_date'      => $row['issue_date']
        ]);

        $fee_id     = DATASOURCE_RENTAL."_unit_$row[bid]";
        $fee_amount = (int)$row['unit_cnt'] * (int)$row['unit_rate'];
        $insert_fee->execute([
            'permit_fee_id' => $fee_id,
            'permit_number' => $permit_number,
            'fee_type'      => 'Multi-Family - Units',
            'fee_amount'    => $fee_amount,
            'fee_date'      => $row['issue_date']
        ]);
    }

    // Apply credits as adjustments to the inspection fees
    // From the previous step, the latest $fee_id is what we apply credits to
    $credit = (float)$row['credit'];
    if ($credit > 0) {
        $insert_adjustment->execute([
            'permit_fee_adjustment_id' => $fee_id,
            'permit_fee_id'            => $fee_id,
            'adjustment_type'          => 'Other',
            'adjustment_amount'        => round(($fee_amount - $credit), 2),
            'adjustment_date'          => $row['issue_date'],
            'adjustment_note'          => 'Credit from '.DATASOURCE_RENTAL
        ]);
    }

    // All the other fees and fines
    $fee_amount = (int)$row['noshow_cnt'] * (float)$row['noshow_rate'];
    if ($fee_amount) {
        $fee_id     = DATASOURCE_RENTAL."_noshow_$row[bid]";
        $insert_fee->execute([
            'permit_fee_id' => $fee_id,
            'permit_number' => $permit_number,
            'fee_type'      => 'No Show',
            'fee_amount'    => $fee_amount,
            'fee_date'      => $row['issue_date']
        ]);
    }

    $fee_amount = (int)$row['reinsp_cnt'] * (float)$row['reinsp_rate'];
    if ($fee_amount) {
        $fee_id     = DATASOURCE_RENTAL."_reinsp_$row[bid]";
        $insert_fee->execute([
            'permit_fee_id' => $fee_id,
            'permit_number' => $permit_number,
            'fee_type'      => 'Reinspection',
            'fee_amount'    => $fee_amount,
            'fee_date'      => $row['issue_date']
        ]);
    }

    $fee_amount = (float)$row['bhqa_fine' ];
    if ($fee_amount) {
        $fee_id     = DATASOURCE_RENTAL."_bhqa_$row[bid]";
        $insert_fee->execute([
            'permit_fee_id' => $fee_id,
            'permit_number' => $permit_number,
            'fee_type'      => 'BHQA Fine',
            'fee_amount'    => $fee_amount,
            'fee_date'      => $row['issue_date']
        ]);
    }

    // No missing affidavit fees in the data
//     $summary_fee = 0;
//     if ($bill['summary_flag']) {
//         $summary_cnt = (int)$bill['summary_cnt'];
//         $summary_fee = $summary_cnt
//                         ? ($summary_cnt * (float)$bill['summary_rate'])
//                         : ($uc          * (float)$bill['summary_rate']);
//     }
    if ($row['idl_flag'] && (int)$row['idl_cnt'] > 0) {
        $fee_amount = (int)$row['idl_cnt'] * (int)$row['idl_rate'];
        if ($fee_amount) {
            $fee_id     = DATASOURCE_RENTAL."_idl_$row[bid]";
            $insert_fee->execute([
                'permit_fee_id' => $fee_id,
                'permit_number' => $permit_number,
                'fee_type'      => 'IDL Fine',
                'fee_amount'    => $fee_amount,
                'fee_date'      => $row['issue_date']
            ]);
        }
    }
}
echo "\n";
