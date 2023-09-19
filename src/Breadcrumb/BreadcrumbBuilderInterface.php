<?php

namespace App\Breadcrumb;

class BreadcrumbBuilderInterface
{
    private array $ids;

    public function __construct(
    )
    {
        $this->ids = [];
    }

    public function getNewId(): int
    {
        $id = 1;
        while (in_array($id, $this->ids)) {
            $id++;
        }
        $this->ids[] = $id;
        return $id;
    }
}