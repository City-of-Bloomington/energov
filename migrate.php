<?php
/**
 * @copyright 2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
include './MasterAddress.php';

define('DATASOURCE_RENTAL',   'rentpro' );
define('DATASOURCE_ROW',      'row'     );
define('DATASOURCE_CITATION', 'citation');
define('DATASOURCE_NOV',      'nov'     );

define('SITE_HOME', !empty($_SERVER['SITE_HOME']) ? $_SERVER['SITE_HOME'] : __DIR__.'/data');
$config  = include SITE_HOME.'/config.php';
$RENTAL  = db_connect($config['db']['rental' ]);
$ROW     = db_connect($config['db']['row'    ]);
$DCT     = db_connect($config['db']['energov']);

$DCT->query('delete from attachment_document');
$DCT->query('delete from permit_payment_detail');
$DCT->query('delete from payment');
$DCT->query('delete from permit_fee');
$DCT->query('delete from permit_activity');
$DCT->query('delete from permit_note');
$DCT->query('delete from permit_inspection');
$DCT->query('delete from permit_contact');
$DCT->query('delete from permit_address');
$DCT->query('delete from permit');
$DCT->query('delete from inspection');

$DCT->query('delete from contact_address');
$DCT->query('delete from contact_note');
$DCT->query('delete from contact');

$DCT->query("dbcc checkident('contact',             RESEED, 0)");
$DCT->query("dbcc checkident('inspection',          RESEED, 0)");
$DCT->query("dbcc checkident('permit',              RESEED, 0)");
$DCT->query("dbcc checkident('permit_activity',     RESEED, 0)");
$DCT->query("dbcc checkident('permit_fee',          RESEED, 0)");
$DCT->query("dbcc checkident('payment',             RESEED, 0)");
$DCT->query("dbcc checkident('attachment_document', RESEED, 0)");

include './rental/contact.php';
include './rental/inspection.php';
include './rental/permit.php';
include './rental/permit_address.php';
include './rental/permit_contact.php';
include './rental/permit_inspection.php';
include './rental/permit_note.php';
include './rental/permit_activity.php';
include './rental/permit_fee.php';
include './rental/payment.php';
include './rental/attachment_document.php';

include './row/contact.php';

function db_connect(array $config): \PDO
{
    $pdo = new \PDO($config['dsn'], $config['user'], $config['pass'], $config['opts']);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    return $pdo;
}
