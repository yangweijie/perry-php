<?php

declare(strict_types=1);

namespace Perry\UI;

enum WidgetKind: int
{
    case Text = 0;
    case Button = 1;
    case VStack = 2;
    case HStack = 3;
    case Spacer = 4;
    case Image = 5;
    case ScrollView = 6;
    case TextInput = 7;
    case Toggle = 8;
    case Slider = 9;
    case ListWidget = 10;
    case NavigationView = 11;
    case TabView = 12;
    case TextEditor = 13;
    case WebView = 14;
    case Checkbox = 15;
    case RadioButton = 16;
    case Progress = 17;
    case Dialog = 18;
    case Toast = 19;
    case Dropdown = 20;
    case SegmentedControl = 21;
    case ContextMenu = 22;
    case DatePicker = 23;
}
