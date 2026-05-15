<?php

namespace Tests\Feature\Fleet\PermissionAdapters;

use Illuminate\Support\Facades\Gate;

class GatePolicyAdapter implements FleetPermissionAdapterInterface
{
    public function supports(object $testCase, object $user): bool
    {
        return (bool) config('fleet_test_permissions.enable_gate_policy_adapter', false);
    }

    public function grant(object $testCase, object $user, array $permissions): object
    {
        config(['fleet_test_permissions.' . $user->getKey() => $permissions]);

        Gate::before(function ($authUser, string $ability) {
            $allowed = config('fleet_test_permissions.' . $authUser->getKey(), []);

            return in_array($ability, $allowed, true) ? true : null;
        });

        return $user;
    }
}
