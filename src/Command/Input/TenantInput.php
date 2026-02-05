<?php

namespace App\Command\Input;

use Symfony\Component\Console\Attribute\Option;

final class TenantInput
{
    #[Option('Tenant code (subdomain) to run against', name: 'tenant')]
    public ?string $tenantCode = null;
}
