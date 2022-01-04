<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $rental   PDO connection to rental database
 * @param $energov  PDO connection to DCT database
 */
declare (strict_types=1);
$sql = "insert into permit(
             permit_number,
             permit_type,
             permit_sub_type,
             permit_status,
             apply_date,
             issue_date,
             expire_date,
             legacy_data_source_name
         )
         values(
             :permit_number,
             :permit_type,
             :permit_sub_type,
             :permit_status,
             :apply_date,
             :issue_date,
             :expire_date,
             :legacy_data_source_name
         )";
$insert = $energov->prepare($sql);

$sql = "select r.id           as permit_number,
            'rental'          as permit_type,
            s.status_text     as permit_sub_type,
            case when r.inactive='Y' then 'inactive' else 'active' end as permit_status,
            r.registered_date as apply_date,
            r.permit_issued   as issue_date,
            r.permit_expires  as expire_date,
            'rentpro'         as legacy_data_source_name
        from rental.registr r
        join rental.prop_status s on r.property_status=s.status";
$result = $rental->query($sql);
foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    $insert->execute($row);
}
