<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\SellRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\UserOwnedInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\Core\Annotation\ApiOperation;
use ApiPlatform\Core\Annotation\ApiResponse;

#[ORM\Entity(repositoryClass: SellRepository::class)]
#[ApiResource(
            formats: ['jsonld', 'json'],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')",
            openapiContext: ['security' => [['JWTAuth' => []]]],
            operations: [
        new Get(normalizationContext: ['sellInfosOn:readLead']),
        new Get(
                name: "Retrieves Sell resource informations using specific fields.",
                uriTemplate: '/sells/lead/{id}',
                routeName: 'sells_by_lead',
                openapi: new Model\Operation(
                        summary: 'Retrieves Sell resource informations using lead ID.',
                        description: "Retrieves Sell resource informations using lead ID."
                )
        ),
        new GetCollection(
                normalizationContext: ['groups' => ['leadsInfosOn:listSell', 'sellInfosOn:listSell', 'enterpriseInfosOn:listSell']],
                paginationItemsPerPage: 10,
                ),
        new GetCollection(
                name: 'User leads buyed & selled',
                uriTemplate: '/user-leads/buyed-selled',
                routeName: 'user_leads_buyed_selled',
                description: 'Retrieves the collection of current user Leads buyed & selled.',
                //normalizationContext: ['groups' => ['enterpriseInfosOn:readLead', 'leadsInfosOn:list', 'litigsInfosOn:readLead']],
                openapi: new Model\Operation(
                        summary: 'Retrieves the collection of current user Leads buyed & selled.',
                        description: "Retrieves the collection of current user Leads buyed & selled."
                )
        ),
        new GetCollection(
                name: 'Selled By date interval',
                uriTemplate: '/sells/invoices/buyed-selled/from-to',
                routeName: 'sells_invoices_buyed_selled_from_to',
                description: 'Retrieves the collection of invoice between [from date - to date].',
                //normalizationContext: ['groups' => ['enterpriseInfosOn:readLead', 'leadsInfosOn:list', 'litigsInfosOn:readLead']],
                openapi: new Model\Operation(
                        summary: 'Retrieves the collection of invoice between [from date - to date].',
                        description: "summary: 'Retrieves the collection of invoice between [from date - to date]."
                )
        ),
        new GetCollection(
                name: 'Sells invoices',
                uriTemplate: '/sells/invoices',
                routeName: 'sells_invoices',
                description: 'Retrieves the collection of Sells invoices.',
                //normalizationContext: ['groups' => ['enterpriseInfosOn:readLead', 'leadsInfosOn:list', 'litigsInfosOn:readLead']],
                openapi: new Model\Operation(
                        summary: 'Retrieves the collection of Sells invoices.',
                        description: "summary: 'Retrieves the collection of Sells invoices."
                )
        ),
        new Post(
                name: 'Create a Sell resource',
                uriTemplate: '/sells',
                routeName: 'custom_add_new_sell',
                description: 'Creates a Sell resource.',
                //normalizationContext: ['groups' => ['enterpriseInfosOn:readLead', 'leadsInfosOn:list', 'litigsInfosOn:readLead']],
                openapi: new Model\Operation(
                        summary: 'Creates a Sell resource.',
                        description: "The new Sell resource.",
                        requestBody: new Model\RequestBody(
                                content: new \ArrayObject([
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                "pricing" => "string",
                                                "statut" => "string",
                                                "lead" => "number",
                                                "idStripe" => "string",
                                                "idCharge" => "string",
                                                "invoiceNum" => "string",
                                                "invoiceLink" => "string",
                                                "stripePaymentId" => "string",
                                                "buyerEnterprise" => "number"
                                            ]
                                        ]
                                    ]
                                        ])
                        ),
                )
        ),
        new Patch(),
        new Delete(),
        new Post(
                name: "stripe payment checkout",
                uriTemplate: '/stripe/checkout',
                routeName: 'stripe_checkout',
                openapi: new Model\Operation(
                        summary: 'Stripe payment checkout',
                        description: "Api to get the stripe payment checkout url",
                        requestBody: new Model\RequestBody(
                                content: new \ArrayObject([
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'amount' => ['type' => 'number'],
                                                'success_url' => ['type' => 'url'],
                                                'cancel_url' => ['type' => 'url']
                                            ]
                                        ],
                                        'example' => [
                                            'amount' => 1000,
                                            'success' => "http://www.exemplendd.com/sucess",
                                            'cancel' => "http://www.exemplendd.com/cancel",
                                        ]
                                    ]
                                        ])
                        ),
                        responses: [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => ['application/json' => ['example' => ['code' => 200, 'status' => 'success', "url_checkout" => 'https://checkout.stripe.com/c/pay/....']]],
                    ],
                    '201' => [
                        'description' => 'Successful response',
                        'content' => ['application/json' => ['example' => ['code' => 200, 'status' => 'success', "url_checkout" => 'https://checkout.stripe.com/c/pay/....']]],
                    ],
                    '400' => [
                        'description' => 'Error response',
                        'content' => ['application/json' => ['example' => ['code' => 400, 'status' => 'error', 'message' => "message d'erreur"]]],
                    ],
                    '401' => [
                        'description' => 'Error response',
                        'content' => ['application/json' => ['example' => ['code' => 401, 'status' => 'error', 'message' => "message d'erreur"]]],
                    ],
                    '422' => [
                        'description' => 'Error response',
                        'content' => ['application/json' => ['example' => ['code' => 422, 'status' => 'error', 'message' => "message d'erreur"]]],
                    ]
                        ],
                )
        ),
            ]
    )]
class Sell implements UserOwnedInterface {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sellInfosOn:listSell', 'sellInfosOn:readLead',  'leadsInfosOn:listLitige'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '2', nullable: true)]
    #[Groups(['sellInfosOn:listSell', 'sellInfosOn:readLead'])]
    private ?string $pricing = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['sellInfosOn:readLead'])]
    private ?string $id_stripe = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['sellInfosOn:listSell', 'sellInfosOn:readLead'])]
    private ?string $id_charge = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['sellInfosOn:listSell', 'sellInfosOn:readLead'])]
    private ?string $statut = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['sellInfosOn:listSell', 'sellInfosOn:readLead'])]
    private ?string $invoice_id = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['sellInfosOn:listSell', 'sellInfosOn:readLead'])]
    private ?string $invoice_num = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['sellInfosOn:listSell', 'sellInfosOn:readLead'])]
    private ?string $invoice_link = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['sellInfosOn:listSell', 'sellInfosOn:readLead', 'leadsInfosOn:listLitige'])]
    private ?string $stripe_payment_id = null;

    #[Groups(['sellInfosOn:listSell', 'sellInfosOn:readLead'])]
    #[ORM\ManyToOne(inversedBy: 'sells')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Enterprise $buyer_enterprise = null;

    #[Groups(['sellInfosOn:listSell'])]
    #[ORM\OneToOne(inversedBy: 'sell', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Leads $lead = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['sellInfosOn:listSell', 'sellInfosOn:readLead'])]
    private ?\DateTimeImmutable $created_at = null;

    public function __construct() {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getPricing(): ?string {
        return $this->pricing;
    }

    public function setPricing(?string $pricing): static {
        $this->pricing = $pricing;

        return $this;
    }

    public function getIdStripe(): ?string {
        return $this->id_stripe;
    }

    public function setIdStripe(?string $id_stripe): static {
        $this->id_stripe = $id_stripe;

        return $this;
    }

    public function getIdCharge(): ?string {
        return $this->id_charge;
    }

    public function setIdCharge(?string $id_charge): static {
        $this->id_charge = $id_charge;

        return $this;
    }

    public function getStatut(): ?string {
        return $this->statut;
    }

    public function setStatut(?string $statut): static {
        $this->statut = $statut;

        return $this;
    }

    public function getInvoiceId(): ?string {
        return $this->invoice_id;
    }

    public function setInvoiceId(?string $invoice_id): static {
        $this->invoice_id = $invoice_id;

        return $this;
    }
    
    public function getInvoiceNum(): ?string {
        return $this->invoice_num;
    }

    public function setInvoiceNum(?string $invoice_num): static {
        $this->invoice_num = $invoice_num;

        return $this;
    }

    public function getInvoiceLink(): ?string {
        return $this->invoice_link;
    }

    public function setInvoiceLink(?string $invoice_link): static {
        $this->invoice_link = $invoice_link;

        return $this;
    }

    public function getEnterprise(): ?Enterprise {
        return $this->buyer_enterprise;
    }

    public function setEnterprise(?Enterprise $enterprise): static {
        $this->buyer_enterprise = $enterprise;

        return $this;
    }

    public function getStripePaymentId(): ?string {
        return $this->stripe_payment_id;
    }

    public function setStripePaymentId(string $stripe_payment_id): static {
        $this->stripe_payment_id = $stripe_payment_id;

        return $this;
    }

    public function getBuyerEnterprise(): ?Enterprise {
        return $this->buyer_enterprise;
    }

    public function setBuyerEnterprise(?Enterprise $buyer_enterprise): static {
        $this->buyer_enterprise = $buyer_enterprise;

        return $this;
    }

    public function getLead(): ?Leads {
        return $this->lead;
    }

    public function setLead(Leads $lead): static {
        $this->lead = $lead;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static {
        $this->created_at = $created_at;

        return $this;
    }
}
