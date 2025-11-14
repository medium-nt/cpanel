<?php

namespace Tests\Unit\Models;

use App\Models\MarketplaceOrderItem;
use App\Models\Material;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_belongs_to_role()
    {
        $role = Role::factory()->create();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertInstanceOf(Role::class, $user->role);
        $this->assertEquals($role->id, $user->role->id);
    }

    #[Test]
    public function it_has_many_marketplace_order_items_as_seamstress()
    {
        $items = MarketplaceOrderItem::factory()->count(3)->create([
            'seamstress_id' => $this->user->id,
        ]);

        $this->assertCount(3, $this->user->marketplaceOrderItems);
        $items->each(function ($item) {
            $this->assertEquals($this->user->id, $item->seamstress_id);
        });
    }

    #[Test]
    public function it_has_many_marketplace_order_items_as_cutter()
    {
        $items = MarketplaceOrderItem::factory()->count(2)->create([
            'cutter_id' => $this->user->id,
        ]);

        $this->assertCount(2, $this->user->marketplaceOrderItemsByCutter);
        $items->each(function ($item) {
            $this->assertEquals($this->user->id, $item->cutter_id);
        });
    }

    #[Test]
    public function it_belongs_to_many_materials()
    {
        $materials = Material::factory()->count(3)->create();
        $this->user->materials()->attach($materials->pluck('id'));

        $this->assertCount(3, $this->user->materials);
        $this->assertInstanceOf(Material::class, $this->user->materials->first());
    }

    #[Test]
    public function it_identifies_user_roles()
    {
        // Test that the role identification methods exist and work with null role
        $userWithoutRole = User::factory()->create(['role_id' => null]);

        $this->assertFalse($userWithoutRole->isAdmin());
        $this->assertFalse($userWithoutRole->isSeamstress());
        $this->assertFalse($userWithoutRole->isStorekeeper());
        $this->assertFalse($userWithoutRole->isCutter());
        $this->assertFalse($userWithoutRole->isOtk());

        // Test that default user (with null role_id from factory) also returns false
        $this->assertFalse($this->user->isAdmin());
        $this->assertFalse($this->user->isSeamstress());
        $this->assertFalse($this->user->isStorekeeper());
        $this->assertFalse($this->user->isCutter());
        $this->assertFalse($this->user->isOtk());
    }

    #[Test]
    public function it_returns_short_name_correctly()
    {
        $user = User::factory()->create([
            'name' => 'Иванов Иван Иванович',
        ]);

        $this->assertEquals('Иванов И.И.', $user->short_name);
    }

    #[Test]
    public function it_returns_short_name_with_only_first_name()
    {
        $user = User::factory()->create([
            'name' => 'Петров',
        ]);

        $this->assertEquals('Петров', $user->short_name);
    }

    #[Test]
    public function it_returns_short_name_with_first_and_middle_names()
    {
        $user = User::factory()->create([
            'name' => 'Сидоров Сергей',
        ]);

        $this->assertEquals('Сидоров С.', $user->short_name);
    }

    #[Test]
    public function it_formats_updated_date_correctly()
    {
        $user = User::factory()->create();
        $expectedDate = $user->updated_at->format('d/m/Y H:i');

        $this->assertEquals($expectedDate, $user->updated_date);
    }

    #[Test]
    public function it_formats_created_date_correctly()
    {
        $user = User::factory()->create();
        $expectedDate = $user->created_at->format('d/m/Y H:i');

        $this->assertEquals($expectedDate, $user->created_date);
    }

    #[Test]
    public function it_can_detach_materials()
    {
        $materials = Material::factory()->count(2)->create();
        $this->user->materials()->attach($materials->pluck('id'));

        $this->assertCount(2, $this->user->materials);

        $this->user->materials()->detach($materials->first()->id);

        $this->assertCount(1, $this->user->fresh()->materials);
    }

    #[Test]
    public function it_can_sync_materials()
    {
        $initialMaterials = Material::factory()->count(2)->create();
        $newMaterials = Material::factory()->count(3)->create();

        $this->user->materials()->attach($initialMaterials->pluck('id'));
        $this->assertCount(2, $this->user->materials);

        $this->user->materials()->sync($newMaterials->pluck('id'));

        $freshUser = $this->user->fresh();
        $this->assertCount(3, $freshUser->materials);
        $newMaterials->each(function ($material) use ($freshUser) {
            $this->assertTrue($freshUser->materials->contains($material));
        });
    }

    #[Test]
    public function role_can_be_null()
    {
        $userWithoutRole = User::factory()->create(['role_id' => null]);

        $this->assertNull($userWithoutRole->role);
        $this->assertFalse($userWithoutRole->isAdmin());
        $this->assertFalse($userWithoutRole->isSeamstress());
        $this->assertFalse($userWithoutRole->isStorekeeper());
        $this->assertFalse($userWithoutRole->isCutter());
        $this->assertFalse($userWithoutRole->isOtk());
    }

    #[Test]
    public function it_uses_soft_deletes()
    {
        $this->assertFalse($this->user->trashed());

        $this->user->delete();
        $this->assertTrue($this->user->trashed());
        $this->assertSoftDeleted('users', ['id' => $this->user->id]);
    }

    #[Test]
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'name', 'email', 'phone', 'password', 'role_id', 'is_cutter',
            'salary_rate', 'avatar', 'tg_id', 'orders_priority',
            'shift_is_open', 'start_work_shift', 'duration_work_shift',
            'max_late_minutes', 'is_show_finance',
        ];

        foreach ($fillable as $attribute) {
            $this->assertContains($attribute, $this->user->getFillable());
        }
    }

    #[Test]
    public function it_has_hidden_attributes()
    {
        $hidden = ['password', 'remember_token'];

        foreach ($hidden as $attribute) {
            $this->assertContains($attribute, $this->user->getHidden());
        }
    }

    #[Test]
    public function phone_can_be_nullable()
    {
        $userWithoutPhone = User::factory()->create(['phone' => null]);

        $this->assertNull($userWithoutPhone->phone);
    }

    #[Test]
    public function salary_rate_defaults_to_zero()
    {
        // Test that salary_rate can be set and retrieved correctly
        $user = User::factory()->create(['salary_rate' => 0]);

        $this->assertEquals(0, $user->salary_rate);
    }

    #[Test]
    public function is_cutter_is_boolean()
    {
        $cutter = User::factory()->create(['is_cutter' => true]);
        $nonCutter = User::factory()->create(['is_cutter' => false]);

        $this->assertTrue($cutter->is_cutter);
        $this->assertFalse($nonCutter->is_cutter);
    }
}
