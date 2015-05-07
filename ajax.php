<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

function getThumbnailAddToCartJS($id)
{
	$link_instance = new LinkCore();
	$product_instance = new Product((int)$id);
	$product_fields = $product_instance->getFields();
	$category_instance = new Category($product_instance->id_category_default);   

	$js_category = "false";
	$arr_categoryBreadcrumb = array();

	if (Validate::isLoadedObject($category_instance))
	{
		$categoryTree = $category_instance->getParentsCategories();
		foreach ($categoryTree as $key => $categoryNode)
		{
			if ($categoryNode['is_root_category']) continue;
			else if ($key == 0 && ( (isset($categoryTree[$key + 1]) && $categoryTree[$key + 1]['is_root_category']) || !isset($categoryTree[$key + 1]) )) $js_category = '{ "id": "'.$categoryNode['id_category'].'", "name": "'.$categoryNode['name'].'", "parent": false }';
			else if ($key == 0) $js_category = '{ "id": "'.$categoryNode['id_category'].'", "name": "'.$categoryNode['name'].'", "parent": "'.$categoryNode['id_parent'].'" }';
			else if (isset($categoryTree[$key + 1]) && $categoryTree[$key + 1]['is_root_category']) $arr_categoryBreadcrumb[] = '{ "id": "'.$categoryNode['id_category'].'", "name": "'.$categoryNode['name'].'", "parent": false }';
			else $arr_categoryBreadcrumb[] = '{ "id": "'.$categoryNode['id_category'].'", "name": "'.$categoryNode['name'].'", "parent": "'.$categoryNode['id_parent'].'" }';
		}
	}

	$js_categoryBreadcrumb = '['.implode(', ', $arr_categoryBreadcrumb).']';

	$js_variation = 'false';
	$vid = Product::getDefaultAttribute((int)$id);
	if ($vid != 0)
	{
		$productAtttributes = Product::getAttributesParams((int)$id, (int)$vid);
		if (count($productAtttributes) > 0)
		{
			$arr_variationCode = array();
			$arr_variationDetails = array();
			foreach ($productAtttributes as $key => $productAtttribute) {
				$arr_variationCode[] = $productAtttribute['name'];
				$arr_variationDetails[] = '"'.$productAtttribute['name'].'": {
						"category_name": "'.$productAtttribute['group'].'",
						"category": "'.$productAtttribute['group'].'",
						"value": "'.$productAtttribute['name'].'"
					}';
			}
			$js_variationCode = implode('-', $arr_variationCode);
			$js_variationDetails = implode(', ', $arr_variationDetails);
			$js_variation = '{
				"code": "'.$js_variationCode.'",
				"details": {
					'.$js_variationDetails.'
				}
			}';
		}
	}

	$js_code = '_ra.sendProduct({
			"id": "'.$product_fields['id_product'].'",
			"name": "'.$product_instance->name[1].'",
			"url": "'.$product_instance->getLink().'", 
		  	"img": "'.$link_instance->getImageLink($product_instance->link_rewrite[1], $product_fields['id_product'], 'large_default').'", 
		  	"price": "'.$product_instance->getPrice(true, null, 2).'",
			"promo": "'.($product_instance->getPriceWithoutReduct() > $product_instance->getPrice() ? $product_instance->getPrice() : 0).'",
			"stock": '.($product_instance->available_now[1] == "In stock" ? 1 : 0).',
			"brand": '.($product_instance->manufacturer_name != "" ? '"'.$product_instance->manufacturer_name.'"' : "false").',
			"category": '.$js_category.',
			"category_breadcrumb": '.$js_categoryBreadcrumb.'
		}, function() {
			_ra.addToCart("'.$product_fields['id_product'].'", '.$js_variation.');
		});
	';

	return $js_code;
}

function getProductAddToCartJS($id, $vid)
{	
	$js_variation = 'false';
	
	$productAtttributes = Product::getAttributesParams((int)$id, (int)$vid);
	if (count($productAtttributes) > 0)
	{
		$arr_variationCode = array();
		$arr_variationDetails = array();
		foreach ($productAtttributes as $key => $productAtttribute) {
			$arr_variationCode[] = $productAtttribute['name'];
			$arr_variationDetails[] = '"'.$productAtttribute['name'].'": {
					"category_name": "'.$productAtttribute['group'].'",
					"category": "'.$productAtttribute['group'].'",
					"value": "'.$productAtttribute['name'].'"
				}
				';
		}
		$js_variationCode = implode('-', $arr_variationCode);
		$js_variationDetails = implode(', ', $arr_variationDetails);
		$js_variation = '{
			"code": "'.$js_variationCode.'",
			"details": {
				'.$js_variationDetails.'
			}
		}';
	}

	$js_code = '_ra.addToCart("'.$id.'", '.$js_variation.');';

	return $js_code;
}

function getSetVariationJS($id, $vid)
{
	$js_variation = 'false';
	
	$productAtttributes = Product::getAttributesParams((int)$id, (int)$vid);
	if (count($productAtttributes) > 0)
	{
		$arr_variationCode = array();
		$arr_variationDetails = array();
		foreach ($productAtttributes as $key => $productAtttribute) {
			$arr_variationCode[] = $productAtttribute['name'];
			$arr_variationDetails[] = '"'.$productAtttribute['name'].'": {
					"category_name": "'.$productAtttribute['group'].'",
					"category": "'.$productAtttribute['group'].'",
					"value": "'.$productAtttribute['name'].'"
				}
				';
		}
		$js_variationCode = implode('-', $arr_variationCode);
		$js_variationDetails = implode(', ', $arr_variationDetails);
		$js_variation = '{
			"code": "'.$js_variationCode.'",
			"details": {
				'.$js_variationDetails.'
			}
		}';
	}

	$js_code = '_ra.setVariation("'.$id.'", '.$js_variation.');';

	return $js_code;
}

if (isset($_GET['ajax']) && $_GET['ajax'] == 'true' && isset($_GET['method']))
{
	if ($_GET['method'] == 'getAddToCartJS' && isset($_GET['pid']) && isset($_GET['type'])) 
	{
		if ($_GET['type'] == 'product' && isset($_GET['vid'])) die(getProductAddToCartJS($_GET['pid'], $_GET['vid']));
		die(getThumbnailAddToCartJS($_GET['pid']));
	}
	else if ($_GET['method'] == 'getSetVariationJS' && isset($_GET['pid']) && isset($_GET['vid'])) 
	{
		die(getSetVariationJS($_GET['pid'], $_GET['vid']));
	}
	die('ERROR : No valid method selected.');
}
else die('ERROR : Invalid parametres.');

