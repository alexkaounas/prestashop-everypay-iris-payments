<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class IrisPayments extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'irispayments';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'ALPHA DEV';
        $this->controllers = ['session', 'callback'];
        $this->is_eu_compatible = 1;
        $this->bootstrap = true;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        parent::__construct();

        $this->displayName = $this->l('Iris Payments (EveryPay)');
        $this->description = $this->l('Accept payments via IRIS / EveryPay.');

        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];
    }

    /**
     * Install the module and set default configuration values.
     *
     * @return bool True on success, false on failure
     */
    public function install(): bool
    {
        return parent::install()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('displayOrderConfirmation')
            && $this->registerHook('displayPaymentTop')
            && Configuration::updateValue('IRIS_PUBLIC_KEY', '')
            && Configuration::updateValue('IRIS_SECRET_KEY', '')
            && Configuration::updateValue('IRIS_MERCHANT_NAME', Configuration::get('PS_SHOP_NAME'))
            && Configuration::updateValue('IRIS_ORDER_STATE', (int)Configuration::get('PS_OS_PAYMENT'))
            && Configuration::updateValue('IRIS_SANDBOX', 1);
    }

    /**
     * Uninstall the module and remove configuration values.
     *
     * @return bool True on success, false on failure
     */
    public function uninstall(): bool
    {
        Configuration::deleteByName('IRIS_PUBLIC_KEY');
        Configuration::deleteByName('IRIS_SECRET_KEY');
        Configuration::deleteByName('IRIS_MERCHANT_NAME');
        Configuration::deleteByName('IRIS_ORDER_STATE');
        Configuration::deleteByName('IRIS_SANDBOX');

        return parent::uninstall();
    }

    /**
     * Display payment option during checkout.
     *
     * @param array $params Parameters passed to the hook
     *
     * @return array Array of PaymentOption objects or empty if not available
     */
    public function hookPaymentOptions(array $params): array
    {
        if (!$this->active) {
            return [];
        }

        if (!$this->checkCurrency($params['cart'])) {
            return [];
        }

        $option = new PaymentOption();
        $option->setCallToActionText($this->l('Pay with IRIS'))
            ->setModuleName($this->name)
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/assets/logo.svg'))
            ->setAction($this->context->link->getModuleLink($this->name, 'session', [], true))
            ->setAdditionalInformation(
                $this->fetch('module:' . $this->name . '/views/templates/hook/payment_info.tpl')
            );

        return [$option];
    }

    /**
     * Display success / error message on order-confirmation page
     */
    public function hookDisplayOrderConfirmation($params)
    {
        if (!$this->active) {
            return;
        }

        if (!isset($params['order']) || !Validate::isLoadedObject($params['order'])) {
            return;
        }

        if ($params['order']->module != $this->name) {
            return;
        }

        /** @var Order $order */
        $order = $params['order'];
        $order_state = $order->getCurrentState();

        $this->smarty->assign([
            'status'       => ($order_state == (int) Configuration::get('PS_OS_PAYMENT')) ? 'ok' : 'error',
            'id_order'     => $order->id,
            'reference'    => $order->reference,
            'total_paid'   => Tools::displayPrice($order->total_paid, (int) $order->id_currency),
            'link'         => $this->context->link,
            'module_dir'   => $this->_path,
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/payment_success.tpl');
    }

    /**
     * Display error message on top of payment page.
     */
    public function hookDisplayPaymentTop()
    {
        if (!empty($_GET['is_iris_error']) && $_GET['is_iris_error'] == 'true') {
            ob_start();
            ?>
            <div class="alert alert-danger"><?php echo $this->l('The transaction process failed. Please try again or choose a different payment method.', 'irispayments'); ?></div>
            <?php
            return ob_get_clean();
        }
    }

    /**
     * Check if the module supports the currency of the cart.
     *
     * @param Cart $cart The cart to check
     *
     * @return bool True if the currency is supported, false otherwise
     */
    public function checkCurrency(Cart $cart): bool
    {
        $orderCurrency = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency) {
                if ($orderCurrency->id == $currency['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Back office configuration page.
     *
     * @return string HTML content
     */
    public function getContent(): string
    {
        $output = '';

        if (Tools::isSubmit('submitIrisPayments')) {
            $public = Tools::getValue('IRIS_PUBLIC_KEY');
            $secret = Tools::getValue('IRIS_SECRET_KEY');
            $merchant = Tools::getValue('IRIS_MERCHANT_NAME');
            $orderState = Tools::getValue('IRIS_ORDER_STATE');
            $sandbox = (int) Tools::getValue('IRIS_SANDBOX');

            Configuration::updateValue('IRIS_PUBLIC_KEY', $public);
            Configuration::updateValue('IRIS_SECRET_KEY', $secret);
            Configuration::updateValue('IRIS_MERCHANT_NAME', $merchant);
            Configuration::updateValue('IRIS_ORDER_STATE', $orderState);
            Configuration::updateValue('IRIS_SANDBOX', $sandbox);

            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        return $output . $this->renderForm();
    }

    /**
     * Render configuration form.
     *
     * @return string HTML form
     */
    protected function renderForm(): string
    {
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('IRIS / EveryPay Settings'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Public Key'),
                    'name' => 'IRIS_PUBLIC_KEY',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Secret Key'),
                    'name' => 'IRIS_SECRET_KEY',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Merchant Name'),
                    'name' => 'IRIS_MERCHANT_NAME',
                    'required' => true,
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Order State'),
                    'name' => 'IRIS_ORDER_STATE',
                    'options' => [
                        'query' => OrderState::getOrderStates($this->context->language->id),
                        'id' => 'id_order_state',
                        'name' => 'name',
                    ],
                    'required' => true,
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Sandbox mode'),
                    'name' => 'IRIS_SANDBOX',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'iris_sandbox_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id' => 'iris_sandbox_off',
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
        ];

        $helper = new HelperForm();

        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;
        $helper->submit_action = 'submitIrisPayments';

        $helper->fields_value['IRIS_PUBLIC_KEY'] = Configuration::get('IRIS_PUBLIC_KEY');
        $helper->fields_value['IRIS_SECRET_KEY'] = Configuration::get('IRIS_SECRET_KEY');
        $helper->fields_value['IRIS_MERCHANT_NAME'] = Configuration::get('IRIS_MERCHANT_NAME');
        $helper->fields_value['IRIS_ORDER_STATE'] = Configuration::get('IRIS_ORDER_STATE');
        $helper->fields_value['IRIS_SANDBOX'] = (int) Configuration::get('IRIS_SANDBOX');

        return $helper->generateForm($fields_form);
    }
}
