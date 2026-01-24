<?php
declare(strict_types=1);

namespace eRecht24\RechtstexteSDK\Tests\Service;

use eRecht24\RechtstexteSDK\ApiHandler;
use eRecht24\RechtstexteSDK\Model\LegalText;
use eRecht24\RechtstexteSDK\Model\LegalText\Imprint;
use eRecht24\RechtstexteSDK\Model\LegalText\PrivacyPolicy;
use eRecht24\RechtstexteSDK\Model\LegalText\PrivacyPolicySocialMedia;
use eRecht24\RechtstexteSDK\Model\Response;
use eRecht24\RechtstexteSDK\Tests\Service\ApiHandlerTrait;
use PHPUnit\Framework\TestCase;

final class GetMessageTest extends TestCase
{
    use ApiHandlerTrait;

    public function testShouldHandleInvalidApiKey(): void
    {
        $service = $this->getApiHandler('invalid');

        $message = $service->getMessage();
        $this->assertSame(null, $message);

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

    public function testShouldHandleValidApiKey(): void
    {
        $service = $this->getApiHandler();

        $message = $service->getMessage();
        $response = $service->getResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(true, $response->isSuccess());
        if (!is_null($message)) {
            $this->assertIsString($message);
            $this->assertSame(200, $response->getCode());

            $bodyData = $response->getBodyDataAsArray();
            $this->assertArrayHasKey('message', $bodyData);
            $this->assertArrayHasKey('message_de', $bodyData);
            $this->assertArrayHasKey('call2action', $bodyData);
            $this->assertArrayHasKey('call2action_de', $bodyData);
            $this->assertArrayHasKey('link', $bodyData);
        } else {
            $this->assertNull($message);
            $this->assertSame(204, $response->getCode());
        }
    }
}