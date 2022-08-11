<?php

namespace App\Entity;

use App\Repository\YoutubeVideoTagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: YoutubeVideoTagRepository::class)]
class YoutubeVideoTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\ManyToMany(targetEntity: YoutubeVideo::class, inversedBy: 'tags')]
    private Collection $ytVideos;

    public function __construct()
    {
        $this->ytVideos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Collection<int, YoutubeVideo>
     */
    public function getYtVideos(): Collection
    {
        return $this->ytVideos;
    }

    public function addYtVideo(YoutubeVideo $ytVideo): self
    {
        if (!$this->ytVideos->contains($ytVideo)) {
            $this->ytVideos->add($ytVideo);
        }

        return $this;
    }

    public function removeYtVideo(YoutubeVideo $ytVideo): self
    {
        $this->ytVideos->removeElement($ytVideo);

        return $this;
    }
}
