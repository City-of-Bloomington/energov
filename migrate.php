<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
include './ArcGIS.php';
include './MasterAddress.php';

define('COUNTRY_TYPE', 'US');

define('DATASOURCE_RENTAL',   'rentpro' );
define('DATASOURCE_ROW',      'row'     );
define('DATASOURCE_CITATION', 'citation');
define('DATASOURCE_NOV',      'nov'     );
define('DATASOURCE_PLANNING', 'CZC tracking sheet');

define('SITE_HOME', !empty($_SERVER['SITE_HOME']) ? $_SERVER['SITE_HOME'] : __DIR__.'/data');
$config   = include SITE_HOME.'/config.php';
$PLANNING = db_connect($config['db']['planning']);
$CITATION = db_connect($config['db']['citation']);
$RENTAL   = db_connect($config['db']['rental'  ]);
$ROW      = db_connect($config['db']['row'     ]);
$NOV      = db_connect($config['db']['nov'     ]);
$DCT      = db_connect($config['db']['energov' ]);
$ARCGIS   = new ArcGIS($config['api']['arcgis']);

$DCT->query('delete from plan_address');
$DCT->query('delete from plan_case');
$DCT->query('delete from code_case_activity');
$DCT->query('delete from code_case_violation_payment_detail');
$DCT->query('delete from code_case_violation_fee');
$DCT->query('delete from code_case_violation');
$DCT->query('delete from code_case_fee');
$DCT->query('delete from code_case_contact');
$DCT->query('delete from code_case_address');
$DCT->query('delete from code_case');
$DCT->query('delete from permit_bond');
$DCT->query('delete from bond_note');
$DCT->query('delete from bond');
$DCT->query('delete from attachment_document');
$DCT->query('delete from permit_payment_detail');
$DCT->query('delete from payment');
$DCT->query('delete from permit_fee_adjustment');
$DCT->query('delete from permit_fee');
$DCT->query('delete from permit_activity');
$DCT->query('delete from permit_note');
$DCT->query('delete from permit_inspection_case');
$DCT->query('delete from permit_inspection');
$DCT->query('delete from inspection_additional_fields');
$DCT->query('delete from inspection');
$DCT->query('delete from inspection_case_address');
$DCT->query('delete from inspection_case_parcel');
$DCT->query('delete from inspection_case');
$DCT->query('delete from PERMIT_TABLE_custom_fields');
$DCT->query('delete from permit_additional_fields');
$DCT->query('delete from permit_parent_permit');
$DCT->query('delete from permit_contact');
$DCT->query('delete from permit_parcel');
$DCT->query('delete from permit_address');
$DCT->query('delete from permit');

$DCT->query('delete from contact_subcontact');
$DCT->query('delete from contact_address');
$DCT->query('delete from contact_note');
$DCT->query('delete from contact');

$DCT->query("dbcc checkident('attachment_document', RESEED, 0)");

// include './rental/contact.php';
// include './rental/permit.php';
// include './rental/permit_contact.php';
// include './rental/permit_address.php';
// include './rental/inspection.php';
// include './rental/permit_inspection.php';
// include './rental/permit_note.php';
// include './rental/permit_activity.php';
// include './rental/permit_fee.php';
// // include './rental/payment.php';
// include './rental/attachment_document.php';
//
// include './row/contact.php';
// include './row/bond_permit.php';
// include './row/bond.php';
// // include './row/inspection.php';
// // include './row/permit_inspection.php';
include './row/permit.php';
include './row/permit_additional.php';

// include './citation/code_case.php';
// include './citation/code_case_activity.php';
// include './citation/contact.php';
// include './citation/code_case_contact.php';
// // include './citation/payment.php';
// include './citation/attachment_document.php';
//
// include './nov/contact.php';
// include './nov/code_case.php';
// include './nov/code_case_contact.php';
//
// // include './planning/permit.php';
// // include './planning/permit_address.php';
// // include './planning/plan_case.php';
// // include './planning/plan_address.php';
//
// include './de-dupe.php';

function db_connect(array $config): \PDO
{
    $pdo = new \PDO($config['dsn'], $config['user'], $config['pass'], $config['opts']);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    return $pdo;
}
