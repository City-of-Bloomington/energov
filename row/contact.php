<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $ROW  PDO connection to row database
 * @param $DCT  PDO connection to DCT database
 */
declare (strict_types=1);
$contact_fields = [
    'contact_id',
    'company_name',
    'first_name',
    'last_name',
    'isactive',
    'is_company',
    'is_individual',
    'email',
    'website',
    'business_phone',
    'mobile_phone',
    'fax',
    'legacy_data_source_name'
];
$note_fields = [
    'contact_id',
    'note_text'
];
$address_fields = [
    'contact_id',
    'address_line_3',
    'city',
    'state_code',
    'zip',
    'country_type'
];
$columns = implode(',', $contact_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $contact_fields));
$insert_contact  = $DCT->prepare("insert into contact ($columns) values($params)");

$columns = implode(',', $note_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $note_fields));
$insert_note     = $DCT->prepare("insert into contact_note ($columns) values($params)");

$columns = implode(',', $address_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $address_fields));
$insert_address = $DCT->prepare("insert into contact_address ($columns) values($params)");

$query  = $ROW->query('select * from companies');
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrow/contact companies: $percent% $row[id]";

    $data_source = DATASOURCE_ROW."_companies";
    $contact_id  = "{$data_source}_{$row['id']}";

    $insert_contact->execute([
        'contact_id'     => $contact_id,
        'company_name'   => $row['name'   ],
        'first_name'     => null,
        'last_name'      => null,
        'email'          => null,
        'website'        => $row['website'],
        'business_phone' => $row['phone'  ],
        'mobile_phone'   => null,
        'fax'            => null,
        'isactive'       => 1,
        'is_company'     => 1,
        'is_individual'  => 0,
        'legacy_data_source_name' => $data_source
    ]);

    if ($row['notes']) {
        $insert_note->execute([
            'contact_id' => $contact_id,
            'note_text'  => $row['notes']
        ]);
    }
    if ($row['address']) {
        $data = [
            'contact_id'        => $contact_id,
            'address_line_3'    => $row['address'],
            'city'              => $row['city' ],
            'state_code'        => $row['state'],
            'zip'               => $row['zip'  ],
            'country_type'      => COUNTRY_TYPE
        ];
        $insert_address->execute($data);
    }
}
echo "\n";

$query  = $ROW->query('select * from contacts');
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrow/contact contacts: $percent% $row[id]";

    $data_source = DATASOURCE_ROW.'_contacts';
    $contact_id  = "{$data_source}_{$row['id']}";

    $insert_contact->execute([
        'contact_id'     => $contact_id,
        'company_name'   => null,
        'first_name'     => $row['fname'],
        'last_name'      => $row['lname'],
        'email'          => $row['email'],
        'website'        => null,
        'business_phone' => $row['work_phone'],
        'mobile_phone'   => $row['cell_phone'],
        'fax'            => $row['fax'],
        'isactive'       => 1,
        'is_company'     => 0,
        'is_individual'  => 1,
        'legacy_data_source_name' => $data_source
    ]);

    if ($row['notes']) {
        $insert_note->execute([
            'contact_id' => $contact_id,
            'note_text'  => $row['notes']
        ]);
    }
    if ($row['address']) {
        $data = [
            'contact_id'        => $contact_id,
            'address_line_3'    => $row['address'],
            'city'              => $row['city' ],
            'state_code'        => $row['state'],
            'zip'               => $row['zip'  ],
            'country_type'      => COUNTRY_TYPE
        ];
        $insert_address->execute($data);
    }
}
echo "\n";

$query  = $ROW->query('select * from bond_companies');
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrow/contact bond_companies: $percent% $row[id]";

    $data_source = DATASOURCE_ROW.'_bond_companies';
    $contact_id  = "{$data_source}_{$row['id']}";

    $insert_contact->execute([
        'contact_id'     => $contact_id,
        'company_name'   => $row['name'],
        'first_name'     => null,
        'last_name'      => null,
        'email'          => null,
        'website'        => null,
        'business_phone' => null,
        'mobile_phone'   => null,
        'fax'            => null,
        'isactive'       => 1,
        'is_company'     => 1,
        'is_individual'  => 0,
        'legacy_data_source_name' => $data_source
    ]);
}
echo "\n";
