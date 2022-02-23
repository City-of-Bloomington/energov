<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @param $DCT    PDO connection to DCT database
 */
declare (strict_types=1);

$fields = [
    'company_name',
    'first_name',
    'last_name',
    'email',
    'business_phone',
    'home_phone'
];

$where  = [];
foreach ($fields as $f) { $where[] = "$f=:$f"; }
$where  = 'where '.implode(' and ', $where);
$select = $DCT->prepare("select contact_id from contact $where");

$update_permits   = $DCT->prepare('update permit_contact    set contact_id=? where contact_id=?');
$update_cases     = $DCT->prepare('update code_case_contact set contact_id=? where contact_id=?');

$update_obligee   = $DCT->prepare('update bond set   obligee_contact_id=? where   obligee_contact_id=?');
$update_principal = $DCT->prepare('update bond set principal_contact_id=? where principal_contact_id=?');
$update_surety    = $DCT->prepare('update bond set    surety_contact_id=? where    surety_contact_id=?');

$delete_addresses = $DCT->prepare('delete from contact_address where contact_id=?');
$delete_notes     = $DCT->prepare('delete from contact_note    where contact_id=?');
$delete_contact   = $DCT->prepare('delete from contact         where contact_id=?');

$cols    = implode(',', $fields);
$sql     = "select $cols, count(*) as dupes
            from contact
            group by $cols
            having count(*) > 1";
$query   = $DCT->query($sql);
$result  = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rde-dupe contact: $percent% ".implode(' ', $row);

    $data  = [];
    $where = [];
    foreach ($fields as $f) {
        if ($row[$f]) {
            $where[] = "$f=:$f";
            $data[$f] = $row[$f];
        }
        else { $where[] = "$f is null"; }
    }
    $where  = 'where '.implode(' and ', $where);
    $select = $DCT->prepare("select contact_id from contact $where");
    $select->execute($data);

    $id_primary = null;
    $ids        = $select->fetchAll(\PDO::FETCH_COLUMN);
    foreach ($ids as $i=>$id) {
        if ($i == 0) { $id_primary = $id; continue; }

        $update_permits  ->execute([$id_primary, $id]);
        $update_cases    ->execute([$id_primary, $id]);
        $update_obligee  ->execute([$id_primary, $id]);
        $update_principal->execute([$id_primary, $id]);
        $update_surety   ->execute([$id_primary, $id]);

        $delete_addresses->execute([$id]);
        $delete_notes    ->execute([$id]);
        $delete_contact  ->execute([$id]);
    }
}
