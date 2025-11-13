<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\Component;

class PaginationComponent extends Component
{
    public LengthAwarePaginator $collection;

    /**
     * Create a new component instance.
     */
    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.pagination-component');
    }
}
