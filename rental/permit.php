<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $RENTAL PDO connection to rental database
 * @param $DCT    PDO connection to DCT database
 */
declare (strict_types=1);
$fields = [
    'permit_number',
    'permit_type',
    'permit_status',
    'apply_date',
    'issue_date',
    'expire_date',
    'legacy_data_source_name'
];

$additional_fields = [
    'permit_number',
    'TypeofHousing',
    'PermitLength',
    'Stories',
    'Foundation',
    'Heat',
    'Attic',
    'Accessory',
    'Affordable',
    'Buildings',
    'Units',
    'Bathrooms'
];

$custom_fields = [
    'permit_number',
    'structure',
    'Units',
    'NumberOfBedrooms',
    'OccupancyLoad',
    'SleepRooms'
];

$inspection_fields = [
    'inspection_case_number',
    'inspection_case_type',
    'inspection_case_status',
    'create_date',
    'last_inspection_date',
    'run_schedule'
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert into permit ($columns) values($params)");

$columns = implode(',', $additional_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $additional_fields));
$insert_additional  = $DCT->prepare("insert into permit_additional_fields ($columns) values($params)");

$columns = implode(',', $custom_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $custom_fields));
$insert_custom = $DCT->prepare("insert into PERMIT_TABLE_custom_fields ($columns) values($params)");

$columns = implode(',', $inspection_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $inspection_fields));
$insert_inspection = $DCT->prepare("insert into inspection_case ($columns) values($params)");


$sql = "select s.identifier,
               u.units,
               u.bedrooms,
               u.occload,
               case when u.sleeproom is not null then 1 else null end as sleeproom
        from rental.rental_structures s
        join rental.rental_units      u on s.id=u.sid
        where s.rid=?";
$select_units = $RENTAL->prepare($sql);

$sql = "select insp_id,
               inspection_date,
               foundation,
               heat_src,
               story_cnt,
               case when attic='Yes' then 1 else null end as attic
        from rental.inspections
        where id=? and rownum=1
        order by inspection_date desc";
$select_inspections = $RENTAL->prepare($sql);

$sql = "select  r.id,
                s.status_text,
                r.inactive,
                r.registered_date,
                r.permit_issued,
                r.permit_expires,
                r.permit_length,
                r.bath_count,
                r.prop_type,
                r.building_type,
                case when r.accessory_dwelling is not null then 1 else null end as accessory_dwelling,
                case when r.affordable         is not null then 1 else null end as affordable,
                pulls.earliest_pull
        from rental.registr r
        join rental.prop_status s on r.property_status=s.status
        left join (
            select p.rental_id, min(p.pull_date) as earliest_pull
            from rental.pull_history p
            group by p.rental_id
        ) pulls on pulls.rental_id=r.id
        where (r.registered_date is not null or earliest_pull is not null)";
$query  = $RENTAL->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rrental/permit: $percent% $row[id]";

    $apply_date    = $row['registered_date'] ? $row['registered_date'] : $row['earliest_pull'];
    $permit_number = DATASOURCE_RENTAL."_$row[id]";
    $active        = $row['inactive'] ? 'inactive' : 'active';
    $permit_status = DATASOURCE_RENTAL."_$row[status_text]_$active";

    $insert->execute([
        'permit_number'           => $permit_number,
        'permit_type'             => 'rental',
        'permit_status'           => $permit_status,
        'apply_date'              => $apply_date,
        'issue_date'              => $row['permit_issued'  ],
        'expire_date'             => $row['permit_expires' ],
        'legacy_data_source_name' => DATASOURCE_RENTAL,
    ]);

    $structures     = [];
    $totalBuildings = 0;
    $totalUnits     = 0;
    $select_units->execute([$row['id']]);
    $units = $select_units->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($units as $u) {
        $insert_custom->execute([
            'permit_number'     => $permit_number,
            'structure'         => $u['identifier'],
            'Units'             => $u['units'     ],
            'NumberOfBedrooms'  => $u['bedrooms'  ],
            'OccupancyLoad'     => $u['occload'   ],
            'SleepRooms'        => $u['sleeproom' ]
        ]);

        if (!in_array($u['identifier'], $structures)) { $structures[] = $u['identifier']; }
        $totalUnits += (int)$u['units'];
    }
    $totalBuildings = count($structures);

    // Use the first inspection we find as the source for the custom fields
    $select_inspections->execute([$row['id']]);
    $inspections = $select_inspections->fetchAll(\PDO::FETCH_ASSOC);

    $housingType = null;
    switch ($row['prop_type']) {
        case 'Condo':         $housingType = 'Condominium';   break;
        case 'Apartment':     $housingType = 'Multi-Family';  break;
        case 'Rooming House': $housingType = 'Rooming House'; break;
        default:
            switch ($row['building_type']) {
                case 'Multi-Family': $housingType = 'Multi-Family'; break;
                default:             $housingType = 'Single-Family';
            }
    }

    $permit_length = (int)$row['permit_length'];

    $insert_additional->execute([
        'permit_number'  => $permit_number,
        'TypeofHousing'  => $housingType,
        'PermitLength'   => $permit_length ? $permit_length : null,
        'Stories'        => $inspections[0]['story_cnt' ] ?? null,
        'Foundation'     => $inspections[0]['foundation'] ?? null,
        'Heat'           => $inspections[0]['heat_src'  ] ?? null,
        'Attic'          => $inspections[0]['attic'     ] ?? null,
        'Accessory'      => $row['affordable'        ],
        'Affordable'     => $row['accessory_dwelling'],
        'Bathrooms'      => $row['bath_count'        ],
        'Buildings'      => $totalBuildings,
        'Units'          => $totalUnits,
    ]);

    $insert_inspection->execute([
        'inspection_case_number' => $permit_number,
        'inspection_case_status' => $permit_status,
        'inspection_case_type'   => 'Recurring Rental Property Inspection',
        'create_date'            => $apply_date,
        'last_inspection_date'   => $inspections[0]['inspection_date'] ?? null,
        'run_schedule'           => $permit_length ? "$permit_length years" : null
    ]);
}
echo "\n";
