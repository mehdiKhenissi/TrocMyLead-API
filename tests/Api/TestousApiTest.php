<?php

namespace App\Tests\Api;

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use \Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of EnterpriseApi
 *
 * @author Dev
 */
class TestousApiTest extends ApiTestCase {

    public function testGetTestous(): void {
        //self::bootKernel();
        $client = self::createClient();
        $response = $client->request(
                Request::METHOD_GET,
                '/api/all-leads',
        );
        //$response = $request->getResponse();
        $responseContent = $response->getContent();
        $responseDecode = json_decode($responseContent);

        self::assertResponseIsSuccessful();
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertJson($responseContent);
        self::assertNotEmpty($responseDecode);
    }
}
