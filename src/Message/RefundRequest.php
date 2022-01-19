<?php

namespace Omnipay\Cardstream\Message\Api;

use Omnipay\Common\Message\AbstractRequest;

/**
 * Refund an authorisation.
 */

class RefundRequest extends AbstractRequest
{
    public function getEndpoint()
    {
        return static::$directUrl;
    }

    public function getEndpoint()
    {
        return $this->getPaymentUrl(static::SERVICE_GROUP_PAYMENT_CAPTURE);
    }

    public function getData()
    {
        $data = parent::getData();

        $data['modificationAmount'] = [
            'currency' => $this->getCurrency(),
            'value' => $this->getAmountInteger(),
        ];

        return $data;
    }

    /**
     * TODO: there is also a `technicalCancel` service where the
     * originalMerchantReference (original transactionid) can be supplied
     * instead of the originalPspReferenec (transactionReference).
     */
    public function getEndpoint()
    {
        $service = (
        $this->getRefundIfCaptured()
            ? static::SERVICE_GROUP_PAYMENT_CANCELORREFUND
            : static::SERVICE_GROUP_PAYMENT_CANCEL
        );

        return $this->getPaymentUrl($service);
    }

    public function getData()
    {
        $data = $this->getBaseData();

        $this->validate('transactionReference');

        $data['originalReference'] = $this->getTransactionReference();

        if ($transactionId = $this->getTransactionId()) {
            $data['reference'] = $transactionId;
        }

        return $data;
    }

    /**
     * @return ModificationResponse
     */
    public function createResponse($payload)
    {
        return new ModificationResponse($this, $payload);
    }

    /**
     * @return mixed
     */
    public function getRefundIfCaptured()
    {
        return $this->getParameter('refundIfCaptured');
    }

    /**
     * If set, then when performing a void, then if the authorisation
     * has already been cleared, a full `refund` will be performed
     * automatically in place of the `cancel`.
     *
     * @param mixed $value Treated as boolean
     * @return $this
     */
    public function setRefundIfCaptured($value)
    {
        return $this->setParameter('refundIfCaptured', $value);
    }

    public function sendData($data)
    {
        // TODO: Implement sendData() method.
    }
}
