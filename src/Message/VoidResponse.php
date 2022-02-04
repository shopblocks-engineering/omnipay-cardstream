<?php

namespace Omnipay\Cardstream\Message;

/**
 * Send the user to the Hosted Payment Page to complete their payment.
 */

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

class VoidResponse extends AbstractResponse implements RedirectResponseInterface
{
    public $response;
    public $request;

    public function __construct($request, $response)
    {
        $this->response = $response;
        $this->request = $request;
    }
    
    public function getRedirectUrl()
    {
        return 'https://gateway.cardstream.com/';
    }

    public function getTransactionReference()
    {
        return $this->request->getTransactionReference();
    }

    public function getTransactionId(): ?string
    {
        return $this->data['orderRef'];
    }
    
    public function getResponseCode()
    {
        return $this->response['responseCode'];
    }
    
    public function isSuccessful()
    {
        return $this->getResponseCode() == 0;
    }

    public function getMessage()
    {
        return $this->response['responseMessage'];
    }
}