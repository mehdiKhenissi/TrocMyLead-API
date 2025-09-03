<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use \Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Api\LoginApi;

/**
 * Description of EnterpriseApiTest
 *
 * @author Dev
 */
class EnterpriseApiTest extends ApiTestCase {

    public function testGetEnterprise(): void {
        $client = self::createClient();
        $response = $client->request(
                Request::METHOD_GET,
                '/api/enterprises',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . LoginApi::getToken(),
                    ]
                ]
        );

        $responseContent = $response->getContent();
        $responseDecode = json_decode($responseContent);

        self::assertResponseIsSuccessful();
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertJson($responseContent);
        self::assertNotEmpty($responseDecode);
    }
}
