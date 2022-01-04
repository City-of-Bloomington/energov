<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $rental   PDO connection to rental database
 * @param $energov  PDO connection to DCT database
 */
declare (strict_types=1);
$sql = "insert into permit_address(
            permit_number,
            main_address,
            street_number,
            pre_direction,
            street_name,
            street_type,
            post_direction,
            unit_suite_number,
            country_type
        )
        values(
            :permit_number,
            :main_address,
            :street_number,
            :pre_direction,
            :street_name,
            :street_type,
            :post_direction,
            :unit_suite_number,
            'unknown'
        )";
$insert = $energov->prepare($sql);

$sql = "select r.id         as permit_number,
            case when subunit_id is not null then 1 else 0 end as main_address,
            a.street_num    as street_number,
            a.street_dir    as pre_direction,
            a.street_name   as street_name,
            a.street_type   as street_type,
            a.post_dir      as post_direction,
            a.sud_type || ' ' || a.sud_num as unit_suite_number
        from rental.registr  r
        join rental.address2 a on r.id=a.registr_id";
$result = $rental->query($sql);
foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    $insert->execute($row);
}
