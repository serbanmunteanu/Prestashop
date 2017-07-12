<?php
/**
 * 2014-2017 Retargeting SRL
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
 * @copyright 2014-2017 Retargeting SRL
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');

$ra_domain_api_key = Configuration::get('ra_apikey');

if ((Tools::getValue('key') != '' && Tools::getValue('key') == $ra_domain_api_key) || $ra_domain_api_key == 'allowFeed') {
    $products = Product::getProducts(1, 0, 0, "id_product", "desc");
    $retargetingFeed = array();
    
    foreach ($products as $product) {
        $retargetingFeed[] = array(
            'id' => $product_fields['id_product'],
            'price' => $product_price,
            'promo' => $product_promo,
            'promo_price_end_date' => null,
            'inventory' => array(
                'variations' => false,
                'stock' = > $product_stock
            ),
            'user_groups' => false,
            'product_availability' => null
        );
    }
    
    echo json_encode($retargetingFeed);
}    
