<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\EventRepository;
use App\Service\DateService;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ApiResource]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // https://symfony.com/doc/current/validation.html
    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subheading = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?DateTime $date;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt;

    #[ORM\Column]
    private ?DateTime $updatedAt;

    #[ORM\Column]
    private ?bool $visible;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbnail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $banner = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventImage::class)]
    private Collection $images;

    public function __construct(DateService $dateService)
    {
//        $this->date = new DateTime('now', new \DateTimeZone('+0100'));
        $this->date = $dateService->getNow();
        $this->createdAt = $dateService->newDateImmutable('now', 'Europe/Paris'); //new DateTimeImmutable();
        $this->updatedAt = $dateService->getNow();
        $this->visible = false;
        $this->images = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;

        return $this;
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

    public function getSubheading(): ?string
    {
        return $this->subheading;
    }

    public function setSubheading(string $subheading): self
    {
        $this->subheading = $subheading;

        return $this;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?string $thumbnail): self
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function getBanner(): ?string
    {
        return $this->banner;
    }

    public function setBanner(?string $banner): self
    {
        $this->banner = $banner;

        return $this;
    }

    /**
     * @return Collection<int, EventImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(EventImage $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setEvent($this);
        }

        return $this;
    }

    public function removeImage(EventImage $image): self
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getEvent() === $this) {
                $image->setEvent(null);
            }
        }

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'type' => 'event', // 'event' or 'alert'

            'banner' => $this->getBanner(),
            'createdAt' => $this->getCreatedAt(),
            'date' => $this->getDate(),
            'description' => $this->getDescription(),
            'images' => $this->getImages(),
            'name' => $this->getName(),
            'subheading' => $this->getSubheading(),
            'thumbnail' => $this->getThumbnail(),
            'updatedAt' => $this->getUpdatedAt(),
            'user' => $this->getUser(),
            'visible' => $this->isVisible(),
        ];
    }
}
