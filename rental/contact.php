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
    'mobile_phone',
    'other_phone',
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

$phones  = $RENTAL->prepare('select * from rental.owner_phones where name_num=?');
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
    $phones->execute([$row['name_num']]);
    $p = mapPhoneNumbers($phones->fetchAll(\PDO::FETCH_ASSOC));

    $insert_contact->execute([
        'contact_id'              => $contact_id,
        'first_name'              => $row['name'      ],
        'email'                   => $row['email'     ],
        'business_phone'          => $p['business_phone'] ?? null,
        'home_phone'              => $p['home_phone'    ] ?? null,
        'mobile_phone'            => $p['mobile_phone'  ] ?? null,
        'other_phone'             => $p['other_phone'   ] ?? null,
        'isactive'                => 1,
        'is_company'              => 0,
        'is_individual'           => 1,
        'legacy_data_source_name' => DATASOURCE_RENTAL
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
            'street_number'     => $row['address'],
            'city'              => $row['city' ],
            'state_code'        => $row['state'],
            'zip'               => $row['zip'  ],
            'country_type'      => COUNTRY_TYPE
        ];
        $insert_address->execute($data);
    }
}
echo "\n";


/**
 *  Map rental phone numbers to EnerGov phone numbers
 *
 * Energov only has four phone numbers allowed: business, home, mobile, and other
 * Rental allows for infinite numbers of four different types.  However, there
 * are only 138 people with more than one number for a given label.  Of those,
 * only two people have more than two numbers for a given label.
 *
 * This function finds an unused label for the owner's second phone number for
 * a given label.  More than two numbers for a given label with be lost.
 * This should be okay, as there are only two records this affects.
 */
function mapPhoneNumbers(array $rental_phones): array
{
    $map = [
        'Work'      => 'business_phone',
        'Home'      => 'home_phone',
        'Cell'      => 'mobile_phone',
        'Emergency' => 'other_phone'
    ];
    $fallback_order = ['Emergency', 'Home', 'Work', 'Cell'];

    $out = [];
    foreach ($rental_phones as $p) { $out[$p['type']][] = $p['phone_num']; }
    foreach ($out as $type=>$numbers) {
        if (count($numbers) > 1) {
            foreach ($fallback_order as $f) {
                if (empty($out[$f])) { $out[$f][] = $numbers[1]; }
            }
        }
    }
    $data = [];
    foreach ($out as $type=>$numbers) { $data[$map[$type]] = $numbers[0]; }
    return $data;
}
