<?php
/**
 * Rental Pull History will go into EnerGov as permit_activity
 *
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $rental   PDO connection to rental database
 * @param $energov  PDO connection to DCT database
 */
declare (strict_types=1);
$fields = [
    'permit_number',
    'activity_type',
    'activity_comment',
    'activity_user',
    'activity_date'
];
$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $energov->prepare("insert into permit_activity ($columns) values($params)");
$permit  = $energov->prepare('select permit_number from permit where legacy_id=? and legacy_data_source_name=?');

$sql = "select h.rental_id,
               r.pull_text,
               h.username,
               h.pull_date
        from rental.pull_history h
        join rental.pull_reas    r on h.pull_reason=r.p_reason";
$result = $rental->query($sql);
foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    echo "Permit Activity: $row[rental_id] => ";
    $permit->execute([$row['rental_id'], DATASOURCE_RENTAL]);
    $permit_number = $permit ->fetchColumn();
    echo "$permit_number\n";

    $insert->execute([
        'permit_number'    => $permit_number,
        'activity_type'    => 'Pull',
        'activity_comment' => $row['pull_text'],
        'activity_user'    => $row['username' ],
        'activity_date'    => $row['pull_date']
    ]);
}
