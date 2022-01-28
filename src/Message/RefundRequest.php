<?php

namespace Omnipay\Cardstream\Message;

class RefundRequest extends BaseRequest
{

    public function getData()
    {
        $data = [];

        $data['merchantID'] = $this->getMerchantId();
        $data['action'] = $this->getAction(); // PREAUTH, VERIFY, SALE, REFUND, REFUND_SALE
        $data['type'] = 1; // ecommerce type
        $data['formResponsive'] = 'Y';
        $data['xref'] = $this->getXref();
        // a delay of zero allows duplicate transactions to go through
        $data['duplicateDelay'] = $this->getTestMode() ? 0 : 300;

        $data['amount'] = $this->getAmountInteger();

        if ($returnUrl = $this->getReturnUrl()) {
            $data['redirectURL'] = $returnUrl;
        }

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

        $this->validateParams([
            'merchantID',
            'merchantSecret',
            'action',
            'currencyCode',
            'countryCode',
        ], array_merge($data, ['merchantSecret' => $this->getMerchantSecret()]));

        return $this->prepare($data);
    }

    public function sendData($data)
    {
        // TODO: Implement sendData() method.
    }

    public function refundRequest()
    {
        $queryPayload = [
            'merchantID' => $this->merchantID,
            'xref' => $xref,
            'action' => 'QUERY',
        ];

        $queryPayload['signature'] = static::sign($queryPayload, $this->merchantSecret);

        $paymentInfo = $this->client->post($queryPayload);

        $state = $paymentInfo['state'] ?? null;

        $payload = [
            'merchantID' => $this->merchantID,
            'xref' => $xref,
        ];

        switch ($state) {
            case 'approved':
            case 'captured':
                $payload['action'] = 'CANCEL';
                break;
            case 'accepted':
                $payload = array_merge($payload, [
                    'type' => 1,
                    'action' => 'REFUND_SALE',
                    'amount' => $amount,
                ]);
                break;
            default:
                throw new \InvalidArgumentException('Something went wrong, we can\'t find transaction '. $xref);
        }

        $payload['signature'] = static::sign($payload, $this->merchantSecret);
        $res = $this->client->post($payload);

        if (isset($res['responseCode']) && $res['responseCode'] == "0") {
            $orderMessage = ($res['responseCode'] == "0" ? "Refund Successful" : "Refund Unsuccessful") . "<br/><br/>";

            $state = $res['state'] ?? null;

            if ($state != 'canceled') {
                $orderMessage .= "Amount Refunded: " . (isset($res['amountReceived']) ? number_format($res['amountReceived'] / pow(10, $res['currencyExponent']), $res['currencyExponent']) : "None") . "<br/><br/>";
            }

            $orderMessage .=
                "Message: " . $res['responseMessage'] . "<br/>" .
                "xref: " . $res['xref'] . "<br/>";

            return [
                'message' => $orderMessage,
                'response' => $res,
            ];
        }
    }
}