<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use function Sublime\{body_, document, h1_, head_, html_, main_, meta_, p_, title_};

echo document(html_(lang: 'fr', data: [
    head_(data: [
        meta_(charset: 'utf-8'),
        title_('Sublime'),
    ]),
    body_(data: main_(class: 'app', data: [
        h1_('Sublime'),
        p_('Du HTML simple en PHP.'),
    ])),
]));
