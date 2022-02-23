<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $RENTAL PDO connection to rental database
 * @param $DCT    PDO connection to DCT database
 */
declare (strict_types=1);
$fields = [
    'inspection_number',
    'inspection_type',
    'inspection_status',
    'create_date',
    'completed',
    'inspector',
    'inspected_date_start',
    'inspected_date_end',
    'comment'
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert into inspection ($columns) values($params)");

$sql     = "select insp_id,
                   inspection_type,
                   time_status,
                   inspected_by,
                   inspection_date,
                   comments
            from rental.inspections
            where inspection_date<sysdate";
$query   = $RENTAL->query($sql);
$result  = $query->fetchAll(\PDO::FETCH_ASSOC);
$total   = count($result);
$c       = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/inspection: $percent% $row[insp_id]";

    $insert->execute([
        'inspection_number'    => DATASOURCE_RENTAL."_$row[insp_id]",
        'inspection_type'      => $row['inspection_type'],
        'inspection_status'    => $row['time_status'    ],
        'completed'            => 1,
        'inspector'            => $row['inspected_by'   ],
        'create_date'          => $row['inspection_date'],
        'inspected_date_start' => $row['inspection_date'],
        'inspected_date_end'   => $row['inspection_date'],
        'comment'              => $row['comments'       ]
    ]);
}
echo "\n";
