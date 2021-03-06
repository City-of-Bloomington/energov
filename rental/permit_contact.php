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
    'contact_id',
    'contact_type',
    'primary_billing_contact',
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert permit_contact ($columns) values($params)");

$sql     = "select r.id,
                   r.agent
            from rental.registr r
            left join (
                select p.rental_id, min(p.pull_date) as earliest_pull
                from rental.pull_history p
                group by p.rental_id
            ) pulls on pulls.rental_id=r.id
            where (r.registered_date is not null or earliest_pull is not null)
              and agent>0";
$query   = $RENTAL->query($sql);
$result  = $query->fetchAll(\PDO::FETCH_ASSOC);
$total   = count($result);
$c       = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/permit_contact agent: $percent% $row[id]";

    $insert->execute([
        'permit_number' => DATASOURCE_RENTAL."_$row[id]",
        'contact_id'    => DATASOURCE_RENTAL."_$row[agent]",
        'contact_type'  => 'agent',
        'primary_billing_contact' => 0
    ]);
}
echo "\n";

$sql     = "select r.id,
                   n.name_num
            from rental.registr   r
            join rental.regid_name n on r.id=n.id
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
    echo chr(27)."[2K\rrental/permit_contact owner: $percent% $row[id]";

    $insert->execute([
        'permit_number' => DATASOURCE_RENTAL."_$row[id]",
        'contact_id'    => DATASOURCE_RENTAL."_$row[name_num]",
        'contact_type'  => 'owner',
        'primary_billing_contact' => 1
    ]);
}
echo "\n";
