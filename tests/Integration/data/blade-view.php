<?php

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
     */
    private function doSomethingWithAView(string $view): void
    {
    }
}
