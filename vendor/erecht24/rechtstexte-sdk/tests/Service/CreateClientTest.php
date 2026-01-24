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

final class CreateClientTest extends TestCase
{
    use ApiHandlerTrait;

    public function testShouldHandleInvalidApiKey(): void
    {
        $client = new Client();
        $service = $this->getApiHandler('invalid');
        $client = $service->createClient($client);

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

    public function test400ForInvalidHttpMethod(): void
    {
        $service = $this->getApiHandler();
        $this->forceExactTwoClients($service);

        $newClient = new Client([
            'push_method' => 'INVALID',
            'push_uri' => $this->getRandomDomain(),
            'cms' => 'CI',
            'plugin_name' => $this->getPluginName(),
        ]);
        $client = $service->createClient($newClient);

        $response = $service->getResponse();
        $this->assertInstanceOf(Response::class, $response);

        $this->assertSame(false, $response->isSuccess());
        $this->assertSame(422, $response->getCode());

        $bodyData = $response->getBodyDataAsArray();
        $this->assertArrayHasKey('message', $bodyData);
        $this->assertArrayHasKey('message_de', $bodyData);
    }

    public function test400ForMissingPushUri(): void
    {
        $service = $this->getApiHandler();
        $this->forceExactTwoClients($service);

        $newClient = new Client([
            'push_method' => 'GET',
            'cms' => 'CI',
            'plugin_name' => $this->getPluginName(),
        ]);
        $client = $service->createClient($newClient);

        $response = $service->getResponse();
        $this->assertInstanceOf(Response::class, $response);

        $this->assertSame(false, $response->isSuccess());
        $this->assertSame(422, $response->getCode());

        $bodyData = $response->getBodyDataAsArray();
        $this->assertArrayHasKey('message', $bodyData);
        $this->assertArrayHasKey('message_de', $bodyData);
    }

    public function testCanCreateNewClientWithClientModel(): void
    {
        $service = $this->getApiHandler();
        $this->forceExactTwoClients($service);

        $data = [
            'push_method' => 'GET',
            'push_uri' => $this->getRandomDomain(),
            'cms' => 'CI',
            'plugin_name' => $this->getPluginName(),
        ];
        $newClient = new Client($data);
        $client = $service->createClient($newClient);

        $response = $service->getResponse();
        $this->assertInstanceOf(Response::class, $response);

        $this->assertSame(true, $response->isSuccess());
        $this->assertSame(200, $response->getCode());

        $bodyData = $response->getBodyDataAsArray();
        $this->assertArrayHasKey('secret', $bodyData);
        $this->assertArrayHasKey('client_id', $bodyData);

        $createdClient = $this->getNewestClient($service);
        foreach ($data as $key => $value)
            $this->assertSame($value, $createdClient->getAttribute($key));
    }

    public function testCanNotCreateMoreThanThreeClients(): void
    {
        $service = $this->getApiHandler();
        $this->forceExactTwoClients($service);
        $this->addDummyClient($service);

        $newClient = new Client([
            'push_method' => 'GET',
            'push_uri' => $this->getRandomDomain(),
            'cms' => 'CI',
            'plugin_name' => $this->getPluginName(),
        ]);
        $client = $service->createClient($newClient);

        $response = $service->getResponse();
        $this->assertInstanceOf(Response::class, $response);

        $this->assertSame(false, $response->isSuccess());
        $this->assertSame(403, $response->getCode());

        $bodyData = $response->getBodyDataAsArray();
        $this->assertArrayHasKey('message', $bodyData);
        $this->assertArrayHasKey('message_de', $bodyData);
    }
}