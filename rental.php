<?php
/**
 * @copyright 2021 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
include './MasterAddress.php';

define('SITE_HOME', !empty($_SERVER['SITE_HOME']) ? $_SERVER['SITE_HOME'] : __DIR__.'/data');
$config  = include SITE_HOME.'/config.php';
$rental  = db_connect($config['db']['rental' ]);
$energov = db_connect($config['db']['energov']);

if (!is_dir(SITE_HOME.'/rental')) { mkdir(SITE_HOME.'/rental'); }

// $energov->query('delete from permit_contact');
// $energov->query('delete from permit_address');
// $energov->query('delete from permit');

$energov->query('delete from contact_address');
$energov->query('delete from contact');
$energov->query("dbcc checkident('contact', RESEED, 0)");

// include './rental/permit.php';
// include './rental/permit_address.php';
include './rental/contact.php';

function db_connect(array $config): \PDO
{
    $pdo = new \PDO($config['dsn'], $config['user'], $config['pass'], $config['opts']);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    return $pdo;
}
