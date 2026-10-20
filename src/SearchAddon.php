<?php

declare(strict_types=1);

namespace Shopper\Search;

use Shopper\Addon\BaseAddon;
use Shopper\ShopperPanel;

final class SearchAddon extends BaseAddon
{
    public function getId(): string
    {
        return 'search';
    }

    public function register(ShopperPanel $panel): void {}
}
