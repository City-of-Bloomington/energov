<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $NOV  PDO connection to nov database
 * @param $DCT  PDO connection to DCT database
 */
declare (strict_types=1);
$fields = [
    'case_number',
    'contact_id',
    'contact_type',
    'primary_billing_contact'
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert into code_case_contact ($columns) values($params)");

$query  = $NOV->query('select * from citation_owners');
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rnov/code_case_contact: $percent% $row[cite_id]";

    $contact_id  = DATASOURCE_NOV."_$row[owner_id]";
    $case_number = DATASOURCE_NOV."_$row[cite_id]";

    $insert->execute([
        'case_number'             => $case_number,
        'contact_id'              => $contact_id,
        'contact_type'            => 'owner',
        'primary_billing_contact' => 1
    ]);
}
echo "\n";
