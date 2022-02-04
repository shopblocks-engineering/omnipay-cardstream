<?php

namespace Omnipay\Cardstream\Message;

/**
 * Voids an authorised payment, so it can no longer be captured.
 */
class VoidRequest extends BaseRequest
{
    public function getData()
    {
        $data = [];

        $data['merchantID'] = $this->getMerchantId();
        $data['action'] = "CANCEL";
        
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