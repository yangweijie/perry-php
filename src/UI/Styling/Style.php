<?php

declare(strict_types=1);

namespace Perry\UI\Styling;

final class Style
{
    /** @var array<string, mixed> */
    private array $properties = [];

    /** @var array<string, Style> */
    private array $responsiveVariants = [];

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

    // --- Responsive Variants (S1) ---

    public function forBreakpoint(Breakpoint $breakpoint, self $variant): static
    {
        $this->responsiveVariants[$breakpoint->value] = $variant;
        return $this;
    }

    public function resolveVariant(?Breakpoint $breakpoint): ?self
    {
        if ($breakpoint === null) {
            return null;
        }
        return $this->responsiveVariants[$breakpoint->value] ?? null;
    }

    /** @return array<string, Style> */
    public function allVariants(): array
    {
        return $this->responsiveVariants;
    }

    /** @return array<string, mixed> */
    public function allProperties(): array
    {
        return $this->properties;
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

    public function minWidth(float $width): static
    {
        return $this->set(StyleProperty::MinWidth, $width);
    }

    public function minHeight(float $height): static
    {
        return $this->set(StyleProperty::MinHeight, $height);
    }

    public function maxWidth(float $width): static
    {
        return $this->set(StyleProperty::MaxWidth, $width);
    }

    public function maxHeight(float $height): static
    {
        return $this->set(StyleProperty::MaxHeight, $height);
    }

    public function paddingTop(float $top): static
    {
        return $this->set(StyleProperty::PaddingTop, $top);
    }

    public function paddingBottom(float $bottom): static
    {
        return $this->set(StyleProperty::PaddingBottom, $bottom);
    }

    public function paddingLeading(float $leading): static
    {
        return $this->set(StyleProperty::PaddingLeading, $leading);
    }

    public function paddingTrailing(float $trailing): static
    {
        return $this->set(StyleProperty::PaddingTrailing, $trailing);
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

    public function fontFamily(string $family): static
    {
        return $this->set(StyleProperty::FontFamily, $family);
    }

    public function textDecoration(string $decoration): static
    {
        return $this->set(StyleProperty::TextDecoration, $decoration);
    }

    public function letterSpacing(float $spacing): static
    {
        return $this->set(StyleProperty::LetterSpacing, $spacing);
    }

    public function lineSpacing(float $spacing): static
    {
        return $this->set(StyleProperty::LineSpacing, $spacing);
    }

    public function margin(float $margin): static
    {
        return $this->set(StyleProperty::Margin, $margin);
    }

    public function textAlignment(string $alignment): static
    {
        return $this->set(StyleProperty::TextAlignment, $alignment);
    }

    // --- S0a: Flex Layout ---

    public function flexDirection(string $direction): static
    {
        return $this->set(StyleProperty::FlexDirection, $direction);
    }

    public function justifyContent(string $justify): static
    {
        return $this->set(StyleProperty::JustifyContent, $justify);
    }

    public function alignItems(string $align): static
    {
        return $this->set(StyleProperty::AlignItems, $align);
    }

    public function flexWrap(string $wrap): static
    {
        return $this->set(StyleProperty::FlexWrap, $wrap);
    }

    public function gap(float $gap): static
    {
        return $this->set(StyleProperty::Gap, $gap);
    }

    public function flexGrow(float $grow): static
    {
        return $this->set(StyleProperty::FlexGrow, $grow);
    }

    public function flexShrink(float $shrink): static
    {
        return $this->set(StyleProperty::FlexShrink, $shrink);
    }

    // --- Transform helpers ---

    public function rotate(float $degrees): static
    {
        return $this->set(StyleProperty::Rotate, $degrees);
    }

    public function scale(float $scale): static
    {
        return $this->set(StyleProperty::Scale, $scale);
    }

    public function translateX(float $x): static
    {
        return $this->set(StyleProperty::TranslateX, $x);
    }

    public function translateY(float $y): static
    {
        return $this->set(StyleProperty::TranslateY, $y);
    }

    // --- Animation helpers ---

    public function animationDuration(int $ms): static
    {
        return $this->set(StyleProperty::AnimationDuration, $ms);
    }

    public function animationDelay(int $ms): static
    {
        return $this->set(StyleProperty::AnimationDelay, $ms);
    }

    public function animationEasing(string $easing): static
    {
        return $this->set(StyleProperty::AnimationEasing, $easing);
    }

    public function animationIterationCount(int $count): static
    {
        return $this->set(StyleProperty::AnimationIterationCount, $count);
    }

    public function animationDirection(string $direction): static
    {
        // normal, reverse, alternate, alternate-reverse
        return $this->set(StyleProperty::AnimationDirection, $direction);
    }

    public function animationFillMode(string $mode): static
    {
        // none, forwards, backwards, both
        return $this->set(StyleProperty::AnimationFillMode, $mode);
    }

    public function animationPlayState(string $state): static
    {
        // running, paused
        return $this->set(StyleProperty::AnimationPlayState, $state);
    }

    // --- Transition helpers ---

    public function transition(string $property, int $duration, string $easing = 'ease'): static
    {
        return $this
            ->set(StyleProperty::TransitionProperty, $property)
            ->set(StyleProperty::TransitionDuration, $duration)
            ->set(StyleProperty::TransitionTimingFunction, $easing);
    }

    public function transitionProperty(string $property): static
    {
        return $this->set(StyleProperty::TransitionProperty, $property);
    }

    public function transitionDuration(int $ms): static
    {
        return $this->set(StyleProperty::TransitionDuration, $ms);
    }

    public function transitionDelay(int $ms): static
    {
        return $this->set(StyleProperty::TransitionDelay, $ms);
    }

    public function transitionTimingFunction(string $easing): static
    {
        return $this->set(StyleProperty::TransitionTimingFunction, $easing);
    }

    // --- Shorthand: animate everything ---
    public function animate(int $duration, string $easing = 'ease'): static
    {
        return $this
            ->set(StyleProperty::TransitionProperty, 'all')
            ->set(StyleProperty::TransitionDuration, $duration)
            ->set(StyleProperty::TransitionTimingFunction, $easing);
    }
}
