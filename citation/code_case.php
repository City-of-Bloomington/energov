<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $CITATION  PDO connection to row database
 * @param $DCT       PDO connection to DCT database
 */
declare (strict_types=1);
define('THREE_YEARS', 1095);
$case_fields = [
    'case_number',
    'case_type',
    'case_description',
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
    'city',
    'state_code',
    'zip',
    'country_type'
];

$fee_fields = [
    'code_case_fee_id',
    'case_number',
    'fee_type',
    'fee_amount',
    'fee_date'
];

$columns = implode(',', $case_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $case_fields));
$insert_case  = $DCT->prepare("insert into code_case ($columns) values($params)");

$columns = implode(',', $violation_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $violation_fields));
$insert_violation  = $DCT->prepare("insert into code_case_violation ($columns) values($params)");

$columns = implode(',', $address_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $address_fields));
$insert_address  = $DCT->prepare("insert into code_case_address ($columns) values($params)");

$columns = implode(',', $fee_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fee_fields));
$insert_fee  = $DCT->prepare("insert into code_case_fee ($columns) values($params)");

$sql = "select c.id,
               u.empid,
               v.name,
               c.citation,
               c.note,
               concat_ws(' ', c.address_street_number,
                              c.address_street_direction,
                              c.address_street_name,
                              c.address_street_type,
                              c.sud_type,
                              c.sud_num) as address,
               c.address_city,
               c.address_state,
               c.address_zipcode,
               c.inactive,
               c.status,
               c.complied_status,
               a.name as legal_status,
               c.amount,
               c.balance,
               c.date_writen,
               datediff(now(), c.date_writen) as age,
               c.compliance_date,
               c.date_complied,
               greatest(coalesce(date_writen,             0),
                        coalesce(due_and_compliance_date, 0),
                        coalesce(date_complied,           0),
                        coalesce(date_paid,               0),
                        coalesce(trans_collection_date,   0),
                        coalesce(recalled_date,           0)) as latest_date
        from citations       c
        join violation_types v on v.id=c.violation
        left join users      u on u.id=c.inspector_id
        left join actions    a on a.id=c.action_id";
$query  = $CITATION->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rcitation/code_case: $percent% $row[id]";

    $status = $row['status'];
    $active = $row['inactive'] ? 'inactive' : 'active';
    if ((int)$row['age'] > THREE_YEARS) {
        $status = 'archive';
        $active = 'inactive';
    }

    $case_number      = DATASOURCE_CITATION."_$row[id]";
    $case_status      = DATASOURCE_CITATION."_{$status}_{$row['legal_status']}_{$active}";
    $violation_number = $case_number;
    $fee_id           = $case_number;

    $closed_date      = null;
    if (in_array($row['status'], ['WARNING', 'VOID', 'ADMIN VOID'])) {
        $closed_date = $row['latest_date'];
    }
    if (in_array($row['status'], ['PAID', 'UNCOLLECTABLE'])
     && in_array($row['complied_status'], ['Complied', 'ABATED'])) {
         $closed_date = $row['latest_date'];
    }


    $insert_case->execute([
        'case_number'      => $case_number,
        'case_type'        => 'Title 6',
        'case_status'      => $case_status,
        'case_description' => $row['name'       ],
        'assigned_to_user' => $row['empid'      ],
        'open_date'        => $row['date_writen'],
        'closed_date'      => $closed_date
    ]);

    $insert_violation->execute([
        'violation_number'       => $violation_number,
        'case_number'            => $case_number,
        'violation_code'         => $row['name'           ],
        'violation_status'       => $row['complied_status'],
        'violation_priority'     => 'Medium',
        'violation_note'         => $row['note'           ],
        'citation_date'          => $row['date_writen'    ],
        'compliance_date'        => $row['date_complied'  ],
        'resolved_date'          => $row['date_complied'  ]
    ]);

    if ($row['address']) {
        $insert_address->execute([
            'case_number'       => $case_number,
            'address_type'      => 'Location',
            'street_number'     => $row['address'],
            'city'              => $row['address_city'],
            'state_code'        => $row['address_state'],
            'zip'               => $row['address_zipcode'],
            'country_type'      => COUNTRY_TYPE
        ]);
    }

    if (   !$row['inactive']
        && !in_array($row['status'], ['WARNING', 'PAID', 'VOID', 'UNCOLLECTABLE', 'ADMIN VOID'])
        &&  (float)$row['balance'] > 0) {

        $amount   = (int)$row['amount'];
        $fee_type = $row['name'];
        switch ($amount) {
            case  15: break;
            case  50: $fee_type .= ' - First';    break;
            case 100: $fee_type .= ' - Second';   break;
            case 150: $fee_type .= ' - Third';    break;
            default: die('Invalid fee amount');
        }

        if ($amount > 0) {
            $insert_fee->execute([
                'code_case_fee_id' => $fee_id,
                'case_number'      => $case_number,
                'fee_type'         => $fee_type,
                'fee_amount'       => $amount,
                'fee_date'         => $row['date_writen']
            ]);
        }
    }
}
echo "\n";
