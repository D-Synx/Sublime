<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use function Sublime\{Sublime, body_, div_, h1_, li_, p_, ul_};

$user = $argv[1] ?? 'guest';
$notifications = ($argv[2] ?? '') === 'empty' ? [] : ['Nouveauté & simplicité', 'Documentation mise à jour'];
$items = [];

foreach ($notifications as $notification) {
    $items[] = li_($notification);
}

echo Sublime(
    class: 'html',
    data: body_(data: div_(class: 'page', data: [
        h1_($user === 'admin' ? 'Bienvenue, admin' : 'Bienvenue'),
        $items !== [] ? ul_(data: $items) : p_('Rien de nouveau.'),
    ]))
);
