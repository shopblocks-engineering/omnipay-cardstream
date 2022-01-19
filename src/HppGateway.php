<?php

namespace Omnipay\Cardstream;

use Omnipay\Cardstream\Traits\GatewayParameters;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\AbstractGateway;

class HppGateway extends AbstractGateway
{
    use GatewayParameters;

    public function getName()
    {
        return 'Cardstream HPP';
    }

    public function getDefaultParameters()
    {
        return [
            'testMode' => true,
            'merchantID' => '',
            'merchantSecret' => '',
        ];
    }

    public function authorize(array $parameters = [])
    {
        return $this->createRequest('\Omnipay\Cardstream\Message\AuthorizeRequest', $parameters);
    }

    public function refund(array $paramaters = [])
    {
        return $this->createRequest();
    }

     public function purchase(array $parameters = [])
     {
         return $this->createRequest('\Omnipay\Cardstream\Message\PurchaseRequest', $parameters);
     }

     public function completePurchase(array $parameters = array())
     {
         // verify the response while we still have the raw parameters from cardstream
         if (!$this->verifyResponse($parameters, $this->getMerchantSecret())) {
             throw new InvalidRequestException(
                 'Invalid response from Cardstream'
             );
         }

         return $this->createRequest('\Omnipay\Cardstream\Message\CompletePurchaseRequest', $parameters);
     }
    //
    // public function completeAuthorize(array $parameters = [])
    // {
    //     return $this->createRequest('\Omnipay\Cardstream\Message\CompleteAuthorizeRequest', $parameters);
    // }
    //
    // public function capture(array $parameters = [])
    // {
    //     return $this->createRequest('\Omnipay\Cardstream\Message\CaptureRequest', $parameters);
    // }
    //
    // public function void(array $parameters = [])
    // {
    //     return $this->createRequest('\Omnipay\Cardstream\Message\VoidRequest', $parameters);
    // }
    //
    // public function refund(array $parameters = [])
    // {
    //     return $this->createRequest('\Omnipay\Cardstream\Message\RefundRequest', $parameters);
    // }
    //
	 /**
	  * Verify the any response.
	  *
	  * This method will verify that the response is present, contains a response
	  * code and is correctly signed.
	  *
	  * If the response is invalid then an exception will be thrown.
	  *
	  * Any signature is removed from the passed response.
	  *
	  * @param	array	$data		reference to the response to verify
	  * @param	string	$secret		secret to use in signing
	  * @return	boolean				true if signature verifies
	  */
     public function verifyResponse($response, $secret = null)
     {
	 	if (!$response || !isset($response['responseCode'])) {
	 		throw new InvalidRequestException('Invalid response from Payment Gateway');
	 	}

	 	if (!$secret) {
	 		$secret = static::$merchantSecret;
	 	}

	 	$fields = null;
	 	$signature = null;
	 	if (isset($response['signature'])) {
	 		$signature = $response['signature'];
	 		unset($response['signature']);
	 		if ($secret && $signature && strpos($signature, '|') !== false) {
	 			list($signature, $fields) = explode('|', $signature);
	 		}
	 	}

	 	// We display three suitable different exception messages to help show
	 	// secret mismatches between ourselves and the Gateway without giving
	 	// too much away if the messages are displayed to the Cardholder.
	 	if (!$secret && $signature) {
	 		// Signature present when not expected (Gateway has a secret but we don't)
	 		throw new InvalidRequestException('Incorrectly signed response from Payment Gateway (1)');
	 	} else if ($secret && !$signature) {
	 		// Signature missing when one expected (We have a secret but the Gateway doesn't)
	 		throw new InvalidRequestException('Incorrectly signed response from Payment Gateway (2)');
	 	} else if ($secret && static::sign($response, $secret, $fields) !== $signature) {
	 		// Signature mismatch
	 		throw new InvalidRequestException('Incorrectly signed response from Payment Gateway');
	 	}

	 	settype($response['responseCode'], 'integer');

	 	return true;
	 }
    //
	// /**
	//  * Sign the given array of data.
	//  * 
	//  * This method will return the correct signature for the data array.
	//  *
	//  * If the secret is not provided then any {@link static::$merchantSecret
	//  * default secret} is used.
	//  *
	//  * The partial parameter is used to indicate that the signature should
	//  * be marked as 'partial' and can take three possible value types as
	//  * follows;
	//  *   + boolean	- sign with all $data fields
	//  *   + string	- comma separated list of $data field names to sign
	//  *   + array	- array of $data field names to sign
	//  *
	//  * @param	array	$data		data to sign
	//  * @param	string	$secret		secret to use in signing
	//  * @param	mixed	$partial	partial signing
	//  * @return	string				signature
	//  */
	 static public function sign(array $data, $secret, $partial = false)
     {

	 	// Support signing only a subset of the data fields
	 	if ($partial) {
	 		if (is_string($partial)) {
	 			$partial = explode(',', $partial);
	 		}
	 		if (is_array($partial)) {
	 			$data = array_intersect_key($data, array_flip($partial));
	 		}
	 		$partial = join(',', array_keys($data));
	 	}

	 	// Sort the data in ascending ascii key order
	 	ksort($data);

	 	// Convert to a URL encoded string
	 	$ret = http_build_query($data, '', '&');

	 	// Normalise all line endings (CRNL|NLCR|NL|CR) to just NL (%0A)
		 $ret = preg_replace('/%0D%0A|%0A%0D|%0D/i', '%0A', $ret);

		 // Hash the string and secret together
		 $ret = hash('SHA512', $ret . $secret);

		 // Mark as partially signed if required
		 if ($partial) {
		 	$ret . '|' . $partial;
		 }

	 	return $ret;
	 }
}

