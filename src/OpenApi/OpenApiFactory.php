<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use http\Env\Response;
use ApiPlatform\OpenApi\Model\RequestBody;

// This classe make you decorated (add delete) the documentation interface ( add csutom paths, add authorizations...)
class OpenApiFactory implements OpenApiFactoryInterface {

    public function __construct(private OpenApiFactoryInterface $decorated) {
        
    }

    public function __invoke(array $context = []): OpenApi {
        $openApi = $this->decorated->__invoke($context);

        /* generate new path manually */
        $openApi->getPaths()->addPath('/mehdi_test',
                new PathItem(null, 'mehdi_test', null, null, new Operation("mehdi-test-id", [], [], "hello"))
        );

        $schemas = $openApi->getComponents()->getSecuritySchemes();
        $schemas['JWTAuth'] = new \ArrayObject([
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT'
        ]);
        $openApi = $openApi->withSecurity(['JWTAuth' => []]);

        // Ajoutez votre opération personnalisée ici
        $pathItem = new PathItem(
                ref: 'Custom Operation',
                get: new Operation(
                        operationId: 'getSpecialInfo',
                        tags: ['Custom'],
                        requestBody: new RequestBody(
                                content: new \ArrayObject([
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                "date_from" => "string",
                                                "date_to" => "string",
                                            ],
                                            'example' => [
                                                 "date_from" => "Format: Y-m-d H:i. (Exemple: ".date("Y-m-d H:i").")",
                                                "date_to" => "Format: Y-m-d H:i. (Exemple: ".date("Y-m-d H:i").")",
                                            ]
                                        ]
                                    ]
                                        ])
                        ),
                        responses: [
                    '200' => [
                        'description' => 'Success response',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'code' => ['type' => 'string', 'example' => '200'],
                                        'status' => ['type' => 'string', 'example' => 'success']
                                    ],
                                ],
                            ],
                        ],
                    ],
                    '400' => [
                        'description' => 'Error response',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'code' => ['type' => 'string', 'example' => '400'],
                                        'status' => ['type' => 'string', 'example' => 'error'],
                                        'message' => ['type' => 'string', 'example' => 'Descript de l\'erreur']
                                    ],
                                ],
                            ],
                        ],
                    ],
                        ],
                        summary: 'Retrieve multiple invoices',
                        description: 'Retrieve multiple invoices in a time interval.'
                )
        );

        $openApi->getPaths()->addPath('/api/download/invoices', $pathItem);

        return $openApi;
    }
}
