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
$contact = $DCT->prepare("select contact_id from contact where legacy_id=? and legacy_data_source_name=?");

$query   = $RENTAL->query("select id, agent from rental.registr where agent>0");
$result  = $query->fetchAll(\PDO::FETCH_ASSOC);
$total   = count($result);
$c       = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/permit_contact agent: $percent% $row[id]";

    $contact->execute([$row['agent'], DATASOURCE_RENTAL]);
    $contact_id = $contact->fetchColumn();

    if ($contact_id) {
        $insert->execute([
            'permit_number' => "rental_$row[id]",
            'contact_id'    => $contact_id,
            'contact_type'  => 'agent',
            'primary_billing_contact' => 0
        ]);
    }
    else {
        print_r($row);
        echo "contact_id: $contact_id";
        exit();
    }
}
echo "\n";

$query  = $RENTAL->query("select id, name_num from rental.regid_name");
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/permit_contact owner: $percent% $row[id]";

    $contact->execute([$row['name_num'], DATASOURCE_RENTAL]);
    $contact_id = $contact->fetchColumn();

    if ($contact_id) {
        $insert->execute([
            'permit_number' => "rental_$row[id]",
            'contact_id'    => $contact_id,
            'contact_type'  => 'owner',
            'primary_billing_contact' => 1
        ]);
    }
    else {
        print_r($row);
        echo "contact_id: $contact_id";
        exit();
    }
}
echo "\n";
