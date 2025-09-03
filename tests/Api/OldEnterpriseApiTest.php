<?php

//namespace App\Tests\Api;
//
///*
// * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
// * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
// */
//
//use App\Tests\Api\AbstractEndPoint;
//use \Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\HttpFoundation\Response;
//
///**
// * Description of EnterpriseApi
// *
// * @author Dev
// */
//class EnterpriseApiTest extends AbstractEndPoint {
//    //put your code here
////    public function __construct() {
////        
////    }
//
//    public function testGetEnterprise(): void {
//        //self::bootKernel();
//        $response = $this->getResponseForRequest(Request::METHOD_GET, '/api/testous/testous');
//        $responseContent = $response->getContent();
//        $responseDecode = json_decode($responseContent);
//
//        self::assertResponseIsSuccessful();
//        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
//        self::assertJson($responseContent);
//        self::assertNotEmpty($responseDecode);
//    }
//}
