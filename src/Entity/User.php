<?php

namespace App\Entity;

//UserOwnedInterface
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Controller\MeController;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\UserOwnedInterface;
use ApiPlatform\OpenApi\Model;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity('email')]
#[ApiResource(
    formats: ['jsonld', 'json'],
    operations: [
        new Get(
                    normalizationContext:['groups'=>['enterpriseInfosOn:readUser', 'userInfosOn:read']],
                    security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')",
                ),
        new GetCollection(
                security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')",
                normalizationContext:['groups'=>['enterpriseInfosOn:readUser', 'userInfosOn:list']],
            ),
        new Post(),
        new Patch(),
        new Delete(),
        new GetCollection(
                name: 'me',
                uriTemplate: '/me',
                controller: MeController::class,
                paginationEnabled:false,
                normalizationContext:['groups'=>['enterpriseInfosOn:readUser',  'userInfosOn:read' ]],
                openapiContext: [
                    'security'=>[ ['JWTAuth'=>[]] ]
                ],
                security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')",
        ),
        new Patch(
                    name: "Reinitisalize user password",
                    uriTemplate: '/users/reinit/password',
                    routeName: 'user_reinit_password',
                     paginationEnabled:false,
                    deserialize: false,
                    openapi: new Model\Operation(
                            summary: 'Reinitisalize user password',
                            description: "Reinitisalize user password",
                            requestBody: new Model\RequestBody(
                                    content: new \ArrayObject([
                                            'application/json' => [
                                                'schema' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'email' => ['type' => 'Email']
                                                    ]
                                                ],
                                                'example' => [
                                                    'email' => 'teste@email.fr',
                                                ]
                                            ]
                                         ])
                            )
                    )
        ),
        new Patch(
                    name: "Update user password",
                    uriTemplate: '/users/{id}/update/password',
                    routeName: 'user_update_password',
                    paginationEnabled:false,
                    deserialize: false,
                    security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')",
                    openapi: new Model\Operation(
                            summary: 'Update user password',
                            description: "Update user password",
                            requestBody: new Model\RequestBody(
                                    content: new \ArrayObject([
                                            'application/json' => [
                                                'schema' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'email' => ['type' => 'Email']
                                                    ]
                                                ],
                                                'example' => [
                                                    'email' => 'teste@email.fr',
                                                ]
                                            ]
                                         ])
                            )
                    )
        ),
    ]
)]

class User implements UserInterface, PasswordAuthenticatedUserInterface, JWTUserInterface, UserOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['userInfosOn:read', 'userInfosOn:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['userInfosOn:read', 'userInfosOn:list'])]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(['userInfosOn:read', 'userInfosOn:list'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[Groups(['userInfosOn:read', 'userInfosOn:list'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 64)]
    #[Groups(['userInfosOn:read', 'userInfosOn:list'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['userInfosOn:read', 'userInfosOn:list'])]
    private ?int $phone = null;

    #[ORM\Column(length: 64)]
    #[Groups(['userInfosOn:read', 'userInfosOn:list'])]
    private ?string $code_validation = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['userInfosOn:read', 'userInfosOn:list'])]
    private ?\DateTimeImmutable $enabled_at = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['userInfosOn:read', 'userInfosOn:list'])]
    private ?\DateTimeImmutable $disabled_at = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups(['userInfosOn:read', 'userInfosOn:list'])]
    private ?int $main = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[Groups(['enterpriseInfosOn:readUser',  'userInfosOn:list'])]
    private ?Enterprise $enterprise = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['userInfosOn:read', 'userInfosOn:list'])]
    private ?string $stripe_customer_id = null;
    
    
    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }
    
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        //$roles[] = 'ROLE_USER';
        
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPhone(): ?int
    {
        return $this->phone;
    }

    public function setPhone(int $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCodeValidation(): ?string
    {
        return $this->code_validation;
    }

    public function setCodeValidation(string $code_validation): static
    {
        $this->code_validation = $code_validation;

        return $this;
    }

    public function getEnabledAt(): ?\DateTimeImmutable
    {
        return $this->enabled_at;
    }

    public function setEnabledAt(?\DateTimeImmutable $enabled_at): static
    {
        $this->enabled_at = $enabled_at;

        return $this;
    }

    public function getDisabledAt(): ?\DateTimeImmutable
    {
        return $this->disabled_at;
    }

    public function setDisabledAt(?\DateTimeImmutable $disabled_at): static
    {
        $this->disabled_at = $disabled_at;

        return $this;
    }

    public function getMain(): ?int
    {
        return $this->main;
    }

    public function setMain(?int $main): static
    {
        $this->main = $main;

        return $this;
    }

    public function getEnterprise(): ?Enterprise
    {
        return $this->enterprise;
    }

    public function setEnterprise(?Enterprise $enterprise): static
    {
        $this->enterprise = $enterprise;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public static function createFromPayload($username, array $payload)
    {
        
        // TODO: Implement createFromPayload() method.
        // to add loading data of user from token if connected to optimise no querying database to get user infos with adding class: App\Entity\User
        //dd($username);
        //$user = new User();
        //$user->setEmail($username);
        
       return (new User())->setId($username)->setEmail($payload['username'] ?? '')->setRoles($payload['roles']);
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripe_customer_id;
    }

    public function setStripeCustomerId(?string $stripe_customer_id): static
    {
        $this->stripe_customer_id = $stripe_customer_id;

        return $this;
    }

    
}
