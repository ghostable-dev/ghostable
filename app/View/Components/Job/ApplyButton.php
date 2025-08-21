<?php

namespace App\View\Components\Job;

use App\Core\Entities\Color;
use App\Job\Models\Job;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ApplyButton extends Component
{
    public function __construct(
        public Job $job,
        public ?Color $color = null
    ) 
    {
        if (is_null($color)) {
            $this->color = new Color('#000000');
        }
    }

    public function render(): View|Closure|string
    {
        return view('components.job.apply-button');
    }
}
