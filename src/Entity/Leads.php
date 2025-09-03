<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\LeadRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\UserOwnedInterface;
use App\Controller\UserLeadsController;
use App\Controller\LeadController;

#[ORM\Entity(repositoryClass: LeadRepository::class)]
#[UniqueEntity('email', 'phone')]
#[ApiResource(
            forceEager: false,
            formats: ['jsonld', 'json'],
            operations: [
        new Get(
                uriTemplate: '/leads/{id}',
                normalizationContext: ['groups' => ['enterpriseInfosOn:readLead', 'leadInfosOn:read', 'leadsInfosOn:listSell', 'litigsInfosOn:readLead', 'leadsInfosOn:listLitige']]
        ),
        new GetCollection(
                name: 'User leads',
                security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')",
                uriTemplate: '/user-leads',
                //routeName: 'current_user_leads',
                description: 'Retrieves the collection of current user Leads resources.',
                normalizationContext: ['groups' => ['enterpriseInfosOn:readLead', 'leadsInfosOn:list', 'litigsInfosOn:readLead', 'litigsInfosOn:readLead', 'sellInfosOn:readLead']],
                paginationItemsPerPage: 10,
                order: ['id' => 'DESC']
                //controller: UserLeadsController::class,
        ),
        new Post(
                uriTemplate: '/leads',
        ),
        new Patch(
                uriTemplate: '/leads/{id}',
                //routeName: 'patch_lead',
        ),
        new Patch(
                name: 'Status leads',
                security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')",
                //uriTemplate: '/status/lead/{id}',
                routeName: 'update_status_lead',
                description: 'Update status lead.',
        ),
        new Delete(
                uriTemplate: '/leads',
        ),
        new GetCollection(
                name: 'All leads',
                uriTemplate: '/all-leads',
                paginationEnabled: true,
                normalizationContext: ['groups' => ['enterpriseInfosOn:readLead', 'leadsInfosOn:list', 'litigsInfosOn:readLead']],
                paginationItemsPerPage: 20,
                order: ['id' => 'DESC']
        ),
            ]
    )]
class Leads implements UserOwnedInterface {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['leadInfosOn:read', 'leadsInfosOn:list', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'leads')]
    #[ORM\JoinColumn(nullable: false)]
    #[Column(name: 'enterprise_id')]
    #[Groups(['enterpriseInfosOn:readLead', 'leadOn:Post', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?Enterprise $enterprise = null;

    #[ORM\Column(length: 64)]
    #[Groups(['leadsInfosOn:list', 'leadOn:Post', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 64)]
    #[Groups(['leadInfosOn:read', 'leadsInfosOn:list', 'leadOn:Post', 'leadsInfosOn', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?string $name = null;

    #[ORM\Column(length: 128)]
    #[Groups(['leadInfosOn:read', 'leadsInfosOn:list', 'leadOn:Post', 'leadsInfosOn', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?string $address = null;

    #[ORM\Column(length: 6, name: 'postal_code')]
    #[Groups(['leadsInfosOn:list', 'leadOn:Post', 'leadsInfosOn', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?string $postalCode = null;

    #[ORM\Column(length: 64)]
    #[Groups(['leadsInfosOn:list', 'leadOn:Post', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?string $city = null;

    #[ORM\Column(length: 64)]
    #[Groups(['leadsInfosOn:list', 'leadOn:Post', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?string $country = null;

    #[ORM\Column(length: 20)]
    #[Groups(['leadsInfosOn:list', 'leadOn:Post', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?int $phone = null;

    #[ORM\Column(length: 128)]
    #[Groups(['leadsInfosOn:list', 'leadOn:Post', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?string $email = null;

    #[ORM\Column(length: 16)]
    #[Groups(['leadsInfosOn:list', 'leadOn:Post', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['leadsInfosOn:list', 'leadOn:Post', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?string $commentary = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '2', name: 'pricing_to_seller')]
    #[Groups(['leadsInfosOn:list', 'leadOn:Post', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?string $pricingToSeller = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '2', name: 'pricing_to_tpl')]
    #[Groups(['leadsInfosOn:list', 'leadOn:Post', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?string $pricingToTpl = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '2', name: 'pricing_to_increase')]
    #[Groups(['leadsInfosOn:list', 'leadOn:Post', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?string $pricingToIncrease = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['leadsInfosOn:list', 'leadOn:Post', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?\DateTimeImmutable $min_date = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['leadsInfosOn:list', 'leadOn:Post', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?\DateTimeImmutable $max_date = null;

    #[ORM\Column]
    #[Groups(['leadsInfosOn:list', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['leadsInfosOn:list', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?\DateTimeImmutable $disabled_at = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['leadsInfosOn:list', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?\DateTimeImmutable $validated_at = null;

    #[Groups(['leadsInfosOn:listSell', 'litigsInfosOn:readLead'])]
    #[ORM\OneToOne(mappedBy: 'lead', cascade: ['persist', 'remove'], fetch: 'EAGER')]
    private ?Litige $litige = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['leadsInfosOn:list', 'leadOn:Post', 'leadsInfosOn:listSell', 'leadsInfosOn:listLitige'])]
    private ?string $activity = null;

    #[Groups(['sellInfosOn:readLead', 'leadsInfosOn:listLitige'])]
    #[ORM\OneToOne(mappedBy: 'lead', cascade: ['persist', 'remove'])]
    private ?Sell $sell = null;

    public function __construct() {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getEnterprise(): ?enterprise {
        return $this->enterprise;
    }

    public function setEnterprise(?enterprise $enterprise): static {
        $this->enterprise = $enterprise;

        return $this;
    }

    public function getFirstname(): ?string {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static {
        $this->firstname = $firstname;

        return $this;
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

    public function getPostalCode(): ?string {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static {
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

    public function getPhone(): ?int {
        return $this->phone;
    }

    public function setPhone(int $phone): static {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function setEmail(string $email): static {
        $this->email = $email;

        return $this;
    }

    public function getStatus(): ?string {
        return $this->status;
    }

    public function setStatus(string $status): static {
        $this->status = $status;

        return $this;
    }

    public function getCommentary(): ?string {
        return $this->commentary;
    }

    public function setCommentary(?string $commentary): static {
        $this->commentary = $commentary;

        return $this;
    }

    public function getPricingToSeller(): ?string {
        return $this->pricingToSeller;
    }

    public function setPricingToSeller(string $pricingToSeller): static {
        $this->pricingToSeller = $pricingToSeller;

        return $this;
    }

    public function getPricingToTpl(): ?string {
        return $this->pricingToTpl;
    }

    public function setPricingToTpl(string $pricingToTpl): static {
        $this->pricingToTpl = $pricingToTpl;

        return $this;
    }

    public function getPricingToIncrease(): ?string {
        return $this->pricingToIncrease;
    }

    public function setPricingToIncrease(string $pricingToIncrease): static {
        $this->pricingToIncrease = $pricingToIncrease;

        return $this;
    }

    public function getMinDate(): ?\DateTimeImmutable {
        return $this->min_date;
    }

    public function setMinDate(?\DateTimeImmutable $min_date): static {
        $this->min_date = $min_date;

        return $this;
    }

    public function getMaxDate(): ?\DateTimeImmutable {
        return $this->max_date;
    }

    public function setMaxDate(?\DateTimeImmutable $max_date): static {
        $this->max_date = $max_date;

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

    public function getLitige(): ?Litige {
        return $this->litige;
    }

    public function setLitige(?Litige $litige): static {
        // unset the owning side of the relation if necessary
        if ($litige === null && $this->litige !== null) {
            $this->litige->setLead(null);
        }

        // set the owning side of the relation if necessary
        if ($litige !== null && $litige->getLead() !== $this) {
            $litige->setLead($this);
        }

        $this->litige = $litige;

        return $this;
    }

    public function getActivity(): ?string {
        return $this->activity;
    }

    public function setActivity(?string $activity): static {
        $this->activity = $activity;

        return $this;
    }

    public function getSell(): ?Sell {
        return $this->sell;
    }

    public function setSell(Sell $sell): static {
        // set the owning side of the relation if necessary
        if ($sell->getLead() !== $this) {
            $sell->setLead($this);
        }

        $this->sell = $sell;

        return $this;
    }

    public function getValidatedAt(): ?\DateTimeImmutable {
        return $this->validated_at;
    }

    public function setValidatedAt(?\DateTimeImmutable $validated_at): static {
        $this->validated_at = $validated_at;

        return $this;
    }
}
