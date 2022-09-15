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
    'contact_id',
    'contact_type',
    'primary_billing_contact'
];

$columns = implode(',', $fields);
$params  = implode(',', array_map(fn($f): string => ":$f", $fields));
$insert  = $DCT->prepare("insert into code_case_contact ($columns) values($params)");

#-----------------------
# Agents
#-----------------------
$sql    = "select * from citation_agents";
$query  = $CITATION->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rcitation/code_case_contact agents: $percent% $row[agent_id]";

    $contact_id  = DATASOURCE_CITATION."_agent_$row[agent_id]";
    $case_number = DATASOURCE_CITATION."_$row[cite_id]";

    $insert->execute([
        'case_number'             => $case_number,
        'contact_id'              => $contact_id,
        'contact_type'            => 'agent',
        'primary_billing_contact' => 0
    ]);
}
echo "\n";

#-----------------------
# Owners
#-----------------------
$sql    = "select * from citation_owners";
$query  = $CITATION->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rcitation/code_case_contact owners: $percent% $row[owner_id]";

    $contact_id  = DATASOURCE_CITATION."_owner_$row[owner_id]";
    $case_number = DATASOURCE_CITATION."_$row[cite_id]";

    $insert->execute([
        'case_number'             => $case_number,
        'contact_id'              => $contact_id,
        'contact_type'            => 'owner',
        'primary_billing_contact' => 1
    ]);
}
echo "\n";

#-----------------------
# Tenants
#-----------------------
$sql = "select c.id,
               t.id       as tenant_id
        from citations       c
        join tenants  t on c.id=t.cite_id
        where c.status not in ('WARNING', 'VOID', 'ADMIN VOID', 'PAID', 'UNCOLLECTABLE')
        and c.balance>0
        and datediff(now(), c.date_writen) < 1095";
$query  = $CITATION->query($sql);
$result = $query->fetchAll(\PDO::FETCH_ASSOC);
$total  = count($result);
$c      = 0;
foreach ($result as $row) {
    $c++;
    $percent = round(($c / $total) * 100);
    echo chr(27)."[2K\rcitation/code_case_contact tenants: $percent% $row[tenant_id]";

    $contact_id  = DATASOURCE_CITATION."_tenant_$row[tenant_id]";
    $case_number = DATASOURCE_CITATION."_$row[id]";

    $insert->execute([
        'case_number'             => $case_number,
        'contact_id'              => $contact_id,
        'contact_type'            => 'tenant',
        'primary_billing_contact' => 0
    ]);
}
echo "\n";

