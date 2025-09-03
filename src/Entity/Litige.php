<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\LitigeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\Core\Annotation\ApiOperation;
use ApiPlatform\Core\Annotation\ApiResponse;
use App\Entity\UserOwnedInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: LitigeRepository::class)]
#[ApiResource(
            forceEager: false,
            paginationItemsPerPage: 30,
            formats: ['jsonld', 'json'],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')",
            openapiContext: ['security' => [['JWTAuth' => []]]],
            operations: [
        new Get(normalizationContext: ['groups' => ['litigsInfosOn:readLead']]),
        new GetCollection(
                normalizationContext: ['groups' => ['leadsInfosOn:listLitige', 'litigeInfosOn:list', 'leadsInfosOn:listSell', 'litigeStepInfosOn:listLitige']],
                paginationItemsPerPage: 10,
                ),
        new Post(
                name: 'Create a Litige resource',
                uriTemplate: '/litiges',
                routeName: 'custom_add_new_litige',
                description: 'Creates a Litige resource.',
                //normalizationContext: ['groups' => ['enterpriseInfosOn:readLead', 'leadsInfosOn:list', 'litigsInfosOn:readLead']],
                openapi: new Model\Operation(
                        summary: 'Creates a Litige resource.',
                        description: "The new Litige resource.",
                        requestBody: new Model\RequestBody(
                                content: new \ArrayObject([
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                "commentary" => "string",
                                                "lead" => "number",
                                                "status" => "string"
                                            ]
                                        ]
                                    ]
                                        ])
                        ),
                )
        ),
        new Patch(),
        new Delete(),
            ]
    )]
class Litige implements UserOwnedInterface {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['litigeInfosOn:list', 'leadsInfosOn:listSell', 'litigsInfosOn:readLead'])]
    private ?int $id = null;

    #[Groups(['litigeInfosOn:list', 'leadsInfosOn:listSell', 'litigsInfosOn:readLead'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentary = null;

    #[Groups(['litigeInfosOn:list', 'leadsInfosOn:listSell', 'litigsInfosOn:readLead'])]
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $created_at = null;

    #[Groups(['litigeInfosOn:list', 'leadsInfosOn:listSell', 'litigsInfosOn:readLead'])]
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $closed_at = null;

    #[Groups(['litigeInfosOn:list', 'leadsInfosOn:listSell', 'litigsInfosOn:readLead'])]
    #[ORM\Column(length: 32, nullable: true)]
    private ?string $status = null;

    #[Groups(['leadsInfosOn:listLitige'])]
    #[ORM\OneToOne(inversedBy: 'litige')]
    private ?Leads $lead = null;
    
    #[ORM\OneToMany(mappedBy: 'litige', targetEntity: LitigeStep::class)]
    private Collection $litigeSteps;

    public function __construct() {
        $this->created_at = new \DateTimeImmutable;
        $this->litigeSteps = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getCommentary(): ?string {
        return $this->commentary;
    }

    public function setCommentary(?string $commentary): static {
        $this->commentary = $commentary;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeImmutable $created_at): static {
        $this->created_at = $created_at;

        return $this;
    }

    public function getClosedAt(): ?\DateTimeImmutable {
        return $this->closed_at;
    }

    public function setClosedAt(?\DateTimeImmutable $closed_at): static {
        $this->closed_at = $closed_at;

        return $this;
    }

    public function getStatus(): ?string {
        return $this->status;
    }

    public function setStatus(?string $status): static {
        $this->status = $status;

        return $this;
    }

    public function getLead(): ?Leads {
        return $this->lead;
    }

    public function setLead(?Leads $lead): static {
        $this->lead = $lead;

        return $this;
    }

    public function getLitigeStep(): Collection {
        return $this->litigeSteps;
    }

    public function setLitigeStep(?LitigeStep $litigeStep): static {
        // unset the owning side of the relation if necessary
        if ($litigeStep === null && $this->litigeSteps !== null) {
            $this->litigeSteps->setLitige(null);
        }

        // set the owning side of the relation if necessary
        if ($litigeStep !== null && $litigeStep->getLitige() !== $this) {
            $litigeStep->setLitige($this);
        }


        $this->litigeSteps = $litigeStep;

        return $this;
    }

    public function getEnterprise(): ?enterprise {
        return null;
    }

    public function setEnterprise(?enterprise $enterprise): static {
        return $this;
    }
}
