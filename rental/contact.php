<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $RENTAL PDO connection to rental database
 * @param $DCT    PDO connection to DCT database
 */
declare (strict_types=1);
$contact_fields = [
    'contact_id',
    'first_name',
    'email',
    'business_phone',
    'home_phone',
    'isactive',
    'is_company',
    'is_individual',
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

$select  = "select  distinct n.*
            from rental.registr r
            join rental.name    n on r.agent=n.name_num
            left join (
                select p.rental_id, min(p.pull_date) as earliest_pull
                from rental.pull_history p
                group by p.rental_id
            ) pulls on pulls.rental_id=r.id
            where (r.registered_date is not null or earliest_pull is not null)
                and agent>0

            union

            select  distinct n.*
            from rental.registr    r
            join rental.regid_name l on r.id=l.id
            join rental.name       n on l.name_num=n.name_num
            left join (
                select p.rental_id, min(p.pull_date) as earliest_pull
                from rental.pull_history p
                group by p.rental_id
            ) pulls on pulls.rental_id=r.id
            where (r.registered_date is not null or earliest_pull is not null)";
$query   = $RENTAL->query($select);
$result  = $query->fetchAll(\PDO::FETCH_ASSOC);
$total   = count($result);
$c       = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/contact: $percent% $row[name_num]";

    $contact_id = DATASOURCE_RENTAL."_$row[name_num]";

    $insert_contact->execute([
        'contact_id'              => $contact_id,
        'first_name'              => $row['name'      ],
        'email'                   => $row['email'     ],
        'business_phone'          => $row['phone_work'],
        'home_phone'              => $row['phone_home'],
        'isactive'                => 1,
        'is_company'              => 0,
        'is_individual'           => 1,
        'legacy_data_source_name' => DATASOURCE_RENTAL,
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
