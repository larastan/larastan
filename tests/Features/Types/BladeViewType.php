<?php

declare(strict_types=1);

namespace Tests\Features\Types;

class BladeViewType
{
    public function testBladeView(): void
    {
        $this->doSomethingWithAView('home');
        $this->doSomethingWithAView('emails.orders.shipped');
        $this->doSomethingWithAView('users.index');
    }

    /**
     * @phpstan-param view-string $view
     * @param string $view
     * @return void
     */
    private function doSomethingWithAView(string $view): void
    {
    }
}
