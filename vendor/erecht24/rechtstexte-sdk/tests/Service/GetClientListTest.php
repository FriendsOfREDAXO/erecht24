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

final class GetClientListTest extends TestCase
{
    use ApiHandlerTrait;

    public function testShouldHandleInvalidApiKey(): void
    {
        $service = $this->getApiHandler('invalid');

        $clients = $service->getClientList();
        $this->assertInstanceOf(Collection::class, $clients);
        $this->assertSame(0, $clients->count());

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

    public function testShouldListClients(): void
    {
        $service = $this->getApiHandler();
        $this->forceAtLeastOneClient($service);

        $clients = $service->getClientList();
        $response = $service->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(true, $response->isSuccess());
        $this->assertSame(200, $response->getCode());
        $this->assertTrue(0 < count($response->getBodyDataAsArray()));
        $this->assertInstanceOf(Collection::class, $clients);

        foreach ($clients as $item) {
            $this->assertInstanceOf(Client::class, $item);
            $this->assertTrue(is_int($item->getClientId()));
        }
    }
}