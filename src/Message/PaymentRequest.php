<?php

namespace Omnipay\Cardstream\Message;

use Omnipay\Cardstream\Traits\GatewayParameters;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\ResponseInterface;

class PaymentRequest extends AbstractRequest
{

    use GatewayParameters;

    public function getData()
    {
        $queryPayload = [
            'merchantID' => $this->getMerchantId(),
            'xref' => $this->getXref(),
            'action' => 'QUERY',
        ];

        $queryPayload['signature'] = static::sign($queryPayload, $this->merchantSecret);

        $paymentInfo = $this->client->post($queryPayload);
    }

    public function sendData($data)
    {
        // TODO: Implement sendData() method.
    }
}