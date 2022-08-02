<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $ROW  PDO connection to row database
 * @param $DCT  PDO connection to DCT database
 */
declare (strict_types=1);
define('CITY_OF_BLOOMINGTON', DATASOURCE_ROW.'_bond_companies_234');

$bond_fields = [
    'bond_id',
    'bond_number',
    'bond_type',
    'bond_status',
    'expire_date',
    'amount',
    'obligee_contact_id',
    'principal_contact_id',
    'surety_contact_id'
];

$permit_fields = [
    'permit_number',
    'bond_id'
];

$note_fields = [
    'bond_id',
    'note_text'
];

$columns = implode(',', $bond_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $bond_fields));
$insert_bond  = $DCT->prepare("insert into bond ($columns) values($params)");

$columns = implode(',', $permit_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $permit_fields));
$insert_permit = $DCT->prepare("insert into permit_bond ($columns) values($params)");

$columns = implode(',', $note_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $note_fields));
$insert_note  = $DCT->prepare("insert into bond_note ($columns) values($params)");

$sql    = "select b.id,
                  b.bond_num,
                  b.type,
                  case when b.expire_date<now() then 'expired' else 'active' end as status,
                  b.expire_date,
                  b.amount,
                  c.company_id,
                  c.contact_id,
                  bc.id as bond_company_id,
                  b.notes,
                  b.description
           from row.bonds b
           join bond_companies  bc on bc.id=b.bond_company_id
           join company_contacts c on  c.id=b.company_contact_id
           where bond_num is not null";
$query  = $ROW->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrow/bond: $percent% $row[id]";

    $bond_id       = DATASOURCE_ROW."_bond_$row[id]";
    $company       = $row['company_id'     ] ? DATASOURCE_ROW.'_companies_'     .$row['company_id'     ] : null;
    $contact       = $row['contact_id'     ] ? DATASOURCE_ROW.'_contacts_'      .$row['contact_id'     ] : null;
    $bond_company  = $row['bond_company_id'] ? DATASOURCE_ROW.'_bond_companies_'.$row['bond_company_id'] : null;
    $principal     = $company ?? $contact;
    $permit_number = $company
                     ? DATASOURCE_ROW.'_company_bond_'.$row['company_id']
                     : DATASOURCE_ROW.'_contact_bond_'.$row['contact_id'];

    $insert_bond->execute([
        'bond_id'              => $bond_id,
        'bond_number'          => $row['bond_num'   ],
        'bond_type'            => 'Bond',
        'bond_status'          => $row['status'     ],
        'expire_date'          => $row['expire_date'],
        'amount'               => $row['amount'     ],
        'obligee_contact_id'   => CITY_OF_BLOOMINGTON,
        'principal_contact_id' => $principal,
        'surety_contact_id'    => $bond_company,
    ]);

    $insert_permit->execute([
        'permit_number' => $permit_number,
        'bond_id'       => $bond_id
    ]);

    if ($row['description']) {
        $insert_note->execute([
            'bond_id'   => $bond_id,
            'note_text' => $row['description']
        ]);
    }
    if ($row['notes']) {
        $insert_note->execute([
            'bond_id'   => $bond_id,
            'note_text' => $row['notes']
        ]);
    }
}
echo "\n";


$sql    = "select i.id,
                  i.policy_num,
                  case when i.expire_date<now() then 'expired' else 'active' end as status,
                  i.expire_date,
                  i.amount,
                  c.company_id,
                  c.contact_id,
                 bc.id as bond_company_id,
                  i.notes
           from insurances       i
           join bond_companies  bc on bc.id=i.insurance_company_id
           join company_contacts c on  c.id=i.company_contact_id
           where i.policy_num is not null
             and i.id not in (73, 74, 75)"; // Expired insurance policies for companies with no bonds or permits
$query  = $ROW->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrow/insurance: $percent% $row[id]";

    $bond_id       = DATASOURCE_ROW."_insurance_$row[id]";
    $company       = $row['company_id'     ] ? DATASOURCE_ROW.'_companies_'     .$row['company_id'     ] : null;
    $contact       = $row['contact_id'     ] ? DATASOURCE_ROW.'_contacts_'      .$row['contact_id'     ] : null;
    $bond_company  = $row['bond_company_id'] ? DATASOURCE_ROW.'_bond_companies_'.$row['bond_company_id'] : null;
    $principal     = $company ?? $contact;
    $permit_number = $company
                     ? DATASOURCE_ROW.'_company_bond_'.$row['company_id']
                     : DATASOURCE_ROW.'_contact_bond_'.$row['contact_id'];
    $insert_bond->execute([
        'bond_id'              => $bond_id,
        'bond_number'          => $row['policy_num' ],
        'bond_type'            => 'Insurance',
        'bond_status'          => $row['status'     ],
        'expire_date'          => $row['expire_date'],
        'amount'               => $row['amount'     ],
        'obligee_contact_id'   => CITY_OF_BLOOMINGTON,
        'principal_contact_id' => $principal,
        'surety_contact_id'    => $bond_company,
    ]);

    $insert_permit->execute([
        'permit_number' => $permit_number,
        'bond_id'       => $bond_id
    ]);

    if ($row['notes']) {
        $insert_note->execute([
            'bond_id'   => $bond_id,
            'note_text' => $row['notes']
        ]);
    }
}
echo "\n";
