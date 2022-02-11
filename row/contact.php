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
    'street_number',
    'pre_direction',
    'street_name',
    'street_type',
    'post_direction',
    'unit_suite_number',
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

$sql    = "select distinct n.*
           from excavpermits     p
           join company_contacts c on c.id=p.company_contact_id
           join companies        n on n.id=c.company_id";
$query  = $ROW->query($sql);
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
        $a = MasterAddress::parseAddress($row['address']);
        $data = [
            'contact_id'        => $contact_id,
            'street_number'     => MasterAddress::streetNumber($a),
            'pre_direction'     => $a['direction'    ] ?? '',
            'street_name'       => $a['street_name'  ] ?? '',
            'street_type'       => $a['streetType'   ] ?? '',
            'post_direction'    => $a['postDirection'] ?? '',
            'unit_suite_number' => MasterAddress::subunit($a),
            'city'              => $row['city' ],
            'state_code'        => $row['state'],
            'zip'               => $row['zip'  ],
            'country_type'      => COUNTRY_TYPE
        ];
        $insert_address->execute($data);
    }
}
echo "\n";

$sql    = "select distinct n.*
           from excavpermits     p
           join company_contacts c on c.id=p.company_contact_id
           join contacts         n on n.id=c.contact_id";
$query  = $ROW->query($sql);
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
        $a = MasterAddress::parseAddress($row['address']);
        $data = [
            'contact_id'        => $contact_id,
            'street_number'     => MasterAddress::streetNumber($a),
            'pre_direction'     => $a['direction'    ] ?? '',
            'street_name'       => $a['street_name'  ] ?? '',
            'street_type'       => $a['streetType'   ] ?? '',
            'post_direction'    => $a['postDirection'] ?? '',
            'unit_suite_number' => MasterAddress::subunit($a),
            'city'              => $row['city' ],
            'state_code'        => $row['state'],
            'zip'               => $row['zip'  ],
            'country_type'      => COUNTRY_TYPE
        ];
        $insert_address->execute($data);
    }
}
echo "\n";

$sql    = "select distinct n.*
           from excavpermits   p
           join bonds          b on b.id=p.bond_id
           join bond_companies n on n.id=b.bond_company_id";
$query  = $ROW->query($sql);
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
