<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $rental   PDO connection to rental database
 * @param $energov  PDO connection to DCT database
 */
declare (strict_types=1);
$fields = [
    'permit_number',
    'note_text',
    'note_user',
    'note_date'
];
$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $energov->prepare("insert into permit_note ($columns) values($params)");
$permit  = $energov->prepare('select permit_number     from permit     where legacy_id=? and legacy_data_source_name=?');

$sql = "select rental_id
               notes,
               userid,
               note_date
        from rental.rental_notes";
$result = $rental->query($sql);
foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    echo "Permit Note: $row[rental_id]\n";
    $permit->execute([$row['rental_id'], DATASOURCE_RENTAL);
    $permit_number = $permit ->fetchColumn();

    $insert->execute([
        'permit_number' => $permit_number,
        'note_text'     => $row['notes'    ],
        'note_user'     => $row['note_user'],
        'note_date'     => $row['note_date']
    ]);
}
