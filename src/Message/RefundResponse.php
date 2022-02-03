<?php

namespace Omnipay\Cardstream\Message;

/**
 * Send the user to the Hosted Payment Page to complete their payment.
 */

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

class RefundResponse extends AbstractResponse implements RedirectResponseInterface
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

    /**
     * Get the required redirect method (either GET or POST).
     *
     * @return string
     */
    public function getRedirectMethod()
    {
        return 'POST';
    }

    /**
     * Does the response require a redirect?
     *
     * @return boolean
     */
    public function isRedirect()
    {
        return true;
    }

    /**
     * Gets the redirect form data array, if the redirect method is POST.
     *
     * @return array
     */
    public function getRedirectData()
    {
        return $this->getData();
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

    public function isPending()
    {
        return $this->getResponseCode() == 9;
    }

    public function isCancelled()
    {
        return $this->getResponseCode() == 17;
    }

    public function getMessage()
    {
        return $this->response['responseMessage'];
    }

}