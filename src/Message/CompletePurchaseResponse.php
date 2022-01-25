<?php

namespace Omnipay\Cardstream\Message;

/**
 * Complete an HPP Authorize a payment.
 */

use Omnipay\Common\Message\AbstractResponse;

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


    public function getCode()
    {
        return $this->request->getResonseCode();
    }


    public function getTransactionReference(): ?int
    {
        return $this->request->getTransactionId() ?? null;
    }

    public function getTransactionId(): ?string
    {
        return $this->request->getOrderRef() ?? null;
    }
}

