<?php

namespace Omnipay\Cardstream;

use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->gateway = new HppGateway($this->getHttpClient(), $this->getHttpRequest());
    }

    public function testAuthorize()
    {
        $request = $this->gateway->authorize(['amount' => '10.00']);
        $this->assertInstanceOf('Omnipay\Cardstream\Message\AuthorizeRequest', $request);
        $this->assertSame('10.00', $request->getAmount());
    }
}

