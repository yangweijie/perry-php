<?php

declare(strict_types=1);

namespace Perry\UI\Styling;

final class Style
{
    /** @var array<string, mixed> */
    private array $properties = [];

    public function set(StyleProperty $property, mixed $value): static
    {
        $this->properties[$property->value] = $value;
        return $this;
    }

    public function get(StyleProperty $property): mixed
    {
        return $this->properties[$property->value] ?? null;
    }

    public function has(StyleProperty $property): bool
    {
        return array_key_exists($property->value, $this->properties);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->properties;
    }

    public function merge(self $other): static
    {
        $clone = clone $this;
        $clone->properties = array_merge($clone->properties, $other->properties);
        return $clone;
    }

    public static function make(): self
    {
        return new self();
    }

    public function backgroundColor(string $color): static
    {
        return $this->set(StyleProperty::BackgroundColor, $color);
    }

    public function foregroundColor(string $color): static
    {
        return $this->set(StyleProperty::ForegroundColor, $color);
    }

    public function fontSize(float $size): static
    {
        return $this->set(StyleProperty::FontSize, $size);
    }

    public function padding(float $all): static
    {
        return $this->set(StyleProperty::Padding, $all);
    }

    public function paddingAll(float $top, float $bottom, float $leading, float $trailing): static
    {
        return $this
            ->set(StyleProperty::PaddingTop, $top)
            ->set(StyleProperty::PaddingBottom, $bottom)
            ->set(StyleProperty::PaddingLeading, $leading)
            ->set(StyleProperty::PaddingTrailing, $trailing);
    }

    public function width(float $width): static
    {
        return $this->set(StyleProperty::Width, $width);
    }

    public function height(float $height): static
    {
        return $this->set(StyleProperty::Height, $height);
    }

    public function cornerRadius(float $radius): static
    {
        return $this->set(StyleProperty::CornerRadius, $radius);
    }

    public function opacity(float $opacity): static
    {
        return $this->set(StyleProperty::Opacity, $opacity);
    }

    public function border(float $width, string $color): static
    {
        return $this
            ->set(StyleProperty::BorderWidth, $width)
            ->set(StyleProperty::BorderColor, $color);
    }

    public function shadow(string $color, float $radius, float $offsetX, float $offsetY): static
    {
        return $this
            ->set(StyleProperty::ShadowColor, $color)
            ->set(StyleProperty::ShadowRadius, $radius)
            ->set(StyleProperty::ShadowOffsetX, $offsetX)
            ->set(StyleProperty::ShadowOffsetY, $offsetY);
    }

    public function fontWeight(string $weight): static
    {
        return $this->set(StyleProperty::FontWeight, $weight);
    }

    public function textAlignment(string $alignment): static
    {
        return $this->set(StyleProperty::TextAlignment, $alignment);
    }
}
