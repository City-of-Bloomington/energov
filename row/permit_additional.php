<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $ROW  PDO connection to row database
 * @param $DCT  PDO connection to DCT database
 */
declare (strict_types=1);
$additional_fields = [
    'permit_number',
    'CutType',
    'CutDescription',
    'Utility'
];
$custom_fields = [
    'permit_number',
    'CutLength',
    'CutWidth',
    'CutDepth'
];

$columns = implode(',', $additional_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $additional_fields));
$insert_additional = $DCT->prepare("insert into permit_additional_fields ($columns) values($params)");

$columns = implode(',', $custom_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $custom_fields));
$insert_custom = $DCT->prepare("insert into PERMIT_TABLE_custom_fields ($columns) values($params)");

$sql = "select e.id,
               e.permit_num,
               e.cut_type,
               e.cut_description,
               u.name
        from      excavpermits      p
        join excavcuts              e on e.id=(select min(id) from excavcuts where permit_num=p.permit_num)
        left join utility_types     u on u.id=e.utility_type_id
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
    echo chr(27)."[2K\rrow/permit_additional: $percent% $row[id]";

    $insert_additional->execute([
        'permit_number'  => $row['permit_num'     ],
        'CutType'        => $row['cut_type'       ],
        'CutDescription' => $row['cut_description'],
        'Utility'        => $row['name'           ],
    ]);
}
echo "\n";

$sql = "select e.id,
               e.permit_num,
               e.depth,
               e.width,
               e.length
        from      excavpermits      p
             join excavcuts         e on p.permit_num=e.permit_num
        left join utility_types     u on u.id=e.utility_type_id
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
    echo chr(27)."[2K\rrow/permit_custom: $percent% $row[id]";

    $insert_custom->execute([
        'permit_number' => $row['permit_num'],
        'CutLength'        => $row['length'    ],
        'CutWidth'         => $row['width'     ],
        'CutDepth'         => $row['depth'     ]
    ]);
}

echo "\n";
