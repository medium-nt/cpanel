<?php

namespace Tests\Feature\Controllers;

use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);

        $this->employee = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'seamstress'])->id,
        ]);

        Storage::fake('public');
    }

    /**
     * Выплата зарплаты должна писать аудит-лог в канал salary
     * с идентификатором сотрудника, количеством строк и суммой.
     */
    #[Test]
    public function payout_salary_writes_audit_log_to_salary_channel()
    {
        $date = now()->toDateString();

        // Неоплаченное начисление сотруднику (transaction_type 'out').
        Transaction::factory()->create([
            'user_id' => $this->employee->id,
            'is_bonus' => false,
            'transaction_type' => 'out',
            'amount' => 100,
            'accrual_for_date' => $date,
            'paid_at' => null,
            'status' => 0,
        ]);

        // Оплаченный приход в кассу компании — чтобы пройти гард «достаточно денег».
        Transaction::factory()->create([
            'user_id' => $this->admin->id,
            'is_bonus' => false,
            'transaction_type' => 'in',
            'amount' => 1000,
            'accrual_for_date' => $date,
            'paid_at' => now(),
            'status' => 2,
        ]);

        Log::shouldReceive('channel')->once()->with('salary')->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('Выплата зарплаты', Mockery::on(function ($context) {
                return $context['user_id'] === $this->employee->id
                    && $context['rows'] == 1
                    && $context['sum'] == 100
                    && $context['paid_by'] === $this->admin->id;
            }));

        $this->actingAs($this->admin)
            ->post(route('transactions.store_payout_salary'), [
                'user_id' => $this->employee->id,
                'start_date' => $date,
                'end_date' => $date,
            ])
            ->assertRedirect();
    }

    /**
     * Штраф с фото: POST store с transaction_type=in + UploadedFile
     * → редирект на transactions.index, в БД fine_photo не null (строка 'fines/...'),
     * файл существует на диске public.
     */
    #[Test]
    public function fine_with_photo_saves_file_and_path()
    {
        $photo = UploadedFile::fake()->image('fine.jpg', 400, 300);

        $response = $this->actingAs($this->admin)
            ->post(route('transactions.store'), [
                'user_id' => $this->employee->id,
                'title' => 'Штраф за брак',
                'amount' => 500,
                'transaction_type' => 'in',
                'accrual_for_date' => now()->toDateString(),
                'type' => 'salary',
                'fine_photo' => $photo,
            ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->employee->id,
            'title' => 'Штраф за брак',
            'amount' => 500,
            'transaction_type' => 'in',
        ]);

        $transaction = Transaction::where('user_id', $this->employee->id)
            ->where('title', 'Штраф за брак')
            ->first();

        $this->assertNotNull($transaction->fine_photo);
        $this->assertStringStartsWith('fines/', $transaction->fine_photo);
        $this->assertStringEndsWith('.jpg', $transaction->fine_photo);

        Storage::disk('public')->assertExists($transaction->fine_photo);
    }

    /**
     * Штраф без фото: POST store с transaction_type=in без файла
     * → Transaction создана, fine_photo = null.
     */
    #[Test]
    public function fine_without_photo_creates_transaction_with_null_photo()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('transactions.store'), [
                'user_id' => $this->employee->id,
                'title' => 'Штраф за опоздание',
                'amount' => 300,
                'transaction_type' => 'in',
                'accrual_for_date' => now()->toDateString(),
                'type' => 'salary',
            ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->employee->id,
            'title' => 'Штраф за опоздание',
            'amount' => 300,
            'transaction_type' => 'in',
            'fine_photo' => null,
        ]);
    }

    /**
     * Премия/зарплата (transaction_type=out) с файлом игнорируется:
     * POST store с transaction_type=out + UploadedFile
     * → Transaction создана, fine_photo = null (фото НЕ сохраняется).
     */
    #[Test]
    public function payout_transaction_ignores_photo_even_if_provided()
    {
        $photo = UploadedFile::fake()->image('bonus.jpg', 400, 300);

        $response = $this->actingAs($this->admin)
            ->post(route('transactions.store'), [
                'user_id' => $this->employee->id,
                'title' => 'Премия за перевыполнение',
                'amount' => 2000,
                'transaction_type' => 'out',
                'accrual_for_date' => now()->toDateString(),
                'type' => 'salary',
                'fine_photo' => $photo,
            ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->employee->id,
            'title' => 'Премия за перевыполнение',
            'amount' => 2000,
            'transaction_type' => 'out',
            'fine_photo' => null,
        ]);

        Storage::disk('public')->assertMissing('fines/'.$photo->hashName());
    }

    /**
     * UI create-страницы salary: GET transactions.create с type=salary
     * → ответ 200, в HTML присутствует поле для фото штрафа.
     */
    #[Test]
    public function salary_create_page_contains_fine_photo_field()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('transactions.create', ['type' => 'salary']));

        $response->assertStatus(200);

        // Проверяем что в ответе есть <div> с полем для загрузки фото
        $content = $response->getContent();
        $this->assertStringContainsString('<div class="form-group" id="fine-photo-group"', $content);
        $this->assertStringContainsString('name="fine_photo"', $content);
    }

    /**
     * UI create-страницы company: GET transactions.create с type=company
     * → ответ 200, поле fine_photo ОТСУТСТВУЕТ в HTML.
     */
    #[Test]
    public function company_create_page_does_not_contain_fine_photo_field()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('transactions.create', ['type' => 'company']));

        $response->assertStatus(200);

        // Проверяем что в ответе НЕТ <div> с полем для загрузки фото
        // (JS скрипт может содержать идентификаторы, но самого div быть не должно)
        $content = $response->getContent();
        $this->assertStringNotContainsString('<div class="form-group" id="fine-photo-group"', $content);
    }

    /**
     * Валидация: POST store с fine_photo = не-изображение (txt)
     * → сессия содержит ошибку валидации для fine_photo, транзакция не создана.
     */
    #[Test]
    public function validation_fails_when_fine_photo_is_not_an_image()
    {
        $file = UploadedFile::fake()->create('document.txt', 100);

        $this->actingAs($this->admin)
            ->from(route('transactions.create', ['type' => 'salary']))
            ->post(route('transactions.store'), [
                'user_id' => $this->employee->id,
                'title' => 'Штраф с документом',
                'amount' => 500,
                'transaction_type' => 'in',
                'accrual_for_date' => now()->toDateString(),
                'type' => 'salary',
                'fine_photo' => $file,
            ])
            ->assertSessionHasErrors(['fine_photo']);

        $this->assertDatabaseMissing('transactions', [
            'title' => 'Штраф с документом',
        ]);
    }

    /**
     * Удаление штрафной транзакции с фото:
     * DELETE transactions.destroy → файл fine_photo удаляется с диска public.
     */
    #[Test]
    public function destroying_fine_transaction_deletes_photo_file()
    {
        $photo = UploadedFile::fake()->image('fine.jpg', 400, 300);

        $this->actingAs($this->admin)
            ->post(route('transactions.store'), [
                'user_id' => $this->employee->id,
                'title' => 'Штраф за брак',
                'amount' => 500,
                'transaction_type' => 'in',
                'accrual_for_date' => now()->toDateString(),
                'type' => 'salary',
                'fine_photo' => $photo,
            ])
            ->assertRedirect(route('transactions.index'));

        $transaction = Transaction::where('title', 'Штраф за брак')->first();
        $this->assertNotNull($transaction->fine_photo);
        Storage::disk('public')->assertExists($transaction->fine_photo);

        $this->actingAs($this->admin)
            ->delete(route('transactions.destroy', $transaction))
            ->assertRedirect();

        Storage::disk('public')->assertMissing($transaction->fine_photo);
    }
}
