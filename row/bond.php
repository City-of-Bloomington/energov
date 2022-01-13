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
$contact      = $DCT->prepare('select contact_id from contact where legacy_id=? and legacy_data_source_name=?');

$sql    = "select b.id,
                  b.bond_num,
                  b.type,
                  case when b.expire_date<now() then 'expired' else 'not expired yet' end as status,
                  b.expire_date,
                  b.amount,
                  c.company_id,
                  c.contact_id,
                  b.bond_company_id,
                  b.notes,
                  b.description
           from row.bonds b
           left join company_contacts c on b.company_contact_id=c.id
           where bond_num        is not null
             and bond_company_id is not null";
$query  = $ROW->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrow/bond: $percent% $row[id] => ";

    $company_id      = null;
    $person_id       = null;
    $bond_company_id = null;

    if ($row['company_id']) {
        $contact->execute([$row['company_id'], 'row_companies']);
        $company_id = $contact->fetchColumn();
        if (!$company_id) {
            echo "failed company lookup $row[company_id]\n";
            exit();
        }
    }
    if ($row['contact_id']) {
        $contact->execute([$row['contact_id'], 'row_contacts']);
        $person_id = $contact->fetchColumn();
        if (!$person_id) {
            echo "failed person lookup $row[contact_id]\n";
            exit();
        }
    }
    if ($row['bond_company_id']) {
        $contact->execute([$row['bond_company_id'], 'row_bond_companies']);
        $bond_company_id = $contact->fetchColumn();
        if (!$bond_company_id) {
            echo "failed bond_company lookup $row[bond_company_id]\n";
            exit();
        }
    }

    $insert_bond->execute([
        'bond_id'              => $row['id'             ],
        'bond_number'          => $row['bond_num'       ],
        'bond_type'            => $row['type'           ],
        'bond_status'          => $row['status'         ],
        'expire_date'          => $row['expire_date'    ],
        'amount'               => $row['amount'         ],
        'obligee_contact_id'   => $company_id,
        'principal_contact_id' => $person_id,
        'surety_contact_id'    => $bond_company_id,
    ]);
    $bond_id = $DCT->lastInsertId();

    if ($row['description']) {
        $insert_note->execute([
            'bond_id'   => $row['id'],
            'note_text' => $row['description']
        ]);
    }
    if ($row['notes']) {
        $insert_note->execute([
            'bond_id'   => $row['id'],
            'note_text' => $row['notes']
        ]);
    }
}
echo "\n";
