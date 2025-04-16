<?php

namespace Database\Seeders;

use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Transaction::query()->create([
            'title' => 'получение денег за продажу No1',
            'amount' => 1000,
            'transaction_type' => 'inflow',
            'status' => 1,
        ]);

        Transaction::query()->create([
            'title' => 'получение денег за продажу №2',
            'amount' => 500,
            'transaction_type' => 'inflow',
            'status' => 1,
        ]);

        Transaction::query()->create([
            'user_id' => 3,
            'title' => 'Выдача зарплаты швее',
            'amount' => 300,
            'transaction_type' => 'outflow',
            'status' => 1,
        ]);

        Transaction::query()->create([
            'user_id' => 2,
            'title' => 'Выдача зарплаты кладовщику',
            'amount' => 900,
            'transaction_type' => 'outflow',
            'status' => 1,
        ]);
    }
}
