<?php

namespace Omnipay\Cardstream\Traits;

trait GatewayParameters
{
    /**
     * @return string|null
     */
    public function getMerchantId()
    {
        return $this->getParameter('merchantID');
    }

    public function setMerchantId($value)
    {
        return $this->setParameter('merchantID', $value);
    }

    /**
     * @return string|null
     */
    public function getMerchantSecret()
    {
        return $this->getParameter('merchantSecret');
    }

    public function setMerchantSecret($value)
    {
        return $this->setParameter('merchantSecret', $value);
    }

    public function getAction()
    {
        return $this->getParameter('action');
    }
    public function setAction($value)
    {
        return $this->setParameter('action', $value);
    }
    public function getCurrencyCode()
    {
        return $this->getParameter('currencyCode');
    }
    public function setCurrencyCode($value)
    {
        return $this->setParameter('currencyCode', $value);
    }
    public function getCountryCode()
    {
        return $this->getParameter('countryCode');
    }
    public function setCountryCode($value)
    {
        return $this->setParameter('countryCode', $value);
    }
    public function getAmount()
    {
        return $this->getParameter('amount');
    }
    public function setAmount($value)
    {
        return $this->setParameter('amount', $value);
    }
    public function getDuplicateDelay()
    {
        return $this->getParameter('duplicateDelay');
    }
    public function setDuplicateDelay($value)
    {
        return $this->setParameter('duplicateDelay', $value);
    }
    public function getCustomerName()
    {
        return $this->getParameter('customerName');
    }
    public function setCustomerName($value)
    {
        return $this->setParameter('customerName', $value);
    }
    public function getCustomerEmail()
    {
        return $this->getParameter('customerEmail');
    }
    public function setCustomerEmail($value)
    {
        return $this->setParameter('customerEmail', $value);
    }
    public function getCustomerAddress()
    {
        return $this->getParameter('customerAddress');
    }
    public function setCustomerAddress($value)
    {
        return $this->setParameter('customerAddress', $value);
    }
    public function getCustomerPostCode()
    {
        return $this->getParameter('customerPostCode');
    }
    public function setcustomerPostCode($value)
    {
        return $this->setParameter('customerPostCode', $value);
    }
    public function getCustomerPhone()
    {
        return $this->getParameter('customerPhone');
    }
    public function setCustomerPhone($value)
    {
        return $this->setParameter('customerPhone', $value);
    }
    public function getOrderRef()
    {
        return $this->getParameter('orderRef');
    }
    public function setOrderRef($value)
    {
        return $this->setParameter('orderRef', $value);
    }
    public function getRemoteAddress()
    {
        return $this->getParameter('remoteAddress');
    }
    public function setRemoteAddress($value)
    {
        return $this->setParameter('remoteAddress', $value);
    }
    public function getThreeDSRedirectURL()
    {
        return $this->getParameter('threeDSRedirectURL');
    }
    public function setThreeDSRedirectURL($value)
    {
        return $this->setParameter('threeDSRedirectURL', $value);
    }
    public function getRedirectURL()
    {
        return $this->getParameter('redirectURL');
    }
    public function setRedirectURL($value)
    {
        return $this->setParameter('redirectURL', $value);
    }

    public function getResponseCode()
    {
        return $this->getParameter('responseCode');
    }
    public function setResponseCode($value)
    {
        return $this->setParameter('responseCode', $value);
    }

    public function getSignature()
    {
        return $this->getParameter('signature');
    }
    public function setSignature($value)
    {
        return $this->setParameter('signature', $value);
    }

    public function getCustomerId()
    {
        return $this->getParameter('customerId');
    }

    public function setCustomerId($value)
    {
        return $this->setParameter('customerId', $value);
    }

    public function getXref()
    {
        return $this->getParameter('xref');
    }

    public function setXref(string $value)
    {
        return $this->setParameter('xref', $value);
    }

    public function setCardNumber($number)
    {
        return $this->setParameter("cardNumber", $number);
    }

    public function getCardNumber()
    {
        return $this->getParameter("cardNumber");
    }

    public function getCaptureDelay()
    {
        return $this->getParameter("captureDelay");
    }

    public function setCaptureDelay($delay)
    {
        return $this->setParameter("captureDelay", $delay);
    }
}
