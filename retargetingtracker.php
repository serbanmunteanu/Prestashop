<?php
/**
 * 2014-2017 Retargeting BIZ SRL
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

if (!defined('_PS_VERSION_')) {
    exit;
}



include(dirname(__FILE__) . '/lib/retargeting-rest-api/Client.php');

class RetargetingTracker extends Module
{   
    const ELEMENT = '<div id="%s"><img src="http://i0.kym-cdn.com/entries/icons/original/000/021/807/4d7.png"></div>';

       public function __construct()
    {

        $this->name = 'retargetingtracker';
        $this->tab = 'analytics_stats';
        $this->version = '1.0.6';
        $this->author = 'Retargeting Team';
        $this->module_key = '07f632866f76537ce3f8f01eedad4f00';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Retargeting Tracker');
        $this->description = $this->l('Retargeting is a marketing automation tool that boosts the conversion rate and sales of your online store.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('ra_apikey') || Configuration::get('ra_apikey') == '') {
            $this->warning = $this->l('No Tracking API Key provided');
        }

        /* Backward compatibility */
        if (_PS_VERSION_ < '1.5') {
            require(_PS_MODULE_DIR_ . $this->name . '/backward_compatibility/backward.php');
        }
    }

    public function install()
    {
        if (_PS_VERSION_ >= '1.6' && Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (_PS_VERSION_ >= '1.6') {
            return parent::install() &&
            Configuration::updateValue('ra_apikey', '') &&
            Configuration::updateValue('ra_token', '') &&
            Configuration::updateValue('ra_productFeedUrl', '') &&
            Configuration::updateValue('ra_discountApiUrl', '') &&
            Configuration::updateValue('ra_opt_visitHelpPage', '') &&
            Configuration::updateValue('ra_mediaServerProtocol', 'http://') &&
            Configuration::updateValue('ra_qs_addToCart', '') &&
            Configuration::updateValue('ra_qs_variation', '') &&
            Configuration::updateValue('ra_qs_addToWishlist', '') &&
            Configuration::updateValue('ra_qs_productImages', '') &&
            Configuration::updateValue('ra_qs_review', '') &&
            Configuration::updateValue('ra_qs_price', '') &&
            Configuration::updateValue('ra_qs_oldPrice', '') &&
            Configuration::updateValue('ra_checkout_url', '') &&
            Configuration::updateValue('ra_init', 'false') &&
            $this->registerHook('displayHome') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayOrderConfirmation') &&
            $this->registerHook('actionAuthentication') &&
            $this->registerHook('actionCustomerAccountAdd') &&
            $this->registerHook('displayReHome') &&
            $this->registerHook('displayCategory') &&
            $this->registerHook('displayProduct') &&
            $this->registerHook('displayCheckout') &&
            $this->registerHook('displayThankyou') &&
            $this->registerHook('displayOutOfStock') &&
            $this->registerHook('displaySearch') &&
            $this->registerHook('display404') ;
        } else {
            return parent::install() &&
            Configuration::updateValue('ra_apikey', '') &&
            Configuration::updateValue('ra_token', '') &&
            Configuration::updateValue('ra_productFeedUrl', '') &&
            Configuration::updateValue('ra_discountApiUrl', '') &&
            Configuration::updateValue('ra_opt_visitHelpPage', '') &&
            Configuration::updateValue('ra_qs_addToCart', '') &&
            Configuration::updateValue('ra_qs_variation', '') &&
            Configuration::updateValue('ra_qs_addToWishlist', '') &&
            Configuration::updateValue('ra_qs_productImages', '') &&
            Configuration::updateValue('ra_qs_review', '') &&
            Configuration::updateValue('ra_qs_price', '') &&
            Configuration::updateValue('ra_qs_oldPrice', '') &&
            Configuration::updateValue('ra_checkout_url', '') &&
            Configuration::updateValue('ra_init', 'false') &&
            $this->registerHook('header') &&
            $this->registerHook('orderConfirmation') &&
            $this->registerHook('authentication') &&
            $this->registerHook('createAccount') &&
            $this->registerHook('displayReHome') &&
            $this->registerHook('displayCategory') &&
            $this->registerHook('displayProduct') &&
            $this->registerHook('displayCheckout') &&
            $this->registerHook('displayThankyou') &&
            $this->registerHook('displayOutOfStock') &&
            $this->registerHook('displaySearch') &&
            $this->registerHook('display404') ;
        }
    }

    public function uninstall()
    {
        return Configuration::deleteByName('ra_apikey') &&
        Configuration::deleteByName('ra_token') &&
        Configuration::deleteByName('ra_productFeedUrl') &&
        Configuration::deleteByName('ra_discountApiUrl') &&
        Configuration::deleteByName('ra_opt_visitHelpPage') &&
        Configuration::deleteByName('ra_mediaServerProtocol') &&
        Configuration::deleteByName('ra_qs_addToCart', '') &&
        Configuration::deleteByName('ra_qs_variation', '') &&
        Configuration::deleteByName('ra_qs_addToWishlist', '') &&
        Configuration::deleteByName('ra_qs_productImages', '') &&
        Configuration::deleteByName('ra_qs_review', '') &&
        Configuration::deleteByName('ra_qs_price', '') &&
        Configuration::deleteByName('ra_qs_oldPrice', '') &&
        Configuration::deleteByName('ra_checkout_url', '') &&
        Configuration::deleteByName('ra_init') &&
        parent::uninstall();
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submitDisableInit')) {
            if ((int)Tools::getValue('ra_init')) {
                Configuration::updateValue('ra_init', 'true');
            }
        } else if (Tools::isSubmit('submitBasicSettings')) {
            $ra_apikey = (string)Tools::getValue('ra_apikey');
            $ra_token = (string)Tools::getValue('ra_token');
            $ra_mediaServerProtocol = (string)Tools::getValue('ra_mediaServerProtocol');

            Configuration::updateValue('ra_apikey', $ra_apikey);
            Configuration::updateValue('ra_token', $ra_token);
            Configuration::updateValue('ra_mediaServerProtocol', $ra_mediaServerProtocol);


            $output .= $this->displayConfirmation($this->l('Settings updated! Enjoy!'));
        } else if (Tools::isSubmit('submitTrackerOptions')) {
            $ra_opt_visitHelpPages = array();
            $ra_addToCart = (string)Tools::getValue('ra_qs_addToCart');
            $ra_variation = (string)Tools::getValue('ra_qs_variation');
            $ra_addToWishlist = (string)Tools::getValue('ra_qs_addToWishlist');
            $ra_productImages = (string)Tools::getValue('ra_qs_productImages');
            $ra_review = (string)Tools::getValue('ra_qs_review');
            $ra_price = (string)Tools::getValue('ra_qs_price');
            $ra_oldPrice = (string)Tools::getValue('ra_qs_oldPrice');
            $ra_checkout_url = (string)Tools::getValue('ra_checkout_url');

            foreach (CMS::listCMS() as $cmsPage) {
                $option = (string)Tools::getValue('ra_opt_visitHelpPage_' . $cmsPage['id_cms']);
                if ($option == 'on') {
                    $ra_opt_visitHelpPages[] = $cmsPage['id_cms'];
                }
            }

            Configuration::updateValue('ra_opt_visitHelpPage', implode('|', $ra_opt_visitHelpPages));
            Configuration::updateValue('ra_qs_addToCart', $ra_addToCart);
            Configuration::updateValue('ra_qs_variation', $ra_variation);
            Configuration::updateValue('ra_qs_addToWishlist', $ra_addToWishlist);
            Configuration::updateValue('ra_qs_productImages', $ra_productImages);
            Configuration::updateValue('ra_qs_review', $ra_review);
            Configuration::updateValue('ra_qs_price', $ra_price);
            Configuration::updateValue('ra_qs_oldPrice', $ra_oldPrice);
            Configuration::updateValue('ra_checkout_url', $ra_checkout_url);

            $output .= $this->displayConfirmation($this->l('Settings updated! Enjoy!'));
        }

        if (Configuration::get('ra_init') == 'false') {
            return $this->displayInitForm();
        } else {
            if (_PS_VERSION_ < '1.5') {
                return $this->displayFormManually();
            } else {
                return $this->displayForm();
            }
        }
    }

    public function displayInitForm()
    {
        // Form Tags
        $form = '
            <form id="configuration_form" class="initForm defaultForm form-horizontal retargetingtracker" action="' .
            $_SERVER['REQUEST_URI'] . '" method="post" enctype="multipart/form-data" novalidate="">
        ';

        // Basic Settings
        $form .= '
            <section class="init">

                <input type="hidden" name="ra_init" value="1">

                <article>
                    <!--<img src="imgs/logo-big.jpg">-->
                    <h1>Hello!</h1>
                    <h2>To have access to our awesome features you need a <a href="https://retargeting.biz" target="_blank" rel="noopener noreferrer">Retargeting account</a>.</h2>
                    <div class="ra_row">
                        <button type="submit" value="1" id="configuration_form_submit_btn" name="submitDisableInit" class="btn-init btn-disableInit">I already have an account</button>
                        <a href="https://retargeting.biz/signup" target="_blank" rel="noopener noreferrer"><div class="btn-init btn-cta">Start your 14-day Free Trial</div></a>
                    </div>
                </article>
            
            </section>

<link href="//fonts.googleapis.com/css?family=Lato:300,400,700,900,300italic" rel="stylesheet" type="text/css">

<style>
section.init {
    position: relative;
    width: 100%;
    height: 400px;
    top: 0;
    left: 0;
}
section.init a, section.init a:hover {
    color: #48494F;
    font-weight: bold;
    text-decoration: none;
}
section.init article {
    position: absolute;
    max-width: 500px;
    height: 329px;
    padding: 0;
    background-color: white;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    margin: auto;
}
section.init img {
    margin: 0 auto;
    display: block;
}
section.init h1 {
    font-family: "Lato", sans-serif;
    font-size: 50px;
    font-weight: 900;
    margin: 80px 0 0 0;
    color: #48494F;
    text-align: center;
}
section.init h2 {
    font-family: "Lato", sans-serif;
    font-size: 20px;
    font-weight: 300;
    color: #48494F;
    text-align: center;
    line-height: 1.5em;
}
section.init .ra_row {
    position: relative;
    display: block;
    width: 100%;
    margin-top: 70px;
    overflow: auto;
}
section.init .btn-init {
    position: relative;
    display: block;
    width: 50%;
    padding: 15px 0px;
    border: none;
    color: #48494F;
    font-weight: bold;
    background-color: whitesmoke;
    float: left;
    cursor: pointer;
    margin: 0px;
    text-align: center;
}
section.init .btn-init.btn-cta {
    background-color: #F11A22;
    border-color: #F11A22;
    color: white;
}
</style>
        ';

        // Form Tags
        $form .= '</form>';

        return $form;
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form = array();

        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Basic Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Tracking API Key'),
                    'name' => 'ra_apikey',
                    'desc' => 'You can find your Secure Tracking API Key in your <a href="https://retargeting.biz/admin?action=api_redirect&token=5ac66ac466f3e1ec5e6fe5a040356997">Retargeting</a> account.'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('REST API Key'),
                    'name' => 'ra_token',
                    'desc' => 'You can find your Secure REST API Key in your <a href="https://retargeting.biz/admin?action=api_redirect&token=028e36488ab8dd68eaac58e07ef8f9bf">Retargeting</a> account.'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Media Server'),
                    'name' => 'ra_mediaServerProtocol',
                    'desc' => $this->l('If you\'re using media server, you\'ll have to set the http protocol for it so Retargeting can get the real image paths')),
            ),
            'submit' => array(
                'name' => 'submitBasicSettings',
                'title' => $this->l('Save')
            )
        );

        $fields_form[1]['form'] = array('legend' => array('title' => $this->l('Specific URLs'),), 'input' => array(array('type' => 'text', 'label' => $this->l('Product Feed URL'), 'name' => 'ra_productFeedUrl', 'desc' => '', 'disabled' => 'disabled'), array('type' => 'text', 'label' => $this->l('Discounts API URL'), 'name' => 'ra_discountApiUrl', 'desc' => '', 'disabled' => 'disabled'),),);

        $fields_form[2]['form'] = array(
            'legend' => array(
                'title' => $this->l('Tracker Options'),
                ),
            'input' => array(
                array(
                    'type' => 'checkbox',
                    'label' => $this->l('Help Pages'),
                    'name' => 'ra_opt_visitHelpPage',
                    'desc' => $this->l('Choose the pages on which the "visitHelpPage" event should fire.'),
                    'values' => array(
                        'query' => CMS::listCMS(),
                        'id' => 'id_cms', 'name' => 'meta_title'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Add To Cart Button'
                    ),
                    'name' => 'ra_qs_addToCart',
                    'desc' => '[Experimental] Query selector for the button used to add a product to cart.'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Product Variants Buttons'),
                    'name' => 'ra_qs_variation',
                    'desc' => '[Experimental] Query selector for the product options used to change the preferences of the product.'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Add To Wishlist Button'),
                    'name' => 'ra_qs_addToWishlist',
                    'desc' => '[Experimental] Query selector for the button used to add a product to wishlist.'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Product Images'),
                    'name' => 'ra_qs_productImages',
                    'desc' => '[Experimental] Query selector for the main product image on a product page.'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Submit Review Button'),
                    'name' => 'ra_qs_review',
                    'desc' => '[Experimental] Query selector for the button used to submit a comment/review for a product.'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Price'),
                    'name' => 'ra_qs_price',
                    'desc' => '[Experimental] Query selector for the main product price on a product page.'
                ),
            array(
                'type' => 'text',
                'label' => $this->l('Old Price'),
                'name' => 'ra_qs_oldPrice',
                'desc' => '[Experimental] Query selector for the main product price without discount on a product page.'
            ),
            array(
                'type'  =>  'text',
                'label' =>  $this->l('Checkout URL'),
                'name'  =>  'ra_checkout_url',
                'desc'  =>  'Checkout URL'
            ),
        ),
            'submit' => array(
                'name' => 'submitTrackerOptions',
                'title' => $this->l('Save')
            )
        );

        $helper = new HelperForm();

        // Module, Token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array('save' => array('desc' => $this->l('Save'), 'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),), 'back' => array('href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'), 'desc' => $this->l('Back to list')));

        // Load current value
        $helper->fields_value['ra_apikey'] = Configuration::get('ra_apikey');
        $helper->fields_value['ra_token'] = Configuration::get('ra_token');

        $helper->fields_value['ra_productFeedUrl'] = Configuration::get('ra_productFeedUrl') != '' ? Configuration::get('ra_productFeedUrl') : '/modules/retargetingtracker/productFeed.php';
        $helper->fields_value['ra_discountApiUrl'] = Configuration::get('ra_discountApiUrl') != '' ? Configuration::get('ra_discountApiUrl') : '/modules/retargetingtracker/discountsApi.php?params';

        $options_visitHelpPages = explode('|', Configuration::get('ra_opt_visitHelpPage'));
        foreach ($options_visitHelpPages as $option) {
            $helper->fields_value['ra_opt_visitHelpPage_' . $option] = true;
        }

        $helper->fields_value['ra_mediaServerProtocol'] = Configuration::get('ra_mediaServerProtocol');

        $helper->fields_value['ra_qs_addToCart'] = Configuration::get('ra_qs_addToCart');
        $helper->fields_value['ra_qs_variation'] = Configuration::get('ra_qs_variation');
        $helper->fields_value['ra_qs_addToWishlist'] = Configuration::get('ra_qs_addToWishlist');
        $helper->fields_value['ra_qs_productImages'] = Configuration::get('ra_qs_productImages');
        $helper->fields_value['ra_qs_review'] = Configuration::get('ra_qs_review');
        $helper->fields_value['ra_qs_price'] = Configuration::get('ra_qs_price');
        $helper->fields_value['ra_qs_oldPrice'] = Configuration::get('ra_qs_oldPrice');
        $helper->fields_value['ra_checkout_url'] = Configuration::get('ra_checkout_url');

        return $helper->generateForm($fields_form);
    }

    public function displayFormManually()
    {
        // Form Tags
        $form = '<form id="configuration_form" class="defaultForm form-horizontal retargetingtracker" action="' . $_SERVER['REQUEST_URI'] . '" method="post" enctype="multipart/form-data" novalidate="">';

        // Basic Settings
        $form .= '
            <input type="hidden" name="submitretargetingtracker" value="1">
            <fieldset>

                <legend> Basic Settings </legend>
                
                <label> Tracking API Key </label>
                <div class="margin-form">
                    <input type="text" name="ra_apikey" id="ra_apikey" value="' . Tools::getValue('ra_apikey', Configuration::get('ra_apikey')) . '" class="">
                    <p class="clear"> You can find your Secure Domain Tracking Key in your <a href="https://retargeting.biz/admin?action=api_redirect&amp;token=5ac66ac466f3e1ec5e6fe5a040356997">Retargeting</a> account. </p>
                </div>
           
                <label> REST API Key </label>
                <div class="margin-form">
                    <input type="text" name="ra_token" id="ra_token" value="' . Tools::getValue('ra_token', Configuration::get('ra_token')) . '" class="">
                    <p class="clear"> You can find your Secure REST API Key in your <a href="https://retargeting.biz/admin?action=api_redirect&amp;token=5ac66ac466f3e1ec5e6fe5a040356997">Retargeting</a> account. </p>
                </div>
                
                <center>
                    <button type="submit" value="1" id="configuration_form_submit_btn" name="submitBasicSettings" class="btn btn-default pull-right"> <i class="process-icon-save"></i> Save </button>
                </center>

            </fieldset>';

        // Specific URLs
        $form .= '
            <fieldset>

                <legend> Specific URLs </legend>

                <label> Product Feed URL </label>
                <div class="margin-form">
                    <input type="text" name="ra_productFeedUrl" id="ra_productFeedUrl" value="' . (Configuration::get('ra_productFeedUrl') != '' ? Configuration::get('ra_productFeedUrl') : '/modules/retargetingtracker/productFeed.php') . '" class="" disabled="disabled">
                </div>

                <label> Discounts API URL </label>
                <div class="margin-form">
                    <input type="text" name="ra_discountApiUrl" id="ra_discountApiUrl" value="' . (Configuration::get('ra_discountApiUrl') != '' ? Configuration::get('ra_discountApiUrl') : '/modules/retargetingtracker/discountsApi.php?params') . '" class="" disabled="disabled">
                </div>

            </fieldset>
            ';

        // Tracker Options
        $form .= '
            <fieldset>

                <legend> Tracker Options </legend>
                
                <label> Help Pages </label>
                <div class="margin-form">';

        $options_visitHelpPages = explode('|', Configuration::get('ra_opt_visitHelpPage'));
        $helpPagesChecked = array();
        foreach ($options_visitHelpPages as $option) {
            $helpPagesChecked['ra_opt_visitHelpPage_' . $option] = true;
        }

        foreach (CMS::listCMS() as $page) {
            $form .= '
            <div>
                    <input type="checkbox" name="ra_opt_visitHelpPage_' . $page['id_cms'] . '" id="ra_opt_visitHelpPage_' . $page['id_cms'] . '" class="" ' . (!empty($helpPagesChecked['ra_opt_visitHelpPage_' . $page['id_cms']]) ? 'checked="checked"' : 'notchecked') . '>
                    <label class="t" for="ra_opt_visitHelpPage_' . $page['id_cms'] . '">' . $page['meta_title'] . '</label>
            </div>
            ';
        }

        $form .= '
                    <p class="clear"> Choose the pages on which the "visitHelpPage" event should fire. </p>
                </div>

                <label> Add To Cart Button </label>
                <div class="margin-form">
                    <input type="text" name="ra_qs_addToCart" id="ra_qs_addToCart" value="' . Tools::getValue('ra_qs_addToCart', Configuration::get('ra_qs_addToCart')) . '" class="">
                    <p class="clear"> [Experimental] Query selector for the button used to add a product to cart. </p>
                </div>
                <label> Product Variants Buttons </label>
                <div class="margin-form">
                    <input type="text" name="ra_qs_variation" id="ra_qs_variation" value="' . Tools::getValue('ra_qs_variation', Configuration::get('ra_qs_variation')) . '" class="">
                    <p class="clear"> [Experimental] Query selector for the product options used to change the preferences of the product. </p>
                </div>
                <label> Add To Wishlist Button </label>
                <div class="margin-form">
                    <input type="text" name="ra_qs_addToWishlist" id="ra_qs_addToWishlist" value="' . Tools::getValue('ra_qs_addToWishlist', Configuration::get('ra_qs_addToWishlist')) . '" class="">
                    <p class="clear"> [Experimental] Query selector for the button used to add a product to wishlist. </p>
                </div>
                <label> Product Images </label>
                <div class="margin-form">
                    <input type="text" name="ra_qs_productImages" id="ra_qs_productImages" value="' . Tools::getValue('ra_qs_productImages', Configuration::get('ra_qs_productImages')) . '" class="">
                    <p class="clear"> [Experimental] Query selector for the main product image on a product page. </p>
                </div>
                <label> Submit Review Button </label>
                <div class="margin-form">
                    <input type="text" name="ra_qs_review" id="ra_qs_review" value="' . Tools::getValue('ra_qs_review', Configuration::get('ra_qs_review')) . '" class="">
                    <p class="clear"> [Experimental] Query selector for the button used to submit a comment/review for a product. </p>
                </div>
                <label> Price </label>
                <div class="margin-form">
                    <input type="text" name="ra_qs_price" id="ra_qs_price" value="' . Tools::getValue('ra_qs_price', Configuration::get('ra_qs_price')) . '" class="">
                    <p class="clear"> [Experimental] Query selector for the main product price on a product page. </p>
                </div>
                <label> Old Price </label>
                <div class="margin-form">
                    <input type="text" name="ra_oqs_ldPrice" id="ra_qs_oldPrice" value="' . Tools::getValue('ra_qs_oldPrice', Configuration::get('ra_qs_oldPrice')) . '" class="">
                    <p class="clear"> [Experimental] Query selector for the main product price without discount on a product page. </p>
                </div>
                <label> Checkout Cart URL </label>
                <div class="margin-form">
                    <input type="text" name="ra_checkout_url" id="ra_checkout_url" value="' . Tools::getValue('ra_checkout_url', Configuration::get('ra_checkout_url')) . '" class="">
                    <p class="clear"> If you have 3rd party plugin for checkout add here your cart URL. Must be the page where your clients can insert discount codes. (Default page is: http://yourdomain.com/order) </p>
                </div>
                
                <center>
                    <button type="submit" value="1" id="configuration_form_submit_btn_2" name="submitTrackerOptions" class="btn btn-default pull-right"> <i class="process-icon-save"></i> Save </button>
                </center>
                
                
            </fieldset>';

        // Form Tags
        $form .= '</form>';

        return $form;
    }

    /**
     * Triggers Embedding
     * ----------------------------------------------------------
     */
    public function hookHeader()
    {
        $this->controller = $this->getCurrentController();

        if (empty($this->controller)) {
            return '/*<script>console.info("Retargeting Info: Can\'t get current Controller details..");</script>*/';
        }

        // embedd RA.js
        $js_code = $this->_assignEmbedding();

        if (!$js_code) {
            return;
        }

        // setEmail
        if ($this->context->cookie->ra_setEmail != '') {
            $js_code .= urldecode(unserialize($this->context->cookie->ra_setEmail));
            unset($this->context->cookie->ra_setEmail);
        }

        // sendCategory
        if ($this->controller == 'category') {
            $js_sendCategory = $this->_assignSendCategory();
            $js_code .= $js_sendCategory;
        }

        // sendBrand
        if ($this->controller == 'manufacturer') {
            $js_sendBrand = $this->_assignSendBrand();
            $js_code .= $js_sendBrand;
        }

        // sendProduct
        if ($this->controller == 'product') {
            $js_sendProduct = $this->_assignSendProduct();
            $js_code .= $js_sendProduct;
        }

        // visitHelpPages
        if ($this->controller == 'cms') {
            $js_visitHelpPage = $this->_assignVisitHelpPage();
            $js_code .= $js_visitHelpPage;
        }

        //custom checkoutURL && checkoutIds
        if(Configuration::get('ra_checkout_url') != '') {
            $current_url = strtok(_PS_BASE_URL_.$_SERVER['REQUEST_URI'], '?');
            $custom_url = Configuration::get('ra_checkout_url');

            if($current_url == $custom_url) {
                $js_setCartUrl = $this->_assignSetCartUrl();
                $js_code .= $js_setCartUrl;
                $js_checkoutIds = $this->_assignCheckoutIds();
                $js_code .= $js_checkoutIds;

            }
        } elseif ($this->controller == 'order' || $this->controller == 'orderopc' || $this->controller == 'module-supercheckout-supercheckout') {
            $js_checkoutIds = $this->_assignCheckoutIds();
            $js_code .= $js_checkoutIds;
            $js_setCartUrl = $this->_assignSetCartUrl();
            $js_code .= $js_setCartUrl;
        }

        // [block-begin] js that needs the DOM to be loaded
        $js_code .= '$(function() {';

        // addToCart
        $js_addToCart = $this->_assignAddToCart($this->controller);
        $js_code .= $js_addToCart;

        // addToWishlist
        $js_addToWishlist = $this->_assignAddToWishlist();
        $js_code .= $js_addToWishlist;

        // setVariation, clickImage, commentOnProduct, mouseOverPrice, mouseOverAddToCart, likeFacebook
        if ($this->controller == 'product') {
            // setVariation
            $js_setVariation = $this->_assignSetVariation();
            $js_code .= $js_setVariation;

            // clickImage
            $js_clickImage = $this->_assignClickImage();
            $js_code .= $js_clickImage;

            // commentOnProduct
            $js_commentOnProduct = $this->_assignCommentOnProduct();
            $js_code .= $js_commentOnProduct;

            // likeFacebook
            $js_likeFacebook = $this->_assignLikeFacebook();
            $js_code .= $js_likeFacebook;
        }

        $js_code .= ' });';
        // [block-end]

        return $this->_runJs($js_code);
    }
    /*
          Function return type of page
          Exemple : xxxxx.xx/homepage , xxxxx.xx/productpage   

    */

   
    public static function get_id_page($id)
    {
       if($id)
       {
         return sprintf(self::ELEMENT,$id);
       }
    }

    /*
       
      ---------->Recommendation Engine for  Home Page <------------------

      */

      public function hookDisplayReHome() 
      {
         return self::get_id_page("retargeting-recommeng-home-page");
      }     
      
      /*
       
      ---------->Recommendation Engine for Category Page <-------------------

      */

      public function hookDisplayCategory() 
      {
         return self::get_id_page("retargeting-recommeng-category-page");
      }

       /*
       
      ---------->Recommendation Engine for  Product Page <------------------

      */

      public function hookDisplayProduct() 
      {
         return self::get_id_page("retargeting-recommeng-product-page");
      }
      
       /*
       
      ---------->Recommendation Engine for  Checkout Page <------------------

      */

      public function hookDisplayCheckout() 
      {
         return self::get_id_page("retargeting-recommeng-checkout-page");
      }
      
       /*
       
      ---------->Recommendation Engine for  Thankyou Page <------------------

      */

      public function hookDisplayThankyou() 
      {
         return self::get_id_page("retargeting-recommeng-thank-you-page");
      }
      
       /*
       
      ---------->Recommendation Engine for Out Of Stock Page <---------------

      */

      public function hookDisplayOutOfStock() 
      {
         return self::get_id_page("retargeting-recommeng-out-of-stock-page");
      }
      
      /*
       
      ---------->Recommendation Engine for Search Page <---------------

      */


      public function hookDisplaySearch() 
      {
         return self::get_id_page("retargeting-recommeng-search-page");
      }
      
      /*
       
      ---------->Recommendation Engine for Not Found Page <---------------

      */

      public function hookDisplay404() 
      {
         return self::get_id_page("retargeting-recommeng-not-found-page");
      }

    /**
     * Functions _assign[::retargeting_trigger::]
     * ----------------------------------------------------------
     */

    /**
     * Specific hooks
     * ----------------------------------------------------------
     */

    /**
     * setEmail - hook for customer authentification (except registration)
     */
    public function hookActionAuthentication()
    {
        $this->prepSetEmailJS();
    }

    /**
     * setEmail - hook for customer authentification (except registration)
     */
    public function hookAuthentication()
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

    /**
     * setEmail - hook for customer registration
     */
    public function hookCreateAccount()
    {
        $this->prepSetEmailJS();
    }

    protected function prepSetEmailJS()
    {
        $customer = $this->context->customer;
        $birthday = $this->context->customer->birthday;
        
        if ($birthday == 'null' || $birthday == '0000-00-00') {
            $setEmailBirthday = '';
        } else {
            $setEmailBirthday = date("d-m-Y", strtotime($birthday));  
        }

        $js_code = 'var _ra = _ra || {};
            _ra.setEmailInfo = {
                "email": "' . $customer->email . '",
                "name": "' . $customer->firstname . ' ' . $customer->lastname . '",
                "birthday": "' . $setEmailBirthday . '"
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
        $js_code = '';

        $order = $params['objOrder'];
        $discounts = $order->getCartRules();
        $customer = new Customer((int)$order->id_customer);
        $address = new Address((int)$order->id_address_delivery);
        $birthday = $this->context->customer->birthday;
        $homePhone = $address->phone;  
        $mobilePhone = $address->phone_mobile;
        
        if ($birthday == 'null' || $birthday == '0000-00-00') {
            $formattedBirthday = '';
        } else {
            $formattedBirthday = date("d-m-Y", strtotime($birthday));  
        }

        if (Validate::isLoadedObject($order) && Validate::isLoadedObject($customer)) {
            $paramsAPI = array('orderInfo' => null, 'orderProducts' => array());

            $orderProducts = array();
            $cart_instance = new Cart($order->id_cart);

            foreach ($cart_instance->getProducts() as $orderProduct) {
                $orderProductAttributes = (!empty($orderProduct['attributes_small']) ? str_replace(', ', '-', $orderProduct['attributes_small']) : '');

                $orderProduct_instance = new Product((int)$orderProduct['id_product']);
                $orderProducts[] = '{
                  "id": "' . $orderProduct['id_product'] . '", 
                  "quantity": ' . $orderProduct['quantity'] . ', 
                  "price": ' . $orderProduct_instance->getPrice(true, null, 2) . ', 
                  "variation_code": "' . $orderProductAttributes . '"
                }';

                $paramsAPI['orderProducts'][] = array(
                  'id' => $orderProduct['id_product'], 
                  'quantity' => $orderProduct['quantity'], 
                  'price' => $orderProduct_instance->getPrice(true, null, 2), 
                  'variation_code' => $orderProductAttributes
                );
            }

            $orderProducts = '[' . implode(', ', $orderProducts) . ']';

            $discountsCode = '';
            if (count($discounts) > 0) {
                $discountsCode = array();
                foreach ($discounts as $discount) {
                    $cartRule = new CartRule((int)$discount['id_cart_rule']);
                    $discountsCode[] = $cartRule->code;
                }
                $discountsCode = implode(', ', $discountsCode);
            }

            $js_code .= 'var _ra = _ra || {};   
                _ra.saveOrderInfo = {
                    "order_no": ' . $order->id . ',
                    "lastname": "' . $address->lastname . '",
                    "firstname": "' . $address->firstname . '",
                    "email": "' . $customer->email . '",
                    "phone": "' . ($mobilePhone !== '' ? $mobilePhone : $homePhone) . '",
                    "state": "' . (isset($address->id_state) ? State::getNameById($address->id_state) : '') . '",
                    "city": "' . $address->city . '",
                    "address": "' . $address->address1 . '",
                    "birthday": "' . $formattedBirthday . '",
                    "discount": ' . $order->total_discounts . ',
                    "discount_code": "' . $discountsCode . '",
                    "shipping": ' . $order->total_shipping . ',
                    "rebates": 0,
                    "fees": 0,
                    "total": ' . $order->total_paid . '
                };
                _ra.saveOrderProducts = ' . $orderProducts . ';

                if( _ra.ready !== undefined ){
                    _ra.saveOrder(_ra.saveOrderInfo, _ra.saveOrderProducts);
                }
            ';

            $paramsAPI['orderInfo'] = array(
                'order_no' => $order->id,
                'lastname' => $address->lastname,
                'firstname' => $address->firstname,
                'email' => $customer->email,
                'phone' => ($mobilePhone !== '' ? $mobilePhone : $homePhone),
                'state' => (isset($address->id_state) ? State::getNameById($address->id_state) : ''),
                'city' => $address->city,
                'address' => $address->address1,
                'discount' => $order->total_discounts,
                'discount_code' => $discountsCode,
                'shipping' => $order->total_shipping,
                'rebates' => 0,
                'fees' => 0,
                'total' => $order->total_paid
            );

            $this->_apiOrderSave($paramsAPI);

            return $this->_runJs($js_code);
        }
    }

    private function _apiOrderSave($params)
    {
        $ra_domain_api_key = Configuration::get('ra_apikey');
        $ra_token = Configuration::get('ra_token');

        if ($ra_token && $ra_token != '') {
            $client = new Retargeting_REST_API_Client($ra_token);
            $client->setResponseFormat("json");
            $client->setDecoding(false);

            $response = $client->order->save($params['orderInfo'], $params['orderProducts']);

            return $response;
        }

        return false;
    }

    /**
     * Functions _assign[::retargeting_trigger::]
     * ----------------------------------------------------------
     */

    protected function _assignEmbedding()
    {
        $js_embedd = false;


        $ra_domain_api_key = Configuration::get('ra_apikey');
        $ra_addToCart = Configuration::get('ra_qs_addToCart');
        $ra_price = Configuration::get('ra_qs_price');

        if ($ra_domain_api_key && $ra_domain_api_key != '') {
            $js_embedd = '
                // Retargeting 49.54.x 1.0.6
                (function(){
                ra_key = "' . $ra_domain_api_key . '";
                ra_params = {
                    add_to_cart_button_id: "' . $ra_addToCart . '",
                    price_label_id: "' . $ra_price . '",
                };
                var ra = document.createElement("script"); ra.type ="text/javascript"; ra.async = true; ra.src = ("https:" ==
                document.location.protocol ? "https://" : "http://") + "tracking.retargeting.biz/v3/rajs/" + ra_key + ".js";
                var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ra,s);})();
                
            ';
        } else {
            $js_embedd = 'console.info("Retargeting Tracker: please set the Tracking API Key.");';
        }

        return $js_embedd;
    }

    protected function _assignSendCategory()
    {
        if (method_exists($this->context->controller, 'getCategory')) {
            $category_instance = $this->context->controller->getCategory();
        } else {
            $category_instance = new Category((int)Tools::getValue('id_category'), $this->context->language->id);
        }

        $js_category = array();
        $arr_categoryBreadcrumb = array();

        if (Validate::isLoadedObject($category_instance)) {
            if (_PS_VERSION_ >= '1.6') {
                $categoryTree = $category_instance->getParentsCategories();
                foreach ($categoryTree as $key => $categoryNode) {
                    if ($categoryNode['is_root_category']) {
                        continue;
                    } elseif (
                      $key == 0 && ((isset($categoryTree[$key + 1]) && $categoryTree[$key + 1]['is_root_category']) || !isset($categoryTree[$key + 1]))) {
                        $js_category = '
                        "id": "' . $categoryNode['id_category'] . '", 
                        "name": "' . $categoryNode['name'] . '", 
                        "parent": false
                        ';
                    } elseif ($key == 0) {
                        $js_category = '
                        "id": "' . $categoryNode['id_category'] . '", 
                        "name": "' . $categoryNode['name'] . '", 
                        "parent": "' . $categoryNode['id_parent'] . '"
                        ';
                    } elseif (isset($categoryTree[$key + 1]) && $categoryTree[$key + 1]['is_root_category']) {
                        $arr_categoryBreadcrumb[] = '{ 
                          "id": "' . $categoryNode['id_category'] . '", 
                          "name": "' . $categoryNode['name'] . '", 
                          "parent": false 
                          }';
                    } else {
                        $arr_categoryBreadcrumb[] = '{ 
                          "id": "' . $categoryNode['id_category'] . '", 
                          "name": "' . $categoryNode['name'] . '", 
                          "parent": "' . $categoryNode['id_parent'] . '" 
                        }';
                    }
                }
            } else {
                $categoryTree = $category_instance->getParentsCategories();
                foreach ($categoryTree as $key => $categoryNode) {
                    if ($key == 0 && ((isset($categoryTree[$key + 1]) && $categoryTree[$key + 1]['level_depth'] < 1) || !isset($categoryTree[$key + 1]))) {
                        $js_category = '
                        "id": "' . $categoryNode['id_category'] . '", 
                        "name": "' . $categoryNode['name'] . '", 
                        "parent": false
                        ';
                    } elseif ($key == 0) {
                        $js_category = '
                        "id": "' . $categoryNode['id_category'] . '", 
                        "name": "' . $categoryNode['name'] . '", 
                        "parent": "' . $categoryNode['id_parent'] . '"
                        ';
                    } elseif ((isset($categoryTree[$key + 1]) && $categoryTree[$key + 1]['level_depth'] < 1) || !isset($categoryTree[$key + 1])) {
                        $arr_categoryBreadcrumb[] = '{ 
                          "id": "' . $categoryNode['id_category'] . '", 
                          "name": "' . $categoryNode['name'] . '", 
                          "parent": false 
                        }';
                    } else {
                        $arr_categoryBreadcrumb[] = '{ 
                          "id": "' . $categoryNode['id_category'] . '", 
                          "name": "' . $categoryNode['name'] . '", 
                          "parent": "' . $categoryNode['id_parent'] . '" 
                        }';
                    }
                }
            }
        }

        $js_categoryBreadcrumb = '[' . implode(', ', $arr_categoryBreadcrumb) . ']';

        $js_code = 'var _ra = _ra || {};
            _ra.sendCategoryInfo = { ' . $js_category . ',
                "breadcrumb": ' . $js_categoryBreadcrumb . '
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

        if (method_exists($this->context->controller, 'getManufacturer')) {
            $brand_instance = $this->context->controller->getManufacturer();
        } else {
            $brand_instance = new Manufacturer((int)Tools::getValue('id_manufacturer'), $this->context->language->id);
        }

        if (Validate::isLoadedObject($brand_instance)) {
            $js_code .= 'var _ra = _ra || {};
                _ra.sendBrandInfo = {
                    "id": "' . $brand_instance->id_manufacturer . '",
                    "name": "' . $brand_instance->name . '"
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

        if (method_exists($this->context->controller, 'getProduct')) {
            $product_instance = $this->context->controller->getProduct();
        } else {
            $product_instance = new Product((int)Tools::getValue('id_product'), $this->context->language->id);
        }

        if (Validate::isLoadedObject($product_instance)) {
            $product_fields = $product_instance->getFields();
            $category_instance = new Category($product_instance->id_category_default, $this->context->language->id);

            $js_category = 'false';
            $arr_categoryBreadcrumb = array();

            if (Validate::isLoadedObject($category_instance)) {
                if (_PS_VERSION_ >= '1.6') {
                    $categoryTree = $category_instance->getParentsCategories();
                    foreach ($categoryTree as $key => $categoryNode) {
                        if ($categoryNode['is_root_category']) {
                            continue;
                        } elseif ($key == 0 && ((isset($categoryTree[$key + 1]) && $categoryTree[$key + 1]['is_root_category']) || !isset($categoryTree[$key + 1]))) {
                            $js_category = '
                             "id": "' . $categoryNode['id_category'] . '", 
                             "name": "' . $categoryNode['name'] . '", 
                             "parent": false 
                             ';
                        } elseif ($key == 0) {
                            $js_category = '
                             "id": "' . $categoryNode['id_category'] . '", 
                             "name": "' . $categoryNode['name'] . '", 
                             "parent": "' . $categoryNode['id_parent'] . '" 
                             ';
                        } elseif (isset($categoryTree[$key + 1]) && $categoryTree[$key + 1]['is_root_category']) {
                            $arr_categoryBreadcrumb[] = '{ 
                              "id": "' . $categoryNode['id_category'] . '", 
                              "name": "' . $categoryNode['name'] . '", 
                              "parent": false 
                            }';
                        } else {
                            $arr_categoryBreadcrumb[] = '{ 
                              "id": "' . $categoryNode['id_category'] . '", 
                              "name": "' . $categoryNode['name'] . '", 
                              "parent": "' . $categoryNode['id_parent'] . '" 
                            }';
                        }
                    }
                } else {
                    $categoryTree = $category_instance->getParentsCategories();
                    foreach ($categoryTree as $key => $categoryNode) {
                        if ($key == 0 && ((isset($categoryTree[$key + 1]) && $categoryTree[$key + 1]['level_depth'] < 1) || !isset($categoryTree[$key + 1]))) {
                            $js_category = ' 
                            "id": "' . $categoryNode['id_category'] . '", 
                            "name": "' . $categoryNode['name'] . '", 
                            "parent": false 
                            ';
                        } elseif ($key == 0) {
                            $js_category = ' 
                            "id": "' . $categoryNode['id_category'] . '", 
                            "name": "' . $categoryNode['name'] . '", 
                            "parent": "' . $categoryNode['id_parent'] . '" 
                            ';
                        } elseif ((isset($categoryTree[$key + 1]) && $categoryTree[$key + 1]['level_depth'] < 1) || !isset($categoryTree[$key + 1])) {
                            $arr_categoryBreadcrumb[] = '{ 
                              "id": "' . $categoryNode['id_category'] . '", 
                              "name": "' . $categoryNode['name'] . '", 
                              "parent": false 
                            }'; 
                        } else {
                            $arr_categoryBreadcrumb[] = '{ 
                              "id": "' . $categoryNode['id_category'] . '", 
                              "name": "' . $categoryNode['name'] . '", 
                              "parent": "' . $categoryNode['id_parent'] . '" 
                            }';
                        }
                    }
                }
            }

            $js_categoryBreadcrumb = '[' . implode(', ', $arr_categoryBreadcrumb) . ']';
            
            // In case the product has no category, return Home as default category
            
            if ($js_category == "false") {
                $js_category = '[{
                    "id": 1, 
                    "name": "Home", 
                    "parent": false,
                    "breadcrumb": []
                  }]';
            } else {
                $js_category = '[{
                   ' . $js_category . ',
                    "breadcrumb": ' . $js_categoryBreadcrumb . ' 
                  }]';              
            }
            


            $imgDomain = _PS_BASE_URL_;
            if (_MEDIA_SERVER_1_ != null) {
                $imgDomain = Configuration::get('ra_mediaServerProtocol') . _MEDIA_SERVER_1_;
            } elseif (_MEDIA_SERVER_2_ != null) {
                $imgDomain = Configuration::get('ra_mediaServerProtocol') . _MEDIA_SERVER_2_;
            } elseif (_MEDIA_SERVER_3_ != null) {
                $imgDomain = Configuration::get('ra_mediaServerProtocol') . _MEDIA_SERVER_3_;
            }

            //          $product_image = '';
            $id_image = Product::getCover($product_fields['id_product']);
            if (sizeof($id_image) > 0) {
                $image = new Image($id_image['id_image']);
                if (_PS_VERSION_ >= '1.6') {
                    $product_image = $imgDomain . _THEME_PROD_DIR_ . $image->getExistingImgPath() . "-" . ImageType::getFormatedName('large') . ".jpg";
                } else {
                    $product_image = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->id_product . "-" . $image->id_image . "-large.jpg";
                }
            } else {
                $product_image = $link_instance->getImageLink($product_instance->link_rewrite, $product_fields['id_product'], ImageType::getFormatedName('large'));
            }
            /*
            * Img
            */

            $iProt = Tools::strtolower(Tools::substr($_SERVER["SERVER_PROTOCOL"], 0, strpos($_SERVER["SERVER_PROTOCOL"], '/'))) . '://';
            $iType = ImageType::getFormatedName('large');
            $raImg = $iProt . $link_instance->getImageLink($product_instance->link_rewrite, $product_instance->id . '-' . $id_image['id_image'], $iType);

            if (_PS_VERSION_ >= '1.6') {

                $vat = $product_instance->tax_rate;
                $vat_value = ((100 + $vat) / 100);

                if ($vat > 0) {
                    $product_price = round(($product_instance->base_price * $vat_value), 2);
                } else {
                    $product_price = $product_instance->getPriceWithoutReduct(true, null, 2);
                }

                $product_promo = ($product_instance->getPriceWithoutReduct() > $product_instance->getPrice() ? $product_instance->getPrice(true, null, 2) : 0);
                $product_stock = (Product::getQuantity($product_fields['id_product']) > 0 ? 1 : 0);
            } else {
                $product_price = $product_instance->getPrice(true, null, 2, null, false, false);
                $product_promo = ($product_instance->getPrice(true, null, 2, null, false, false) > $product_instance->getPrice(true, null, 2) ? $product_instance->getPrice(true, null, 2) : 0);
                $product_stock = (Product::getQuantity($product_fields['id_product']) > 0 ? 1 : 0);
            }

            $productName = htmlspecialchars($product_instance->name);
            $productNameLangId = htmlspecialchars($product_instance->name[$this->context->language->id]);

            $js_code = 'var _ra = _ra || {};
                _ra.sendProductInfo = {
                    "id": "' . $product_fields['id_product'] . '",
                    "name": "' . (is_array($productName) ? $productNameLangId : $productName) . '",
                    "url": "' . $product_instance->getLink() . '", 
                    "img": "' . $raImg . '", 
                    "price": ' . $product_price . ',
                    "promo": ' . $product_promo . ',
                    "brand": ' . ($product_instance->manufacturer_name != '' ? '"' . $product_instance->manufacturer_name . '"' : 'false') . ',
                    "category": ' . $js_category . ',
                    "inventory": {
                        "variations": false,
                        "stock": ' . $product_stock . '
                    }
                };
                
                if (_ra.ready !== undefined) {
                    _ra.sendProduct(_ra.sendProductInfo);
                }
            ';
        }

        return $js_code;
    }

    protected function _assignAddToCart($controller)
    {
        $js_code = '
            if (typeof ajaxCart !== "undefined") {
                var _ra_ajaxCart_add = ajaxCart.add;
                ajaxCart.add = function(idProduct, idCombination, addedFromProductPage, callerElement, quantity, whishlist) {
    
                    $.ajax({
                        url: baseDir + "modules/retargetingtracker/ajax.php",
                        type: "GET",
                        data: "ajax=true&method=getAddToCartJS&type=' . $controller . '&pid=" + idProduct + "&vid=" + idCombination,
                        success: function(data) {
                            var s = document.createElement("script");
                            s.type = "text/javascript";
                            s.text = data;
                            $("head").append(s);
                        }
                    });
        
                    return _ra_ajaxCart_add(idProduct, idCombination, addedFromProductPage, callerElement, quantity, whishlist);
                }
            }
            
            // #buy_block compatability
            if ($("#buy_block").length) {
                $("#buy_block").submit(function() {
                    var $pid = $("#buy_block input[name=\'id_product\']");
                    if (typeof _ra.addToCart === "function" && $pid.length) {
                        _ra.addToCart($pid.val(), 1, false);
                    }
                }); 
            }
            if ($(".ajax_add_to_cart_button").length) {
                $(".ajax_add_to_cart_button").click(function() {
                    var pid = $(this).data("id-product");
                    if (typeof _ra.addToCart === "function" && pid) {
                        _ra.addToCart(pid, 1, false);
                    }
                });
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
            
            $(".color_pick").click(_ra_setVariation);
            $("#attributes select").change(_ra_setVariation);
            $("#attributes radio").click(_ra_setVariation);
        ';

        return $js_code;
    }

    protected function _assignAddToWishlist()
    {
        $js_code = 'if (typeof WishlistCart !== "undefined") {
                var _ra_WishlistCart = WishlistCart;
                WishlistCart = function(id, action, id_product, id_product_attribute, quantity, id_wishlist) {
                    _ra.addToWishlist(id_product);
                    return _ra_WishlistCart(id, action, id_product, id_product_attribute, quantity, id_wishlist);
                }
            }
        ';

        return $js_code;
    }

    protected function _assignClickImage()
    {
        $ra_productImages = (Configuration::get('ra_qs_productImages') != '' ? Configuration::get('ra_qs_productImages') : '#image-block');

        $js_code = 'function _ra_clickImage() {
                _ra.clickImage($("#product_page_product_id").val());
            }

            $("' . $ra_productImages . '").click(_ra_clickImage);
        ';

        return $js_code;
    }

    protected function _assignCommentOnProduct()
    {
        $ra_review = (Configuration::get('ra_qs_review') != '' ? Configuration::get('ra_qs_review') : '#submitNewMessage');

        $js_code = 'function _ra_commentOnProduct() {
                _ra.commentOnProduct($("#product_page_product_id").val());
            }

            $("' . $ra_review . '").click(_ra_commentOnProduct);
        ';

        return $js_code;
    }

    protected function _assignMouseOverPrice()
    {
        if (method_exists($this->context->controller, 'getProduct')) {
            $product_instance = $this->context->controller->getProduct();
        } else {
            $product_instance = new Product((int)Tools::getValue('id_product'), $this->context->language->id);
        }

        if (Validate::isLoadedObject($product_instance)) {
            $product_fields = $product_instance->getFields();

            if (_PS_VERSION_ >= '1.6') {
                $vat = $product_instance->tax_rate;
                $vat_value = ((100 + $vat) / 100); // default value

                if ($vat > 0) {
                    $product_price = round(($product_instance->base_price * $vat_value), 2);
                } else {
                    $product_price = $product_instance->getPriceWithoutReduct(true, null, 2);
                }
                $product_promo = ($product_instance->getPriceWithoutReduct() > $product_instance->getPrice() ? $product_instance->getPrice(true, null, 2) : 0);
            } else {
                $product_price = $product_instance->getPrice(true, null, 2, null, false, false);
                $product_promo = ($product_instance->getPrice(true, null, 2, null, false, false) > $product_instance->getPrice(true, null, 2) ? $product_instance->getPrice(true, null, 2) : 0);
            }

            $js_code = 'function _ra_mouseOverPrice() {
                    if (typeof _ra.mouseOverPrice !== "function") return false;
                    _ra.mouseOverPrice("' . $product_fields['id_product'] . '", {
                        "price": ' . $product_price . ',
                        "promo": ' . $product_promo . '
                    });
                }

                $("#our_price_display").mouseenter(_ra_mouseOverPrice);
            ';
        }

        return $js_code;
    }

    protected function _assignMouseOverAddToCart()
    {
        if (method_exists($this->context->controller, 'getProduct')) {
            $product_instance = $this->context->controller->getProduct();
        } else {
            $product_instance = new Product((int)Tools::getValue('id_product'), $this->context->language->id);
        }

        if (Validate::isLoadedObject($product_instance)) {
            $product_fields = $product_instance->getFields();

            $js_code = 'function _ra_mouseOverAddToCart() {
                    if (typeof _ra.mouseOverAddToCart !== "function") return false;
                    _ra.mouseOverAddToCart("' . $product_fields['id_product'] . '");
                }

                $("#add_to_cart [type=\'submit\']").mouseenter(_ra_mouseOverAddToCart);
            ';
        }

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
        $str_visitHelpPage = Configuration::get('ra_opt_visitHelpPage');
        $arr_visitHelpPage = explode('|', $str_visitHelpPage);

        $currentCMSPageId = $this->context->controller->cms->id;

        $js_code = '';

        if (in_array($currentCMSPageId, $arr_visitHelpPage)) {
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
        foreach ($cartProducts as $product) {
            $arr_cartProducts[] = $product['id_product'];
        }

        $js_cartProducts = '[' . implode(', ', $arr_cartProducts) . ']';

        $js_code = 'var _ra = _ra || {};
            _ra.checkoutIdsInfo = ' . $js_cartProducts . ';
            
            if (_ra.ready !== undefined) {
                _ra.checkoutIds(_ra.checkoutIdsInfo);
            }
        ';

        return $js_code;
    }

    protected function _assignSetCartUrl()
    {
        $js_code = ' var _ra = _ra || {};
            _ra.setCartUrlInfo = {
                "url": window.location.toString()
            };

            if (_ra.ready !== undefined) {
                _ra.setCartUrl(_ra.setCartUrlInfo.url);
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
            ' . $js_code . '
        </script>';
    }

    protected function getCurrentController()
    {
        // For Prestashop v1.6 and newer
        if (_PS_VERSION_ >= '1.6') {
            return Dispatcher::getInstance()->getController();
        }

        // For Prestashop v1.4
        $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        return str_replace('.php', '', basename($script_name));
    }
}
