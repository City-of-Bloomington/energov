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
    'pre_direction',
    'street_name',
    'street_type',
    'unit_suite_number',
    'address_line_3',
    'po_box',
    'city',
    'state_code',
    'zip',
    'country_type'
];

$case_fields = [
    'case_number',
    'contact_id',
    'contact_type',
    'primary_billing_contact'
];

$columns = implode(',', $contact_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $contact_fields));
$insert_contact  = $DCT->prepare("insert into contact ($columns) values($params)");

$columns = implode(',', $address_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $address_fields));
$insert_address = $DCT->prepare("insert into contact_address ($columns) values($params)");

$columns = implode(',', $case_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $case_fields));
$insert_case = $DCT->prepare("insert into code_case_contact ($columns) values($params)");

$sql    = "select c.id as cite_id, a.*
           from citations       c
           join citation_agents l on c.id=l.cite_id
           join agents          a on a.id=l.agent_id";
$query  = $CITATION->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rcitation/contact agents: $percent% $row[id]";

    $contact_id  = DATASOURCE_CITATION."_agent_$row[id]";
    $case_number = DATASOURCE_CITATION."_$row[cite_id]";

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
        'street_number'     => $row['street_num' ],
        'pre_direction'     => $row['street_dir' ],
        'street_name'       => $row['street_name'],
        'street_type'       => $row['street_type'],
        'unit_suite_number' => "$row[sud_num] $row[sud_type]",
        'address_line_3'    => !empty($row['rr'   ]) ? "RR $row[rr]"        : null,
        'po_box'            => !empty($row['pobox']) ? "PO BOX $row[pobox]" : null,
        'city'              => $row['city' ],
        'state_code'        => $row['state'],
        'zip'               => $row['zip'  ],
        'country_type'      => COUNTRY_TYPE,
    ]);

    $insert_case->execute([
        'case_number'             => $case_number,
        'contact_id'              => $contact_id,
        'contact_type'            => 'agent',
        'primary_billing_contact' => 0
    ]);
}
echo "\n";

$sql    = "select c.id as cite_id, o.*
           from citations       c
           join citation_owners l on c.id=l.cite_id
           join owners          o on o.id=l.owner_id";
$query  = $CITATION->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rcitation/contact owners: $percent% $row[id]";

    $contact_id  = DATASOURCE_CITATION."_owner_$row[id]";
    $case_number = DATASOURCE_CITATION."_$row[cite_id]";

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
        'street_number'     => $row['street_num' ],
        'pre_direction'     => $row['street_dir' ],
        'street_name'       => $row['street_name'],
        'street_type'       => $row['street_type'],
        'unit_suite_number' => "$row[sud_num] $row[sud_type]",
        'address_line_3'    => !empty($row['rr'   ]) ? "RR $row[rr]"        : null,
        'po_box'            => !empty($row['pobox']) ? "PO BOX $row[pobox]" : null,
        'city'              => $row['city' ],
        'state_code'        => $row['state'],
        'zip'               => $row['zip'  ],
        'country_type'      => COUNTRY_TYPE,
    ]);

    $insert_case->execute([
        'case_number'             => $case_number,
        'contact_id'              => $contact_id,
        'contact_type'            => 'owner',
        'primary_billing_contact' => 1
    ]);
}
echo "\n";
