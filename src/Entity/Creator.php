<?php

namespace App\Entity;

use App\Repository\CreatorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CreatorRepository::class)]
class Creator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $creator_id;

    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private $credit_id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $name;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $gender;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $profile_path;

    #[ORM\ManyToMany(targetEntity: TvShow::class, mappedBy: 'created_by')]
    private $tvShows;

    public function __construct()
    {
        $this->tvShows = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatorId(): ?int
    {
        return $this->creator_id;
    }

    public function setCreatorId(?int $creator_id): self
    {
        $this->creator_id = $creator_id;

        return $this;
    }

    public function getCreditId(): ?string
    {
        return $this->credit_id;
    }

    public function setCreditId(?string $credit_id): self
    {
        $this->credit_id = $credit_id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getGender(): ?int
    {
        return $this->gender;
    }

    public function setGender(?int $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getProfilePath(): ?string
    {
        return $this->profile_path;
    }

    public function setProfilePath(?string $profile_path): self
    {
        $this->profile_path = $profile_path;

        return $this;
    }

    /**
     * @return Collection<int, TvShow>
     */
    public function getTvShows(): Collection
    {
        return $this->tvShows;
    }

    public function addTvShow(TvShow $tvShow): self
    {
        if (!$this->tvShows->contains($tvShow)) {
            $this->tvShows[] = $tvShow;
            $tvShow->addCreatedBy($this);
        }

        return $this;
    }

    public function removeTvShow(TvShow $tvShow): self
    {
        if ($this->tvShows->removeElement($tvShow)) {
            $tvShow->removeCreatedBy($this);
        }

        return $this;
    }
}
