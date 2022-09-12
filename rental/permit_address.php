<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $RENTAL PDO connection to rental database
 * @param $DCT    PDO connection to DCT database
 * @param $ARCGIS ArcGIS rest api connection
 */
declare (strict_types=1);
$address_fields = [
    'permit_number',
    'street_number',
    'pre_direction',
    'street_name',
    'street_type',
    'post_direction',
    'unit_suite_number',
    'city',
    'state_code',
    'zip',
    'country_type'
];

$parcel_fields = [
    'permit_number',
    'parcel_number'
];

$inspection_fields = [
    'inspection_case_number',
    'street_number',
    'pre_direction',
    'street_name',
    'street_type',
    'post_direction',
    'unit_suite_number',
    'city',
    'state_code',
    'zip',
    'country_type'
];

$inspection_parcel_fields = [
    'inspection_case_number',
    'parcel_number'
];

$columns = implode(',', $address_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $address_fields));
$insert_address = $DCT->prepare("insert permit_address ($columns) values($params)");

$columns = implode(',', $inspection_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $inspection_fields));
$insert_inspection = $DCT->prepare("insert inspection_case_address ($columns) values($params)");

$sql = "insert into parcel (parcel_number)
        select ?
        where not exists (select parcel_number from parcel where parcel_number=?)";
$insert_parcel  = $DCT->prepare($sql);

$columns = implode(',', $parcel_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $parcel_fields));
$insert_permit = $DCT->prepare("insert permit_parcel ($columns) values($params)");

$columns = implode(',', $inspection_parcel_fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $inspection_parcel_fields));
$insert_inspection_parcel = $DCT->prepare("insert inspection_case_parcel ($columns) values($params)");

$sql = "select r.id,
               a.street_num,
               a.street_dir,
               a.street_name,
               a.street_type,
               a.post_dir,
               a.subunit_id,
               a.sud_type,
               a.sud_num,
               a.street_address_id,
               r.inactive
        from rental.registr  r
        join rental.address2 a on r.id=a.registr_id
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
    echo chr(27)."[2K\rrental/permit_address: $percent% $row[id] => ";
    echo "$row[street_num] $row[street_dir] $row[street_name] $row[street_type] $row[sud_type] $row[sud_num]";

    $city   = 'Bloomington';
    $state  = 'IN';
    $zip    = null;
    $active = $row['inactive'] ? 'inactive' : 'active';

    $permit_number = DATASOURCE_RENTAL."_$row[id]";

    if ($row['street_address_id']) {
        $info = MasterAddress::addressInfo((int)$row['street_address_id']);
        if (!empty($info['address'])) {
            $city  = $info['address']['city' ];
            $state = $info['address']['state'];
            $zip   = $info['address']['zip'  ];
        }
        else {
            echo "$row[street_address_id] ";
            echo "Address not found\n";
            exit();
        }
    }

    $insert_address->execute([
        'permit_number'     => $permit_number,
        'street_number'     => $row['street_num' ],
        'pre_direction'     => $row['street_dir' ],
        'street_name'       => $row['street_name'],
        'street_type'       => $row['street_type'],
        'post_direction'    => $row['post_dir'   ],
        'unit_suite_number' => trim("$row[sud_type] $row[sud_num]"),
        'city'              => $city,
        'state_code'        => $state,
        'zip'               => $zip,
        'country_type'      => COUNTRY_TYPE,
    ]);
    if ($active == 'active') {
        $insert_inspection->execute([
            'inspection_case_number' => $permit_number,
            'street_number'     => $row['street_num' ],
            'pre_direction'     => $row['street_dir' ],
            'street_name'       => $row['street_name'],
            'street_type'       => $row['street_type'],
            'post_direction'    => $row['post_dir'   ],
            'unit_suite_number' => trim("$row[sud_type] $row[sud_num]"),
            'city'              => $city,
            'state_code'        => $state,
            'zip'               => $zip,
            'country_type'      => COUNTRY_TYPE,
        ]);
    }
    if (   !empty($info['address']['state_plane_x'])
        && !empty($info['address']['state_plane_y'])) {
        $parcels = $ARCGIS->parcels('/Energov/EnergovData/MapServer/1',
                                   (int)$info['address']['state_plane_x'],
                                   (int)$info['address']['state_plane_y']);
        if ($parcels) {
            foreach ($parcels as $p) {
                if (!empty($p['pin_18'])) {
                    $insert_parcel->execute([$p['pin_18'], $p['pin_18']]);
                    $insert_permit->execute([
                        'permit_number' => $permit_number,
                        'parcel_number' => $p['pin_18']
                    ]);
                    if ($active == 'active') {
                        $insert_inspection_parcel->execute([
                            'inspection_case_number' => $permit_number,
                            'parcel_number'          => $p['pin_18']
                        ]);
                    }
                }
            }
        }
    }
}
echo "\n";
