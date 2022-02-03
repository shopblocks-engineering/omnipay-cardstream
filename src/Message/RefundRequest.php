<?php

namespace Omnipay\Cardstream\Message;

class RefundRequest extends BaseRequest
{

    public function getData()
    {
        $data = [];

        $data['merchantID'] = $this->getMerchantId();
        $data['action'] = "REFUND"; // PREAUTH, VERIFY, SALE, REFUND, REFUND_SALE
        $data['type'] = 1; // ecommerce type
        $data['xref'] = $this->getXref();

        // a delay of zero allows duplicate transactions to go through
        $data['duplicateDelay'] = $this->getTestMode() ? 0 : 300;

        $data['amount'] = $this->getAmountInteger();

        // Remove items we don't want to send in the request;
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

        return $this->prepare($data);
    }

    public function getEndpoint()
    {
        return static::$directUrl;
    }

    public function sendData($data)
    {
        $response = $this->httpClient->request(
            'POST',
            $this->getEndpoint(),
            [
                'Content-Type' => ' application/x-www-form-urlencoded',
            ],
            http_build_query($data)
        );

        parse_str($response->getBody()->getContents(), $payload);

        return new RefundResponse($this, $payload);
    }

}