<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $ROW  PDO connection to row database
 * @param $DCT  PDO connection to DCT database
 */
declare (strict_types=1);
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

$note_fields = [
    'bond_id',
    'note_text'
];

$columns = implode(',', $bond_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $bond_fields));
$insert_bond  = $DCT->prepare("insert into bond ($columns) values($params)");

$columns = implode(',', $note_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $note_fields));
$insert_note  = $DCT->prepare("insert into bond_note ($columns) values($params)");

$sql    = "select b.id,
                  b.bond_num,
                  b.type,
                  case when b.expire_date<now() then 'expired' else 'not expired yet' end as status,
                  b.expire_date,
                  b.amount,
                  c.company_id,
                  c.contact_id,
                  bc.id as bond_company_id,
                  b.notes,
                  b.description
           from row.bonds b
           left join bond_companies  bc on bc.id=b.bond_company_id
           left join company_contacts c on  c.id=b.company_contact_id
           where bond_num        is not null
             and bond_company_id is not null";
$query  = $ROW->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrow/bond: $percent% $row[id]";

    $bond_id         = DATASOURCE_ROW."_$row[id]";
    $company_id      = null;
    $person_id       = null;
    $bond_company_id = null;

    if ($row['company_id']) {
        $company_id = DATASOURCE_ROW."_companies_$row[company_id]";
    }
    if ($row['contact_id']) {
        $person_id = DATASOURCE_ROW."_contacts_$row[contact_id]";
    }
    if ($row['bond_company_id']) {
        $bond_company_id = DATASOURCE_ROW."_bond_companies_$row[bond_company_id]";
    }

    if (!$company_id)      { $company_id      = $bond_company_id ?? $person_id; }
    if (!$person_id )      { $person_id       = $company_id      ?? $bond_company_id; }
    if (!$bond_company_id) { $bond_company_id = $company_id      ?? $person_id; }

    $insert_bond->execute([
        'bond_id'              => $bond_id,
        'bond_number'          => $row['bond_num'   ],
        'bond_type'            => $row['type'       ],
        'bond_status'          => $row['status'     ],
        'expire_date'          => $row['expire_date'],
        'amount'               => $row['amount'     ],
        'obligee_contact_id'   => $company_id,
        'principal_contact_id' => $person_id,
        'surety_contact_id'    => $bond_company_id,
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
