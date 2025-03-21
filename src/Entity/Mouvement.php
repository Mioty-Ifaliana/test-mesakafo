<?php

namespace App\Entity;

use App\Repository\MouvementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MouvementRepository::class)]
#[ORM\Table(name: 'mouvements')]
class Mouvement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['mouvement:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ingredient::class)]
    #[ORM\JoinColumn(name: "id_ingredient", referencedColumnName: "id", nullable: false)]
    #[Groups(['mouvement:read'])]
    private ?Ingredient $ingredient = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['mouvement:read'])]
    private ?int $entree = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['mouvement:read'])]
    private ?int $sortie = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['mouvement:read'])]
    private ?\DateTimeInterface $dateMouvement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIngredient(): ?Ingredient
    {
        return $this->ingredient;
    }

    public function setIngredient(?Ingredient $ingredient): static
    {
        $this->ingredient = $ingredient;
        return $this;
    }

    public function getEntree(): ?int
    {
        return $this->entree;
    }

    public function setEntree(?int $entree): static
    {
        $this->entree = $entree;
        return $this;
    }

    public function getSortie(): ?int
    {
        return $this->sortie;
    }

    public function setSortie(?int $sortie): static
    {
        $this->sortie = $sortie;
        return $this;
    }

    public function getDateMouvement(): ?\DateTimeInterface
    {
        return $this->dateMouvement;
    }

    public function setDateMouvement(\DateTimeInterface $dateMouvement): static
    {
        $this->dateMouvement = $dateMouvement;
        return $this;
    }
}
