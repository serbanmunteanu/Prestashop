<?php
if (!defined('_PS_VERSION_'))
	exit;

class RetargetingTracker extends Module
{
	public function __construct()
	{
		$this->name = 'retargetingtracker';
		$this->tab = 'analytics_stats';
		$this->version = '1.0.0';
		$this->author = 'Cosmin Atomei';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_); 
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('Retargeting Tracker');
		$this->description = $this->l('Module implementing Retargeting tracker functions and also giving access to our awesome triggers.');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		if (!Configuration::get('retargetingtracker_apikey') || 
			Configuration::get('retargetingtracker_apikey') == '') 
			$this->warning = $this->l('No Domain API Key provided');
	}

	public function install()
	{

		if (Shop::isFeatureActive()) Shop::setContext(Shop::CONTEXT_ALL);
		
		return parent::install() &&
			Configuration::updateValue('retargetingtracker_apikey', '') &&
			Configuration::updateValue('retargetingtracker_discountApikey', '') &&
			Configuration::updateValue('retargetingtracker_opt_visitHelpPage', '') &&
			$this->registerHook('displayHome') &&
			$this->registerHook('displayHeader') &&
			$this->registerHook('displayOrderConfirmation') &&
			$this->registerHook('actionAuthentication') &&
			$this->registerHook('actionCustomerAccountAdd');
	}

	public function uninstall()
	{
		return Configuration::deleteByName('retargetingtracker_apikey') && 
			Configuration::deleteByName('retargetingtracker_discountApikey') && 
			Configuration::deleteByName('retargetingtracker_opt_visitHelpPage') && 
			parent::uninstall();
	}

	public function getContent()
	{
		$output = null;

		if (Tools::isSubmit('submitBasicSettings'))
		{
			$ra_apikey = strval(Tools::getValue('retargetingtracker_apikey'));
			$ra_discountsApikey = strval(Tools::getValue('retargetingtracker_discountApikey'));
			
			Configuration::updateValue('retargetingtracker_apikey', $ra_apikey);
			Configuration::updateValue('retargetingtracker_discountApikey', $ra_discountsApikey);
			
			$output .= $this->displayConfirmation($this->l('Settings updated! Enjoy!'));
		}
		else if (Tools::isSubmit('submitTrackerOptions'))
		{
			$ra_opt_visitHelpPages = array();

			foreach (CMS::listCMS() as $key => $cmsPage) {
				$option = Tools::getValue('retargetingtracker_opt_visitHelpPage_' . $cmsPage['id_cms']);
				if ($option == "on") $ra_opt_visitHelpPages[] = $cmsPage['id_cms'];
			}
			
			Configuration::updateValue('retargetingtracker_opt_visitHelpPage', implode('|', $ra_opt_visitHelpPages));

			$output .= $this->displayConfirmation($this->l('Settings updated! Enjoy!'));
		}	
		return $output.$this->displayForm();
	}

	public function displayForm()
	{
		// Get default language
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

		// Init Fields form array
		$fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Basic Settings'),
			),
			'input' => array(
				array(
					'type' => 'text',
					'label' => $this->l('Domain API Key'),
					'name' => 'retargetingtracker_apikey',
					'desc' => 'You can find your Secure Domain API Key in your <a href="http://www.retargeting.biz">Retargeting</a> account.'
				),
				array(
					'type' => 'text',
					'label' => $this->l('Discounts API Key'),
					'name' => 'retargetingtracker_discountApikey',
					'desc' => 'You can find your Secure Discount API Key in your <a href="http://www.retargeting.biz">Retargeting</a> account.'
				),
			),
			'submit' => array(
				'name' => 'submitBasicSettings',
				'title' => $this->l('Save')
			)
		);
		$fields_form[1]['form'] = array(
			'legend' => array(
				'title' => $this->l('Tracker Options'),
			),
			'input' => array(
				array(
					'type' => 'checkbox',
					'label' => $this->l('Help Pages'),
					'name' => 'retargetingtracker_opt_visitHelpPage',
					'desc' => $this->l('Choose the pages on which the "visitHelpPage" event should fire.'),
					'values' => array(
						'query' => CMS::listCMS(),
						'id' => 'id_cms',
						'name' => 'meta_title'
					)
				),
			),
			'submit' => array(
				'name' => 'submitTrackerOptions',
				'title' => $this->l('Save')
			)
		);

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

		// Language
		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;

		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = true;
		$helper->toolbar_scroll = true;
		$helper->submit_action = 'submit'.$this->name;
		$helper->toolbar_btn = array(
			'save' =>
				array(
					'desc' => $this->l('Save'),
					'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
					'&token='.Tools::getAdminTokenLite('AdminModules'),
				),
			'back' => array(
				'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
				'desc' => $this->l('Back to list')
			)
		);

		// Load current value
		$helper->fields_value['retargetingtracker_apikey'] = Configuration::get('retargetingtracker_apikey');
		$helper->fields_value['retargetingtracker_discountApikey'] = Configuration::get('retargetingtracker_discountApikey');
		
		$options_visitHelpPages = explode('|', Configuration::get('retargetingtracker_opt_visitHelpPage'));
		foreach ($options_visitHelpPages as $key => $option) {
			$helper->fields_value['retargetingtracker_opt_visitHelpPage_' . $option] = true;
		}

		return $helper->generateForm($fields_form);
	}

	// embedding
	public function hookHeader()
	{
		$this->controller = Dispatcher::getInstance()->getController();

		// embedd RA.js
		$js_code = $this->_assignEmbedding();

		if (!$js_code) return;
		
		// setEmail
		if (isset($this->context->cookie->ra_setEmail))
		{
			$js_code .= urldecode(unserialize($this->context->cookie->ra_setEmail));
			unset($this->context->cookie->ra_setEmail);
		}

		// sendCategory
		if ($this->controller == "category")
		{
			$js_sendCategory = $this->_assignSendCategory();
			$js_code .= $js_sendCategory;
		}

		// sendBrand
		if ($this->controller == "manufacturer")
		{
			$js_sendBrand = $this->_assignSendBrand();
			$js_code .= $js_sendBrand;
		}

		// sendProduct
		if ($this->controller == "product")
		{
			$js_sendProduct = $this->_assignSendProduct();
			$js_code .= $js_sendProduct;
		}

		// visitHelpPages
		if ($this->controller == "cms")
		{
			$js_visitHelpPage = $this->_assignVisitHelpPage();
			$js_code .= $js_visitHelpPage;
		}

		// checkoutIds
		if ($this->controller == "order")
		{
			$js_checkoutIds = $this->_assignCheckoutIds();
			$js_code .= $js_checkoutIds;
		}

		// [block-begin] js that needs the DOM to be loaded
		$js_code .= '$(document).ready(function() {';

		// addToCart
		$js_addToCart = $this->_assignAddToCart($this->controller);
		$js_code .= $js_addToCart;

		// addToWishlist
		$js_addToWishlist = $this->_assignAddToWishlist();
		$js_code .= $js_addToWishlist;

		// setVariation, clickImage, commentOnProduct, mouseOverPrice, mouseOverAddToCart, likeFacebook
		if ($this->controller == "product") 
		{
			// setVariation
			$js_setVariation = $this->_assignSetVariation();
			$js_code .= $js_setVariation;

			// clickImage
			$js_clickImage = $this->_assignClickImage();
			$js_code .= $js_clickImage;

			// commentOnProduct
			$js_commentOnProduct = $this->_assignCommentOnProduct();
			$js_code .= $js_commentOnProduct;

			// mouseOverPrice
			$js_mouseOverPrice = $this->_assignMouseOverPrice();
			$js_code .= $js_mouseOverPrice;
		
			// mouseOverAddToCart
			$js_mouseOverAddToCart = $this->_assignMouseOverAddToCart();
			$js_code .= $js_mouseOverAddToCart;
		
			// likeFacebook
			$js_likeFacebook = $this->_assignLikeFacebook();
			$js_code .= $js_likeFacebook;
		}

		$js_code .= ' });';
		// [block-end]

		return $this->_runJs($js_code);
	}


	/**
	* Specific hooks
	* --------------------------------------------------------------
	*/

	/**
	* setEmail - hook for customer authentification (except registration)
	*/
	public function hookActionAuthentication()
	{
		$this->prepSetEmailJS();
	}

	/**
	* setEmail - hook for customer registration
	*/
	public function hookActionCustomerAccountAdd()
	{
		$this->prepSetEmailJS();
	}

	protected function prepSetEmailJS() {
		$customer = $this->context->customer;

		$js_code = '/* register */
			var _ra = _ra || {};
			_ra.setEmailInfo = {
				"email": "'.$customer->email.'",
				"name": "'.$customer->firstname.' '.$customer->lastname.'",
				"phone": "",
				"city": "",
				"sex": "'.$customer->id_gender.'"
			};
			
			if (_ra.ready !== undefined) {
				_ra.setEmail(_ra.setEmailInfo)
			}
		';

		$this->context->cookie->ra_setEmail = serialize(urlencode($js_code));
	}

	/**
	* saveOrder - hook for order confirmation
	*/
	public function hookOrderConfirmation($params)
	{
		$order = $params['objOrder'];
		$discounts = $order->getDiscounts();
		$customer = new Customer((int)$order->id_customer);
		$address = new Address((int)$order->id_address_delivery);

		if (Validate::isLoadedObject($order) && Validate::isLoadedObject($customer))
		{
			$orderProducts = array();
			$cart_instance = new Cart($order->id_cart);
			
			foreach ($cart_instance->getProducts() as $orderProduct)
			{
				$orderProduct_instance = new Product((int)$orderProduct['id_product']);
				$orderProducts[] = '{"id": "'.$orderProduct['id_product'].'", "quantity": '.$orderProduct['quantity'].', "price": '.$orderProduct_instance->getPrice(true, null, 2).', "variation_code": "'.$orderProduct['attributes_small'].'"}';
			}

			$orderProducts = '['.implode(', ', $orderProducts).']';

			$discountsCode = '';
			if (count($discounts) > 0)
			{	
				$discountsCode = array();
				foreach ($discounts as $key => $discount)
				{
					$cartRule = new CartRule((int)$discount['id_cart_rule']);
					$discountsCode[] = $cartRule->code;
				}
				$discountsCode = implode(', ', $discountsCode);
			}
			
			$js_code = 'var _ra = _ra || {};	
				_ra.saveOrderInfo = {
					"order_no": '.$order->id.',
					"lastname": "'.$address->lastname.'",
					"firstname": "'.$address->firstname.'",
					"email": "'.$customer->email.'",
					"phone": "'.($address->phone == '' ? $address->phone : $address->phone_mobile).'",
					"state": "'.(isset($address->id_state) ? State::getNameById($address->id_state) : '').'",
					"city": "'.$address->city.'",
					"address": "'.$address->address1.'",
					"discount": '.$order->total_discounts.',
					"discount_code": "'.$discountsCode.'",
					"shipping": '.$order->total_shipping.',
					"total": '.$order->total_paid.'
				};
				_ra.saveOrderProducts = '.$orderProducts.';

				if( _ra.ready !== undefined ){
					_ra.saveOrder(_ra.saveOrderInfo, _ra.saveOrderProducts);
				}
			';

			return $this->_runJs($js_code);
		}
	}

	/**
	* Functions _assign[::retargeting_trigger::]
	*/

	protected function _assignEmbedding()
	{
		$js_embedd = false;

		$ra_domain_api_key = Configuration::get('retargetingtracker_apikey'); 
		
		if ($ra_domain_api_key && $ra_domain_api_key != '')
		{
			$js_embedd = '(function(){
				var ra_key = "'.$ra_domain_api_key.'";
				var ra = document.createElement("script"); ra.type ="text/javascript"; ra.async = true; ra.src = ("https:" ==
				document.location.protocol ? "https://" : "http://") + "retargeting-data.eu/rajs/" + ra_key + ".js";
				var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ra,s);})();
			
			';
		}

		return $js_embedd;
	}

	protected function _assignSendCategory()
	{
		$category_instance = $this->context->controller->getCategory();
		$category_fields = $category_instance->getFields();
		
		$js_category = array();
		$arr_categoryBreadcrumb = array();

		if (Validate::isLoadedObject($category_instance))
		{
			$categoryTree = $category_instance->getParentsCategories();
			foreach ($categoryTree as $key => $categoryNode)
			{
				if ($categoryNode['is_root_category']) continue;
				else if ($key == 0 && ( (isset($categoryTree[$key + 1]) && $categoryTree[$key + 1]['is_root_category']) || !isset($categoryTree[$key + 1]) )) $js_category = '{ "id": "'.$categoryNode['id_category'].'", "name": "'.$categoryNode['name'].'", "parent": false';
				else if ($key == 0) $js_category = '{ "id": "'.$categoryNode['id_category'].'", "name": "'.$categoryNode['name'].'", "parent": "'.$categoryNode['id_parent'].'"';
				else if (isset($categoryTree[$key + 1]) && $categoryTree[$key + 1]['is_root_category']) $arr_categoryBreadcrumb[] = '{ "id": "'.$categoryNode['id_category'].'", "name": "'.$categoryNode['name'].'", "parent": false }';
				else $arr_categoryBreadcrumb[] = '{ "id": "'.$categoryNode['id_category'].'", "name": "'.$categoryNode['name'].'", "parent": "'.$categoryNode['id_parent'].'" }';
			}
		}

		$js_categoryBreadcrumb = '['.implode(', ', $arr_categoryBreadcrumb).']';

		$js_code = 'var _ra = _ra || {};
			_ra.sendCategoryInfo = '.$js_category.',
				"category_breadcrumb": '.$js_categoryBreadcrumb.'
			};
			
			if (_ra.ready !== undefined) {
				_ra.sendCategory(_ra.sendCategoryInfo);
			}
		';

		return $js_code;
	}

	protected function _assignSendBrand()
	{
		$js_code = '';

		$brand_instance = $this->context->controller->getManufacturer();
		
		if (isset($brand_instance))
		{
			$js_code = 'var _ra = _ra || {};
				_ra.sendBrandInfo = {
					"id": "'.$brand_instance->id_manufacturer.'",
					"name": "'.$brand_instance->name.'"
				};
				
				if (_ra.ready !== undefined) {
					_ra.sendBrand(_ra.sendBrandInfo);
				}
			';
		}

		return $js_code;
	}

	protected function _assignSendProduct()
	{
		$link_instance = new LinkCore();
		$product_instance = $this->context->controller->getProduct();
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

		$js_code = 'var _ra = _ra || {};
			_ra.sendProductInfo = {
				"id": "'.$product_fields['id_product'].'",
				"name": "'.$product_instance->name.'",
				"url": "'.$product_instance->getLink().'", 
			  	"img": "'.$link_instance->getImageLink($product_instance->link_rewrite, $product_fields['id_product'], 'large_default').'", 
			  	"price": '.$product_instance->getPriceWithoutReduct(true, null, 2).',
				"promo": '.($product_instance->getPriceWithoutReduct() > $product_instance->getPrice() ? $product_instance->getPrice(true, null, 2) : 0).',
				"stock": '.($product_instance->available_now == "In stock" ? 1 : 0).',
				"brand": '.($product_instance->manufacturer_name != "" ? '"'.$product_instance->manufacturer_name.'"' : "false").',
				"category": '.$js_category.',
				"category_breadcrumb": '.$js_categoryBreadcrumb.'
			};
			
			if (_ra.ready !== undefined) {
				_ra.sendProduct(_ra.sendProductInfo);
			}
		';

		return $js_code;
	}

	protected function _assignAddToCart($controller) 
	{	
		$js_code = '
			var _ra_ajaxCart_add = ajaxCart.add;
			ajaxCart.add = function(idProduct, idCombination, addedFromProductPage, callerElement, quantity, whishlist) {

				$.ajax({
					url: baseDir + "modules/retargetingtracker/ajax.php",
					type: "GET",
					data: "ajax=true&method=getAddToCartJS&type='.$controller.'&pid=" + idProduct + "&vid=" + idCombination,
					success: function(data) {
						var s = document.createElement("script");
						s.type = "text/javascript";
						s.text = data;
						$("head").append(s);
					}
				});
	
				return _ra_ajaxCart_add(idProduct, idCombination, addedFromProductPage, callerElement, quantity, whishlist);
			}
		';

		return $js_code;
	}

	protected function _assignSetVariation()
	{
		$js_code = 'function _ra_setVariation() {
				var pid = $("#product_page_product_id").val(),
					vid = $("#idCombination").val();
				if (pid !== null && vid > 0) {
					$.ajax({
						url: baseDir + "modules/retargetingtracker/ajax.php",
						type: "GET",
						data: "ajax=true&method=getSetVariationJS&pid=" + pid + "&vid=" + vid,
						success: function(data) {
							var s = document.createElement("script");
							s.type = "text/javascript";
							s.text = data;
							$("head").append(s);
						}
					});
				}
			}
			
			$(document).on("click", ".color_pick", _ra_setVariation);
			$(document).on("change", ".attribute_select", _ra_setVariation);
			$(document).on("click", ".attribute_radio", _ra_setVariation);
		';

		return $js_code;
	}

	protected function _assignAddToWishlist()
	{
		$js_code = 'var _ra_WishlistCart = WishlistCart;
			WishlistCart = function(id, action, id_product, id_product_attribute, quantity, id_wishlist) {
				_ra.addToWishlist(id_product);
				return _ra_WishlistCart(id, action, id_product, id_product_attribute, quantity, id_wishlist);
			}
		';

		return $js_code;
	}

	protected function _assignClickImage()
	{
		$js_code = 'function _ra_clickImage() {
				_ra.clickImage($("#product_page_product_id").val());
			}

			$(document).on("click", "#image-block", _ra_clickImage);
		';

		return $js_code;
	}

	protected function _assignCommentOnProduct()
	{
		$js_code = 'function _ra_commentOnProduct() {
				_ra.commentOnProduct($("#product_page_product_id").val());
			}

			$(document).on("click", "#submitNewMessage", _ra_commentOnProduct);
		';

		return $js_code;
	}

	protected function _assignMouseOverPrice()
	{
		$product_instance = $this->context->controller->getProduct();
		$product_fields = $product_instance->getFields();
		
		$js_code = 'function _ra_mouseOverPrice() {
				_ra.mouseOverPrice("'.$product_fields['id_product'].'", {
					"price": '.$product_instance->getPriceWithoutReduct(true, null, 2).',
					"promo": '.($product_instance->getPriceWithoutReduct() > $product_instance->getPrice() ? $product_instance->getPrice(true, null, 2) : 0).'
				});
			}

			$(document).on("mouseenter", "#our_price_display", _ra_mouseOverPrice);
		';

		return $js_code;
	}

	protected function _assignMouseOverAddToCart()
	{
		$product_instance = $this->context->controller->getProduct();
		$product_fields = $product_instance->getFields();

		$js_code = 'function _ra_mouseOverAddToCart() {
				_ra.mouseOverAddToCart("'.$product_fields['id_product'].'");
			}

			$(document).on("mouseenter", "#add_to_cart button", _ra_mouseOverAddToCart);
		';

		return $js_code;
	}

	protected function _assignLikeFacebook()
	{
		$js_code = 'if (typeof FB != "undefined") {
				FB.Event.subscribe("edge.create", function () {
					_ra.likeFacebook($("#product_page_product_id").val());
				});
			};
		';

		return $js_code;
	}

	protected function _assignVisitHelpPage()
	{
		$str_visitHelpPage = Configuration::get('retargetingtracker_opt_visitHelpPage');
		$arr_visitHelpPage = explode('|', $str_visitHelpPage);
		
		$currentCMSPageId = $this->context->controller->cms->id;

		$js_code = '';
		
		if (in_array($currentCMSPageId, $arr_visitHelpPage))
		{
			$js_code .= 'var _ra = _ra || {};
				_ra.visitHelpPageInfo = {
					"visit" : true
				}
				
				if (_ra.ready !== undefined) {
					_ra.visitHelpPage();
				}
			';
		}

		return $js_code;
	}

	protected function _assignCheckoutIds()
	{
		$cart_instance = $this->context->cart;
		$cartProducts = $cart_instance->getProducts();

		$arr_cartProducts = array();
		foreach ($cartProducts as $key => $product) {
			$arr_cartProducts[] = $product['id_product'];
		}

		$js_cartProducts = '['.implode(', ', $arr_cartProducts).']';

		$js_code = 'var _ra = _ra || {};
			_ra.checkoutIdsInfo = '.$js_cartProducts.';
			
			if (_ra.ready !== undefined) {
				_ra.checkoutIds(_ra.checkoutIdsInfo);
			}
		';

		return $js_code;
	}

	/**
	* Wrap JS to be written on page
	*/
	private function _runJs($js_code)
	{
		return '
		<script type="text/javascript" id="ra">
			'.$js_code.'
		</script>';
	}
}