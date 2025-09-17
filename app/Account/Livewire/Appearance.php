<?php

namespace App\Account\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.shells.user-settings')]
class Appearance extends Component
{
    public function render()
    {
        return view('account.settings.appearance');
    }
}
