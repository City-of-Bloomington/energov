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
    'permit_type',
    'permit_sub_type',
    'permit_status',
    'apply_date',
    'issue_date',
    'expire_date',
    'legacy_data_source_name'
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert into permit ($columns) values($params)");

$sql = "select  r.id,
                s.status_text,
                r.inactive,
                r.registered_date,
                r.permit_issued,
                r.permit_expires,
                pulls.earliest_pull
        from rental.registr r
        join rental.prop_status s on r.property_status=s.status
        left join (
            select p.rental_id, min(p.pull_date) as earliest_pull
            from rental.pull_history p
            group by p.rental_id
        ) pulls on pulls.rental_id=r.id
        where (r.registered_date is not null or earliest_pull is not null)";
$query  = $RENTAL->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/permit: $percent% $row[id]";

    $apply_date    = $row['registered_date'] ? $row['registered_date'] : $row['earliest_pull'];
    $permit_number = DATASOURCE_RENTAL."_$row[id]";

    $insert->execute([
        'permit_number'           => $permit_number,
        'permit_type'             => 'rental',
        'permit_sub_type'         => $row['status_text'],
        'permit_status'           => $row['inactive'] ? 'inactive' : 'active',
        'apply_date'              => $apply_date,
        'issue_date'              => $row['permit_issued'  ],
        'expire_date'             => $row['permit_expires' ],
        'legacy_data_source_name' => DATASOURCE_RENTAL,
    ]);
}
echo "\n";
