<?php
declare(strict_types=1);

namespace eRecht24\RechtstexteSDK\Tests\Service;

use eRecht24\RechtstexteSDK\ApiHandler;
use eRecht24\RechtstexteSDK\Model\Client;
use eRecht24\RechtstexteSDK\Model\Collection;
use eRecht24\RechtstexteSDK\Model\LegalText;
use eRecht24\RechtstexteSDK\Model\LegalText\Imprint;
use eRecht24\RechtstexteSDK\Model\LegalText\PrivacyPolicy;
use eRecht24\RechtstexteSDK\Model\LegalText\PrivacyPolicySocialMedia;
use eRecht24\RechtstexteSDK\Model\Response;
use eRecht24\RechtstexteSDK\Tests\Service\ApiHandlerTrait;
use PHPUnit\Framework\TestCase;

final class DeleteClientTest extends TestCase
{
    use ApiHandlerTrait;

    public function testShouldHandleInvalidApiKey(): void
    {
        $client = $this->getRemoteClient($this->getApiHandler());

        $service = $this->getApiHandler('invalid');
        $result = $service->deleteClient($client->getClientId());
        $this->assertSame(false, $result);

        $response = $service->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(false, $response->isSuccess());
        $this->assertSame(401, $response->getCode());

        $bodyData = $response->getBodyDataAsArray();
        $this->assertArrayHasKey('message', $bodyData);
        $this->assertArrayHasKey('message_de', $bodyData);
        $this->assertArrayHasKey('faq_link', $bodyData);
        $this->assertEquals(1649936693, $bodyData['error_code']);
        $this->assertArrayHasKey('debug', $bodyData);
    }

    public function testShouldRejectWrongClientId(): void
    {
        $service = $this->getApiHandler();
        $result = $service->deleteClient(0);
        $this->assertSame(false, $result);

        $response = $service->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(false, $response->isSuccess());
        $this->assertSame(404, $response->getCode());

        $bodyData = $response->getBodyDataAsArray();
        $this->assertArrayHasKey('message', $bodyData);
        $this->assertArrayHasKey('message_de', $bodyData);
    }

    public function testShouldDeleteClient(): void
    {
        $service = $this->getApiHandler();
        $client = $this->getRemoteClient($service);
        $result = $service->deleteClient($client->getClientId());
        $this->assertSame(true, $result);

        $response = $service->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(true, $response->isSuccess());
        $this->assertSame(200, $response->getCode());

        $bodyData = $response->getBodyDataAsArray();
        $this->assertSame([], $bodyData);
    }
}