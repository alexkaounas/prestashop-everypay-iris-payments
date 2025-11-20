<?php

class IrisPaymentsCallbackModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        parent::postProcess();

        $secretKey = Configuration::get('IRIS_SECRET_KEY');

        if (empty($secretKey)) {
            $this->redirectToCheckout($this->module->l('IRIS Payments secret key not configured.', 'callback'));
        }

        $data = [
            'md'    => Tools::getValue('md'),
            'token' => Tools::getValue('token'),
            'hash'  => Tools::getValue('hash'),
        ];

        if (empty($data)) {
            $raw = Tools::file_get_contents('php://input');
            $data = json_decode($raw, true);
        }

        if (!is_array($data)) {
            $this->redirectToCheckout($this->module->l('Invalid IRIS Payments callback payload.', 'callback'));
        }

        $errorStatus = isset($data['error']) ? $data['error']['status'] : null;
        $errorMessage = isset($data['error']) ? $data['error']['message'] : null;

        
        // If we have an error_status, show error and exit
        if (!empty($errorStatus)) {
            $this->redirectToCheckout($errorMessage, $errorStatus);
        }
        
        $md    = isset($data['md']) ? $data['md'] : null;
        $token = isset($data['token']) ? $data['token'] : null;
        $hash  = isset($data['hash']) ? $data['hash'] : null;

        // Basic presence checks.
        if (empty($token) || empty($md) || empty($hash)) {
            $this->redirectToCheckout($this->module->l('Missing required fields in IRIS callback.', 'callback'));
        }

        // 1. Decode base64.
        $decoded = base64_decode($hash);

        // 2. Split into signature + JSON payload.
        list($signatureHex, $jsonPayload) = explode('|', $decoded, 2);

        // 3. Compute local expected HMAC (hex output).
        $expectedSignatureHex = hash_hmac('sha256', $jsonPayload, $secretKey);

        // 4. Validate.
        if (!hash_equals($signatureHex, $expectedSignatureHex)) {
            $this->redirectToCheckout($this->module->l('Invalid IRIS callback signature.', 'callback'));
        }

        // 5. Now decode payload.
        $data = json_decode($jsonPayload, true);

        // Extract cart id from md (we stored "cart:{id_cart}").
        if (strpos($md, 'cart:') !== 0) {
            $this->redirectToCheckout($this->module->l('Invalid IRIS Payments cart reference.', 'callback'));
        }

        $id_cart = (int) substr($md, 5);

        if ($id_cart <= 0) {
            $this->redirectToCheckout($this->module->l('Invalid IRIS Payments cart in response.', 'callback'));
        }

        $cart = new Cart($id_cart);

        if (!Validate::isLoadedObject($cart)) {
            $this->redirectToCheckout($this->module->l('IRIS Payments cart not found.', 'callback'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            $this->redirectToCheckout($this->module->l('IRIS Payments customer not found.', 'callback'));
        }

        $orderTotal = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $currency = new Currency($cart->id_currency);

        $orderStatusId = (int) Configuration::get('IRIS_ORDER_STATE');

        $existingOrderId = Order::getOrderByCartId((int) $cart->id);

        // Order already exists – just redirect to confirmation.
        if ($existingOrderId) {
            Tools::redirect(
                'index.php?controller=order-confirmation'
                . '&id_cart=' . (int) $cart->id
                . '&id_module=' . (int) $this->module->id
                . '&id_order=' . (int) $existingOrderId
                . '&key=' . $customer->secure_key
            );
        }

        $this->module->validateOrder(
            (int) $cart->id,
            $orderStatusId,
            $orderTotal,
            $this->module->displayName,
            null,
            ['transaction_id' => pSQL($token)],
            (int) $currency->id,
            false,
            $customer->secure_key
        );

        $orderId = (int) $this->module->currentOrder;

        Tools::redirect(
            'index.php?controller=order-confirmation'
            . '&id_cart=' . (int) $cart->id
            . '&id_module=' . (int) $this->module->id
            . '&id_order=' . $orderId
            . '&key=' . $customer->secure_key
        );
    }

    /**
     * Redirect to checkout with error logging.
     *
     * @param string $message Log message
     * @param string $status Optional status code
     *
     * @return void
     */
    protected function redirectToCheckout(string $message, string $status = '0'): void
    {
        PrestaShopLogger::addLog($message, 3, $status, 'Cart', (int) $this->context->cart->id, true);
        Tools::redirect($this->context->link->getPageLink('order', true, null, ['is_iris_error' => 'true']));
        exit;
    }
}
