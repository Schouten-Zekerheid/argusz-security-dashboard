<?php

namespace Tests\Feature;

use App\Livewire\Admin\SlaSettings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SlaSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure settings.manage permission exists
        $managePermission = Permission::firstOrCreate([
            'name' => 'settings.manage',
            'guard_name' => 'web',
        ]);

        // Create admin role and give permission
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $adminRole->givePermissionTo($managePermission);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $this->unauthorizedUser = User::factory()->create();

        // Clear settings cache before each test
        Cache::forget('sla_settings');
    }

    public function test_unauthorized_user_cannot_access_sla_settings(): void
    {
        $this->actingAs($this->unauthorizedUser);

        Livewire::test(SlaSettings::class)
            ->assertForbidden();
    }

    public function test_authorized_user_can_access_sla_settings(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(SlaSettings::class)
            ->assertOk()
            ->assertSet('critical', 7)
            ->assertSet('high', 30)
            ->assertSet('medium', 90)
            ->assertSet('low', 180);
    }

    public function test_rapporteur_and_management_can_access_sla_settings(): void
    {
        // Create rapporteur role and give permission
        $rapporteurRole = Role::firstOrCreate([
            'name' => 'rapporteur',
            'guard_name' => 'web',
        ]);
        $rapporteurRole->givePermissionTo('settings.manage');

        $rapporteurUser = User::factory()->create();
        $rapporteurUser->assignRole($rapporteurRole);

        $this->actingAs($rapporteurUser);
        Livewire::test(SlaSettings::class)->assertOk();

        // Create management role and give permission
        $managementRole = Role::firstOrCreate([
            'name' => 'management',
            'guard_name' => 'web',
        ]);
        $managementRole->givePermissionTo('settings.manage');

        $managementUser = User::factory()->create();
        $managementUser->assignRole($managementRole);

        $this->actingAs($managementUser);
        Livewire::test(SlaSettings::class)->assertOk();
    }

    public function test_validation_fails_when_thresholds_are_not_logical(): void
    {
        $this->actingAs($this->admin);

        // Enforce Critical <= High <= Medium <= Low
        Livewire::test(SlaSettings::class)
            ->set('critical', 10)
            ->set('high', 5) // Invalid: High is less than Critical
            ->call('saveSettings')
            ->assertHasErrors(['high' => 'min']);
    }

    public function test_validation_fails_when_value_is_under_minimum(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(SlaSettings::class)
            ->set('critical', 0) // Invalid: must be >= 1
            ->call('saveSettings')
            ->assertHasErrors(['critical' => 'min']);
    }

    public function test_can_save_valid_sla_settings(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(SlaSettings::class)
            ->set('critical', 10)
            ->set('high', 20)
            ->set('medium', 80)
            ->set('low', 150)
            ->call('saveSettings')
            ->assertHasNoErrors();

        // Verify values exist in SQL database
        $this->assertEquals('10', Setting::get('sla_critical_days'));
        $this->assertEquals('20', Setting::get('sla_high_days'));
        $this->assertEquals('80', Setting::get('sla_medium_days'));
        $this->assertEquals('150', Setting::get('sla_low_days'));

        // Verify active config reflects DB changes
        $this->assertEquals(10, config('sla.critical'));
        $this->assertEquals(20, config('sla.high'));
        $this->assertEquals(80, config('sla.medium'));
        $this->assertEquals(150, config('sla.low'));
    }
}
