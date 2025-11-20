<?php

class IrisPaymentsSessionModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        parent::postProcess();

        if (!$this->module->active) {
            $this->redirectToCheckout('IRIS Payments module is not active.');
        }

        $cart = $this->context->cart;

        if (!$cart || !$cart->id || !$cart->id_customer || !$cart->id_address_delivery || !$cart->id_address_invoice) {
            $this->redirectToCheckout('Invalid cart or customer information.');
        }

        $amount = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $amountInCents = (int) round($amount * 100);
        $currencyIso = $this->context->currency->iso_code;
        $country = 'GR';

        $secretKey = Configuration::get('IRIS_SECRET_KEY');
        $publicKey = Configuration::get('IRIS_PUBLIC_KEY');
        $merchantName = Configuration::get('IRIS_MERCHANT_NAME', null, null, $this->context->shop->id, 'EveryPay Merchant');
        $sandbox = (bool) Configuration::get('IRIS_SANDBOX');

        if (empty($secretKey) || empty($publicKey)) {
            $this->redirectToCheckout('IRIS Payments configuration not set.');
        }

        $uuid = $this->generateUuidV4();
        $md = 'cart:' . (int) $cart->id;

        $callbackUrl = $this->context->link->getModuleLink($this->module->name, 'callback', [], true);

        $apiBase = $sandbox
            ? 'https://sandbox-api.everypay.gr'
            : 'https://api.everypay.gr';

        $postRequest = [
            'amount'        => $amountInCents,
            'currency'      => strtoupper($currencyIso),
            'merchantName'  => $merchantName,
            'country'       => $country,
            'uuid'          => $uuid,
            'callback_url'  => $callbackUrl,
            'md'            => $md,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiBase . '/iris/sessions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postRequest));
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $secretKey . ':');

        $response = curl_exec($ch);

        if ($response === false) {
            $this->redirectToCheckout('IRIS Payments session error.');
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode < 200 || $httpCode >= 300) {
            $this->redirectToCheckout($response['error']['message'], $response['error']['status']);
        }

        $json = json_decode($response, true);

        if (!$json || !isset($json['signature'], $json['uuid'])) {
            $this->redirectToCheckout('Invalid IRIS Payments session response.');
        }

        // Initialize payment form data.
        $sessionSignature = $json['signature'];
        $sessionUuid = $json['uuid'];

        $payformUrl = $sandbox
            ? 'https://sandbox-payform-api.everypay.gr/api/payment-methods/iris'
            : 'https://payform-api.everypay.gr/api/payment-methods/iris';

        $customer = new Customer($cart->id_customer);
        $address = new Address($cart->id_address_invoice);
        $countryObj = new Country($address->id_country);
        $isoCountry = $countryObj->iso_code;

        $paymentData = [
            'flow'                 => 'direct',
            'token'                => $sessionSignature,
            'signature'            => $sessionSignature,
            'pk'                   => $publicKey,
            // 'public_key'           => $publicKey,
            'amount'               => $amountInCents,
            'currency'             => strtoupper($currencyIso),
            'uuid'                 => $sessionUuid,
            'callback_url'         => $callbackUrl,
            'md'                   => $md,
            'locale'               => Language::getIsoById($this->context->language->id),
            'payer_email'          => $customer->email,
            'billing_country'      => $isoCountry,
            'billing_city'         => $address->city,
            'billing_postal_code'  => $address->postcode,
            'billing_address_line1' => $address->address1,
            'billing_address_line2' => $address->address2,
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $payformUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($paymentData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);

        $data = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $this->redirectToCheckout($data['error']['message'], $data['error']['status']);
        }

        echo $response;
        exit;
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

    /**
     * Generate a UUID v4 string.
     *
     * @return string UUID v4
     */
    protected function generateUuidV4(): string
    {
        $data = openssl_random_pseudo_bytes(16);

        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
