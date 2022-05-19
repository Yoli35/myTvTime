<?php

namespace App\Entity;

use App\Repository\CastRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CastRepository::class)]
class Cast
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $adult;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $gender;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $cast_id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $known_for_department;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $original_name;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $popularity;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $profile_path;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $character_name;

    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private $credit_id;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $cast_order;

    #[ORM\ManyToMany(targetEntity: TvEpisode::class, mappedBy: 'guest_stars')]
    private $tvEpisodes;

    public function __construct()
    {
        $this->tvEpisodes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdult(): ?bool
    {
        return $this->adult;
    }

    public function setAdult(?bool $adult): self
    {
        $this->adult = $adult;

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

    public function getCastId(): ?int
    {
        return $this->cast_id;
    }

    public function setCastId(?int $cast_id): self
    {
        $this->cast_id = $cast_id;

        return $this;
    }

    public function getKnownForDepartment(): ?string
    {
        return $this->known_for_department;
    }

    public function setKnownForDepartment(?string $known_for_department): self
    {
        $this->known_for_department = $known_for_department;

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

    public function getOriginalName(): ?string
    {
        return $this->original_name;
    }

    public function setOriginalName(?string $original_name): self
    {
        $this->original_name = $original_name;

        return $this;
    }

    public function getPopularity(): ?int
    {
        return $this->popularity;
    }

    public function setPopularity(?int $popularity): self
    {
        $this->popularity = $popularity;

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

    public function getCharacterName(): ?string
    {
        return $this->character_name;
    }

    public function setCharacterName(?string $character_name): self
    {
        $this->character_name = $character_name;

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

    public function getCastOrder(): ?int
    {
        return $this->cast_order;
    }

    public function setCastOrder(?int $cast_order): self
    {
        $this->cast_order = $cast_order;

        return $this;
    }

    /**
     * @return Collection<int, TvEpisode>
     */
    public function getTvEpisodes(): Collection
    {
        return $this->tvEpisodes;
    }

    public function addTvEpisode(TvEpisode $tvEpisode): self
    {
        if (!$this->tvEpisodes->contains($tvEpisode)) {
            $this->tvEpisodes[] = $tvEpisode;
            $tvEpisode->addGuestStar($this);
        }

        return $this;
    }

    public function removeTvEpisode(TvEpisode $tvEpisode): self
    {
        if ($this->tvEpisodes->removeElement($tvEpisode)) {
            $tvEpisode->removeGuestStar($this);
        }

        return $this;
    }
}
