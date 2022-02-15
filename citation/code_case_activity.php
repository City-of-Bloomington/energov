<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $CITATION  PDO connection to row database
 * @param $DCT       PDO connection to DCT database
 */
declare (strict_types=1);

$fields = [
    'case_number',
    'activity_type',
    'activity_number',
    'activity_user',
    'activity_date'
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert into code_case_activity ($columns) values($params)");

$sql    = "select l.id,
                  l.cite_id,
                  a.name as action,
                  l.action_by,
                  l.action_date
            from legal_actions l
            join actions       a on a.id=l.action_id";

$query  = $CITATION->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rcitation/code_case_activity: $percent% $row[id]";

    $case_number     = DATASOURCE_CITATION."_$row[cite_id]";
    $activity_number = DATASOURCE_CITATION."_$row[id]";

    $insert->execute([
        'case_number'     => $case_number,
        'activity_number' => $activity_number,
        'activity_type'   => $row['action'     ],
        'activity_user'   => $row['action_by'  ],
        'activity_date'   => $row['action_date'],
    ]);
}
echo "\n";
