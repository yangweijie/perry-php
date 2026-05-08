<?php

declare(strict_types=1);

namespace Perry\UI\Styling;

enum StyleProperty: string
{
    case BackgroundColor = 'background_color';
    case ForegroundColor = 'foreground_color';
    case BorderColor = 'border_color';
    case BorderWidth = 'border_width';
    case CornerRadius = 'cornerRadius';
    case Opacity = 'opacity';
    case Padding = 'padding';
    case PaddingTop = 'padding_top';
    case PaddingBottom = 'padding_bottom';
    case PaddingLeading = 'padding_leading';
    case PaddingTrailing = 'padding_trailing';
    case Margin = 'margin';
    case Width = 'width';
    case Height = 'height';
    case FontSize = 'font_size';
    case FontWeight = 'font_weight';
    case FontFamily = 'font_family';
    case TextAlignment = 'text_alignment';
    case ShadowColor = 'shadow_color';
    case ShadowRadius = 'shadow_radius';
    case ShadowOffsetX = 'shadow_offset_x';
    case ShadowOffsetY = 'shadow_offset_y';
    case TextDecoration = 'text_decoration';
    case LineSpacing = 'line_spacing';
    case LetterSpacing = 'letter_spacing';
    case MinWidth = 'min_width';
    case MinHeight = 'min_height';
    case MaxWidth = 'max_width';
    case MaxHeight = 'max_height';
}
