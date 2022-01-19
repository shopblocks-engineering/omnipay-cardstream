<?php

namespace Omnipay\Cardstream\Message;

/**
 * Complete an HPP Authorize.
 */

use Omnipay\Common\Exception\InvalidRequestException;

class CompletePurchaseRequest extends BaseRequest
{
    public function getData()
    {
        return $this->getParameters();
    }

    /**
     * Check that the data has retained its correct signature,
     * before passing it on to the response.
     *
     * @param array $data
     * @return CompleteAuthorizeResponse
     * @throws InvalidRequestException
     */
    public function sendData($data)
    {
        return new CompletePurchaseResponse($this, $data);
    }
}
