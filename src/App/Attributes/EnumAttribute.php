<?php

namespace LaravelCommonNew\App\Attributes;

use Attribute;

#[Attribute]
class EnumAttribute
{
    public ?string $label;
    public ?string $value;
    public ?string $color;

    /**
     * @param string|null $label
     * @param string|null $value
     * @param string|null $color
     */
    public function __construct(?string $label = null, ?string $value = null, ?string $color = null)
    {
        $this->label = $label;
        $this->value = $value;
        $this->color = $color;
    }
}
