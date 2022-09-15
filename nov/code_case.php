<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $NOV  PDO connection to nov database
 * @param $DCT  PDO connection to DCT database
 */
declare (strict_types=1);
$case_fields = [
    'case_number',
    'case_type',
    'case_status',
    'case_description',
    'open_date',
    'closed_date',
    'assigned_to_user'
];

$violation_fields = [
    'violation_number',
    'case_number',
    'violation_code',
    'violation_status',
    'violation_priority',
    'violation_note',
    'citation_date',
    'compliance_date',
    'resolved_date'
];

$address_fields = [
    'case_number',
    'street_number',
    'city',
    'state_code',
    'zip',
    'country_type'
];

// $fee_fields = [
//     'code_case_violation_fee_id',
//     'violation_number',
//     'fee_amount',
//     'fee_date'
// ];

$columns = implode(',', $case_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $case_fields));
$insert_case  = $DCT->prepare("insert into code_case ($columns) values($params)");

$columns = implode(',', $violation_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $violation_fields));
$insert_violation  = $DCT->prepare("insert into code_case_violation ($columns) values($params)");

$columns = implode(',', $address_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $address_fields));
$insert_address  = $DCT->prepare("insert into code_case_address ($columns) values($params)");

// $columns = implode(',', $fee_fields);
// $params  = implode(',', array_map(fn($f): string => ":$f", $fee_fields));
// $insert_fee  = $DCT->prepare("insert into code_case_violation_fee ($columns) values($params)");

$sql    = "select c.*,
                  concat_ws(' ', c.street_num,
                                 c.street_dir,
                                 c.street_name,
                                 c.street_type,
                                 c.sud_type,
                                 c.sud_num) as address,
                  v.name  as violation_type,
                  s.name  as status,
                  u.empid as inspector
           from citations       c
           left join violation_types v on v.id=c.violation_id
           left join statuses        s on s.id=c.status_id
           left join users           u on u.id=c.inspector_id";
$query  = $NOV->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rnov/code_case: $percent% $row[id]";

    $case_number      = DATASOURCE_NOV."_$row[id]";
    $case_status      = DATASOURCE_NOV."_$row[status]";
    $violation_number = $case_number;
    $fee_id           = $case_number;

    $insert_case->execute([
        'case_number'      => $case_number,
        'case_type'        => 'zoning',
        'case_status'      => $case_status,
        'case_description' => $row['legal_description'],
        'open_date'        => $row['date_written'     ],
        'closed_date'      => $row['compliance_date'  ],
        'assigned_to_user' => $row['inspector'        ],
    ]);

    $insert_violation->execute([
        'violation_number'       => $violation_number,
        'case_number'            => $case_number,
        'violation_code'         => substr($row['violation_type'], 0, 50),
        'violation_status'       => $case_status,
        'violation_priority'     => $row['citation'       ],
        'violation_note'         => $row['note'           ],
        'citation_date'          => $row['date_written'   ],
        'compliance_date'        => $row['compliance_date'],
        'resolved_date'          => $row['date_complied'  ]
    ]);

    if ($row['street_num'] && $row['street_name']) {
        $insert_address->execute([
            'case_number'       => $case_number,
            'street_number'     => $row['address'],
            'city'              => $row['city' ],
            'state_code'        => $row['state'],
            'zip'               => $row['zip'  ],
            'country_type'      => COUNTRY_TYPE
        ]);
    }
}
echo "\n";
