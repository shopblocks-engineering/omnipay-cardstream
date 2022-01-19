<?php

namespace Omnipay\Cardstream\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Cardstream\Traits\GatewayParameters;
use Omnipay\Common\Exception\InvalidRequestException;

abstract class BaseRequest extends AbstractRequest
{
    use GatewayParameters;

    protected $returnContentType = 'application/json';

	/**
	 * @var string	Gateway Hosted API Endpoint
	 */
	static public $hostedUrl = 'https://gateway.cardstream.com/hosted/';

	/**
	 * @var string	Gateway Direct API Endpoint
	 */
	static public $directUrl = 'https://gateway.cardstream.com/direct/';

	/**
	 * @var string	Merchant Account Id or Alias
	 */
	static public $merchantID = '100001';

	/**
	 * @var string	Password for above Merchant Account
	 */
	static public $merchantPwd = null;

	/**
	 * @var string	Secret for above Merchant Account
	 */
	static public $merchantSecret = 'Circle4Take40Idea';

	/**
	 * @var string	Proxy URL if required (eg. 'https://www.proxy.com:3128')
	 */
	static public $proxyUrl = null;

	/**
	 * @var boolean	Enable debugging
	 */
	static public $debug = false;

	/**
	 * Useful response codes
	 */
	const RC_SUCCESS						= 0;	// Transaction successful
	const RC_DO_NOT_HONOR					= 5;	// Transaction declined
	const RC_NO_REASON_TO_DECLINE			= 85;	// Verification successful
	const RC_3DS_AUTHENTICATION_REQUIRED	= 0x1010A;

    /**
     * Transform/cleanse any data as necessary before sending to Cardstream
     */
    public function prepare($data)
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = preg_replace_callback('/[\x00-\x1f]/', function($matches) { return '&#' . ord($matches[0]) . ';'; }, htmlentities($value, ENT_COMPAT, 'UTF-8', true));
            }
        }

        return $data;
    }

    public function validateParams($required, $data)
    {
        foreach ($required as $key) {
            if (empty($data[$key])) {
                throw new InvalidRequestException("The $key parameter must not be empty");
            }
        }
    }

	/**
	 * Send request to Gateway using HTTP Direct API.
	 *
	 * The method will send a request to the Gateway using the HTTP Direct API.
	 *
	 * The request will use the following Gateway properties unless alternative
	 * values are provided in the request;
	 *   + 'directUrl'		- Gateway Direct API Endpoint
	 *   + 'merchantID'		- Merchant Account Id or Alias
	 *   + 'merchantPwd'	- Merchant Account Password (or null)
	 *   + 'merchantSecret'	- Merchant Account Secret (or null)
	 *
	 * The method will {@link sign() sign} the request and also {@link
	 * verifySignature() check the signature} on any response.
	 *
	 * The method will throw an exception if it is unable to send the request
	 * or receive the response.
	 *
	 * The method does not attempt to validate any request fields.
	 *
	 * The method will attempt to send the request using the PHP cURL extension
	 * or failing that the  PHP http stream wrappers. If neither are available
	 * then an exception will be thrown.
	 *
	 * @param	array	$request	request data
	 * @param	array	$options	options (or null)
	 * @return	array				request response
	 *
	 * @throws	InvalidArgumentException	invalid request data
	 * @throws	RuntimeException			communications failure
	 */
	static public function directRequest(array $request, array $options = null) {

		static::debug(__METHOD__ . '() - args=', func_get_args());

		static::prepareRequest($request, $options, $secret, $direct_url, $hosted_url);

		// Sign the request
		if ($secret) {
			$request['signature'] = static::sign($request, $secret);
		}

        var_dump($request);
        exit;

		if (function_exists('curl_init')) {
			$opts = array(
				CURLOPT_POST			=> true,
				CURLOPT_POSTFIELDS		=> http_build_query($request, '', '&'),
				CURLOPT_HEADER			=> false,
				CURLOPT_FAILONERROR		=> true,
				CURLOPT_FOLLOWLOCATION	=> true,
				CURLOPT_RETURNTRANSFER	=> true,
				CURLOPT_USERAGENT		=> $_SERVER['HTTP_USER_AGENT'],
				CURLOPT_PROXY			=> static::$proxyUrl,
			);

			$ch = curl_init($direct_url);

			if (($ch = curl_init($direct_url)) === false) {
				throw new RuntimeException('Failed to initialise communications with Payment Gateway');
			}

			if (curl_setopt_array($ch, $opts) === false || ($data = curl_exec($ch)) === false) {
				$err = curl_error($ch);
				curl_close($ch);
				throw new RuntimeException('Failed to communicate with Payment Gateway: ' . $err);
			}

		} else if (ini_get('allow_url_fopen')) {

			$opts = array(
				'http' => array(
					'method'		=> 'POST',
					'user_agent'	=> $_SERVER['HTTP_USER_AGENT'],
					'proxy'			=> static::$proxyUrl,
					'header'		=> 'Content-Type: application/x-www-form-urlencoded',
					'content'		=> http_build_query($request, '', '&'),
					'timeout'		=> 5,
				)
			);

			$context = stream_context_create($opts);

			if (($data = file_get_contents($direct_url, false, $context)) === false) {
				throw new RuntimeException('Failed to send request to Payment Gateway');
			}

		} else {
			throw new RuntimeException('No means of communicate with Payment Gateway, please enable CURL or HTTP Stream Wrappers');
		}

		if (!$data) {
			throw new RuntimeException('No response from Payment Gateway');
		}

		$response = null;
		parse_str($data, $response);

		static::verifyResponse($response, $secret);

		static::debug(__METHOD__ . '() - ret=', $response);

		return $response;
	}

	/**
	 * Send request to Gateway using HTTP Hosted API.
	 *
	 * The method will send a request to the Gateway using the HTTP Hosted API.
	 *
	 * The request will use the following Gateway properties unless alternative
	 * values are provided in the request;
	 *   + 'hostedUrl'		- Gateway Hosted API Endpoint
	 *   + 'merchantID'		- Merchant Account Id or Alias
	 *   + 'merchantPwd'	- Merchant Account Password (or null)
	 *   + 'merchantSecret'	- Merchant Account Secret (or null)
	 *
	 * The method accepts the following options;
	 *   + 'formAttrs'		- HTML form attributes string
	 *   + 'formHtml'		- HTML to show inside the form
	 *   + 'submitAttrs'	- HTML submit button attributes string
	 *   + 'submitImage'	- URL of image to use as the Submit button
	 *   + 'submitHtml'		- HTML to show on the Submit button
	 *   + 'submitText'		- Text to show on the Submit button
	 *
	 * 'submitImage', 'submitHtml' and 'submitText' are mutually exclusive
	 * options and will be checked for in that order. If none are provided
	 * the submitText='Pay Now' is assumed.
	 *
	 * The method will {@link sign() sign} the request, to allow for submit
	 * button images etc. partial signing will be used.
	 *
	 * The method returns the HTML fragment that needs including in order to
	 * send the request.
	 *
	 * The method will throw an exception if it is unable to send the request.
	 *
	 * The method does not attempt to validate any request fields.
	 *
	 * If the request doesn't contain a 'redirectURL' element then one will be
	 * added which redirects the response to the current script.
	 *
	 * Any response can be {@link verifyResponse() verified} using the following
	 * PHP code;
	 * <code>
	 * try {
	 *     \P3\SDK\Gateway::verifyResponse($_POST);
	 * } catch(\Exception $e) {
	 *     die($e->getMessage());
	 * }
	 * </code>
	 *
	 * @param	array	$request	request data
	 * @param	array	$options	options (or null)
	 * @return	string				request HTML form.
	 *
	 * @throws	InvalidArgumentException	invalid request data
	 */
	static public function hostedRequest(array $request, array $options = null) {

		static::debug(__METHOD__ . '() - args=', func_get_args());

		static::prepareRequest($request, $options, $secret, $direct_url, $hosted_url);
        echo '<pre>';var_dump($secret);echo '</pre>';exit;

		if (!isset($request['redirectURL'])) {
			$request['redirectURL'] = ($_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}

		if ($secret) {
			$request['signature'] = static::sign($request, $secret, true);
		}

        echo '<pre>';var_dump($request);echo '</pre>';exit;

		$ret = '<form method="post" ' .
			(isset($options['formAttrs']) ? $options['formAttrs'] : '') .
			' action="' . htmlentities($hosted_url, ENT_COMPAT, 'UTF-8') . "\">\n";

		foreach ($request as $name => $value) {
			$ret .= static::fieldToHtml($name, $value);
		}

		if (isset($options['formHtml'])) {
			$ret .= $options['formHtml'];
		}

		if (isset($options['submitImage'])) {
			$ret .= '<input ' .
					(isset($options['submitAttrs']) ? $options['submitAttrs'] : '') .
					' type="image" src="' . htmlentities($options['submitImage'], ENT_COMPAT, 'UTF-8') . "\">\n";
		} else if (isset($options['submitHtml'])) {
			$ret .= '<button type="submit" ' .
					(isset($options['submitAttrs']) ? $options['submitAttrs'] : '') .
					">{$options['submitHtml']}</button>\n";
		} else {
			$ret .= '<input ' .
					(isset($options['submitAttrs']) ? $options['submitAttrs'] : '') .
					' type="submit" value="' . (isset($options['submitText']) ? htmlentities($options['submitText'], ENT_COMPAT, 'UTF-8') : 'Pay Now') . "\">\n";
		}

		$ret .= "</form>\n";

		static::debug(__METHOD__ . '() - ret=', $ret);

		return $ret;
	}

	/**
	 * Prepare a request for sending to the Gateway.
	 *
	 * The method will extract the following configuration properties from the
	 * request if they are present;
	 *   + 'merchantSecret'	- Merchant Account Secret (or null)
	 *   + 'directUrl'		- Gateway Direct API Endpoint
	 *   + 'hostedUrl'		- Gateway Hosted API Endpoint
	 *
	 * The method will insert the following configuration properties into the
	 * request if they are not already present;
	 *   + 'merchantID'		- Merchant Account Id or Alias
	 *   + 'merchantPwd'	- Merchant Account Password (or null)
	 *
	 * The method will throw an exception if the request doesn't contain an
	 * 'action' element or a 'merchantID' element (and none could be inserted).
	 *
	 * The method does not attempt to validate any request fields.
	 *
	 * @param	array	$request	request data (input & return)
	 * @param	array	$options	options (or null)
	 * @param	string	$secret		any extracted 'merchantSecret' (return)
	 * @param	string	$direct_url	any extracted 'directUrl' (return)
	 * @param	string	$hosted_url	any extracted 'hostedUrl' (return)
	 * @return	void
	 *
	 * @throws	InvalidArgumentException	invalid request data
	 */
	static public function prepareRequest(array &$request, array $options = null, &$secret, &$direct_url, &$hosted_url) {

		if (!$request) {
			throw new InvalidArgumentException('Request must be provided.');
		}

		if (!isset($request['action'])) {
			throw new InvalidArgumentException('Request must contain an \'action\'.');
		}

		// Insert 'merchantID' if doesn't exist and default is available
		if (!isset($request['merchantID']) && static::$merchantID) {
			$request['merchantID'] = static::$merchantID;
		}

		// Insert 'merchantPwd' if doesn't exist and default is available
		if (!isset($request['merchantPwd']) && static::$merchantPwd) {
			$request['merchantPwd'] = static::$merchantPwd;
		}

		// A 'merchantID' must be set
		if (empty($request['merchantID'])) {
			throw new InvalidArgumentException('Merchant ID or Alias must be provided.');
		}

		if (array_key_exists('merchantSecret', $request)) {
			$secret = $request['merchantSecret'];
			unset($request['merchantSecret']);
		} else {
			$secret = static::$merchantSecret;
		}

		if (array_key_exists('hostedUrl', $request)) {
			$hosted_url = $request['hostedUrl'];
			unset($request['hostedUrl']);
		} else {
			$hosted_url = static::$hostedUrl;
		}

		if (array_key_exists('directUrl', $request)) {
			$direct_url = $request['directUrl'];
			unset($request['directUrl']);
		} else {
			$direct_url = static::$directUrl;
		}

		// Remove items we don't want to send in the request
		// (they may be there if a previous response is sent)
		$request = array_diff_key($request, array(
			'responseCode'=> null,
			'responseMessage' => null,
			'responseStatus' => null,
			'state' => null,
			'signature' => null,
			'merchantAlias' => null,
			'merchantID2' => null,
		));
	}

	/**
	 * Sign the given array of data.
	 * 
	 * This method will return the correct signature for the data array.
	 *
	 * If the secret is not provided then any {@link static::$merchantSecret
	 * default secret} is used.
	 *
	 * The partial parameter is used to indicate that the signature should
	 * be marked as 'partial' and can take three possible value types as
	 * follows;
	 *   + boolean	- sign with all $data fields
	 *   + string	- comma separated list of $data field names to sign
	 *   + array	- array of $data field names to sign
	 *
	 * @param	array	$data		data to sign
	 * @param	string	$secret		secret to use in signing
	 * @param	mixed	$partial	partial signing
	 * @return	string				signature
	 */
	static public function sign(array $data, $secret, $partial = false) {

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

	/**
	 * Collect browser device information.
	 *
	 * The method will return a self submitting HTML form designed to provided
	 * the browser device details in the following integration fields;
	 *   + 'deviceChannel'			- Fixed value 'browser',
	 *   + 'deviceIdentity'			- Browser's UserAgent string
	 *   + 'deviceTimeZone'			- Browser's timezone
	 *   + 'deviceCapabilities'		- Browser's capabilities
	 *   + 'deviceScreenResolution'	- Browser's screen resolution (widthxheightxcolour-depth)
	 *   + 'deviceAcceptContent'	- Browser's accepted content types
	 *   + 'deviceAcceptEncoding'	- Browser's accepted encoding methods
	 *   + 'deviceAcceptLanguage'	- Browser's accepted languages
	 *   + 'deviceAcceptCharset'	- Browser's accepted character sets
	 *
	 * The above fields will be submitted as child elements of a 'browserInfo'
	 * parent field.
	 *
	 * The method accepts the following options;
	 *   + 'formAttrs'		- HTML form attributes string
	 *   + 'formData'		- associative array of additional post data
	 *
	 *
	 * The method returns the HTML fragment that needs including in order to
	 * render the HTML form.
	 *
	 * The browser must suport JavaScript in order to obtain the details and
	 * submit the form.
	 *
	 * @param	array	$options	options (or null)
	 * @return	string				request HTML form.
	 *
	 * @throws	InvalidArgumentException	invalid request data
	 */
	static public function collectBrowserInfo(array $options = null) {

		static::debug(__METHOD__ . '() - args=', func_get_args());

		$form_attrs = 'id="collectBrowserInfo" method="post" action="?"';

		if (isset($options['formAttrs'])) {
			$form_attrs .= $options['formAttrs'];
		}

		$device_data = array(
			'deviceChannel'				=> 'browser',
			'deviceIdentity'			=> (isset($_SERVER['HTTP_USER_AGENT']) ? htmlentities($_SERVER['HTTP_USER_AGENT']) : null),
			'deviceTimeZone'			=> '0',
			'deviceCapabilities'		=> '',
			'deviceScreenResolution'	=> '1x1x1',
			'deviceAcceptContent'		=> (isset($_SERVER['HTTP_ACCEPT']) ? htmlentities($_SERVER['HTTP_ACCEPT']) : null),
			'deviceAcceptEncoding'		=> (isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? htmlentities($_SERVER['HTTP_ACCEPT_ENCODING']) : null),
			'deviceAcceptLanguage'		=> (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? htmlentities($_SERVER['HTTP_ACCEPT_LANGUAGE']) : null),
			'deviceAcceptCharset'		=> (isset($_SERVER['HTTP_ACCEPT_CHARSET']) ? htmlentities($_SERVER['HTTP_ACCEPT_CHARSET']) : null),
		);

		$form_fields = static::fieldToHtml('browserInfo', $device_data);

		if (isset($options['formData'])) {
			foreach ((array)$options['formData'] as $name => $value) {
				$form_fields .= static::fieldToHtml($name, $value);
			}
		}

		$ret = <<<EOS
			<form {$form_attrs}>
				{$form_fields}
			</form>
			<script>
				var screen_depths = [1, 4, 8, 15, 16, 24, 32, 48];
				var screen_width = (window && window.screen ? window.screen.width : '0');
				var screen_height = (window && window.screen ? window.screen.height : '0');
				var screen_depth = (window && window.screen && window.screen.colorDepth && screen_depths.indexOf(window.screen.colorDepth) >= 0 ? window.screen.colorDepth : '0');
				var identity = (window && window.navigator ? window.navigator.userAgent : '');
				var language = (window && window.navigator ? (window.navigator.language ? window.navigator.language : window.navigator.browserLanguage) : '');
				var timezone = (new Date()).getTimezoneOffset();
				var java = (window && window.navigator ? navigator.javaEnabled() : false);
				var fields = document.forms.collectBrowserInfo.elements;
				fields['browserInfo[deviceIdentity]'].value = identity;
				fields['browserInfo[deviceTimeZone]'].value = timezone;
				fields['browserInfo[deviceCapabilities]'].value = 'javascript' + (java ? ',java' : '');
				fields['browserInfo[deviceAcceptLanguage]'].value = language;
				fields['browserInfo[deviceScreenResolution]'].value = screen_width + 'x' + screen_height + 'x' + screen_depth;
				window.setTimeout('document.forms.collectBrowserInfo.submit()', 0);
			</script>
EOS;

		static::debug(__METHOD__ . '() - ret=', $ret);

		return $ret;
	}

	/**
	 * Return the field name and value as HTML input tags.
	 *
	 * The method will return a string containing one or more HTML <input
	 * type="hidden"> tags which can be used to store the name and value.
	 *
	 * @param	string		$name		field name
	 * @param	mixed		$value		field value
	 * @return	string					HTML containing <INPUT> tags
	 */
	private function fieldToHtml($name, $value) {
		$ret = '';
		if (is_array($value)) {
			foreach ($value as $n => $v) {
				$ret .= static::fieldToHtml($name . '[' . $n . ']', $v);
			}
		} else {
			// Convert all applicable characters or none printable characters to HTML entities
			$value = preg_replace_callback('/[\x00-\x1f]/', function($matches) { return '&#' . ord($matches[0]) . ';'; }, htmlentities($value, ENT_COMPAT, 'UTF-8', true));
			$ret = "<input type=\"hidden\" name=\"{$name}\" value=\"{$value}\" />\n";
		}

		return $ret;
	}

	/**
	 * Display debug message into PHP error log.
	 *
	 * The method will write the arguments into the PHP error log if
	 * the {@link $debug} property is true. Any none string arguments
	 * will be {@link \var_export() formatted}.
	 *
	 * @param	mixed		...			value to debug
	 * @return	void
	 */
	static public function debug() {
		if (static::$debug) {
			$msg = '';
			foreach (func_get_args() as $arg) {
				$msg .= (is_string($arg) ? $arg : var_export($arg, true)) . ' ';
			}
			error_log($msg);
		}
	}

    /**
     * Send the request with specified data.
     * This is a URL encoded POST with a JSON response.
     * The POST could be done from teh client, so all parameters
     * are signed. These must be signed in getData()
     *
     * @param  array $data The data to send
     * @return Omnipay\Common\Message\ResponseInterface
     * @throws InvalidRequestException
     */
    public function sendData($data)
    {
        $response = $this->httpClient->request(
            'POST',
            $this->getEndpoint(),
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            http_build_query($data)
        );

        $payload = $this->getJsonData($response);

        return $this->createResponse($payload);
    }

    /**
     * Construct the string for signing from the data.
     * @return string
     */
    public function getSigningString(array $data, $secret, $partial = false)
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
}
