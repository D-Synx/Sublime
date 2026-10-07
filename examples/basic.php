<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use function Sublime\{Sublime, body_, div_, p_};

echo Sublime(body_(
    data: div_(class: 'app', data: p_('Hello'))
));
