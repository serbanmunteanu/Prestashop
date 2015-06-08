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

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '
<products>';

$ra_domain_api_key = Configuration::get('retargetingtracker_apikey'); 

if (Tools::getValue('key') != '' && Tools::getValue('key') == $ra_domain_api_key)
{
	$products = Product::getProducts((int)Context::getContext()->language->id, 0, 0, "id_product", "desc");
	foreach ($products as $product) {
		$link_instance = new LinkCore();
		$product_instance = new Product((int)$product['id_product']);
		$product_fields = $product_instance->getFields();

		echo '
		<product>
			<id>'.$product_fields['id_product'].'</id>
			<stock>'.($product_instance->available_now[1] == 'In stock' ? 1 : 0).'</stock>
			<price>'.$product_instance->getPriceWithoutReduct(true, null, 2).'</price>
			<promo>'.($product_instance->getPriceWithoutReduct() > $product_instance->getPrice() ? $product_instance->getPrice(true, null, 2) : 0).'</promo>
			<url>'.$product_instance->getLink().'</url>
			<image>'.$link_instance->getImageLink($product_instance->link_rewrite[1], $product_fields['id_product'], ImageType::getFormatedName('large')).'</image>
		</product>';

	}
}

echo '
</products>';
