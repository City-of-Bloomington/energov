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
    'note_text',
    'note_user',
    'note_date'
];
$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert into permit_note ($columns) values($params)");

$query   = $RENTAL->query("select * from rental.rental_notes");
$result  = $query->fetchAll(\PDO::FETCH_ASSOC);
$total   = count($result);
$c       = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/permit_note: $percent% $row[rental_id]";

    $insert->execute([
        'permit_number' => DATASOURCE_RENTAL."_$row[rental_id]",
        'note_text'     => $row['notes'    ],
        'note_user'     => $row['userid'   ],
        'note_date'     => $row['note_date']
    ]);
}
echo "\n";
