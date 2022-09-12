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

$parent_fields = [
    'permit_number',
    'parent_permit_number'
];

// $fee_fields = [
//     'permit_fee_id',
//     'permit_number',
//     'fee_amount',
//     'fee_date'
// ];

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

$columns = implode(',', $parent_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $parent_fields));
$insert_parent = $DCT->prepare("insert into permit_parent_permit ($columns) values($params)");

// $columns = implode(',', $fee_fields);
// $params  = implode(',', array_map(fn($f): string => ":$f", $fee_fields));
// $insert_fee = $DCT->prepare("insert into permit_fee ($columns) values($params)");

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
              pc.company_id as permit_company_id,
              bc.company_id,
              pc.contact_id as permit_contact_id,
              bc.contact_id,
               b.bond_num,
               b.bond_company_id
        from      excavpermits      p
        left join inspectors        i on  p.reviewer_id=i.user_id
             join company_contacts pc on pc.id=p.company_contact_id
        left join bonds             b on  b.id=p.bond_id and b.amount>1
             join company_contacts bc on bc.id=b.company_contact_id";
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

    $permit_number = $row['permit_num'];
    $permit_status = DATASOURCE_ROW."_$row[status]";

    $insert_permit->execute([
        'permit_type'             => 'Excavation',
        'permit_number'           => $permit_number,
        'permit_sub_type'         => $row['permit_type'],
        'permit_status'           => $permit_status,
        'permit_description'      => $row['project'    ],
        'apply_date'              => $apply_date,
        'issue_date'              => $issue_date,
        'assigned_to'             => "$row[first_name] $row[last_name]",
        'legacy_data_source_name' => DATASOURCE_ROW
    ]);

    if ($row['bond_id']) {
        // The bond permit should already exist for this bond_id
        // The company and contact should be pulled from the Bond's company_contact_id
        $company       = $row['company_id'] ? DATASOURCE_ROW.'_companies_'.$row['company_id'] : null;
        $contact       = $row['contact_id'] ? DATASOURCE_ROW.'_contacts_' .$row['contact_id'] : null;
        $principal     = $company ?? $contact;
        $bond_permit   = $company
                        ? DATASOURCE_ROW.'_company_bond_'.$row['company_id']
                        : DATASOURCE_ROW.'_contact_bond_'.$row['contact_id'];
        $insert_parent->execute([
            'permit_number'        => $bond_permit,
            'parent_permit_number' => $permit_number
        ]);
    }

    if ($row['permit_company_id']) {
        $insert_contact->execute([
            'permit_number'           => $permit_number,
            'contact_id'              => DATASOURCE_ROW."_companies_$row[permit_company_id]",
            'contact_type'            => 'company',
            'primary_billing_contact' => 1
        ]);
    }
    if ($row['permit_contact_id']) {
        $insert_contact->execute([
            'permit_number'           => $permit_number,
            'contact_id'              => DATASOURCE_ROW."_contacts_$row[permit_contact_id]",
            'contact_type'            => 'contact',
            'primary_billing_contact' => 0
        ]);
    }
}
echo "\n";
