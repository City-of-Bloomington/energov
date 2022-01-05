<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $rental   PDO connection to rental database
 * @param $energov  PDO connection to DCT database
 */
declare (strict_types=1);
$contact_fields = [
    'first_name',
    'email',
    'business_phone',
    'home_phone',
    'isactive',
    'is_company',
    'is_individual',
    'legacy_data_source_name',
    'legacy_id'
];
$note_fields = [
    'contact_id',
    'note_text'
];
$address_fields = [
    'contact_id',
    'street_number',
    'pre_direction',
    'street_name',
    'street_type',
    'post_direction',
    'unit_suite_number',
    'country_type'
];

$columns = implode(',', $contact_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $contact_fields));
$insert_contact  = $energov->prepare("insert into contact ($columns) values($params)");

$columns = implode(',', $note_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $note_fields));
$insert_note     = $energov->prepare("insert into contact_note ($columns) values($params)");

$columns = implode(',', $address_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $address_fields));
$insert_address = $energov->prepare("insert into contact_address ($columns) values($params)");


$select  = "select n.name_num   as legacy_id,
                   n.name       as first_name,
                   n.email      as email,
                   n.phone_work as business_phone,
                   n.phone_home as home_phone,
                   'rentpro'    as legacy_data_source_name,
                   1            as isactive,
                   0            as is_company,
                   0            as is_individual,
                   n.notes      as note_text,
                   n.address,
                   n.city,
                   n.state,
                   n.zip
            from rental.name n";
$result  = $rental->query($select);
foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    echo "$row[legacy_id]\n";
    $data = [];
    foreach ($contact_fields as $f) { $data[$f] = $row[$f]; }
    $insert_contact->execute($data);
    $contact_id = $energov->lastInsertId();

    if ($row['note_text']) {
        $data = [
            'contact_id' => $contact_id,
            'note_text'  => $row['note_text']
        ];
        print_r($data);
        $insert_note->execute($data);
    }

    if ($row['address']) {
        $a = MasterAddress::parseAddress($row['address']);
        $data = [
            'contact_id'        => $contact_id,
            'street_number'     => MasterAddress::streetNumber($a),
            'pre_direction'     => $a['direction'    ] ?? '',
            'street_name'       => $a['street_name'  ] ?? '',
            'street_type'       => $a['streetType'   ] ?? '',
            'post_direction'    => $a['postDirection'] ?? '',
            'unit_suite_number' => MasterAddress::subunit($a),
            'country_type'      => 'unknown'
        ];
        $insert_address->execute($data);
    }
}
