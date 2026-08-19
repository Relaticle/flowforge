<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

/**
 * A card action carrying both ->url() and a custom modal heading. shouldOpenModal()
 * reports true for it even though hasModal() does not, so the modal must win.
 */
class TestCardActionModalHeadingBoard extends TestCardActionBoard
{
    protected function cardActionName(): string
    {
        return 'urlWithModalHeading';
    }
}
