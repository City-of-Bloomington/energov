<?php
/**
 * Rental Pull History will go into EnerGov as permit_activity
 *
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $RENTAL PDO connection to rental database
 * @param $DCT    PDO connection to DCT database
 */
declare (strict_types=1);
$fields = [
    'activity_number',
    'permit_number',
    'activity_type',
    'activity_comment',
    'activity_user',
    'activity_date'
];
$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert into permit_activity ($columns) values($params)");

$sql = "select h.id,
               h.rental_id,
               r.pull_text,
               h.username,
               h.pull_date
        from rental.pull_history h
        join rental.pull_reas    r on h.pull_reason=r.p_reason";
$query  = $RENTAL->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/permit_activity: $percent% $row[id]";

    $insert->execute([
        'activity_number'  => DATASOURCE_RENTAL."_$row[id]",
        'permit_number'    => DATASOURCE_RENTAL."_$row[rental_id]",
        'activity_type'    => 'Pull',
        'activity_comment' => $row['pull_text'],
        'activity_user'    => $row['username' ],
        'activity_date'    => $row['pull_date']
    ]);
}
echo "\n";
