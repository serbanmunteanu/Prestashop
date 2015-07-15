<?php
/**
 * 2014-2015 Retargeting SRL
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@retargeting.biz so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    Retargeting SRL <info@retargeting.biz>
 * @copyright 2014-2015 Retargeting SRL
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

$ra_domain_api_key = Configuration::get('ra_apikey'); 

$key = Tools::getValue('key');
$type = Tools::getValue('type');
$value = Tools::getValue('value');
$count = Tools::getValue('count');

if ($key != '' && $key == $ra_domain_api_key && !is_bool($type) && !is_bool($value) && !is_bool($count))
{
	$generatedCodes = array();

	for ($i = $count; $i > 0; $i --)
	{

		$cart_rule = new CartRuleCore();
		
		$cart_rule_code = '';
		while ($cart_rule_code == '' || CartRuleCore::getIdByCode($cart_rule_code)) $cart_rule_code = Tools::strtoupper(Tools::passwdGen(8));

		$cart_rule_name = 'RA-' . $type . '-' . $value . '-' . $cart_rule_code;
		
		$array = array();
		$languages = Language::getLanguages(); 
		foreach ($languages as $key => $language) $array[$language['id_lang']] = $cart_rule_name;
		
		$cart_rule->name = $array;
		$cart_rule->description = 'Cart rule created by Retargeting: RA-' . $type . '-' . $value . '-' . $cart_rule_code;
		$cart_rule->code = $cart_rule_code;   
		$cart_rule->active = 1;
		$cart_rule->date_from = date('Y-m-d h:i:s');
		$cart_rule->date_to = date('Y-m-d h:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y') + 1));
		$cart_rule->quantity = 1;
		$cart_rule->quantity_per_user = 1;
		$cart_rule->partial_use = false;
		
		if ($type == 0) 
		{
			$cart_rule->reduction_amount = $value;
		} 
		else if ($type == 1) 
		{
			$cart_rule->reduction_percent = $value;
		} 
		else if ($type == 2) 
		{
			$cart_rule->free_shipping = true;
		}
		else 
		{
			echo Tools::jsonEncode(array(
				'status' => false,
				'error' => '0003: Invalid Parameters!'
			));
			return false;
		}

		$cart_rule->add();

		$generatedCodes[] = $cart_rule->code;
	}

	echo Tools::jsonEncode($generatedCodes);
	return true;
}
else
{
	echo Tools::jsonEncode(array(
		'status' => false,
		'error' => '0002: Invalid Parameters!'
	));
	return false;
}