<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $RENTAL PDO connection to rental database
 * @param $DCT    PDO connection to DCT database
 */
declare (strict_types=1);
$inspection_fields = [
    'inspection_number',
    'inspection_case_number',
    'inspection_type',
    'inspection_status',
    'create_date',
    'completed',
    'inspector',
    'inspected_date_start',
    'inspected_date_end',
    'comment'
];

$additional_fields = [
    'inspection_number',
    'Affidavit',
    'Violation',
    'SmokeDetectors',
    'LifeSafety'
];

$columns = implode(',', $inspection_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $inspection_fields));
$insert_inspection = $DCT->prepare("insert into inspection ($columns) values($params)");

$columns = implode(',', $additional_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $additional_fields));
$insert_additional = $DCT->prepare("insert into inspection_additional_fields ($columns) values($params)");

$sql     = "select i.insp_id,
                   i.id,
                   i.inspection_type,
                   i.time_status,
                   i.inspected_by,
                   i.inspection_date,
                   i.comments,
                   i.has_affidavit,
                   i.violations,
                   i.smook_detectors,
                   i.life_safety,
                   r.inactive
            from rental.inspections i
            join rental.registr     r on r.id=i.id";
$query   = $RENTAL->query($sql);
$result  = $query->fetchAll(\PDO::FETCH_ASSOC);
$total   = count($result);
$c       = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/inspection: $percent% $row[insp_id]";

    $permit_number     = DATASOURCE_RENTAL."_$row[id]";
    $inspection_number = DATASOURCE_RENTAL."_$row[insp_id]";
    $active            = $row['inactive'] ? 'inactive' : 'active';
    $case_number       = $active=='active' ? $permit_number : null;

    $insert_inspection->execute([
        'inspection_number'      => $inspection_number,
        'inspection_case_number' => $case_number,
        'inspection_type'        => $row['inspection_type'],
        'inspection_status'      => $row['time_status'    ],
        'completed'              => 1,
        'inspector'              => $row['inspected_by'   ],
        'create_date'            => $row['inspection_date'],
        'inspected_date_start'   => $row['inspection_date'],
        'inspected_date_end'     => $row['inspection_date'],
        'comment'                => $row['comments'       ]
    ]);

    $insert_additional->execute([
        'inspection_number' => $inspection_number,
        'Affidavit'         => 1,
        'Violation'         => $row['violations'     ],
        'SmokeDetectors'    => $row['smook_detectors'],
        'LifeSafety'        => $row['life_safety'    ]
    ]);
}
echo "\n";
