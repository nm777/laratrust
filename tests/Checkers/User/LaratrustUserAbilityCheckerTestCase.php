<?php

namespace Laratrust\Tests\Checkers\User;

use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;

class LaratrustUserAbilityCheckerTestCase extends LaratrustTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->app['config']->set('laratrust.use_teams', true);

        $permissionA = Permission::create(['name' => 'permission_a']);
        $permissionB = Permission::create(['name' => 'permission_b']);
        $permissionC = Permission::create(['name' => 'permission_c']);

        $team = Team::create(['name' => 'team_a']);
        $roleA = Role::create(['name' => 'role_a']);
        $roleB = Role::create(['name' => 'role_b']);

        $roleA->attachPermission($permissionA);
        $roleB->attachPermissions([$permissionB, $permissionC]);

        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);
        $this->user->attachRole($roleA)->attachRole($roleB, $team);
    }

    protected function abilityShouldReturnBooleanAssertions()
    {
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Case: User has everything.
        $this->assertTrue(
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_c']
            )
        );
        $this->assertTrue(
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_c'],
                'team_a'
            )
        );
        $this->assertTrue(
            $this->user->ability(
                ['role_a'],
                ['permission_a'],
                ['validate_all' => true]
            )
        );

        // Case: User lacks a role.
        $this->assertTrue(
            $this->user->ability(
                ['Nonrole_a', 'role_b'],
                ['permission_a', 'permission_b']
            )
        );
        $this->assertFalse(
            $this->user->ability(
                ['Nonrole_a', 'role_b'],
                ['permission_a', 'permission_b'],
                ['validate_all' => true]
            )
        );

        // Case: User lacks a permission.
        $this->assertTrue(
            $this->user->ability(
                ['role_a', 'role_b'],
                ['user_cannot_a', 'permission_b']
            )
        );
        $this->assertFalse(
            $this->user->ability(
                ['role_a', 'role_b'],
                ['user_cannot_a', 'permission_b'],
                ['validate_all' => true]
            )
        );

        // Case: User lacks everything.
        $this->assertFalse(
            $this->user->ability(
                ['Nonrole_a', 'Nonrole_b'],
                ['user_cannot_a', 'user_cannot_b']
            )
        );
        $this->assertFalse(
            $this->user->ability(
                ['Nonrole_a', 'Nonrole_b'],
                ['user_cannot_a', 'user_cannot_b'],
                ['validate_all' => true]
            )
        );
    }

    protected function abilityShouldReturnArrayAssertions()
    {
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Case: User has everything.
        $this->assertSame(
            [
                'roles' => ['role_a' => true, 'role_b' => true],
                'permissions' => ['permission_a' => true, 'permission_b' => true]
            ],
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b'],
                ['return_type' => 'array']
            )
        );
        $this->assertSame(
            [
                'roles' => ['role_a' => false, 'role_b' => true],
                'permissions' => ['permission_a' => false, 'permission_b' => true]
            ],
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b'],
                'team_a',
                ['validate_all' => true, 'return_type' => 'array']
            )
        );
        $this->assertSame(
            [
                'roles' => ['role_a' => true],
                'permissions' => ['permission_a' => true]
            ],
            $this->user->ability(
                ['role_a'],
                ['permission_a'],
                ['validate_all' => true, 'return_type' => 'array']
            )
        );
    }

    protected function abilityShouldReturnBothAssertions()
    {
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame(
            [
                true,
                [
                    'roles' => ['role_a' => true, 'role_b' => true],
                    'permissions' => ['permission_a' => true, 'permission_b' => true]
                ]
            ],
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b'],
                ['return_type' => 'both']
            )
        );
        $this->assertSame(
            [
                true,
                [
                    'roles' => ['role_a' => false, 'role_b' => true],
                    'permissions' => ['permission_a' => false, 'permission_b' => true]
                ]
            ],
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b'],
                'team_a',
                ['return_type' => 'both']
            )
        );
        $this->assertSame(
            [
                true,
                [
                    'roles' => ['role_a' => true, 'role_b' => true],
                    'permissions' => ['permission_a' => true, 'permission_b' => true]
                ]
            ],
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b'],
                ['validate_all' => true, 'return_type' => 'both']
            )
        );
    }

    protected function abilityShouldAcceptStringsAssertions()
    {
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame(
            $this->user->ability(
                ['role_a', 'Nonrole_b'],
                ['permission_a', 'user_cannot_b'],
                ['return_type' => 'both']
            ),
            $this->user->ability(
                'role_a|Nonrole_b',
                'permission_a|user_cannot_b',
                ['return_type' => 'both']
            )
        );

        $this->assertSame(
            $this->user->ability(
                ['role_a'],
                ['permission_a'],
                ['return_type' => 'both']
            ),
            $this->user->ability(
                'role_a',
                'permission_a',
                ['return_type' => 'both']
            )
        );
    }

    protected function abilityDefaultOptionsAssertions()
    {
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Case: User has everything.
        $this->assertSame(
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b']
            ),
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b'],
                ['validate_all' => false, 'return_type' => 'boolean']
            )
        );

        $this->assertSame(
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b'],
                'team_a'
            ),
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b'],
                'team_a',
                ['validate_all' => false, 'return_type' => 'boolean']
            )
        );

        // Case: User lacks a role.
        $this->assertSame(
            $this->user->ability(
                ['Nonrole_a', 'role_b'],
                ['permission_a', 'permission_b']
            ),
            $this->user->ability(
                ['Nonrole_a', 'role_b'],
                ['permission_a', 'permission_b'],
                ['validate_all' => false, 'return_type' => 'boolean']
            )
        );
    }

    protected function abilityShouldThrowInvalidArgumentExceptionAssertions()
    {
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertFalse($this->isExceptionThrown($this->user, ['RoleA'], ['manage_a'], ['return_type' => 'boolean']));
        $this->assertFalse($this->isExceptionThrown($this->user, ['RoleA'], ['manage_a'], ['return_type' => 'array']));
        $this->assertFalse($this->isExceptionThrown($this->user, ['RoleA'], ['manage_a'], ['return_type' => 'both']));
        $this->assertTrue($this->isExceptionThrown($this->user, ['RoleA'], ['manage_a'], ['return_type' => 'potato']));
        $this->assertTrue($this->isExceptionThrown($this->user, ['RoleA'], ['manage_a'], ['validate_all' => 'potato']));
    }

    /**
     * Check if an exception is thrown when checking the user ability
     * @param  \Laratrust\Tests\Models\User  $user
     * @param  array  $roles
     * @param  array  $perms
     * @param  array  $options
     * @return boolean
     */
    public function isExceptionThrown($user, $roles, $perms, $options)
    {
        $isExceptionThrown = false;

        try {
            $user->ability($roles, $perms, $options);
        } catch (\InvalidArgumentException $e) {
            $isExceptionThrown = true;
        }

        return $isExceptionThrown;
    }
}
