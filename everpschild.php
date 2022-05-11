<?php
/**
 * Project : everpschild
 * @author Team Ever
 * @copyright Team Ever
 * @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 * @link https://www.team-ever.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Everpschild extends Module
{
    private $html;
    private $postErrors = array();
    private $postSuccess = array();

    public function __construct()
    {
        $this->name = 'everpschild';
        $this->tab = 'administration';
        $this->version = '1.0.3';
        $this->author = 'Team Ever';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Ever PS Child');
        $this->description = $this->l('Generate child theme ');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->isSeven = Tools::version_compare(_PS_VERSION_, '1.7', '>=') ? true : false;
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('EVERPSCHILD_USE_ASSETS', true);
        return parent::install();
    }

    public function uninstall()
    {
        ConfigurationdeleteByName('EVERPSCHILD_PARENT');
        ConfigurationdeleteByName('EVERPSCHILD_USE_ASSETS');
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitEverpschildModule')) == true) {
            $this->postValidation();

            if (!count($this->postErrors)) {
                $this->postProcess();
            }
        }

        // Display errors
        if (count($this->postErrors)) {
            foreach ($this->postErrors as $error) {
                $this->html .= $this->displayError($error);
            }
        }

        // Display confirmations
        if (count($this->postSuccess)) {
            foreach ($this->postSuccess as $success) {
                $this->html .= $this->displayConfirmation($success);
            }
        }
        $this->context->smarty->assign(array(
            'everpschild_dir' => $this->_path,
        ));

        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/header.tpl');
        $this->html .= $this->renderForm();
        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/footer.tpl');

        return $this->html;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEverpschildModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Parent theme'),
                        'desc' => $this->l('Child theme will use this name'),
                        'hint' => $this->l('Child theme will be named "parent-child"'),
                        'name' => 'EVERPSCHILD_PARENT',
                        'required' => true,
                        'options' => array(
                            'query' => $this->getThemes(),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Use assets'),
                        'name' => 'EVERPSCHILD_USE_ASSETS',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
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
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    public function postValidation()
    {
        if (((bool)Tools::isSubmit('submitEverpschildModule')) == true) {
            if (Tools::getValue('EVERPSCHILD_USE_ASSETS')
                && !Validate::isBool(Tools::getValue('EVERPSCHILD_USE_ASSETS'))
            ) {
                $this->postErrors[] = $this->l(
                    'Error : The field "use assets" is not valid'
                );
            }
            if (!Tools::getValue('EVERPSCHILD_PARENT')
                || !Validate::isAnything(Tools::getValue('EVERPSCHILD_PARENT'))
            ) {
                $this->postErrors[] = $this->l(
                    'Error : The field "use assets" is not valid'
                );
            }
        }
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'EVERPSCHILD_USE_ASSETS' => Configuration::get('EVERPSCHILD_USE_ASSETS'),
            'EVERPSCHILD_PARENT' => Configuration::get('EVERPSCHILD_PARENT'),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
        return $this->createChildTheme(
            Tools::getValue('EVERPSCHILD_PARENT')
        );
    }

    protected function createChildTheme($parent)
    {
        if (!file_exists(_PS_ALL_THEMES_DIR_.$parent.'-child')) {
            mkdir(_PS_ALL_THEMES_DIR_.$parent.'-child', 0755, true);
            mkdir(_PS_ALL_THEMES_DIR_.$parent.'-child/assets', 0755, true);
            mkdir(_PS_ALL_THEMES_DIR_.$parent.'-child/assets/css', 0755, true);
            mkdir(_PS_ALL_THEMES_DIR_.$parent.'-child/assets/js', 0755, true);
            mkdir(_PS_ALL_THEMES_DIR_.$parent.'-child/config', 0755, true);
            mkdir(_PS_ALL_THEMES_DIR_.$parent.'-child/translations', 0755, true);
            mkdir(_PS_ALL_THEMES_DIR_.$parent.'-child/translations/fr-FR', 0755, true);
            $config = _PS_ALL_THEMES_DIR_.$parent.'-child/config/';
            $child_css = _PS_ALL_THEMES_DIR_.$parent.'-child/assets/css/';
            $child_js = _PS_ALL_THEMES_DIR_.$parent.'-child/assets/js/';
            $yml = 'theme.yml';
            $css = 'custom.css';
            $js = 'custom.js';
            $comment = '/*Use this to change your parent theme*/';
            $child_config = 'parent: '.$parent."\r\n";
            $child_config .= 'name: '.$parent.'-child'."\r\n";
            $child_config .= 'display_name: '.$parent.' theme by Team Ever'."\r\n";
            $child_config .= 'version: 1.0.0'."\r\n";
            if (Configuration::get('EVERPSCHILD_USE_ASSETS')) {
                $child_config .= 'assets:'."\r\n";
                $child_config .= ' use_parent_assets: true';
            }
            if (!file_put_contents($config.$yml, $child_config)) {
                $this->postErrors[] = $this->l('Unable to save yml');
            }
            if (!file_put_contents($child_css.$css, $comment)) {
                $this->postErrors[] = $this->l('Unable to save css');
            }
            if (!file_put_contents($child_js.$js, $comment)) {
                $this->postErrors[] = $this->l('Unable to save js');
            }
            $this->postSuccess[] = $this->l('Your child theme has been fully saved');
        } else {
            $this->postErrors[] = $this->l('Child theme already exists');
        }
    }

    protected function getThemes()
    {
        $themes = array();
        $dir = new DirectoryIterator(_PS_ALL_THEMES_DIR_);
        foreach ($dir as $fileinfo) {
            $counter = 1;
            if ($fileinfo->isDir()
                && !$fileinfo->isDot()
                && $fileinfo->getFilename() != '_libraries'
            ) {
                $themes[] = array(
                    'id' => $fileinfo->getFilename(),
                    'name' => $fileinfo->getFilename()
                );
                $counter++;
            }
        }
        return $themes;
    }
}
