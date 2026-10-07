<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Sublime\HtmlElement;

use function Sublime\{Sublime, a_, body_, footer_, h2_, main_, nav_, p_, section_, small_};

function navbar(): HtmlElement
{
    return nav_(data: [
        a_(href: '/', data: 'Accueil'),
        a_(href: '/docs', data: 'Documentation'),
    ]);
}

function card(string $title, string $text): HtmlElement
{
    return section_(class: 'card', data: [
        h2_($title),
        p_($text),
    ]);
}

function layout(HtmlElement ...$children): HtmlElement
{
    return body_(class: 'layout', data: [
        navbar(),
        main_(data: $children),
        footer_(data: small_('Créé avec Sublime')),
    ]);
}

echo Sublime(layout(
    card('Composants', 'Composez des fonctions PHP.'),
    card('Simple', 'Un seul enfant sans tableau.'),
));
