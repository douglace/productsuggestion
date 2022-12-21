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

if(!class_exists('PsgModel'));
    require_once _PS_MODULE_DIR_.'productsuggestion/classes/PsgModel.php';


class AdminPsgconfigController extends ModuleAdminController {

    public function __construct()
    {
        $this->table = 'psg_config';
        $this->className = 'PsgModel';
        $this->lang = false;
        $this->bootstrap = true;

        $this->deleted = false;
        $this->allow_export = true;
        $this->list_id = 'psg_config';
        $this->identifier = 'id_psg_config';
        $this->_defaultOrderBy = 'id_psg_config';
        $this->_defaultOrderWay = 'ASC';
        $this->context = Context::getContext();

        $this->addRowAction('edit');
        $this->addRowAction('delete'); 
      

        $this->_select .="  pl.name product";
        $this->_join .=" LEFT JOIN `"._DB_PREFIX_."product_lang` pl on pl.id_product = a.id_product and pl.id_lang=".$this->context->language->id." and pl.id_shop=".$this->context->shop->id;

        parent::__construct();

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected', [], 'Modules.Productsuggestion.Admin'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?', [], 'Modules.Productsuggestion.Admin')
            )
        );

       
        

        $this->fields_list = array(
            'id_psg_config'=>array(
                'title' => $this->l('ID', [], 'Modules.Productsuggestion.Admin'),
                'align'=>'center',
                'class'=>'fixed-width-xs'
            ),
            'product'=>array(
                'title'=>$this->l('Product', [], 'Modules.Productsuggestion.Admin'),
                'filter_key' => 'pl!name',
                'order_key' => 'id_product'
            ),
            /*'auto' => array(
                'title' => $this->l('Auto', [], 'Modules.Productsuggestion.Admin'),
                'type' =>'bool',
                'align' =>'center',
                'class' =>'fixed-width-xs',
                'orderby' => false,
            )*/
        );
    }

    public function getProducts() {
        $q = new DbQuery();
        $q->select('id_product id, name')
            ->from('product_lang')
            ->where('id_lang='.$this->context->language->id)
            ->where('id_shop='.$this->context->shop->id)
        ;

        return Db::getInstance()->executeS($q);
    }


    public function renderForm()
    {
        if (!($psgconfig = $this->loadObject(true))) {
            return;
        }
        
        $this->fields_form = array(
            'tinymce' => true,
            'legend' => array(
                'title' => $this->l('Suggestions', [], 'Modules.Productsuggestion.Admin'),
                'icon' => 'icon-certificate'
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Product', [], 'Modules.Productsuggestion.Admin'),
                    'name' => 'id_product',
                    'class' => 'chosen',
                    'col' => 4,
                    'options'=> array(
                        'query'=> $this->getProducts(),
                        'id'=>'id',
                        'name'=>'name',
                    ),
                    'required' => false,
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Produits associers', [], 'Modules.Productsuggestion.Admin'),
                    'name' => 'products[]',
                    'col' => 8,
                    'class' => 'chosen',
                    'options'=> array(
                        'query'=>$this->getProducts(),
                        'id'=>'id',
                        'name'=>'name',
                    ),
                    'required' => false,
                    'multiple' => true,
                ),
                /*array(
                    'type' => 'switch',
                    'label' => $this->l('Auto', [], 'Modules.Productsuggestion.Admin'),
                    'name' => 'auto',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'auto_on',
                            'value' => 1,
                            'label' => $this->l('Enabled', [], 'Modules.Productsuggestion.Admin')
                        ),
                        array(
                            'id' => 'auto_off',
                            'value' => 0,
                            'label' => $this->l('Disabled', [], 'Modules.Productsuggestion.Admin')
                        )
                    )
                )*/
            )
        );

        if (!($psgconfig = $this->loadObject(true))) {
            return;
        }


        $this->fields_form['submit'] = array(
            'title' => $this->l('Save', [], 'Modules.Productsuggestion.Admin')
        );
        if($psgconfig->id) {
            $this->fields_value['products[]'] =explode(',', $psgconfig->products);
        }
        


        return parent::renderForm();
    }

    public function postProcess()
    {
        if(Tools::isSubmit('submitAddpsg_config')) {
            $id_product = Tools::getValue('id_product');
            $products = array_filter(Tools::getValue('products'), function($v)use($id_product) {
                 return $v != $id_product; 
            });
            $_POST['products'] = implode(',',$products);
        }
        parent::postProcess();
    }

    public function l($string, $params = [], $domaine = 'Modules.Productsuggestion.Admin', $local = null){
        if(_PS_VERSION_ >= '1.7'){
            if(!$params) {
                $params = [];
            }
            return $this->module->getTranslator()->trans($string, $params, $domaine, $local);
        }else{
            return parent::l($string, null, false, true);
        }
    }

}