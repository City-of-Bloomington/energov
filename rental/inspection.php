<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $rental   PDO connection to rental database
 * @param $energov  PDO connection to DCT database
 */
declare (strict_types=1);
$fields = [
    'inspection_type',
    'inspection_status',
    'completed',
    'inspector',
    'inspected_date_start',
    'inspected_date_end',
    'comment',
    'legacy_data_source_name',
    'legacy_id'
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $energov->prepare("insert into inspection ($columns) values($params)");

$sql     = "select insp_id,
                   inspection_type,
                   time_status,
                   inspected_by,
                   inspection_date,
                   comments
            from rental.inspections";
$result  = $rental->query($sql);
foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    echo "Inspection: $row[insp_id] => ";

    $data = [
        'legacy_id'            => $row['insp_id'        ],
        'inspection_type'      => $row['inspection_type'],
        'inspection_status'    => $row['time_status'    ],
        'completed'            => 1,
        'inspector'            => $row['inspected_by'   ],
        'inspected_date_start' => $row['inspection_date'],
        'inspected_date_end'   => $row['inspection_date'],
        'comment'              => $row['comments'       ],
        'legacy_data_source_name' => DATASOURCE_RENTAL
    ];

    $insert->execute($data);
    $inspection_number = $energov->lastInsertId();
    echo "$inspection_number\n";
}
