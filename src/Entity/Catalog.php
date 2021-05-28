<?php

namespace App\Entity;

use App\Repository\CatalogRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CatalogRepository::class)
 */
class Catalog
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\OneToMany(targetEntity=CatalogItem::class, mappedBy="catalog")
     */
    private $catalogItems;

    public function __construct()
    {
        $this->catalogItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return Collection|CatalogItem[]
     */
    public function getCatalogItems(): Collection
    {
        return $this->catalogItems;
    }

    public function addCatalogItem(CatalogItem $catalogItem): self
    {
        if (!$this->catalogItems->contains($catalogItem)) {
            $this->catalogItems[] = $catalogItem;
            $catalogItem->setCatalog($this);
        }

        return $this;
    }

    public function removeCatalogItem(CatalogItem $catalogItem): self
    {
        if ($this->catalogItems->removeElement($catalogItem)) {
            // set the owning side to null (unless already changed)
            if ($catalogItem->getCatalog() === $this) {
                $catalogItem->setCatalog(null);
            }
        }

        return $this;
    }
}
