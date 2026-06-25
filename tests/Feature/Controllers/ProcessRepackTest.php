<?php

namespace Tests\Feature\Controllers;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\Role;
use App\Models\Roll;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessRepackTest extends TestCase
{
    use RefreshDatabase;

    private User $otk;

    private MarketplaceOrderItem $orderItem;

    private Material $flyerMaterial;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $otkRole = Role::firstOrCreate(['name' => 'otk']);
        $this->otk = User::factory()->create(['role_id' => $otkRole->id]);

        $this->shift = Shift::factory()->create();
        $this->otk->shifts()->attach($this->shift->id, ['effective_from' => now()->toDateString()]);

        $marketplaceItem = MarketplaceItem::factory()->create(['title' => 'Test Product']);
        $marketplaceOrder = MarketplaceOrder::factory()->create();
        $this->orderItem = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $marketplaceOrder->id,
            'marketplace_item_id' => $marketplaceItem->id,
            'status' => 10,
        ]);

        $this->flyerMaterial = Material::factory()->create(['title' => 'Флаер']);
        MaterialConsumption::create([
            'item_id' => $marketplaceItem->id,
            'material_id' => $this->flyerMaterial->id,
            'quantity' => 1,
        ]);

        Session::put('user_id', $this->otk->id);
    }

    #[Test]
    public function process_repack_succeeds_when_roll_available_in_shift(): void
    {
        // Arrange — рулон флаера в цехе текущей смены
        $roll = Roll::factory()->create([
            'material_id' => $this->flyerMaterial->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'shift_id' => $this->shift->id,
        ]);

        // Act
        $response = $this->post(route('kiosk.process_repack', $this->orderItem), [
            'material_used' => 'flyer',
        ]);

        // Assert — редирект на осмотр, товар переупакован, списание привязано к рулону
        $response->assertRedirect(route('on_inspection'));
        $response->assertSessionHas('success');

        $this->orderItem->refresh();
        $this->assertEquals(15, $this->orderItem->status);
        $this->assertEquals($this->otk->id, $this->orderItem->repacker_id);
        $this->assertNotNull($this->orderItem->repacked_at);

        $movement = \App\Models\MovementMaterial::where('roll_id', $roll->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals($this->flyerMaterial->id, $movement->material_id);
    }

    #[Test]
    public function process_repack_fails_when_roll_missing(): void
    {
        // Arrange — рулона в цехе нет

        // Act
        $response = $this->post(route('kiosk.process_repack', $this->orderItem), [
            'material_used' => 'flyer',
        ]);

        // Assert — редирект назад с ошибкой, товар не переупакован, списания нет
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->orderItem->refresh();
        $this->assertEquals(10, $this->orderItem->status);
        $this->assertNull($this->orderItem->repacker_id);

        $this->assertSame(0, \App\Models\MovementMaterial::where('material_id', $this->flyerMaterial->id)->count());
    }

    #[Test]
    public function process_repack_fails_when_shift_is_null(): void
    {
        // Arrange — у пользователя нет активной смены
        $this->otk->shifts()->detach();

        // Act
        $response = $this->post(route('kiosk.process_repack', $this->orderItem), [
            'material_used' => 'flyer',
        ]);

        // Assert — редирект назад с ошибкой про смену, товар не тронут
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->orderItem->refresh();
        $this->assertEquals(10, $this->orderItem->status);
    }
}
