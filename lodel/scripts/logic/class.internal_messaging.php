<?php
/**
 * Fichier de messagerie interne
 *
 * PHP version 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Home page: http://www.lodel.org
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @package lodel/logic
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajout� depuis la version 0.8
 */

/**
 * Classe de logique permettant de g�rer la messagerie interne
 * 
 * @package lodel/logic
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 */
class Internal_MessagingLogic extends Logic 
{

	/**
	 * ID de l'utilisateur courant
	 * De la forme $site-$id ou lodelmain-$id (adminlodel)
	 */
	private $_iduser;

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		global $lodeluser, $context;
		$this->_iduser = $lodeluser['adminlodel'] ? 'lodelmain-'.$lodeluser['id'] : $context['site'].'-'.$lodeluser['id'];
		parent::__construct('internal_messaging');
	}

	/**
	* make the select for this logic
	*/
	public function makeSelect(&$context,$var) 
	{
		if('addressees' != $var) return;
		global $db;
		$arr = array();
		if($context['idparent']) {
			$idparent = (int)$context['idparent'];
			$sender = $db->getRow(lq("SELECT iduser, subject, body, addressees FROM #_MTP_internal_messaging WHERE id='{$idparent}'"));
			preg_match("/(\w+)-(\d+)/", $sender['iduser'], $m);
			$idUser = (int)$m[2];
			if('lodelmain' == $m[1]) {
				$user = $db->getRow(lq("SELECT username, firstname, lastname, userrights from #_MTP_users WHERE id='{$idUser}'"));
			} elseif(!SINGLESITE) {
				$dbname = '`'.DATABASE.'_'.$m[1].'`.'.$GLOBALS['tableprefix'];
				$user = $db->getRow("SELECT username, firstname, lastname, userrights from {$dbname}users WHERE id='{$idUser}'");
			}
			$arr[':'.$sender['iduser'].':'] = $user['username'] . ( ($user['firstname'] || $user['lastname']) ? " ({$user['firstname']} {$user['lastname']})" : ''). ($user['userrights'] == LEVEL_ADMINLODEL ? '(LodelAdmin)' : ($user['userrights'] == LEVEL_ADMIN ? '(Admin)' : ''));
			$addressees = explode(':', $sender['addressees']);
			foreach($addressees as $addr) {
				if($this->_iduser == $addr || preg_match("/(\w+)-(\d+)/", $addr, $m) != 1) continue;
				$idUser = (int)$m[2];
				if('lodelmain' == $m[1]) {
					$user = $db->getArray(lq("SELECT username, firstname, lastname, userrights from #_MTP_users WHERE id='{$idUser}'"));
				} elseif(!SINGLESITE) {
					$dbname = '`'.DATABASE.'_'.$m[1].'`.'.$GLOBALS['tableprefix'];
					$user = $db->getArray("SELECT username, firstname, lastname, userrights from {$dbname}users WHERE id='{$idUser}'");
				}
				$user = $user[0];
				if(!in_array(':'.$addr.':', $arr))
					$arr[':'.$addr.':'] = $user['username'] . ( ($user['firstname'] || $user['lastname']) ? " ({$user['firstname']} {$user['lastname']}) " : ''). ($user['userrights'] == LEVEL_ADMINLODEL ? ' (LodelAdmin) ' : ($user['userrights'] == LEVEL_ADMIN ? ' (Admin) ' : ''));
			}
			$context['addressees'] = $addressees;
			foreach($context['addressees'] as $k=>$v) {
				if($v == '') unset($context['addressees'][$k]);
				else {
					$context['addressees'][$k] = ':'.$v.':';
				}
			}
			$context['subject'] = 'RE: '.$sender['subject'];
		} else {
			if(!SINGLESITE && !defined('backoffice-lodeladmin')) {
				$users = $db->getArray(lq("SELECT id, username, lastname, firstname, userrights FROM #_TP_users ORDER BY username"));
				if(is_array($users)) {
					foreach($users as $user) {
						$arr[':'.$context['site'].'-'.$user['id'].':'] = $user['username'] . ( ($user['firstname'] || $user['lastname']) ? " ({$user['firstname']} {$user['lastname']})" : ''). ($user['userrights'] == LEVEL_ADMINLODEL ? '(LodelAdmin)' : ($user['userrights'] == LEVEL_ADMIN ? '(Admin)' : ''));
						$ids[] = 'allsite'.$context['site'].'-'.$user['id'];
						$siteids[] = $context['site'].'-'.$user['id'];
						if($user['userrights'] == LEVEL_ADMIN) {
							$adminids[] = $context['site'].'-'.$user['id'];
						}
					}
					$arr[':'.join(':', $ids).':'] = getlodeltextcontents('internal_messaging_all_users_from_site', 'admin');
					if($adminids) {
						$arr[':'.join(':', $adminids).':'] = getlodeltextcontents('internal_messaging_all_admin_from_site', 'admin');
					}
					unset($ids, $adminids);
				}
			}
			$users = $db->getArray(lq("SELECT id, username, lastname, firstname, userrights FROM #_MTP_users ORDER BY username"));
			if(is_array($users)) {
				foreach($users as $user) {
					$arr[':lodelmain-'.$user['id'].':'] = $user['username'] . ( ($user['firstname'] || $user['lastname']) ? " ({$user['firstname']} {$user['lastname']}) " : '') . ($user['userrights'] == LEVEL_ADMINLODEL ? ' (LodelAdmin) ' : ($user['userrights'] == LEVEL_ADMIN ? ' (Admin) ' : ''));
					$ids[] = 'alllodelmain-'.$user['id'];
					$mainids[] = 'lodelmain-'.$user['id'];
				}
				$arr[':'.join(':', $ids).':'] = getlodeltextcontents('internal_messaging_all_adminlodel', 'admin');
				unset($ids);
			}
			if($context['lodeluser']['adminlodel']) {
				$sites = $db->getArray(lq("SELECT name FROM #_MTP_sites ORDER BY name"));
				$all = join(':', $mainids).':'.(isset($siteids) ? join(':', $siteids) : '');
				foreach($sites as $site) {
					$dbname = '`'.DATABASE.'_'.$site['name'].'`.'.$GLOBALS['tableprefix'];
					$users = $db->getArray("SELECT id, username, lastname, firstname, userrights FROM {$dbname}users ORDER BY username");
					if(!is_array($users)) continue;
					foreach($users as $user) {
						$ids[] = $site['name'].'-'.$user['id'];
					}				
				}
				$arr[':'.$all.(is_array($ids) ? ':'.join(':', $ids) : '').':'] = getlodeltextcontents('internal_messaging_all_users_all_sites', 'admin');
			}
		}
		$selected = $context['idparent'] ? array_keys($arr) : '';
		renderOptions($arr, $selected);
	}

	/**
	 * Suppression d'un message
	 *
	 * Liste des Status
	 * -32 : supprim� destinataire, dossier 'envoy�s' de l'envoyeur
	 * -16 : corbeille destinataire, lu, supprim� du dossier 'envoy�s' de l'envoyeur
	 * -8 : corbeille destinaire, lu, dossier 'envoy�s' de l'envoyeur 
	 * -2 : corbeille destinaire, non lu, supprim� du dossier 'envoy�s' de l'envoyeur
	 * -1 : corbeille destinaire, non lu, dossier 'envoy�s' de l'envoyeur
	 * 0 : boite de r�ception destinataire, lu, dossier 'envoy�s' de l'envoyeur
	 * 1 : boite de r�ception destinataire, non lu, dossier 'envoy�s' de l'envoyeur
	 * 16 : boite de r�ception destinataire, non lu, supprim� du dossier 'envoy�s' de l'envoyeur
	 * 32 : boite de r�ception destinataire, lu, supprim� du dossier 'envoy�s' de l'envoyeur
	 * @param array &$context le context pass� par r�f�rence
	 * @param array &$error le tableau des erreurs �ventuelles pass� par r�f�rence
	 */
	public function deleteAction(&$context, &$error)
	{
		global $db;
		if (isset($context['all'])) {
			unset($context['all']);
			$context['escape'] = true;
			if(!isset($context['directory']) || $context['directory'] == 'inbox') {
				$where = "status >= 0 AND addressee = '{$this->_iduser}'";
			} elseif($context['directory'] == 'sent') {
				$where = "status in (0,1,-1,-8,-32) AND iduser = '{$this->_iduser}'";
			} elseif($context['directory'] == 'trash') {
				$where = "status in (-1,-8,-2,-16) AND addressee = '{$this->_iduser}'";
			}
			if(isset($where)) {
				$ids = $db->execute(lq("SELECT id FROM #_MTP_internal_messaging WHERE {$where}"));
				if($ids) {
					while(!$ids->EOF) {
						$context['id'] = $ids->fields['id'];
						$this->deleteAction($context, $error);
						$ids->MoveNext();
					}
				}
			}
			unset($context['escape']);
		} elseif(isset($context['im_select'])) {
			$context['escape'] = true;
			$selects = $context['im_select'];
			unset($context['im_select']);
			foreach($selects as $id=>$checked) {
				$id = (int)$id;
				if('sent' === (string)$context['directory']) {
					$childIds = $db->execute(lq("
							SELECT distinct(id) 
							FROM	#_MTP_internal_messaging as i_m, 
								(  SELECT iduser, incom_date, subject, body, addressees 
								   FROM #_MTP_internal_messaging 
								   WHERE id = '{$id}') AS im 
							WHERE 	i_m.iduser=im.iduser 
								AND i_m.incom_date=im.incom_date 
								AND i_m.subject=im.subject 
								AND i_m.body=im.body 
								AND i_m.addressees=im.addressees
								AND id != '{$id}'"));
					if($childIds) {
						while(!$childIds->EOF) {
							$context['id'] = $childIds->fields['id'];
							$this->deleteAction($context, $error);
							$childIds->MoveNext();
						}
					}
				}
				$context['id'] = $id;
				$this->deleteAction($context, $error);
			}
			unset($context['escape']);
		} elseif($id = (int)$context['id']) {
			$childIds = $db->execute(lq("SELECT id FROM #_MTP_internal_messaging WHERE idparent = '{$id}'"));
			if($childIds) {
				$oldEscape = $context['escape'];
				$context['escape'] = true;
				while(!$childIds->EOF) {
					$context['id'] = $childIds->fields['id'];
					$this->deleteAction($context, $error);
					$childIds->MoveNext();
				}
				$context['escape'] = $oldEscape;
				unset($oldEscape);
			}

			$row = $db->getRow(lq("SELECT status, iduser, addressee FROM #_MTP_internal_messaging WHERE id = '{$id}'"));
			
			if((string)$row['addressee'] === $this->_iduser) {
				if(!isset($context['directory']) || $context['directory'] == 'inbox') {
					switch((int)$row['status']) {
					case 0:
					$status = -8;
					break;

					case 1:
					$status = -1;
					break;

					case 16:
					$status = -2;
					break;

					case 32:
					$status = -16;
					break;

					default:break;
					}
					if(isset($status))
						$db->execute(lq("UPDATE #_MTP_internal_messaging SET status = '{$status}' WHERE id = '{$id}' AND addressee = '{$this->_iduser}'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
				} elseif($context['directory'] == 'trash') {
					switch((int)$row['status']) {
					case -16:
					$status = (1 === (int)$context['restore']) ? 32 : 'delete';
					break;
					case -2:
					$status = (1 === (int)$context['restore']) ? 16 : 'delete';
					break;

					case -8:
					$status = (1 === (int)$context['restore']) ? 0 : -32;
					break;
					case -1:
					$status = (1 === (int)$context['restore']) ? 1 : -32;
					break;

					default:break;
					}
					if(isset($status)) {
						if('delete' === $status)
							$db->execute(lq("DELETE FROM #_MTP_internal_messaging WHERE id = '{$id}' AND addressee = '{$this->_iduser}'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						else
							$db->execute(lq("UPDATE #_MTP_internal_messaging SET status = '{$status}' WHERE id = '{$id}' AND addressee = '{$this->_iduser}'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					}
				} elseif($context['directory'] == 'sent') {
					switch((int)$row['status']) {
					case 0:
					$status = 32;
					break;

					case 1:
					$status = 16;
					break;

					case -1:
					$status = -2;
					break;

					case -8:
					$status = -16;
					break;

					case -32:
					$status = 'delete';
					break;
					default:break;
					}
					if(isset($status)) {
						if('delete' === $status)
							$db->execute(lq("DELETE FROM #_MTP_internal_messaging WHERE id = '{$id}' AND addressee = '{$this->_iduser}'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						else
							$db->execute(lq("UPDATE #_MTP_internal_messaging SET status = '{$status}' WHERE id = '{$id}' AND addressee = '{$this->_iduser}'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					}
				}
			} elseif((string)$row['iduser'] === $this->_iduser) {
				if($context['directory'] == 'sent') {
					switch((int)$row['status']) {
					case 0:
					$status = 32;
					break;

					case 1:
					$status = 16;
					break;

					case -1:
					$status = -2;
					break;

					case -8:
					$status = -16;
					break;

					case -32:
					$status = 'delete';
					break;
					default:break;
					}
					if(isset($status)) {
						if('delete' === $status)
							$db->execute(lq("DELETE FROM #_MTP_internal_messaging WHERE id = '{$id}' AND iduser = '{$this->_iduser}'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
						else
							$db->execute(lq("UPDATE #_MTP_internal_messaging SET status = '{$status}' WHERE id = '{$id}' AND iduser = '{$this->_iduser}'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
					}
				}
			}
		}
		unset($context['id'], $id);
		if(!isset($context['escape']) || true !== $context['escape']) {
			update();
			return "_location: index.php?do=list&lo=internal_messaging&deleted=1&directory=".$context['directory'];
		}
	}

	public function listAction(&$context, &$error)
	{
		global $db;
		$id = (int)$context['id'];
		if($id) {
			$context['data'] = array();
			$datas = $db->getRow(lq("SELECT idparent, iduser, addressee, subject, body, cond, status FROM #_MTP_internal_messaging WHERE id='{$id}' AND (addressee = '{$this->_iduser}' OR iduser = '{$this->_iduser}')"));
			if(!$datas) {
				$error = getlodeltextcontents('internal_messaging_no_message_found', 'admin');
				return '_error';
			}
			preg_match("/(\w+)-(\d+)/", $datas['iduser'], $m);
			$idUser = (int)$m[2];
			if('lodelmain' == $m[1]) {
				$sender = $db->getRow(lq("SELECT username, firstname, lastname from #_MTP_users WHERE id='{$idUser}'"));
			} else {
				$dbname = '`'.DATABASE.'_'.$m[1].'`.'.$GLOBALS['tableprefix'];
				$sender = $db->getRow("SELECT username, firstname, lastname from {$dbname}users WHERE id='{$idUser}'");
			}
			preg_match("/(\w+)-(\d+)/", $datas['addressee'], $m);
			$idUser = (int)$m[2];
			if('lodelmain' == $m[1]) {
				$addressee = $db->getRow(lq("SELECT username, firstname, lastname from #_MTP_users WHERE id='{$idUser}'"));
			} else {
				$dbname = '`'.DATABASE.'_'.$m[1].'`.'.$GLOBALS['tableprefix'];
				$addressee = $db->getRow("SELECT username, firstname, lastname from {$dbname}users WHERE id='{$idUser}'");
			}
			$context['data'] = $datas;
			$context['data']['sender'] = $sender['username']. ( ($sender['firstname'] || $sender['lastname']) ? " ({$sender['firstname']} {$sender['lastname']})" : '');
			$context['data']['addressee'] = $addressee['username']. ( ($addressee['firstname'] || $addressee['lastname']) ? " ({$addressee['firstname']} {$addressee['lastname']})" : '');
			// nouveau message, on update son status � 0 == lu
			if(1 == (int)$datas['status'] && $this->_iduser == $datas['addressee']) {
				$db->execute(lq("UPDATE #_MTP_internal_messaging SET status = 0 WHERE id = '{$id}' AND addressee = '{$this->_iduser}'")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
			}
		}
		return '_ok';
	}

	public function viewAction(&$context, &$error)
	{
		usemaindb();
		return parent::viewAction($context, $error);
	}

	public function editAction(&$context, &$error)
	{
		global $db, $lodeluser;
		
		if (!$this->validateFields($context, $error)) {
			return '_error';
		}
		
		$addressees = array();
		foreach($context['addressees'] as $users) {
			$arr = explode(':', $users);
			foreach($arr as $user) {
				// on �vite les doublons (ex: tous les admins + admin s�lectionn�)
				if($user != '' && !in_array($user, $addressees))
					$addressees[] = strtr($user, array('allsite'=>'', 'alllodelmain'=>'lodelmain'));
			}
		}

		usemaindb();
		// get the dao for working with the object
		$dao = $this->_getMainTableDAO();
		if ($lodeluser['rights'] < $dao->rights['write']) {
			trigger_error('ERROR: you don\'t have the right to send internal message', E_USER_ERROR);
		}
		$context['subject'] = addslashes($context['subject']);
		$context['body'] = addslashes($context['body']);
		$requetes = '';
		$context['addressees'] = array();
		$context['addressees'] = join(':', $addressees);
		foreach($addressees as $k=>$addressee) {
			if($context['idparent']) {
				$subject = addslashes($db->getOne(lq("SELECT subject FROM #_MTP_internal_messaging WHERE id='{$context['idparent']}' AND addressee = '{$this->_iduser}'")));
				if($subject)
					$idparent = $db->getOne(lq("SELECT id FROM #_MTP_internal_messaging WHERE subject='{$subject}' AND addressee='{$addressee}' AND addressee = '{$this->_iduser}'"));
				else $idparent=0;
			} else $idparent=0;
			$requetes .= "('{$idparent}', '{$this->_iduser}', '{$addressee}', ':{$context['addressees']}:', '{$context['subject']}', '{$context['body']}', '{$context['cond']}', NOW(), '1'),";
		}
		$requetes = substr_replace($requetes, '', -1);
		$db->execute(lq("INSERT INTO #_MTP_internal_messaging (idparent, iduser, addressee, addressees, subject, body, cond, incom_date, status) VALUES {$requetes}")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
		unset($context['idparent']);
		update();
		return "_location: index.php?do=list&lo=internal_messaging&msgsended=1&directory=".$context['directory'];
	}

	// begin{publicfields} automatic generation  //

	/**
	 * Retourne la liste des champs publics
	 * @access private
	 */
	protected function _publicfields() 
	{
		return array(
				'addressees' => array('multipleselect', '+'),
				'subject' => array('text', ''),
				'body' => array('longtext', '+'),
				'cond' => array('boolean', '+'));
	}

	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //


	// end{uniquefields} automatic generation  //

}// end of Internal_MessagingLogic class
?>