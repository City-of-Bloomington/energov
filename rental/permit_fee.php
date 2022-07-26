<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $RENTAL PDO connection to rental database
 * @param $DCT    PDO connection to DCT database
 */
declare (strict_types=1);
$sql     = "select b.*,
                   r.prop_type
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

    $permit_number = DATASOURCE_RENTAL."_$row[id]";
    $fees          = computeFees($row);
}
echo "\n";

function computeFees(array $bill): array
{
    $total = 0;

    if (!$bill['appeal']) {
        $uc = 0;
        $insp_fee = $bill['bul_cnt'] * $bill['bul_rate'];
        if ($bill['prop_type'] == 'Rooming House') {
            $uc        =  (int)$bill['bath_cnt'];
            $insp_fee += $uc * $bill['bath_rate'];
        }
        else {
            $uc        =  (int)$bill['unit_cnt'];
            $insp_fee += $uc * $bill['unit_rate'];
        }

        $noshow_fee =   (int)$bill['noshow_cnt'] * (float)$bill['noshow_rate'];
        $reinsp_fee =   (int)$bill['reinsp_cnt'] * (float)$bill['reinsp_rate'];
        $total     += (float)$bill['bhqa_fine'];

        $summary_fee = 0;
        if ($bill['summary_flag']) {
            $summary_cnt = (int)$bill['summary_cnt'];
            $summary_fee = $summary_cnt
                         ? ($summary_cnt * (float)$bill['summary_rate'])
                         : ($uc          * (float)$bill['summary_rate']);
        }

        $idl_fee = 0;
        if ($bill['idl_flag']) {
            $idl_cnt = (int)$bill['idl_cnt'];
            $idl_fee = $idl_cnt
                     ? ($idl_cnt * (float)$bill['idl_rate'])
                     : ($uc      * (float)$bill['idl_rate']);
        }
        $other_total = (float)$bill['other_fee'] + (float)$bill['other_fee2'];
        $total  += $insp_fee
                +  $noshow_fee
                +  $reinsp_fee
                +  $summary_fee
                +  $idl_fee
                +  $other_total
                -  (float)$bill['credit'];
    }
    else { // appeal
        $total = (float)$bill['appeal_fee'];
    }

    global $RENTAL;
    $query = $RENTAL->prepare('select sum(rec_sum) from rental.reg_paid where bid=?');
    $query->execute([$bill['bid']]);
    $paidSum = (float)$query->fetchColumn();

    $balance = $total - $paidSum;
    return ['balance' => $balance, 'total' => $total];
}
