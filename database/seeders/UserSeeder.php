<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => '1@1.ru'],
            [
                'name' => 'Тестовый Админ',
                'password' => bcrypt('111111'),
                'role_id' => 3,
            ]
        );

        User::query()->firstOrCreate(
            ['email' => '2@2.ru'],
            [
                'name' => 'Тестовый Кладовщик',
                'password' => bcrypt('222222'),
                'role_id' => 2,
            ]
        );

        User::query()->firstOrCreate(
            ['email' => '3@3.ru'],
            [
                'name' => 'Тестовая Швея',
                'password' => bcrypt('333333'),
                'role_id' => 1,
            ]
        );

        User::query()->firstOrCreate(
            ['email' => '4@4.ru'],
            [
                'name' => 'Тестовый Закройщик',
                'password' => bcrypt('444444'),
                'role_id' => 4,
            ]
        );

        User::query()->firstOrCreate(
            ['email' => '5@5.ru'],
            [
                'name' => 'Тестовый Сотрудник ОТК',
                'password' => bcrypt('555555'),
                'role_id' => 5,
            ]
        );

        User::query()->firstOrCreate(
            ['email' => '6@6.ru'],
            [
                'name' => 'Тестовый Водитель',
                'password' => bcrypt('666666'),
                'role_id' => 6,
            ]
        );

        User::query()->firstOrCreate(
            ['email' => '7@7.ru'],
            [
                'name' => 'Тестовый Менеджер',
                'password' => bcrypt('777777'),
                'role_id' => 7,
            ]
        );

        User::query()->firstOrCreate(
            ['email' => '8@8.ru'],
            [
                'name' => 'Тестовая Уборщица',
                'password' => bcrypt('888888'),
                'role_id' => 8,
            ]
        );

        User::factory(10)->create();
    }
}
