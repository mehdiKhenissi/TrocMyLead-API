<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\EnterpriseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Controller\EnterpriseFileController;
use ApiPlatform\OpenApi\Model;

#[ORM\Entity(repositoryClass: EnterpriseRepository::class)]
#[UniqueEntity('siren')]
#[ApiResource(
            formats: ['jsonld', 'json', 'multipart'],
            operations: [
        new Get(
                normalizationContext: ['groups' => ['enterpriseInfosOn:readUser', 'enterpriseInfosOn:readEnterprise', 'enterpriseInfosOn:listSell', 'leadsInfosOn:listLitige']],
                openapiContext: [
            'security' => [['JWTAuth' => []]]
                ],
                security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')",
        ),
        new GetCollection(
                normalizationContext: ['groups' => ['enterpriseInfosOn:readLead', 'enterpriseInfosOn:listEnterprises']],
                openapiContext: [
            'security' => [['JWTAuth' => []]]
                ],
                security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')",
        ),
        new Post(
                denormalizationContext: ['groups' => ['enterpriseOn:Post']]
        ),
        new Patch(
                //normalizationContext: ['groups' => ['enterpriseOn:Patch']],
                //denormalizationContext: ['groups' => ['enterpriseOn:Patch']],
                openapiContext: [
            'security' => [['JWTAuth' => []]]
                ],
                security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')",
        ),
//        new Post(
//                name: "upload enterprise files",
//                uriTemplate: '/enterprises/{id}/files',
//                controller: EnterpriseFileController::class,
//                 deserialize: false,
//        ),
        new Post(
                name: "upload enterprise files",
                uriTemplate: '/enterprises/{id}/upload/files',
                routeName: 'upload_enterprises_files',
                deserialize: false,
                openapi: new Model\Operation(
                        summary: 'Upload enterprises files',
                        description: "Upload enterprises files",
                        requestBody: new Model\RequestBody(
                                content: new \ArrayObject([
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'file_name' => ['type' => 'File']
                                            ]
                                        ]
                                    ]
                                        ])
                        )
                )
        ),
        new Delete(),
            ]
    )]
class Enterprise {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['enterpriseInfosOn:readLead', 'enterpriseInfosOn:readEnterprise', 'enterpriseInfosOn:readUser', 'enterpriseInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    #[Groups(['enterpriseInfosOn:readLead', 'enterpriseInfosOn:readEnterprise', 'enterpriseInfosOn:readUser', 'enterpriseOn:Post', 'enterpriseOn:Patch', 'enterpriseInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?string $name = null;

    #[ORM\Column(length: 128)]
    #[Groups(['enterpriseInfosOn:listEnterprises', 'enterpriseInfosOn:readEnterprise', 'enterpriseOn:Post', 'enterpriseOn:Patch'])]
    private ?string $address = null;

    #[ORM\Column(name: 'postal_code')]
    #[Groups(['enterpriseInfosOn:listEnterprises', 'enterpriseInfosOn:readEnterprise', 'enterpriseOn:Post', 'enterpriseOn:Patch'])]
    private ?int $postalCode = null;

    #[ORM\Column(length: 128)]
    #[Groups(['enterpriseInfosOn:listEnterprises', 'enterpriseInfosOn:readEnterprise', 'enterpriseOn:Post', 'enterpriseOn:Patch'])]
    private ?string $city = null;

    #[ORM\Column(length: 64)]
    #[Groups(['enterpriseInfosOn:listEnterprises', 'enterpriseInfosOn:readEnterprise', 'enterpriseOn:Post', 'enterpriseOn:Patch'])]
    private ?string $country = null;

    #[ORM\Column]
    #[Groups(['enterpriseInfosOn:listEnterprises', 'enterpriseInfosOn:readEnterprise', 'enterpriseOn:Post', 'enterpriseOn:Patch'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['enterpriseInfosOn:listEnterprises', 'enterpriseInfosOn:readEnterprise', 'enterpriseOn:Post',  'enterpriseOn:Patch'])]
    private ?\DateTimeImmutable $disabled_at = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['enterpriseInfosOn:listEnterprises', 'enterpriseInfosOn:readEnterprise',  'enterpriseOn:Post', 'enterpriseOn:Patch'])]
    private ?\DateTimeImmutable $enabled_at = null;

    #[ORM\Column]
    #[Groups(['enterpriseInfosOn:listEnterprises', 'enterpriseInfosOn:readEnterprise', 'enterpriseOn:Post', 'enterpriseOn:Patch'])]
    private ?int $siren = null;

    #[ORM\OneToMany(mappedBy: 'enterprise', targetEntity: Leads::class)]
    private Collection $leads;

    #[ORM\OneToMany(mappedBy: 'enterprise', targetEntity: User::class)]
    private Collection $users;

    #[ORM\OneToMany(mappedBy: 'buyer_enterprise', targetEntity: Sell::class)]
    private Collection $sells;

    public function __construct() {
        $this->created_at = new \DateTimeImmutable();
        $this->leads = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->sells = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(string $name): static {
        $this->name = $name;

        return $this;
    }

    public function getAddress(): ?string {
        return $this->address;
    }

    public function setAddress(string $address): static {
        $this->address = $address;

        return $this;
    }

    public function getPostalCode(): ?int {
        return $this->postalCode;
    }

    public function setPostalCode(int $postalCode): static {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string {
        return $this->city;
    }

    public function setCity(string $city): static {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string {
        return $this->country;
    }

    public function setCountry(string $country): static {
        $this->country = $country;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static {
        $this->created_at = $created_at;

        return $this;
    }

    public function getDisabledAt(): ?\DateTimeImmutable {
        return $this->disabled_at;
    }

    public function setDisabledAt(?\DateTimeImmutable $disabled_at): static {
        $this->disabled_at = $disabled_at;

        return $this;
    }

    public function getEnabledAt(): ?\DateTimeImmutable {
        return $this->enabled_at;
    }

    public function setEnabledAt(?\DateTimeImmutable $enabled_at): static {
        $this->enabled_at = $enabled_at;

        return $this;
    }

    public function getSiren(): ?int {
        return $this->siren;
    }

    public function setSiren(int $siren): static {
        $this->siren = $siren;

        return $this;
    }

    /**
     * @return Collection<int, Leads>
     */
    public function getLeads(): Collection {
        return $this->leads;
    }

    public function addLead(Leads $lead): static {
        if (!$this->leads->contains($lead)) {
            $this->leads->add($lead);
            $lead->setEnterprise($this);
        }

        return $this;
    }

    public function removeLead(Leads $lead): static {
        if ($this->leads->removeElement($lead)) {
            // set the owning side to null (unless already changed)
            if ($lead->getEnterprise() === $this) {
                $lead->setEnterprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection {
        return $this->users;
    }

    public function addUser(User $user): static {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setEnterprise($this);
        }

        return $this;
    }

    public function removeUser(User $user): static {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getEnterprise() === $this) {
                $user->setEnterprise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Sell>
     */
    public function getSells(): Collection
    {
        return $this->sells;
    }

    public function addSell(Sell $sell): static
    {
        if (!$this->sells->contains($sell)) {
            $this->sells->add($sell);
            $sell->setBuyerEnterprise($this);
        }

        return $this;
    }

    public function removeSell(Sell $sell): static
    {
        if ($this->sells->removeElement($sell)) {
            // set the owning side to null (unless already changed)
            if ($sell->getBuyerEnterprise() === $this) {
                $sell->setBuyerEnterprise(null);
            }
        }

        return $this;
    }

  
}
