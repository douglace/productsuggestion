<?php

namespace Cleandev\Productsuggestion\Classes;


use Db;
use Context;
use Category;
use TaxConfiguration;
use ProductAssembler;
use ProductPresenterFactory;


class Psg {
    
    public function getProducts($id_product) {
        $products = $this->getAllSuggestions($id_product);

        if(!empty($products)){
            $products = array_map([$this, 'prepareProductForTemplate'],$products);
        }
        return $this->groupByCategories($products);
    }

    public function getAllSuggestions($id_product) {
        $ids = Db::getInstance()->getValue('SELECT products FROM `'._DB_PREFIX_.'psg_config` WHERE `id_product`='.$id_product);
        
        if(!$ids) {
            return [];
        }
        return array_map(function($product) {
            return ['id_product' => $product];
        },explode(',', $ids));
    }

    public function groupByCategories($products) {
        $groups = [];
        $id_lang = Context::getContext()->language->id;
        foreach($products as $p){
            if(!isset($groups[$p['id_category_default']])) {
                $category = new Category($p['id_category_default'], $id_lang);
                $groups[$p['id_category_default']] = [
                    'products' => [],
                    'category' => $category->name
                ];
            }
            $groups[$p['id_category_default']]['products'][] = $p;
        }

        return $groups;
    }

    /**
     * Takes an associative array with at least the "id_product" key
     * and returns an array containing all information necessary for
     * rendering the product in the template.
     *
     * @param array $rawProduct an associative array with at least the "id_product" key
     *
     * @return array a product ready for templating
     */
    private function prepareProductForTemplate(array $rawProduct)
    {
        $context = \Context::getContext();
        $product = (new ProductAssembler($context))
            ->assembleProduct($rawProduct)
        ;

        $factory = new ProductPresenterFactory($context, new TaxConfiguration());
        $presenter = $factory->getPresenter();
        $settings = $factory->getPresentationSettings();

        return $presenter->present(
            $settings,
            $product,
            $context->language
        );
    }
}