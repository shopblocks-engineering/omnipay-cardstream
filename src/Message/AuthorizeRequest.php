<?php

namespace Omnipay\Cardstream\Message;

use Omnipay\Common\Exception\InvalidRequestException;

class AuthorizeRequest extends PurchaseRequest
{
    /**
     * @return array
     * @throws InvalidRequestException
     */
    public function getData(): array
    {
        $data = $this->getBaseData();
        $data['action'] = "SALE";
        $data['captureDelay'] = $this->getCaptureDelay();
        $this->validateParams([
            'merchantID',
            'merchantSecret',
            'action',
            'currencyCode',
            'countryCode',
        ], array_merge($data, ['merchantSecret' => $this->getMerchantSecret()]));
        $data['signature'] = $this->getSigningString($data, $this->getMerchantSecret(), true);
        return $this->prepare($data);
    }

    /**
     * @param $data
     * @return PurchaseResponse
     */
    public function sendData($data): PurchaseResponse
    {
        return new PurchaseResponse($this, $data);
    }

    /**
     * addressLocked - determines whether the address can be modified
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

