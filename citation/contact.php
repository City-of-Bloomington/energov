<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $CITATION  PDO connection to row database
 * @param $DCT       PDO connection to DCT database
 */
declare (strict_types=1);
$contact_fields = [
    'contact_id',
    'first_name',
    'last_name',
    'isactive',
    'is_company',
    'is_individual',
    'legacy_data_source_name'
];

$address_fields = [
    'contact_id',
    'street_number',
    'address_line_3',
    'po_box',
    'city',
    'state_code',
    'zip',
    'country_type'
];

$columns = implode(',', $contact_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $contact_fields));
$insert_contact  = $DCT->prepare("insert into contact ($columns) values($params)");

$columns = implode(',', $address_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $address_fields));
$insert_address = $DCT->prepare("insert into contact_address ($columns) values($params)");

$sql    = "select distinct a.id,
                           a.fname,
                           a.lname,
                           concat_ws(' ', a.street_num,
                                          a.street_dir,
                                          a.street_name,
                                          a.street_type,
                                          a.sud_type,
                                          a.sud_num) as address,
                           a.city,
                           a.state,
                           a.zip,
                           a.pobox,
                           a.rr
           from agents a
           join citation_agents c on a.id=c.agent_id;";
$query  = $CITATION->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rcitation/contact agents: $percent% $row[id]";

    $contact_id  = DATASOURCE_CITATION."_agent_$row[id]";

    $insert_contact->execute([
        'contact_id'    => $contact_id,
        'first_name'    => $row['fname'],
        'last_name'     => $row['lname'],
        'isactive'      => 0,
        'is_company'    => 0,
        'is_individual' => 1,
        'legacy_data_source_name' => DATASOURCE_CITATION
    ]);

    $insert_address->execute([
        'contact_id'        => $contact_id,
        'street_number'     => $row['address'],
        'address_line_3'    => !empty($row['rr'   ]) ? "RR $row[rr]"        : null,
        'po_box'            => !empty($row['pobox']) ? "PO BOX $row[pobox]" : null,
        'city'              => $row['city' ],
        'state_code'        => $row['state'],
        'zip'               => $row['zip'  ],
        'country_type'      => COUNTRY_TYPE,
    ]);
}
echo "\n";

$sql    = "select distinct o.id,
                           o.fname,
                           o.lname,
                           concat_ws(' ', o.street_num,
                                          o.street_dir,
                                          o.street_name,
                                          o.street_type,
                                          o.sud_type,
                                          o.sud_num) as address,
                           o.city,
                           o.state,
                           o.zip,
                           o.pobox,
                           o.rr
           from owners o
           join citation_owners c on o.id=c.owner_id";
$query  = $CITATION->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rcitation/contact owners: $percent% $row[id]";

    $contact_id  = DATASOURCE_CITATION."_owner_$row[id]";

    $insert_contact->execute([
        'contact_id'    => $contact_id,
        'first_name'    => $row['fname'],
        'last_name'     => $row['lname'],
        'isactive'      => 0,
        'is_company'    => 0,
        'is_individual' => 1,
        'legacy_data_source_name' => DATASOURCE_CITATION
    ]);

    $insert_address->execute([
        'contact_id'        => $contact_id,
        'street_number'     => $row['address'],
        'address_line_3'    => !empty($row['rr'   ]) ? "RR $row[rr]"        : null,
        'po_box'            => !empty($row['pobox']) ? "PO BOX $row[pobox]" : null,
        'city'              => $row['city' ],
        'state_code'        => $row['state'],
        'zip'               => $row['zip'  ],
        'country_type'      => COUNTRY_TYPE,
    ]);
}
echo "\n";
