<?php
//
///*
// * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
// * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
// */
//
//namespace App\Tests\Api;
//
//use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
//use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
//use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\HttpFoundation\Response;
///**
// * Description of AbstractEndPoint
// *
// * @author Dev
// */
//class AbstractEndPoint extends WebTestCase{
//    //put your code here
//    
//    private array $serverInformations = ['ACCEPT'=>'application/json', 'Content_Type'=>'application/json'];
//    
//    public function getResponseForRequest(string $method, string $url, string $payload = null) : Response{
//        
//        
//        $client = self::createClient();
//        
//        $client->request(
//                $method,
//                $url,
//                [],
//                [],
//                $this->serverInformations,
//                $payload
//        );
//        
//        return $client->getResponse();
//        
//    }
//}
