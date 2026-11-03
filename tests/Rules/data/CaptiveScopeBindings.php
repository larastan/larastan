<?php

declare(strict_types=1);

namespace ScopeCompatibility;

use Illuminate\Contracts\Container\Container;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

#[Scoped]
final class TenantContext
{
}

final class Repository
{
    public function __construct(TenantContext $tenant)
    {
    }
}

final class ReportService
{
    public function __construct(Repository $repository)
    {
    }
}

final class CurrentRequestService
{
    public function __construct(Request $request)
    {
    }
}

final class OrdinaryService
{
    public function __construct(\stdClass $object)
    {
    }
}

final class OptionalContextService
{
    public function __construct(?TenantContext $tenant = null)
    {
    }
}

final class CyclicA
{
    public function __construct(CyclicB $b)
    {
    }
}

final class CyclicB
{
    public function __construct(CyclicA $a)
    {
    }
}

interface ReportContract
{
}

final class ReportImplementation implements ReportContract
{
    public function __construct(TenantContext $tenant)
    {
    }
}

final class ServiceProviderWithBindings extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReportService::class);
        $this->app->singletonIf(CurrentRequestService::class);
        $this->app->singleton(ReportContract::class, ReportImplementation::class);

        // These are intentionally not reported.
        $this->app->scoped(ReportService::class);
        $this->app->bind(ReportService::class);
        $this->app->singleton(OrdinaryService::class);
        $this->app->singleton(OptionalContextService::class);
        $this->app->singleton(CyclicA::class);
        $this->app->singleton('report', fn () => new ReportService(new Repository(new TenantContext())));
        $this->app->singleton(ReportContract::class, fn () => new ReportImplementation(new TenantContext()));
    }
}

function registerFromContainer(Container $app): void
{
    $app->singleton(abstract: ReportContract::class, concrete: ReportImplementation::class);
}

final class UnrelatedRegistry
{
    public function singleton(string $key): void
    {
    }
}

(new UnrelatedRegistry())->singleton('anything');
