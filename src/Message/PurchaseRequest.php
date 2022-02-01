<?php

namespace Omnipay\Cardstream\Message;

/**
 * Send the user to the Hosted Payment Page to complete their payment.
 */


class PurchaseRequest extends BaseRequest
{
    /**
     * @inherit
     */
    public function getData()
    {
        $data = [];

        $data['merchantID'] = $this->getMerchantId();
        $data['action'] = $this->getAction(); // PREAUTH, VERIFY, SALE, REFUND, REFUND_SALE
        $data['type'] = 1; // ecommerce type
        $data['formResponsive'] = 'Y';

        // a delay of zero allows duplicate transactions to go through
        $data['duplicateDelay'] = $this->getTestMode() ? 0 : 300;

        $data['currencyCode'] = $this->getCurrencyCode();
        $data['countryCode'] = $this->getCountryCode();
        $data['amount'] = $this->getAmountInteger();
        $data['orderRef'] = $this->getTransactionId();
        $data['customerName'] = $this->getCustomerName();
        $data['customerEmail'] = $this->getCustomerEmail();
        $data['customerAddress'] = $this->getCustomerAddress();
        $data['customerPostCode'] = $this->getCustomerPostCode();
        $data['customerPhone'] = $this->getCustomerPhone();

        if ($returnUrl = $this->getReturnUrl()) {
            $data['redirectURL'] = $returnUrl;
        }

		// Remove items we don't want to send in the request
		// (they may be there if a previous response is sent)
		$data = array_diff_key($data, [
			'responseCode'=> null,
			'responseMessage' => null,
			'responseStatus' => null,
			'state' => null,
			'signature' => null,
			'merchantAlias' => null,
			'merchantID2' => null,
		]);
        $data['signature'] = $this->getSigningString($data, $this->getMerchantSecret(), true);
        $this->validateParams([
            'merchantID',
            'merchantSecret',
            'action',
            'currencyCode',
            'countryCode',
        ], array_merge($data, ['merchantSecret' => $this->getMerchantSecret()]));

        return $this->prepare($data);
    }

    /**
     * @inherit
     */
    public function sendData($data)
    {
        // The response is a redirect.

        return new PurchaseResponse($this, $data);
    }

    /**
     * addressLocked - determins whether the address can be modidied
     * by the user/shopper.
     */
    public function getAddressLocked()
    {
        return $this->getParameter('addressLocked');
    }

    /**
     * @param mixed $value true or equivalent to lock the address
     */
    public function setAddressLocked($value)
    {
        return $this->setParameter('addressLocked', $value);
    }

    /**
     * addressHidden - determins whether the address can be seen
     * by the user/shopper.
     */
    public function getAddressHidden()
    {
        return $this->getParameter('addressHidden');
    }

    /**
     * @param mixed $value true or equivalent to hide the address
     */
    public function setAddressHidden($value)
    {
        return $this->setParameter('addressHidden', $value);
    }
}
