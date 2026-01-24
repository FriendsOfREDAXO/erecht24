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

final class UpdateClientTest extends TestCase
{
    use ApiHandlerTrait;

    public function testShouldHandleInvalidApiKey(): void
    {
        $client = $this->getRemoteClient($this->getApiHandler());

        $service = $this->getApiHandler('invalid');
        $client = $service->updateClient($client);

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

    public function testShouldRejectInvalidPushMethod(): void
    {
        $service = $this->getApiHandler();

        $client = $this->getRemoteClient($service);
        $client->fill([
            "push_method" => "invalid"
        ]);
        $client = $service->updateClient($client);

        $response = $service->getResponse();
        $this->assertInstanceOf(Response::class, $response);

        $this->assertSame(false, $response->isSuccess());
        $this->assertSame(422, $response->getCode());

        $bodyData = $response->getBodyDataAsArray();
        $this->assertArrayHasKey('message', $bodyData);
        $this->assertArrayHasKey('message_de', $bodyData);
    }

    public function testShouldRejectInvalidPushUri(): void
    {
        $service = $this->getApiHandler();

        $client = $this->getRemoteClient($service);
        $client->fill([
            "push_uri" => null,
        ]);
        $client = $service->updateClient($client);

        $response = $service->getResponse();
        $this->assertInstanceOf(Response::class, $response);

        $this->assertSame(false, $response->isSuccess());
        $this->assertSame(422, $response->getCode());

        $bodyData = $response->getBodyDataAsArray();
        $this->assertArrayHasKey('message', $bodyData);
        $this->assertArrayHasKey('message_de', $bodyData);
    }

    public function testShouldUpdateClientWithClient(): void
    {
        $service = $this->getApiHandler();
        $client = $this->getRemoteClient($service);

        $updates = [
            'push_method' => 'POST',
            'push_uri' => $this->getRandomDomain('update'),
            'cms' => 'WORDPRESS Update',
            'cms_version' => 'v' . rand(1, 100),
            'plugin_name' => $this->getPluginName() . ':' . rand(1, 1000),
            'author_mail' => 'update@update.de',
        ];
        $client->fill($updates);

        $client = $service->updateClient($client);

        $response = $service->getResponse();
        $this->assertInstanceOf(Response::class, $response);

        $this->assertSame(true, $response->isSuccess());
        $this->assertSame(200, $response->getCode());

        $bodyData = $response->getBodyDataAsArray();
        $this->assertArrayHasKey('secret', $bodyData);

        $updatedClient = $this->getRemoteClient($service);

        foreach ($updates as $key => $value)
            $this->assertSame($value, $updatedClient->getAttribute($key));
    }
}