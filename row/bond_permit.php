<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
$permit_fields = [
    'permit_number',
    'permit_type',
    'permit_status',
    'apply_date',
    'expire_date',
    'legacy_data_source_name'
];
$contact_fields = [
    'permit_number',
    'contact_id',
    'contact_type'
];

$columns = implode(',', $permit_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $permit_fields));
$insert_permit = $DCT->prepare("insert into permit ($columns) values($params)");

$columns = implode(',', $contact_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $contact_fields));
$insert_contact = $DCT->prepare("insert into permit_contact ($columns) values($params)");

#-------------------------------
# Companies with bonds
#-------------------------------
$sql    = "select c.company_id,
                  min(p.date)        as issued,
                  max(b.expire_date) as expires,
                  case when max(b.expire_date)<now() then 'Expired' else 'Issued' end as status
           from company_contacts  c
                join bonds        b on c.id=b.company_contact_id
           left join excavpermits p on b.id=p.bond_id
           where c.company_id is not null
           group by c.company_id";
$query  = $ROW->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrow/bond_permit companies: $percent% $row[company_id]";

    $company       = DATASOURCE_ROW.'_companies_'   .$row['company_id'];
    $permit_number = DATASOURCE_ROW.'_company_bond_'.$row['company_id'];

    $insert_permit->execute([
        'permit_number' => $permit_number,
        'permit_type'   => 'Bond Permit',
        'permit_status' => $row['status'     ],
        'apply_date'    => $row['issued' ] ?? '2000-01-01',
        'expire_date'   => $row['expires'],
        'legacy_data_source_name' => DATASOURCE_ROW
    ]);

    $insert_contact->execute([
        'permit_number' => $permit_number,
        'contact_id'    => $company,
        'contact_type'  => 'Applicant'
    ]);
}
echo "\n";

#----------------------------------------------
# Contractors not part of companies
#----------------------------------------------
$sql = "select c.contact_id,
               min(p.date)        as issued,
               max(b.expire_date) as expires,
               case when max(b.expire_date)<now() then 'Expired' else 'Issued' end as status
        from company_contacts  c
             join bonds        b on c.id=b.company_contact_id
        left join excavpermits p on b.id=p.bond_id
        where c.company_id is null
        group by c.contact_id";
$query  = $ROW->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrow/bond_permit contacts: $percent% $row[contact_id]";
    $contact       = DATASOURCE_ROW.'_contacts_'    .$row['contact_id'];
    $permit_number = DATASOURCE_ROW.'_contact_bond_'.$row['contact_id'];

    $insert_permit->execute([
        'permit_number' => $permit_number,
        'permit_type'   => 'Bond Permit',
        'permit_status' => $row['status'     ],
        'apply_date'    => $row['issued' ] ?? '2000-01-01',
        'expire_date'   => $row['expires'],
        'legacy_data_source_name' => DATASOURCE_ROW
    ]);

    $insert_contact->execute([
        'permit_number' => $permit_number,
        'contact_id'    => $contact,
        'contact_type'  => 'Applicant'
    ]);
}
echo "\n";
