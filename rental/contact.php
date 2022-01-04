<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $rental   PDO connection to rental database
 * @param $energov  PDO connection to DCT database
 */
declare (strict_types=1);
$sql = "insert into contact (
             first_name,
             email,
             business_phone,
             home_phone,
             legacy_data_source_name,
             isactive,
             is_company,
             is_individual,
             legacy_id
         )
        values(
             :first_name,
             :email,
             :business_phone,
             :home_phone,
             :legacy_data_source_name,
             :isactive,
             :is_company,
             :is_individual,
             :legacy_id
         )";
$insert = $energov->prepare($sql);

$sql = "select n.name_num   as legacy_id,
               n.name       as first_name,
               n.email      as email,
               n.phone_work as business_phone,
               n.phone_home as home_phone,
               'rentpro'    as legacy_data_source_name,
               1            as isactive,
               0            as is_company,
               0            as is_individual,
               n.address,
               n.city,
               n.state,
               n.zip
        from rental.name n";
$result = $rental->query($sql);
foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    $data = [
        'first_name'              => $row['first_name'],
        'email'                   => $row['email'],
        'business_phone'          => $row['business_phone'],
        'home_phone'              => $row['home_phone'],
        'legacy_data_source_name' => $row['legacy_data_source_name'],
        'isactive'                => $row['isactive'],
        'is_company'              => $row['is_company'],
        'is_individual'           => $row['is_individual'],
        'legacy_id'               => $row['legacy_id']
    ];
    $insert->execute($data);
    $contact_id = $energov->lastInsertId();

    if ($row['address']) {
        $address = MasterAddress::parseAddress($row['address']);
        $data = [];
    }
}
