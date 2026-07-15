<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class DateTimePicker extends Component
{
    public function __construct(
        public string $name,
        public ?string $id = null,
        public string $mode = 'datetime', // date | time | datetime
        public mixed $value = null,
        public string $label = '',
        public string $placeholder = '',
        public bool $required = false,
        public string $wrapperClass = '',
        public string $inputClass = 'panel-input',
        public ?string $help = null,
        public bool $enableTime = false,
    ) {
        $this->id = $this->id ?: str_replace(['[', ']'], ['_', ''], $name);
        if ($this->mode === 'datetime') {
            $this->enableTime = true;
        }
        if ($this->mode === 'time') {
            $this->enableTime = true;
        }
    }

    public function render(): View
    {
        return view('components.date-time-picker');
    }
}
