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
$rental  = db_connect($config['db']['rental' ]);
$energov = db_connect($config['db']['energov']);

$energov->query('delete from permit_activity');
$energov->query('delete from permit_note');
$energov->query('delete from permit_fee');
$energov->query('delete from permit_inspection');
$energov->query('delete from permit_contact');
$energov->query('delete from permit_address');
$energov->query('delete from permit');
$energov->query('delete from inspection');

$energov->query('delete from contact_address');
$energov->query('delete from contact_note');
$energov->query('delete from contact');

$energov->query("dbcc checkident('contact',         RESEED, 0)");
$energov->query("dbcc checkident('inspection',      RESEED, 0)");
$energov->query("dbcc checkident('permit',          RESEED, 0)");
$energov->query("dbcc checkident('permit_fee',      RESEED, 0)");
$energov->query("dbcc checkident('permit_activity', RESEED, 0)");

include './rental/contact.php';
include './rental/inspection.php';
include './rental/permit.php';
include './rental/permit_address.php';
include './rental/permit_contact.php';
include './rental/permit_inspection.php';
include './rental/permit_fee.php';
include './rental/permit_note.php';
include './rental/permit_activity.php';

function db_connect(array $config): \PDO
{
    $pdo = new \PDO($config['dsn'], $config['user'], $config['pass'], $config['opts']);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    return $pdo;
}
