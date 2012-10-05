<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

include("../inc/inc.Settings.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassSession.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassEmail.php");

function _printMessage($heading, $message) {

	UI::htmlStartPage($heading, "login");
	UI::globalBanner();
	UI::pageNavigation($heading);
	UI::contentContainer($message);
	UI::htmlEndPage();
	return;
}

if (isset($_REQUEST["login"])) {
	$login = $_REQUEST["login"];
	$login = str_replace("*", "", $login);
}

if (!isset($login) || strlen($login)==0) {
	_printMessage(getMLText("login_error_title"),	"<p>".getMLText("login_not_given")."</p>\n".
		"<p><a href='".$settings->_httpRoot."op/op.Logout.php'>".getMLText("back")."</a></p>\n");
	exit;
}

$pwd = (string) $_POST["pwd"];
if (get_magic_quotes_gpc()) {
	$pwd = stripslashes($pwd);
}

$guestUser = $dms->getUser($settings->_guestID);
if ((!isset($pwd) || strlen($pwd)==0) && ($login != $guestUser->getLogin()))  {
	_printMessage(getMLText("login_error_title"),	"<p>".getMLText("login_error_text")."</p>\n".
		"<p><a href='".$settings->_httpRoot."op/op.Logout.php'>".getMLText("back")."</a></p>\n");
	exit;
}

//
// LDAP Sign In
//

/* new code by doudoux - TO BE TESTED */
if (isset($settings->_ldapBaseDN)) {
	$ldapSearchAttribut = "uid=";
	$tmpDN = "uid=".$login.",".$settings->_ldapBaseDN;
}

if (isset($settings->_ldapType))
{
    if ($settings->_ldapType==1)
    {
        $ldapSearchAttribut = "sAMAccountName=";
        $tmpDN = $login.'@'.$settings->_ldapAccountDomainName;
    }
} 
/* end of new code */


$user = false;
if (isset($settings->_ldapHost) && strlen($settings->_ldapHost)>0) {
	if (isset($settings->_ldapPort) && is_int($settings->_ldapPort)) {
		$ds = ldap_connect($settings->_ldapHost, $settings->_ldapPort);
	}
	else {
		$ds = ldap_connect($settings->_ldapHost);
	}
	if (!is_bool($ds)) {
		// Ensure that the LDAP connection is set to use version 3 protocol.
		// Required for most authentication methods, including SASL.
		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

		// try an anonymous bind first. If it succeeds, get the DN for the user.
		$bind = @ldap_bind($ds);
		$dn = false;
				
		/* new code by doudoux - TO BE TESTED */
	        if ($bind) {        
	            $search = ldap_search($ds, $settings->_ldapBaseDN, $ldapSearchAttribut.$login);
	            if (!is_bool($search)) {
	                $info = ldap_get_entries($ds, $search);
	                if (!is_bool($info) && $info["count"]>0) {
	                    $dn = $info[0]['dn'];
	                }
	            }
	        } 
		/* end of new code */
		
		/* old code */
		if ($bind) {
			$search = ldap_search($ds, $settings->_ldapBaseDN, "uid=".$login);
			if (!is_bool($search)) {
				$info = ldap_get_entries($ds, $search);
				if (!is_bool($info) && $info["count"]>0) {
					$dn = $info[0]['dn'];
				}
			}
		}
		/* end of old code */

		
		if (is_bool($dn)) {
			// This is the fallback position, in case the anonymous bind does not
			// succeed.
			
			/* new code by doudoux  - TO BE TESTED */
			$dn = $tmpDN;
			/* old code */
			//$dn = "uid=".$login.",".$settings->_ldapBaseDN; 
			
		}
		$bind = @ldap_bind($ds, $dn, $pwd);
		if ($bind) {
			// Successfully authenticated. Now check to see if the user exists within
			// the database. If not, add them in, but do not add their password.
			$user = $dms->getUserByLogin($login);
			if (is_bool($user) && !$settings->_restricted) {
				// Retrieve the user's LDAP information.
				
				
				/* new code by doudoux  - TO BE TESTED */
				$search = ldap_search($ds, $settings->_ldapBaseDN, $ldapSearchAttribut . $login); 
				/* old code */
				//$search = ldap_search($ds, $dn, "uid=".$login);
				
				if (!is_bool($search)) {
					$info = ldap_get_entries($ds, $search);
					if (!is_bool($info) && $info["count"]==1 && $info[0]["count"]>0) {
						$user = $dms->addUser($login, null, $info[0]['cn'][0], $info[0]['mail'][0], $settings->_language, $settings->_theme, "");
					}
				}
			}
			if (!is_bool($user)) {
				$userid = $user->getID();
			}
		}
		ldap_close($ds);
	}
}

if (is_bool($user)) {
	//
	// LDAP Authentication did not succeed or is not configured. Try internal
	// authentication system.
	//

	// Try to find user with given login.
	$user = $dms->getUserByLogin($login);
	if (!$user) {
		_printMessage(getMLText("login_error_title"),	"<p>".getMLText("login_error_text")."</p>\n".
									"<p><a href='".$settings->_httpRoot."op/op.Logout.php'>".getMLText("back")."</a></p>\n");
		exit;
	}

	$userid = $user->getID();

	if (($userid == $settings->_guestID) && (!$settings->_enableGuestLogin)) {
		_printMessage(getMLText("login_error_title"),	"<p>".getMLText("guest_login_disabled").
									"</p>\n<p><a href='".$settings->_httpRoot."op/op.Logout.php'>".getMLText("back")."</a></p>\n");
		exit;
	}

	// Check if password matches (if not a guest user)
	// Assume that the password has been sent via HTTP POST. It would be careless
	// (and dangerous) for passwords to be sent via GET.
	if (($userid != $settings->_guestID) && (md5($pwd) != $user->getPwd())) {
		_printMessage(getMLText("login_error_title"),	"<p>".getMLText("login_error_text").
									"</p>\n<p><a href='".$settings->_httpRoot."op/op.Logout.php'>".getMLText("back")."</a></p>\n");
		/* if counting of login failures is turned on, then increment its value */
		if($settings->_loginFailure) {
			$failures = $user->addLoginFailure();
			if($failures >= $settings->_loginFailure)
				$user->setDisabled(true);
		}
		exit;
	}

	// Check if account is disabled
	if($user->isDisabled()) {
		_printMessage(getMLText("login_disabled_title"),	"<p>".getMLText("login_disabled_text").
									"</p>\n<p><a href='".$settings->_httpRoot."op/op.Logout.php'>".getMLText("back")."</a></p>\n");
		exit;
	}
	
	// control admin IP address if required
	// TODO: extend control to LDAP autentication
	if ($user->isAdmin() && ($_SERVER['REMOTE_ADDR'] != $settings->_adminIP ) && ( $settings->_adminIP != "") ){
		_printMessage(getMLText("login_error_title"),	"<p>".getMLText("invalid_user_id").
									"</p>\n<p><a href='".$settings->_httpRoot."op/op.Logout.php'>".getMLText("back")."</a></p>\n");
		exit;
	}
	
	/* Clear login failures if login was successful */
	$user->clearLoginFailures();

}

// Capture the user's language and theme settings.
if (isset($_REQUEST["lang"]) && strlen($_REQUEST["lang"])>0 && is_numeric(array_search($_REQUEST["lang"],getLanguages())) ) {
	$lang = $_REQUEST["lang"];
	$user->setLanguage($lang);
}
else {
	$lang = $user->getLanguage();
	if (strlen($lang)==0) {
		$lang = $settings->_language;
		$user->setLanguage($lang);
	}
}
if (isset($_REQUEST["sesstheme"]) && strlen($_REQUEST["sesstheme"])>0 && is_numeric(array_search($_REQUEST["sesstheme"],UI::getStyles())) ) {
	$sesstheme = $_REQUEST["sesstheme"];
	$user->setTheme($sesstheme);
}
else {
	$sesstheme = $user->getTheme();
	if (strlen($sesstheme)==0) {
		$sesstheme = $settings->_theme;
		$user->setTheme($sesstheme);
	}
}

$session = new LetoDMS_Session($db);

// Delete all sessions that are more than 24 hours old. Probably not the most
// reliable place to put this check -- move to inc.Authentication.php?
if(!$session->deleteByTime(86400)) {
	_printMessage(getMLText("login_error_title"), "<p>".getMLText("error_occured").": ".$db->getErrorMsg()."</p>");
	exit;
}

// Create new session in database
if(!$id = $session->create(array('userid'=>$userid, 'theme'=>$sesstheme, 'lang'=>$lang))) {
	_printMessage(getMLText("login_error_title"), "<p>".getMLText("error_occured").": ".$db->getErrorMsg()."</p>");
	exit;
}

// Set the session cookie.
setcookie("mydms_session", $id, 0, $settings->_httpRoot);

// TODO: by the PHP manual: The superglobals $_GET and $_REQUEST  are already decoded. 
// Using urldecode() on an element in $_GET or $_REQUEST could have unexpected and dangerous results.

if (isset($_POST["referuri"]) && strlen($_POST["referuri"])>0) {
	$referuri = urldecode($_POST["referuri"]);
}
else if (isset($_GET["referuri"]) && strlen($_GET["referuri"])>0) {
	$referuri = urldecode($_GET["referuri"]);
}

add_log_line();

if (isset($referuri) && strlen($referuri)>0) {
	header("Location: http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'] . $referuri);
}
else {
	header("Location: ../".(isset($settings->_siteDefaultPage) && strlen($settings->_siteDefaultPage)>0 ? $settings->_siteDefaultPage : "out/out.ViewFolder.php?folderid=1"));
}

//_printMessage(getMLText("login_ok"),
//	"<p><a href='".$settings->_httpRoot.(isset($settings->_siteDefaultPage) && strlen($settings->_siteDefaultPage)>0 ? $settings->_siteDefaultPage : "out/out.ViewFolder.php")."'>".getMLText("continue")."</a></p>");

?>