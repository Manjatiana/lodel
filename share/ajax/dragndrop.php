<?php
$tabIds = explode(',', $_POST['tabids']);

if(!preg_match("/^[a-z0-9\-]+$/", $_POST['site']) || 
	in_array($_POST['site'], array('lodel-0.8', 'share-0.8', 'lodeladmin-0.8', 'lodel', 'share', 'lodeladmin')) || 
	!is_dir('../../'.$_POST['site'])) {
	// tentative ?
	echo 'error';
	exit();
}

// chdir pour faciliter les include
chdir('../../'.$_POST['site']);
if(!file_exists('siteconfig.php')) {
	echo 'error';
	return;
}

require 'siteconfig.php';
require 'class.errors.php';
set_error_handler(array('LodelException', 'exception_error_handler'));

// les niveaux d'erreur � afficher
error_reporting(E_ALL);

try
{
require 'auth.php';
// pas de log de l'url dans la base
$GLOBALS['norecordurl'] = true;
// acc�s seulement aux personnes autoris�es
if(!authenticate(LEVEL_VISITOR, null, true) || !$lodeluser['visitor'])
{
	echo 'auth';
	return;
}

$table = lq("#_TP_entities");
$i=1;
foreach($tabIds as $v) {
	$id = (int)str_replace('container_','',$v);
	if($id>0) {
		$db->execute("UPDATE {$table} SET rank = '{$i}' WHERE id='{$id}'") or dberror();
	}
	$i++;
}
require 'cachefunc.php';
removefilesincache('.', './lodel/edition/');
echo 'ok';
}
catch(Exception $e)
{
	echo 'error';
	exit();
}
?>