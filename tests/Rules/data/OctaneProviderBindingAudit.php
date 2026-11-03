<?php

declare(strict_types=1);

namespace OctaneProviderBindingAudit;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

final class ProviderOwner
{
    public function __construct(public Request $request)
    {
    }
}

interface RequestCarrier
{
}

final class RequestCarrierConcrete implements RequestCarrier
{
    public function __construct(public Request $request)
    {
    }
}

final class ProviderListOwner
{
    public function __construct(public RequestCarrier $carrier)
    {
    }
}

#[Singleton]
class HiddenConstructorRoot
{
    public function __construct(public Request $request)
    {
    }
}

final class SafeRootImplementation extends HiddenConstructorRoot
{
    public function __construct()
    {
    }
}

final class RegistrationProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        RequestCarrier::class => RequestCarrierConcrete::class,
        HiddenConstructorRoot::class => SafeRootImplementation::class,
    ];

    /** @var array<array-key, class-string> */
    public array $singletons = [
        ProviderOwner::class => ProviderOwner::class,
        ProviderListOwner::class,
    ];
}
