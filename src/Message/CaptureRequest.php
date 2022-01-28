<?php

namespace Omnipay\Cardstream\Message;

/**
 * Send the user to the Hosted Payment Page to complete their payment.
 */


class CaptureRequest extends BaseRequest
{
    /**
     * @inherit
     */
    public function getData()
    {
        $data = [];

        $data['merchantID'] = $this->getMerchantId();
        $data['action'] = $this->getAction(); // PREAUTH, VERIFY, SALE, REFUND, REFUND_SALE
        
        $data['xref'] = $this->getXref();

		// Remove items we don't want to send in the request
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

    public function getAddressLocked()
    {
        return $this->getParameter('addressLocked');
    }

    public function setAddressLocked($value)
    {
        return $this->setParameter('addressLocked', $value);
    }

    public function getAddressHidden()
    {
        return $this->getParameter('addressHidden');
    }

    public function setAddressHidden($value)
    {
        return $this->setParameter('addressHidden', $value);
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

        return new CaptureResponse($this, $payload);
    }
}
