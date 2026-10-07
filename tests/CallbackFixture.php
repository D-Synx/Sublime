<?php

declare(strict_types=1);

namespace Sublime\Tests;

use Sublime\HtmlElement;
use Sublime\TagFactory;

final class CallbackFixture
{
    public static function render(TagFactory $tags): HtmlElement
    {
        return $tags->p('Hello');
    }

    public function instanceRender(TagFactory $tags): HtmlElement
    {
        return $tags->p('Hello');
    }

    public function __invoke(TagFactory $tags): HtmlElement
    {
        return $tags->p('Hello');
    }
}
