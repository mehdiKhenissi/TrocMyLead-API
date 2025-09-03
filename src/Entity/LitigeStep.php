<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\LitigeStepRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LitigeStepRepository::class)]
#[ApiResource(
            forceEager: false,
            paginationItemsPerPage: 30,
            formats: ['jsonld', 'json'],
            security: "is_granted('ROLE_ADMIN')",
            openapiContext: ['security' => [['JWTAuth' => []]]],
            operations: [
        new Get(normalizationContext: ['groups' => ['litigeInfosOn:readLitigeStep', 'litigsInfosOn:readLead', 'litigeStepInfosOn:listLitige']]),
        new GetCollection(normalizationContext: ['groups' => ['litigeInfosOn:readLitigeStep', 'litigsInfosOn:readLead', 'litigeStepInfosOn:listLitige']]),
        new GetCollection(
                name: 'Litige Steps by Litige',
                security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')",
                uriTemplate: '/litige_steps/litige/{id}',
                routeName: 'litige_steps_by_litige_id',
                description: 'Retrieves the collection of litige steps resources by litige ID.',
        ),
        new Post(),
        new Patch(),
        new Delete(),
            ]
    )]
class LitigeStep {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['litigsInfosOn:readLead', 'litigeStepInfosOn:listLitige'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['litigsInfosOn:readLead', 'litigeStepInfosOn:listLitige'])]
    private ?int $step = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['litigsInfosOn:readLead', 'litigeStepInfosOn:listLitige'])]
    private ?string $commentary = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['litigsInfosOn:readLead', 'litigeStepInfosOn:listLitige'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\ManyToOne(inversedBy: 'litigeSteps')]
    #[Groups(['litigeInfosOn:readLitigeStep', 'litigeInfosOn:listLitige'])]
    private ?Litige $litige = null;

    public function __construct() {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getStep(): ?int {
        return $this->step;
    }

    public function setStep(int $step): static {
        $this->step = $step;

        return $this;
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

    public function getLitige(): ?Litige {
        return $this->litige;
    }

    public function setLitige(?Litige $litige): static {
        $this->litige = $litige;

        return $this;
    }
}
