<?php
/**
 * Implementation of view class
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Parent class for all view classes
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Common {
	var $theme;

	var $params;

//	var $settings;

	function __construct($params, $theme='blue') {
		$this->theme = $theme;
		$this->params = $params;
	}

	function setParams($params) {
		$this->params = $params;
	}

	function setParam($name, $value) {
		$this->params[$name] = $value;
	}

	function unsetParam($name) {
		if(isset($this->params[$name]))
			unset($this->params[$name]);
	}

/*
	function setConfiguration($conf) {
		$this->settings = $conf;
	}
*/

	function show() {
	}

	/**
	 * Call a hook with a given name
	 *
	 * Checks if a hook with the given name and for the current view
	 * exists and executes it. The name of the current view is taken
	 * from the current class name by lower casing the first char.
	 * This function will execute all registered hooks in the order
	 * they were registered.
	 *
	 * @params string $hook name of hook
	 * @return mixed whatever the hook function returns
	 */
	function callHook($hook) {
		$tmp = explode('_', get_class($this));
		$ret = null;
		if(isset($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp[2])])) {
			foreach($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp[2])] as $hookObj) {
				if (method_exists($hookObj, $hook)) {
					switch(func_num_args()) {
						case 1:
							$tmpret = $hookObj->$hook($this);
							if(is_string($tmpret))
								$ret .= $tmpret;
							break;
						case 2:
							$tmpret = $hookObj->$hook($this, func_get_arg(1));
							if(is_string($tmpret))
								$ret .= $tmpret;
							break;
						case 3:
						default:
							$tmpret = $hookObj->$hook($this, func_get_arg(1), func_get_arg(2));
							if(is_string($tmpret))
								$ret .= $tmpret;
					}
				}
			}
		}
		return $ret;
	}
}
?>
