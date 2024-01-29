<?php

namespace App\Entity;

class VideoSeriesMatch
{
    private bool $active;
    private string $expr;
    private string $name;
    private int $position;
    private int $occurrence;
    private string $type;

    public function __construct($active, $expr, $name, $position, $occurrence, $type)
    {
        $this->active = $active;
        $this->expr = $expr;
        $this->name = $name;
        $this->position = $position;
        $this->occurrence = $occurrence;
        $this->type = $type;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getExpr(): string
    {
        return $this->expr;
    }

    public function setExpr(string $expr): void
    {
        $this->expr = $expr;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getOccurrence(): int
    {
        return $this->occurrence;
    }

    public function setOccurrence(int $occurrence): void
    {
        $this->occurrence = $occurrence;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}