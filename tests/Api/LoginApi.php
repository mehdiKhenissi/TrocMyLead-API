<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use \Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of LoginApiTest
 *
 * @author Dev
 */
class LoginApi extends ApiTestCase {

    public static function getToken() {
        $client = self::createClient();
        $response = $client->request('POST', '/api/login', [
            'json' => [
                'username' => 'mehdi.khenissi@tpl17.fr',
                'password' => '1234',
            ],
        ]);
        
        $responseContent = $response->getContent();
        $responseDecode = json_decode($responseContent);

        return $responseDecode->token;
    }
    
}
