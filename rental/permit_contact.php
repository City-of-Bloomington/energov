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

$contact = $DCT->prepare("select contact_id    from contact where legacy_id=? and legacy_data_source_name=?");
$permit  = $DCT->prepare('select permit_number from permit  where legacy_id=? and legacy_data_source_name=?');

$sql     = "select r.id,
                   r.agent
            from rental.registr r
            where r.agent>0";
$result  = $RENTAL->query($sql);
foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    echo "Permit Agent: $row[id]\n";
    $permit ->execute([$row['id'   ], DATASOURCE_RENTAL]);
    $contact->execute([$row['agent'], DATASOURCE_RENTAL]);
    $permit_number = $permit ->fetchColumn();
    $contact_id    = $contact->fetchColumn();

    if ($permit_number && $contact_id) {
        $data = [
            'permit_number' => $permit_number,
            'contact_id'    => $contact_id,
            'contact_type'  => 'agent',
            'primary_billing_contact' => 0
        ];
        $insert->execute($data);
    }
    else {
        print_r($row);
        echo "permit_number: $permit_number contact_id: $contact_id";
        exit();
    }
}


$sql = "select id,
               name_num
        from rental.regid_name";
$result = $RENTAL->query($sql);
foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    echo "Permit Owner: $row[id]";
    $permit ->execute([$row['id'      ], DATASOURCE_RENTAL]);
    $contact->execute([$row['name_num'], DATASOURCE_RENTAL]);
    $permit_number = $permit ->fetchColumn();
    $contact_id    = $contact->fetchColumn();

    if ($permit_number && $contact_id) {
        $data = [
            'permit_number' => $permit_number,
            'contact_id'    => $contact_id,
            'contact_type'  => 'owner',
            'primary_billing_contact' => 1
        ];
        $insert->execute($data);
    }
    else {
        print_r($row);
        echo "permit_number: $permit_number contact_id: $contact_id";
        exit();
    }
}
