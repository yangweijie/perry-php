<?php

declare(strict_types=1);

use Perry\App;
use Perry\Build\Target;
use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Styling\Style;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\VStack;

$count = new Binding('count', 0);
$display = new Binding('display', 'Hello Perry');

return new VStack(
    (new Text('Perry Live Preview'))
        ->style(Style::make()->fontSize(28)->foregroundColor('#333')),
    (new Text($display))
        ->style(Style::make()->fontSize(18)->foregroundColor('#666')),
    (new Text($count))
        ->style(Style::make()->fontSize(48)->foregroundColor('#007AFF')->fontWeight('bold')),
    (new HStack(
        (new Button('-1', Action::fromClosure(function () use ($count) {
            $count = intval($count) - 1;
        })))->style(Style::make()->backgroundColor('#007AFF')->foregroundColor('#fff')->padding(12)->cornerRadius(8)),
        (new Button('+1', Action::fromClosure(function () use ($count) {
            $count = intval($count) + 1;
        })))->style(Style::make()->backgroundColor('#007AFF')->foregroundColor('#fff')->padding(12)->cornerRadius(8)),
    ))->style(Style::make()->gap(12)),
)->style(Style::make()->padding(40)->gap(16));
