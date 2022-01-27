<?php

namespace Omnipay\Cardstream\Message;

class AuthorizeRequest extends PurchaseRequest
{
    /**
     * @inherit
     */
    public function getData()
    {
        $data = [];
        $data['captureDelay'] = 7;

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
        $data['customerPostCode'] = "NN17 8YG";//$this->getCustomerPostCode();
        $data['customerPhone'] = $this->getCustomerPhone();

        // ??? JUST FOR TESTING !!!
        $data['cardNumber'] = '4929421234600821';
        $data['cardExpiryMonth'] = 12;
        $data['cardExpiryYear'] = 22;
        $data['cardCVV'] = '356';

        // if ($card = $this->getCard()) {
        //     //     $data['billingAddress.houseNumberOrName'] = $card->getBillingAddress1() ?: '';
        //     //     $data['billingAddress.street'] = $card->getBillingAddress2() ?: '';
        //     //     $data['billingAddress.city'] = $card->getBillingCity() ?: '';
        //     //     $data['billingAddress.stateOrProvince'] = $card->getBillingState() ?: '';
        //     //     $data['billingAddress.country'] = $card->getBillingCountry() ?: '';
        //     //     $data['billingAddress.postalCode'] = $card->getBillingPostCode() ?: '';
        //
        //     if ($customerEmail = $card->getEmail()) {
        //         $data['customerEmail'] = $customerEmail;
        //     }
        // }

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

