<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

include_once(dirname(__FILE__) . '/classes/FieldClass.php');

class ExtraCustomerField extends Module
{
    public function __construct()
    {
        $this->name = 'extracustomerfield';
        $this->author = 'Otmane AMAL';
        $this->version = '1.0.0';
        $this->need_instance= 0;
        $this->bootstrap = true;
        $this->tab = 'others';
        parent::__construct();

        $this->displayName = $this->l('Extra customer field');
        $this->ps_versions_compliancy = array(
            'min' => '1.7',
            'max' => _PS_VERSION_
        );
        $this->description = $this->l('Add extra customer field');
    }

    /**
     * @return bool
     * @throws
     */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install()
            || !FieldClass::alterCustomerTable()
            || !$this->registerHook('displayMyExtraValue')
            || !$this->registerHook('additionalCustomerFormFields')
            || !$this->registerHook('validateCustomerFormFields')
            || !$this->registerHook('actionObjectCustomerUpdateAfter')
            || !$this->registerHook('actionObjectCustomerAddAfter')
        ) {
            return false;
        }
        return true;
    }

    /**
     * Write module fields values
     *
     * @return nothing
     */
    protected function writeModuleValues($id_customer)
    {
        $custom_field = Tools::getValue('custom_field');
        $query = 'UPDATE `'._DB_PREFIX_.'customer` c '
            .' SET  c.`custom_field` = "'.pSQL($custom_field).'"'
            .' WHERE c.id_customer = '.(int)$id_customer;
        Db::getInstance()->execute($query);
    }

    /**
     * @param $params
     * @return array
     */
    public function hookAdditionalCustomerFormFields($params)
    {
        $module_fields = FieldClass::readModuleValues();
        $custom_field_value = Tools::getValue('custom_field');
        if (isset($module_fields['custom_field'])) {
            $custom_field_value = $module_fields['custom_field'];
        }

        $extra_fields = array();
        $extra_fields['custom_field'] = (new FormField)
            ->setName('custom_field')
            ->setType('text')
            ->setValue($custom_field_value)
            ->setLabel($this->l('custom_field'));

        return $extra_fields;
    }

    /**
     * Customer update
     */
    public function hookactionObjectCustomerUpdateAfter($params)
    {
        $id = (int)$params['object']->id;
        $this->writeModuleValues($id);
    }

    /**
     * Customer add
     */
    public function hookactionObjectCustomerAddAfter($params)
    {
        $id = (int)$params['object']->id;
        $this->writeModuleValues($id);
    }

    public function hookDisplayMyExtraValue($params)
    {
        $id_customer = (int)$params['id_customer'];
        $query = 'SELECT c.`custom_field`'
            .' FROM `'. _DB_PREFIX_.'customer` c '
            .' WHERE c.id_customer = '.(int)$id_customer;
        return  Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }
}
