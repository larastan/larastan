<?php

namespace TestComponentType;

use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Testing\TestComponent;
use Illuminate\View\Component;

use function PHPStan\Testing\assertType;

class Alert extends Component
{
    public string $type = 'info';

    public function isDismissible(): bool
    {
        return true;
    }

    public function render(): string
    {
        return '';
    }
}

class AlertTest
{
    use InteractsWithViews;

    public function test(): void
    {
        $component = $this->component(Alert::class);

        assertType('Illuminate\Testing\TestComponent<TestComponentType\Alert>', $component);
        assertType('TestComponentType\Alert', $component->component);

        // Calls and property reads are forwarded to the component.
        assertType('bool', $component->isDismissible());
        assertType('string', $component->type);
        assertType('*ERROR*', $component->doesNotExist());

        // Macros take precedence.
        assertType('Illuminate\Testing\TestComponent<TestComponentType\Alert>', $component->testComponentMacro());
    }
}

function test(TestComponent $component): void
{
    assertType('bool', $component->shouldRender());
    assertType('*ERROR*', $component->isDismissible());
    assertType('Illuminate\Testing\TestComponent', $component->testComponentMacro());
}
