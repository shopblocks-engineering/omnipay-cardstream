<?php

namespace Omnipay\Cardstream\Message;

/**
 * Complete an HPP Authorize a payment.
 */

use Omnipay\Common\Message\ResponseInterface;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

class CompletePurchaseResponse extends AbstractResponse
{
    public function isSuccessful()
    {
        return $this->request->getResponseCode() == 0;
    }

    public function isPending()
    {
        return $this->request->getResponseCode() == 9;
    }

    public function isCancelled()
    {
        return $this->request->getResponseCode() == 17;
    }

    public function getTransactionId()
    {
        return $this->request->getOrderRef();
    }

    public function getTransactionReference()
    {
        // confusingly the Cardstream transaction reference is called transactionId
        return $this->request->getTransactionId();
    }

    public function getCode()
    {
        return $this->request->getResonseCode();
    }
}

