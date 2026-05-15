<?php

namespace Tests\Feature\Fleet\PermissionAdapters;

interface FleetPermissionAdapterInterface
{
    public function supports(object $testCase, object $user): bool;
    public function grant(object $testCase, object $user, array $permissions): object;
}
