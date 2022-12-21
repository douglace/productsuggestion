<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(_PS_MODULE_DIR_. 'productsuggestion/vendor/autoload.php')) {
    require_once _PS_MODULE_DIR_.  'productsuggestion/vendor/autoload.php';
}

class Productsuggestion extends Module
{
    protected $config_form = false;

    /**
     * @param array $tabs
     */
    public $tabs;

    /**
     * @param Cleandev\Productsuggestion\Repository $repository
     */
    protected $repository;

    /**
     * @param array $languages
     */
    protected $languages;

    public function __construct()
    {
        $this->name = 'productsuggestion';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Cleandev';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        $this->tabs = array(
            array(
                'name'=> $this->l('Product suggestions'),
                'class_name'=>'AdminPsgconfig',
                'parent'=>'AdminCatalog',
            ),
        );

        $this->repository = new Cleandev\Productsuggestion\Repository($this); 
        parent::__construct();
        
        $this->displayName = $this->l('Suggestion des produits et accessoires ');
        $this->description = $this->l('Suggestion des produits et accessoires ');
        
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        return parent::install() && $this->repository->install();
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->repository->uninstall();
    }

    
    /**
     * Load the configuration form
     */
    public function getContent()
    {
        
        $process = $this->postProcess();
        
        $this->context->smarty->assign(
            array(
                'module_dir' => $this->_path,
                'config_form' => $this->config_form,
                'home_global' => $this->renderFormGlobal(),
            )
        );

        return $process.$this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

    }

    public function renderFormGlobal(){
        $form = new Cleandev\Productsuggestion\Utils\FormForm($this);
        $form->setShowToolbar(false)
            ->setTable($this->table)
            ->setModule($this)
            ->setDefaultFromLanguage($this->context->language->id)
            ->setAllowEmployeFromLang(Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0))
            ->setIdentifier($this->identifier)
            ->setSubmitAction('submitProductsuggestionModuleGlobal')
            ->setCurrentIndex($this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name)
            ->setToken(Tools::getAdminTokenLite('AdminModules'))
            ->setTplVar([
                'fields_value' => $this->getConfigGlobalFormValues(), /* Add values for your inputs */
                'languages' => $this->context->controller->getLanguages(),
                'id_language' => $this->context->language->id,
            ])
            /*->addField(
                array(
                    'type' => 'switch',
                    'label' => $this->l('Display the age of the customer on the notices'),
                    'name' => 'PRODUCTSUGGESTION_DISPLAY_YEAR_OLD',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('Disabled')
                        )
                    ),
                )
            )*/
            ->setLegend([
                'title' => $this->l('Global Settings'),
                'icon' => 'icon-cogs',
            ])->setSubmit([
                'title' => $this->l('Save'),
            ])
        ;

        return $form->make();
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigGlobalFormValues()
    {
        
        $data =  array(
            'PRODUCTSUGGESTION_DISPLAY_YEAR_OLD' => Configuration::get('PRODUCTSUGGESTION_DISPLAY_YEAR_OLD'),
        );
        
        return $data;
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $multilang = array();
        $form_values = array();
        $specific_testimony = null;

        $int_fields = [
            'PRODUCTSUGGESTION_PRODUCT_NB_TO_DISPLAY',
            'PRODUCTSUGGESTION_CART_NB_TO_DISPLAY',
            'PRODUCTSUGGESTION_HOME_NB_TO_DISPLAY',
        ];

        if(Tools::isSubmit('submitProductsuggestionModuleGlobal')) {
            $form_values = $this->getConfigGlobalFormValues();
            $this->config_form = 'config-global';
        } else {
            return false;
        }
        
        if(!empty($form_values)) {
            try{
                $values = [];
                if(!empty($multilang)) {
                    $languages = Language::getLanguages();
                    foreach($multilang as $k) {
                        foreach($languages as $l) {
                            $values[$k][$l['id_lang']] = Tools::getValue($k."_".$l['id_lang']);
                        }
                    }
                }
                foreach (array_keys($form_values) as $key) {
                    if(in_array($key, $multilang)) {
                        if(isset($values[$key])) {
                            Configuration::updateValue($key, $values[$key]);
                        }
                    } else {
                        if($key == $specific_testimony.'[]') {
                            $lists = Tools::getValue($specific_testimony);
                            if($lists && !empty($lists)) {
                                Configuration::updateValue($specific_testimony, implode(',', $lists));
                            }else {
                                Configuration::updateValue($specific_testimony, "");
                            }
                        } else {
                            if(in_array($key, $int_fields)){
                                $v = Tools::getValue($key);
                                if(!Validate::isInt($v)) {
                                    $this->_errors[] = $this->l('Some fields are not valid') ;
                                }
                                Configuration::updateValue($key, (int)$v);
                            } else {
                                Configuration::updateValue($key, Tools::getValue($key));
                            }
                            
                        }
                    }
                    
                    
                }
                
                if(!empty($this->_errors)) {
                    return $this->displayError(current($this->_errors));
                }
                return $this->displayConfirmation($this->l('Configuration save with success'));
            }catch(Exception $e) {
                return $this->displayError($this->l('Something when wrong'));
            }
        }
        
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayProductExtraContent($params) {
        $psg = new Cleandev\Productsuggestion\Classes\Psg();
        $suggestions = $psg->getProducts(Tools::getValue('id_product'));
        if(empty($suggestions)) {
            return null;
        }
        $this->context->smarty->assign('suggestions', $suggestions);
        $content = $this->fetch('module:productsuggestion/views/templates/hook/products.tpl');
        $array = array();
        $array[] = (new PrestaShop\PrestaShop\Core\Product\ProductExtraContent())
            ->setTitle($this->trans('Accessoires compatibles'))
            ->setContent($content);

        return $array;
    }
}
