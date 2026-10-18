<?php

declare(strict_types=1);

namespace OctaneAttributeAliasAudit;

use Illuminate\Container\Attributes\sCoPeD as MixedScoped;
use Illuminate\Container\Attributes\sInGlEtOn as MixedSingleton;

#[MixedScoped]
final class TenantContext
{
}

#[MixedSingleton]
final class TenantOwner
{
    public function __construct(public TenantContext $tenant)
    {
    }
}
