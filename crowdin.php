<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

require_once __DIR__.'/vendor/autoload.php';

/**
 * Class Crowdin
 *
 * @since 1.0.0
 */
class Crowdin extends Module
{
    const IN_CONTEXT_ISO = 'zu';
    const PROJECT_IDENTIFIER = 'thirty-bees';
    const JIPT_FRONT_OFFICE = 'CROWDIN_JIPT_FO';
    const JIPT_BACK_OFFICE = 'CROWDIN_JIPT_BO';
    const FORCE_LIVE_TRANSLATION = 'CROWDIN_FORCE_LIVE_TRANSLATION';

    /**
     * Crowdin constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->name = 'crowdin';
        $this->version = '1.0.0';
        $this->author = 'thirty bees';
        $this->tab = 'administration';

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Crowdin in-context');
        $this->description = $this->l('Crowdin in-context translations');
    }

    /**
     * Install this module
     *
     * @return bool Indicates whether this module was successfully installed
     */
    public function install()
    {
        $ok = parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBackOfficeFooter')
            && $this->registerHook('displayBackOfficeHeader');

        Language::downloadAndInstallLanguagePack(static::IN_CONTEXT_ISO);

        Configuration::updateValue(static::JIPT_FRONT_OFFICE, false);
        Configuration::updateValue(static::JIPT_BACK_OFFICE, false);

        return $ok;
    }

    /**
     * Uninstall this module
     *
     * @return bool Indicates whether this module has been successfully uninstalled
     *
     * @since 1.0.0
     */
    public function uninstall()
    {
        Configuration::deleteByName(static::FORCE_LIVE_TRANSLATION);

        $zulu = new Language(Language::getIdByIso(static::IN_CONTEXT_ISO));
        if (Validate::isLoadedObject($zulu)) {
            $zulu->delete();
        }

        return parent::uninstall();
    }

    /**
     * Is dev server
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function isDevServer()
    {
        return (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) || Configuration::get(static::FORCE_LIVE_TRANSLATION);
    }

    /**
     * Hook to displayHeader
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function hookDisplayHeader()
    {
        if (Configuration::get(static::JIPT_FRONT_OFFICE)
            && $this->context->language->iso_code === static::IN_CONTEXT_ISO) {
            $this->smarty->assign('project', static::PROJECT_IDENTIFIER);

            return $this->display(__FILE__, 'views/templates/hook/jipt.tpl');
        }

        return '';
    }

    /**
     * Hook to displayBackOfficeHeader
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::isSubmit(static::JIPT_BACK_OFFICE) && !Tools::getValue(static::JIPT_BACK_OFFICE)) {
            return '';
        }

        if (Configuration::get(static::JIPT_BACK_OFFICE) && $this->context->language->iso_code === static::IN_CONTEXT_ISO) {
            $this->smarty->assign('project', static::PROJECT_IDENTIFIER);

            return $this->display(__FILE__, 'views/templates/hook/jipt.tpl');
        }

        return '';
    }

    /**
     * Hook to displayBackOfficeFooter
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function hookDisplayBackOfficeFooter()
    {
        if (!Configuration::get(static::JIPT_BACK_OFFICE)) {
            return '';
        }

        $liveTranslationEnabled = ($this->context->cookie->incontext_previous_id_lang ? 1 : 0) || $this->context->language->iso_code === static::IN_CONTEXT_ISO;

        $this->context->smarty->assign('incontextEnabled', $liveTranslationEnabled);

        return $this->display(__FILE__, '/views/templates/hook/backofficefooter.tpl');
    }

    /**
     * Get module configuration page
     *
     * @since 1.0.0
     */
    public function getContent()
    {
        if (Tools::getValue('ajax')) {
            header('Content-Type: application/json;charset=utf-8');
            $this->ajaxProcessSwitchIncontextTranslation();

            die();
        }

        if (Tools::isSubmit('switchToZulu')) {
            $incontextIdLang = Language::getIdByIso(Crowdin::IN_CONTEXT_ISO);
            if (Tools::getValue('switchToZulu')) {
                $this->context->cookie->incontext_previous_id_lang = $this->context->employee->id_lang;

                $this->context->employee->id_lang = $incontextIdLang;
                $this->context->employee->save();
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name);
                die();
            } else {
                $targetLang = $this->context->cookie->incontext_previous_id_lang;

                if ((int) $targetLang === (int) $incontextIdLang || !$targetLang) {
                    $targetLang = Configuration::get('PS_LANG_DEFAULT');
                }

                $this->context->employee->id_lang = $targetLang;
                $this->context->employee->save();
                unset($this->context->cookie->incontext_previous_id_lang);
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name);
                die();
            }
        } else {
            $this->postProcess();
        }

        $this->context->smarty->assign([
            'zulu'       => (int) $this->context->employee->id_lang === (int) Language::getIdByIso(static::IN_CONTEXT_ISO),
            'moduleLink' => $this->context->link->getAdminLink('AdminModules').'&configure='.$this->name,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/configure.tpl').$this->generateSettingsForm();
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function ajaxProcessSwitchIncontextTranslation()
    {
        if (!Tools::isSubmit('incontext')) {
            die(json_encode(['success' => true]));
        }

        $incontextIdLang = Language::getIdByIso(Crowdin::IN_CONTEXT_ISO);
        if (!$incontextIdLang) {
            Language::loadLanguages();
            $incontextIdLang = Language::getIdByIso(Crowdin::IN_CONTEXT_ISO);
        }

        if (Tools::getValue('incontext') === 'true') {
            if ($incontextIdLang) {
                $this->context->cookie->incontext_previous_id_lang = $this->context->employee->id_lang;

                $this->context->employee->id_lang = $incontextIdLang;
                $this->context->employee->save();

                Configuration::updateValue(static::JIPT_FRONT_OFFICE, true);
                Configuration::updateValue(static::JIPT_BACK_OFFICE, true);

                die(json_encode(['success' => true]));
            }

            die(json_encode(['success' => false]));
        } else {
            $targetLang = $this->context->cookie->incontext_previous_id_lang;

            if ((int) $targetLang === (int) $incontextIdLang || !$targetLang) {
                $targetLang = Configuration::get('PS_LANG_DEFAULT');
            }

            $this->context->employee->id_lang = $targetLang;
            $this->context->employee->save();
            unset($this->context->cookie->incontext_previous_id_lang);

            Configuration::updateValue(static::JIPT_FRONT_OFFICE, false);
            Configuration::updateValue(static::JIPT_BACK_OFFICE, false);

            die(json_encode(['success' => true]));
        }
    }

    /**
     * Post process
     */
    protected function postProcess()
    {
        if (!Tools::isSubmit('submitSettings')) {
            return;
        }

        if (Tools::getValue('submitSettings')) {
            foreach (array_keys($this->getConfigFieldsValues()) as $key) {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }

        if (!Configuration::get(static::JIPT_BACK_OFFICE)) {
            $incontextIdLang = Language::getIdByIso(Crowdin::IN_CONTEXT_ISO);
            $targetLang = $this->context->cookie->incontext_previous_id_lang;

            if ((int) $targetLang === (int) $incontextIdLang || !$targetLang) {
                $targetLang = Configuration::get('PS_LANG_DEFAULT');
            }

            $this->context->employee->id_lang = $targetLang;
            $this->context->employee->save();
            unset($this->context->cookie->incontext_previous_id_lang);
        }
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    protected function generateSettingsForm()
    {
        $fields = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Translation settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Front Office in-context translations'),
                        'name'   => static::JIPT_FRONT_OFFICE,
                        'values' => [
                            [
                                'id'    => 'confirmationSwitch_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id'    => 'confirmationSwitch_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Back Office in-context translations'),
                        'name'   => static::JIPT_BACK_OFFICE,
                        'values' => [
                            [
                                'id'    => 'confirmationSwitch_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id'    => 'confirmationSwitch_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', true).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = '';
        $helper->submit_action = 'submitSettings';
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
        ];

        return $helper->generateForm([$fields]);
    }

    /**
     * @return array
     */
    protected function getConfigFieldsValues()
    {
        return [
            static::JIPT_FRONT_OFFICE => Configuration::get(static::JIPT_FRONT_OFFICE),
            static::JIPT_BACK_OFFICE  => Configuration::get(static::JIPT_BACK_OFFICE),
        ];
    }
}
