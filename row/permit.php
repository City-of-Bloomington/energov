<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $ROW  PDO connection to row database
 * @param $DCT  PDO connection to DCT database
 */
declare (strict_types=1);
$permit_fields = [
    'permit_number',
    'permit_type',
    'permit_sub_type',
    'permit_status',
    'permit_description',
    'apply_date',
    'issue_date',
    'assigned_to',
    'legacy_data_source_name'
];

$fee_fields = [
    'permit_fee_id',
    'permit_number',
    'fee_amount',
    'fee_date'
];

$bond_fields = [
    'permit_number',
    'bond_id'
];

$contact_fields = [
    'permit_number',
    'contact_id',
    'contact_type',
    'primary_billing_contact'
];

$columns = implode(',', $permit_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $permit_fields));
$insert_permit = $DCT->prepare("insert into permit ($columns) values($params)");

$columns = implode(',', $fee_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fee_fields));
$insert_fee = $DCT->prepare("insert into permit_fee ($columns) values($params)");

$columns = implode(',', $bond_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $bond_fields));
$insert_bond = $DCT->prepare("insert into permit_bond ($columns) values($params)");

$columns = implode(',', $contact_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $contact_fields));
$insert_contact = $DCT->prepare("insert into permit_contact ($columns) values($params)");

$sql = "select p.id,
               p.permit_num,
               p.permit_type,
               p.status,
               p.date,
               p.project,
               p.start_date,
               i.first_name,
               i.last_name,
               p.fee,
               p.bond_id,
               c.company_id,
               c.contact_id,
               b.bond_num,
               b.bond_company_id
        from row.excavpermits      p
        left join inspectors       i on p.reviewer_id=i.user_id
        left join company_contacts c on c.id=p.company_contact_id
        left join bonds            b on b.id=p.bond_id";
$query  = $ROW->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrow/permit: $percent% $row[id]";

    $apply_date = $row['date'      ] ?? $row['start_date'];
    $issue_date = $row['start_date'] ?? $row['date'      ];
    if ($issue_date < $apply_date) { $apply_date = $issue_date; }

    $insert_permit->execute([
        'permit_type'             => 'Excavation',
        'permit_number'           => $row['permit_num' ],
        'permit_sub_type'         => $row['permit_type'],
        'permit_status'           => $row['status'     ],
        'permit_description'      => $row['project'    ],
        'apply_date'              => $apply_date,
        'issue_date'              => $issue_date,
        'assigned_to'             => "$row[first_name] $row[last_name]",
        'legacy_data_source_name' => DATASOURCE_ROW
    ]);

    $fee_id     = DATASOURCE_ROW."_$row[id]";
    $fee_amount = (float)$row['fee'];
    if ($fee_amount > 0) {
        $insert_fee->execute([
            'permit_fee_id' => $fee_id,
            'permit_number' => $row['permit_num'],
            'fee_amount'    => $fee_amount,
            'fee_date'      => $row['date']
        ]);
    }

    if ((int)$row['bond_id'] > 0
          && $row['bond_num']
          && $row['bond_company_id']
          && $row['bond_company_id'] != '-1') {
        $insert_bond->execute([
            'permit_number' => $row['permit_num'],
            'bond_id'       => DATASOURCE_ROW."_$row[bond_id]"
        ]);
    }

    if ($row['company_id'] && $row['contact_id']) {
        $insert_contact->execute([
            'permit_number'           => $row['permit_num'],
            'contact_id'              => DATASOURCE_ROW."_companies_$row[company_id]",
            'contact_type'            => 'company',
            'primary_billing_contact' => 1
        ]);

        $insert_contact->execute([
            'permit_number'           => $row['permit_num'],
            'contact_id'              => DATASOURCE_ROW."_contacts_$row[contact_id]",
            'contact_type'            => 'contact',
            'primary_billing_contact' => 0
        ]);
    }
}
echo "\n";
