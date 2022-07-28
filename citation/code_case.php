<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $CITATION  PDO connection to row database
 * @param $DCT       PDO connection to DCT database
 */
declare (strict_types=1);
$case_fields = [
    'case_number',
    'case_type',
    'case_status',
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
    'address_type',
    'street_number',
    'pre_direction',
    'street_name',
    'street_type',
    'unit_suite_number',
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


$sql     = "select c.id,
                   c.status,
                   c.date_writen,
                   c.compliance_date,
                   u.empid,
                   v.name,
                   c.citation,
                   c.note,
                   c.date_complied,
                   c.address_street_number,
                   c.address_street_direction,
                   c.address_street_name,
                   c.address_street_type,
                   c.address_city,
                   c.address_state,
                   c.address_zipcode,
                   c.sud_type,
                   c.sud_num,
                   c.amount
            from citations       c
            join violation_types v on v.id=c.violation
            left join users      u on u.id=c.inspector_id";
$query  = $CITATION->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rcitation/code_case: $percent% $row[id]";

    $case_number      = DATASOURCE_CITATION."_$row[id]";
    $violation_number = $case_number;
    $fee_id           = $case_number;

    $insert_case->execute([
        'case_number'      => $case_number,
        'case_type'        => 'citation',
        'case_status'      => $row['status'       ],
        'open_date'        => $row['date_writen'  ],
        'closed_date'      => $row['date_complied'],
        'assigned_to_user' => $row['empid'        ]
    ]);

    $insert_violation->execute([
        'violation_number'       => $violation_number,
        'case_number'            => $case_number,
        'violation_code'         => $row['name'           ],
        'violation_status'       => $row['status'         ],
        'violation_priority'     => $row['citation'       ],
        'violation_note'         => $row['note'           ],
        'citation_date'          => $row['date_writen'    ],
        'compliance_date'        => $row['date_complied'  ],
        'resolved_date'          => $row['date_complied'  ]
    ]);

    if ($row['address_street_number'] && $row['address_street_name']) {
        $insert_address->execute([
            'case_number'       => $case_number,
            'address_type'      => 'Location',
            'street_number'     => $row['address_street_number'],
            'pre_direction'     => $row['address_street_direction'],
            'street_name'       => $row['address_street_name'],
            'street_type'       => $row['address_street_type'],
            'unit_suite_number' => trim("$row[sud_type] $row[sud_num]"),
            'city'              => $row['address_city'],
            'state_code'        => $row['address_state'],
            'zip'               => $row['address_zipcode'],
            'country_type'      => COUNTRY_TYPE
        ]);
    }

//     $amount = (float)$row['amount'];
//     if ($amount > 0) {
//         $insert_fee->execute([
//             'code_case_violation_fee_id' => $fee_id,
//             'violation_number'           => $violation_number,
//             'fee_amount'                 => $amount,
//             'fee_date'                   => $row['date_writen']
//         ]);
//     }
}
echo "\n";
