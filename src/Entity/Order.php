<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @ORM\Table(name="`order`")
 */
class Order
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="orders")
     */
    private $user;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $ordered_items;

    /**
     * @ORM\OneToOne(targetEntity=Chat::class, mappedBy="rel_order", cascade={"persist", "remove"})
     */
    private $chat;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getOrderedItems(): ?string
    {
        return $this->ordered_items;
    }

    public function setOrderedItems(string $ordered_items): self
    {
        $this->ordered_items = $ordered_items;

        return $this;
    }

    public function getChat(): ?Chat
    {
        return $this->chat;
    }

    public function setChat(?Chat $chat): self
    {
        // unset the owning side of the relation if necessary
        if ($chat === null && $this->chat !== null) {
            $this->chat->setRelOrder(null);
        }

        // set the owning side of the relation if necessary
        if ($chat !== null && $chat->getRelOrder() !== $this) {
            $chat->setRelOrder($this);
        }

        $this->chat = $chat;

        return $this;
    }
}
