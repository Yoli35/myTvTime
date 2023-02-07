<?php

namespace App\Entity;

use App\Repository\TimeShiftedNetworkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TimeShiftedNetworkRepository::class)]
class TimeShiftedNetwork
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $networkId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNetworkId(): ?int
    {
        return $this->networkId;
    }

    public function setNetworkId(int $networkId): self
    {
        $this->networkId = $networkId;

        return $this;
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
}
