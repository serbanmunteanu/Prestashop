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

include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '
<products>';

$ra_domain_api_key = Configuration::get('ra_apikey');

if ((Tools::getValue('key') != '' && Tools::getValue('key') == $ra_domain_api_key) || $ra_domain_api_key == 'allowFeed') {
    $products = Product::getProducts(1, 0, 0, "id_product", "desc");
    foreach ($products as $product) {
        $link_instance = new LinkCore();
        $product_instance = new Product((int)$product['id_product']);
        $product_fields = $product_instance->getFields();

        $product_image = '';
        $id_image = Product::getCover($product_fields['id_product']);
        if (sizeof($id_image) > 0) {
            $image = new Image($id_image['id_image']);
            if (_PS_VERSION_ >= '1.5') {
                $product_image = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . "-" . ImageType::getFormatedName('large') . ".jpg";
            } else {
                $product_image = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->id_product . "-" . $image->id_image . "-large.jpg";
            }
        } else {
            $product_image = $link_instance->getImageLink($product_instance->link_rewrite, $product_fields['id_product'], ImageType::getFormatedName('large'));
        }

        if (_PS_VERSION_ >= '1.5') {
            $product_price = $product_instance->getPriceWithoutReduct(true, null, 2);
            $product_promo = ($product_instance->getPriceWithoutReduct() > $product_instance->getPrice() ? $product_instance->getPrice(true, null, 2) : 0);

            $product_stock = ($product_instance->available_now == 'In stock' ? 1 : 0);
        } else {
            $product_price = $product_instance->getPrice(true, null, 2, null, false, false);
            $product_promo = ($product_instance->getPrice(true, null, 2, null, false, false) > $product_instance->getPrice(true, null, 2) ? $product_instance->getPrice(true, null, 2) : 0);

            $product_stock = (Product::getQuantity($product_fields['id_product']) > 0 ? 1 : 0);
        }

        // echo '
        // <product>
        // 	<id>'.$product_fields['id_product'].'</id>
        // 	<inventory>
        // 		<variations>0</variations>
        // 		<stock>'.$product_stock.'</stock>
        // 	</inventory>
        // 	<price>'.$product_price.'</price>
        // 	<promo>'.$product_promo.'</promo>
        // 	<url>'.$product_instance->getLink().'</url>
        // 	<image>'.$product_image.'</image>
        // </product>';

        echo '
		<product>
			<id>' . $product_fields['id_product'] . '</id>
			<price>' . $product_price . '</price>
			<promo>' . $product_promo . '</promo>
			<inventory>
				<variations>0</variations>
				<stock>' . $product_stock . '</stock>
			</inventory>
		</product>';
    }
}

echo '
</products>';
