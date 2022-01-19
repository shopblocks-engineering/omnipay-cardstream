<?php

namespace Omnipay\Cardstream\Message;

class AuthorizeRequest extends PurchaseRequest
{

    /**
     * @inherit
     */
    public function sendData($data)
    {
        // The response is a redirect.

        return new AuthorizeResponse($this, $data);
    }
}

