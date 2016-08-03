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

function getThumbnailAddToCartJS($id) {
    $js_code = '';

    $link_instance = new LinkCore();
    $product = new Product((int)$id);
    $product_fields = $product->getFields();
    $category_instance = new Category($product->id_category_default);

    $js_category = 'false';
    $arr_categoryBreadcrumb = array();

    if (Validate::isLoadedObject($product)) {
        if (Validate::isLoadedObject($category_instance)) {
            if (_PS_VERSION_ >= '1.5') {
                $categoryTree = $category_instance->getParentsCategories();
                foreach ($categoryTree as $key => $categoryNode) {
                    if ($categoryNode['is_root_category']) {
                        continue;
                    } elseif ($key == 0 && ((isset($categoryTree[$key + 1]) && $categoryTree[$key + 1]['is_root_category']) || !isset($categoryTree[$key + 1]))) {
                        $js_category = ' "id": "' . $categoryNode['id_category'] . '", "name": "' . $categoryNode['name'] . '", "parent": false ';
                    } elseif ($key == 0) {
                        $js_category = ' "id": "' . $categoryNode['id_category'] . '", "name": "' . $categoryNode['name'] . '", "parent": "' . $categoryNode['id_parent'] . '" ';
                    } elseif (isset($categoryTree[$key + 1]) && $categoryTree[$key + 1]['is_root_category']) {
                        $arr_categoryBreadcrumb[] = '{ "id": "' . $categoryNode['id_category'] . '", "name": "' . $categoryNode['name'] . '", "parent": false }';
                    } else {
                        $arr_categoryBreadcrumb[] = '{ "id": "' . $categoryNode['id_category'] . '", "name": "' . $categoryNode['name'] . '", "parent": "' . $categoryNode['id_parent'] . '" }';
                    }
                }
            } else {
                $categoryTree = $category_instance->getParentsCategories();
                foreach ($categoryTree as $key => $categoryNode) {
                    if ($key == 0 && ((isset($categoryTree[$key + 1]) && $categoryTree[$key + 1]['level_depth'] < 1) || !isset($categoryTree[$key + 1]))) {
                        $js_category = ' "id": "' . $categoryNode['id_category'] . '", "name": "' . $categoryNode['name'] . '", "parent": false ';
                    } else if ($key == 0) {
                        $js_category = ' "id": "' . $categoryNode['id_category'] . '", "name": "' . $categoryNode['name'] . '", "parent": "' . $categoryNode['id_parent'] . '" ';
                    } else if ((isset($categoryTree[$key + 1]) && $categoryTree[$key + 1]['level_depth'] < 1) || !isset($categoryTree[$key + 1])) {
                        $arr_categoryBreadcrumb[] = '{ "id": "' . $categoryNode['id_category'] . '", "name": "' . $categoryNode['name'] . '", "parent": false }';
                    } else {
                        $arr_categoryBreadcrumb[] = '{ "id": "' . $categoryNode['id_category'] . '", "name": "' . $categoryNode['name'] . '", "parent": "' . $categoryNode['id_parent'] . '" }';
                    }
                }
            }
        }

        $js_categoryBreadcrumb = '[' . implode(', ', $arr_categoryBreadcrumb) . ']';
        $js_category = '[{ ' . $js_category . ', breadcrumb: ' . $js_categoryBreadcrumb . ' }]';

        $js_variation = 'false';
        $vid = Product::getDefaultAttribute((int)$id);
        if ($vid != 0) {
            if (_PS_VERSION_ >= '1.5') {
                $productAtttributes = Product::getAttributesParams((int)$id, (int)$vid);
                if (count($productAtttributes) > 0) {
                    $arr_variationCode = array();
                    $arr_variationDetails = array();
                    foreach ($productAtttributes as $productAtttribute) {
                        $productAtttribute['name'] = str_replace('-', ' ', $productAtttribute['name']);
                        $arr_variationCode[] = $productAtttribute['name'];
                        $arr_variationDetails[] = '"' . $productAtttribute['name'] . '": {
								"category_name": "' . $productAtttribute['group'] . '",
								"category": "' . $productAtttribute['group'] . '",
								"value": "' . $productAtttribute['name'] . '"
							}
							';
                    }
                    $js_variationCode = implode('-', $arr_variationCode);
                    $js_variationDetails = implode(', ', $arr_variationDetails);
                    $js_variation = '{
						"code": "' . $js_variationCode . '",
						"stock": ' . ($product->available_now == 'In stock' ? 1 : 0) . ',
						"details": {
							' . $js_variationDetails . '
						}
					}';
                }
            } else {
                $product = new Product($id, 1);
                $productAtttributes = $product->getAttributeCombinaisons(1);

                if (count($productAtttributes) > 0) {
                    $arr_variationCode = array();
                    $arr_variationDetails = array();

                    foreach ($productAtttributes as $productAtttribute) {
                        if ($productAtttribute['id_product_attribute'] == $vid) {
                            $productAtttribute['attribute_name'] = str_replace('-', ' ', $productAtttribute['attribute_name']);
                            $arr_variationCode[] = $productAtttribute['attribute_name'];
                            $arr_variationDetails[] = '"' . $productAtttribute['attribute_name'] . '": {
									"category_name": "' . $productAtttribute['group_name'] . '",
									"category": "' . $productAtttribute['group_name'] . '",
									"value": "' . $productAtttribute['attribute_name'] . '"
								}
								';
                        }
                    }
                    $js_variationCode = implode('-', $arr_variationCode);
                    $js_variationDetails = implode(', ', $arr_variationDetails);
                    $js_variation = '{
						"code": "' . $js_variationCode . '",
						"stock": ' . (Product::getQuantity($product_fields['id_product']) > 0 ? 1 : 0) . ',
						"details": {
							' . $js_variationDetails . '
						}
					}';
                }
            }
        }

        $id_image = Product::getCover($product_fields['id_product']);
        if (sizeof($id_image) > 0) {
            $image = new Image($id_image['id_image']);
            if (_PS_VERSION_ >= '1.5') {
                $product_image = retargetingProductImageBuildNew($image);
            } else {
                $product_image = retargetingProductImageBuilder($image);
            }
        } else {
            $product_image = $link_instance->getImageLink($product->link_rewrite, $product_fields['id_product'], ImageType::getFormatedName('large'));
        }

        if (_PS_VERSION_ >= '1.5') {
            $product_price = $product->getPriceWithoutReduct(true, null, 2);
            if ($product->getPriceWithoutReduct() > $product->getPrice()) {
                $product_promo = $product->getPrice(true, null, 2);
            } else {
                $product_promo = 0;
            }
            $product_stock = ($product->available_now == 'In stock' ? 1 : 0);
        } else {
            $product_price = $product->getPrice(true, null, 2, null, false, false);
            if ($product->getPrice(true, null, 2, null, false, false) > $product->getPrice(true, null, 2)) {
                $product_promo = $product->getPrice(true, null, 2);
            } else {
                $product_promo = 0;
            }
            $product_stock = (Product::getQuantity($product_fields['id_product']) > 0 ? 1 : 0);
        }

        $js_code = '_ra.sendProduct({
				"id": "' . $product_fields['id_product'] . '",
				"name": "' . (is_array($product->name) ? $product->name[1] : $product->name) . '",
				"url": "' . $product->getLink() . '", 
			  	"img": "' . $product_image . '", 
			  	"price": ' . $product_price . ',
				"promo": ' . $product_promo . ',
				"brand": ' . ($product->manufacturer_name != '' ? '"' . $product->manufacturer_name . '"' : 'false') . ',
				"category": ' . $js_category . ',
				"inventory": {
					"variations": false,
					"stock": ' . $product_stock . '
				}
			}, function() {
				_ra.addToCart("' . $product_fields['id_product'] . '", 1, ' . $js_variation . ');
			});
		';
    }

    return $js_code;
}

/**
 * @param $image
 * @return string
 */
function retargetingProductImageBuildNew($image) {
    $base_url = _PS_BASE_URL_;
    $theme_prod_dir = _THEME_PROD_DIR_;
    $imagePath = $image->getExistingImgPath();
    $imageName = ImageType::getFormatedName('large');
    return $base_url . $theme_prod_dir . $imagePath . "-" . $imageName . ".jpg";
}

/**
 * @param $image
 * @return string
 */
function retargetingProductImageBuilder($image) {
    return _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->id_product . "-" . $image->id_image . "-large.jpg";
}

function getProductAddToCartJS($id, $vid) {
    $js_variation = 'false';

    if (_PS_VERSION_ >= '1.5') {
        $product = new Product($id);
        $productAtttributes = Product::getAttributesParams((int)$id, (int)$vid);
        if (count($productAtttributes) > 0) {
            $arr_variationCode = array();
            $arr_variationDetails = array();
            foreach ($productAtttributes as $productAtttribute) {
                $productAtttribute['name'] = str_replace('-', ' ', $productAtttribute['name']);
                $arr_variationCode[] = $productAtttribute['name'];
                $arr_variationDetails[] = '"' . $productAtttribute['name'] . '": {
						"category_name": "' . $productAtttribute['group'] . '",
						"category": "' . $productAtttribute['group'] . '",
						"value": "' . $productAtttribute['name'] . '"
					}
					';
            }
            $js_variationCode = implode('-', $arr_variationCode);
            $js_variationDetails = implode(', ', $arr_variationDetails);
            $js_variation = '{
				"code": "' . $js_variationCode . '",
				"stock": ' . ($product->available_now == 'In stock' ? 1 : 0) . ',
				"details": {
					' . $js_variationDetails . '
				}
			}';
        }
    } else {
        $product = new Product($id, 1);
        $product_fields = $product->getFields();
        $productAtttributes = $product->getAttributeCombinaisons(1);

        if (count($productAtttributes) > 0) {
            $arr_variationCode = array();
            $arr_variationDetails = array();

            foreach ($productAtttributes as $productAtttribute) {
                if ($productAtttribute['id_product_attribute'] == $vid) {
                    $productAtttribute['attribute_name'] = str_replace('-', ' ', $productAtttribute['attribute_name']);
                    $arr_variationCode[] = $productAtttribute['attribute_name'];
                    $arr_variationDetails[] = '"' . $productAtttribute['attribute_name'] . '": {
							"category_name": "' . $productAtttribute['group_name'] . '",
							"category": "' . $productAtttribute['group_name'] . '",
							"value": "' . $productAtttribute['attribute_name'] . '"
						}
						';
                }
            }
            $js_variationCode = implode('-', $arr_variationCode);
            $js_variationDetails = implode(', ', $arr_variationDetails);
            $js_variation = '{
				"code": "' . $js_variationCode . '",
				"stock": ' . (Product::getQuantity($product_fields['id_product']) > 0 ? 1 : 0) . ',
				"details": {
					' . $js_variationDetails . '
				}
			}';
        }
    }

    $js_code = '_ra.addToCart("' . $id . '", 1, ' . $js_variation . ');';

    return $js_code;
}

function getSetVariationJS($id, $vid) {
    $js_variation = 'false';

    if (_PS_VERSION_ >= '1.5') {
        $product = new Product($id);
        $productAtttributes = Product::getAttributesParams((int)$id, (int)$vid);
        if (count($productAtttributes) > 0) {
            $arr_variationCode = array();
            $arr_variationDetails = array();
            foreach ($productAtttributes as $productAtttribute) {
                $productAtttribute['name'] = str_replace('-', ' ', $productAtttribute['name']);
                $arr_variationCode[] = $productAtttribute['name'];
                $arr_variationDetails[] = '"' . $productAtttribute['name'] . '": {
						"category_name": "' . $productAtttribute['group'] . '",
						"category": "' . $productAtttribute['group'] . '",
						"value": "' . $productAtttribute['name'] . '"
					}
					';
            }
            $js_variationCode = implode('-', $arr_variationCode);
            $js_variationDetails = implode(', ', $arr_variationDetails);
            $js_variation = '{
				"code": "' . $js_variationCode . '",
				"stock": ' . ($product->available_now == 'In stock' ? 1 : 0) . ',
				"details": {
					' . $js_variationDetails . '
				}
			}';
        }
    } else {
        $product = new Product($id, 1);
        $product_fields = $product->getFields();
        $productAtttributes = $product->getAttributeCombinaisons(1);

        if (count($productAtttributes) > 0) {
            $arr_variationCode = array();
            $arr_variationDetails = array();

            foreach ($productAtttributes as $productAtttribute) {
                if ($productAtttribute['id_product_attribute'] == $vid) {
                    $productAtttribute['attribute_name'] = str_replace('-', ' ', $productAtttribute['attribute_name']);
                    $arr_variationCode[] = $productAtttribute['attribute_name'];
                    $arr_variationDetails[] = '"' . $productAtttribute['attribute_name'] . '": {
							"category_name": "' . $productAtttribute['group_name'] . '",
							"category": "' . $productAtttribute['group_name'] . '",
							"value": "' . $productAtttribute['attribute_name'] . '"
						}
						';
                }
            }
            $js_variationCode = implode('-', $arr_variationCode);
            $js_variationDetails = implode(', ', $arr_variationDetails);
            $js_variation = '{
				"code": "' . $js_variationCode . '",
				"stock": ' . (Product::getQuantity($product_fields['id_product']) > 0 ? 1 : 0) . ',
				"details": {
					' . $js_variationDetails . '
				}
			}';
        }
    }

    $js_code = '_ra.setVariation("' . $id . '", ' . $js_variation . ');';

    return $js_code;
}

if (Tools::getValue('ajax') == 'true' && Tools::getValue('method')) {
    if (Tools::getValue('method') == 'getAddToCartJS' && Tools::getValue('pid') && Tools::getValue('type')) {
        if (Tools::getValue('type') == 'product' && Tools::getValue('vid')) {
            die(getProductAddToCartJS((int)Tools::getValue('pid'), (int)Tools::getValue('vid')));
        }
        die(getThumbnailAddToCartJS((int)Tools::getValue('pid')));
    } elseif (Tools::getValue('method') == 'getSetVariationJS' && Tools::getValue('pid') && Tools::getValue('vid')) {
        die(getSetVariationJS((int)Tools::getValue('pid'), (int)Tools::getValue('vid')));
    } elseif (Tools::getValue('method') == '') die('ERROR : No valid method selected.');
} else {
    die('ERROR: Invalid parametres.');
}
