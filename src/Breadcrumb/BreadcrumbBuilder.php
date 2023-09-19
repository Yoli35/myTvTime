<?php

namespace App\Breadcrumb;

use Symfony\Contracts\Translation\TranslatorInterface;

class BreadcrumbBuilder extends BreadcrumbBuilderInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    )
    {
        parent::__construct();
        $this->breadcrumbs = [];
    }

    private int $id = 0;
    private array $breadcrumbs;

    public function rootBreadcrumb(string $name, string $url): self
    {
        $this->id = $this->getNewId();
        $this->breadcrumbs[$this->id] = [];
        $this->addBreadcrumb($name, $url);

        return $this;
    }

    public function addBreadcrumb(string $name, string $url): self
    {
        $this->breadcrumbs[$this->id][] = [
            'name' => $this->translator->trans($name),
            'url' => $url,
        ];
        return $this;
    }

    public function getBreadcrumbs(): array
    {
        return $this->breadcrumbs[$this->id];
    }
}