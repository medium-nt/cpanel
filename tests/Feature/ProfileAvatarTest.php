<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(RoleSeeder::class);
});

afterEach(function () {
    Storage::fake('public');
});

test('non-admin user can upload avatar successfully', function () {
    $seamstressRole = Role::where('name', 'seamstress')->first();
    $user = User::factory()->create([
        'role_id' => $seamstressRole->id,
        'name' => 'Test Seamstress',
        'email' => 'seamstress@example.com',
    ]);

    $avatarFile = UploadedFile::fake()->image('avatar.jpg', 800, 600);

    $response = $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'avatar' => $avatarFile,
        ]);

    $response->assertRedirect(route('profile'));
    $response->assertSessionHas('success', 'Изменения сохранены.');

    Storage::disk('public')->assertExists('avatars/'.$user->id.'.png');

    $user->refresh();
    expect($user->avatar)->toBe('avatars/'.$user->id.'.png');
    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');

    $imageContent = Storage::disk('public')->get('avatars/'.$user->id.'.png');
    $imageInfo = getimagesizefromstring($imageContent);

    expect($imageInfo[0])->toBe(256);
    expect($imageInfo[1])->toBe(256);
    expect($imageInfo[2])->toBe(IMAGETYPE_PNG);
});

test('avatar upload fails with non-image file', function () {
    $seamstressRole = Role::where('name', 'seamstress')->first();
    $user = User::factory()->create([
        'role_id' => $seamstressRole->id,
        'name' => 'Test Seamstress',
        'email' => 'seamstress@example.com',
    ]);

    $originalAvatar = $user->avatar;

    $nonImageFile = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

    $response = $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'avatar' => $nonImageFile,
        ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['avatar']);

    Storage::disk('public')->assertMissing('avatars/'.$user->id.'.png');

    $user->refresh();
    expect($user->avatar)->toBe($originalAvatar);
    expect($user->name)->toBe('Test Seamstress');
    expect($user->email)->toBe('seamstress@example.com');
});

test('avatar upload fails with file larger than 2048 KB', function () {
    $seamstressRole = Role::where('name', 'seamstress')->first();
    $user = User::factory()->create([
        'role_id' => $seamstressRole->id,
        'name' => 'Test Seamstress',
        'email' => 'seamstress@example.com',
    ]);

    $originalAvatar = $user->avatar;

    $largeFile = UploadedFile::fake()->create('large.jpg', 2049, 'image/jpeg');

    $response = $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'avatar' => $largeFile,
        ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['avatar']);

    Storage::disk('public')->assertMissing('avatars/'.$user->id.'.png');

    $user->refresh();
    expect($user->avatar)->toBe($originalAvatar);
    expect($user->name)->toBe('Test Seamstress');
    expect($user->email)->toBe('seamstress@example.com');
});

test('avatar upload fails with unsupported mime type', function () {
    $seamstressRole = Role::where('name', 'seamstress')->first();
    $user = User::factory()->create([
        'role_id' => $seamstressRole->id,
        'name' => 'Test Seamstress',
        'email' => 'seamstress@example.com',
    ]);

    $originalAvatar = $user->avatar;

    $pdfFile = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    $response = $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'avatar' => $pdfFile,
        ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['avatar']);

    Storage::disk('public')->assertMissing('avatars/'.$user->id.'.png');

    $user->refresh();
    expect($user->avatar)->toBe($originalAvatar);
});

test('user can update profile without uploading avatar', function () {
    $seamstressRole = Role::where('name', 'seamstress')->first();
    $user = User::factory()->create([
        'role_id' => $seamstressRole->id,
        'name' => 'Test Seamstress',
        'email' => 'seamstress@example.com',
    ]);

    $originalAvatar = $user->avatar;

    $response = $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

    $response->assertRedirect(route('profile'));
    $response->assertSessionHas('success', 'Изменения сохранены.');

    $user->refresh();
    expect($user->avatar)->toBe($originalAvatar);
    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');
});

test('avatar upload validates required fields', function () {
    $seamstressRole = Role::where('name', 'seamstress')->first();
    $user = User::factory()->create([
        'role_id' => $seamstressRole->id,
        'name' => 'Test Seamstress',
        'email' => 'seamstress@example.com',
    ]);

    $avatarFile = UploadedFile::fake()->image('avatar.jpg', 800, 600);

    $response = $this->actingAs($user)
        ->put(route('profile.update'), [
            'avatar' => $avatarFile,
        ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['name', 'email']);

    Storage::disk('public')->assertMissing('avatars/'.$user->id.'.png');
});

test('avatar can be uploaded with all supported image formats', function ($extension, $mimeType) {
    $seamstressRole = Role::where('name', 'seamstress')->first();
    $user = User::factory()->create([
        'role_id' => $seamstressRole->id,
        'name' => 'Test Seamstress',
        'email' => 'seamstress@example.com',
    ]);

    $avatarFile = UploadedFile::fake()->image('avatar.'.$extension, 800, 600);

    $response = $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'avatar' => $avatarFile,
        ]);

    $response->assertRedirect(route('profile'));

    Storage::disk('public')->assertExists('avatars/'.$user->id.'.png');

    $user->refresh();
    expect($user->avatar)->toBe('avatars/'.$user->id.'.png');
})->with([
    ['jpg', 'image/jpeg'],
    ['jpeg', 'image/jpeg'],
    ['png', 'image/png'],
    ['webp', 'image/webp'],
    ['gif', 'image/gif'],
]);

test('avatar overwrites existing avatar when uploading new one', function () {
    $seamstressRole = Role::where('name', 'seamstress')->first();
    $user = User::factory()->create([
        'role_id' => $seamstressRole->id,
        'name' => 'Test Seamstress',
        'email' => 'seamstress@example.com',
        'avatar' => 'avatars/old-avatar.png',
    ]);

    Storage::disk('public')->put('avatars/old-avatar.png', 'old content');

    $avatarFile = UploadedFile::fake()->image('new-avatar.jpg', 800, 600);

    $response = $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'avatar' => $avatarFile,
        ]);

    $response->assertRedirect(route('profile'));

    Storage::disk('public')->assertExists('avatars/'.$user->id.'.png');

    $user->refresh();
    expect($user->avatar)->toBe('avatars/'.$user->id.'.png');

    $imageContent = Storage::disk('public')->get('avatars/'.$user->id.'.png');
    $imageInfo = getimagesizefromstring($imageContent);

    expect($imageInfo[0])->toBe(256);
    expect($imageInfo[1])->toBe(256);
    expect($imageInfo[2])->toBe(IMAGETYPE_PNG);
});

test('non-square image is cropped to square', function () {
    $seamstressRole = Role::where('name', 'seamstress')->first();
    $user = User::factory()->create([
        'role_id' => $seamstressRole->id,
        'name' => 'Test Seamstress',
        'email' => 'seamstress@example.com',
    ]);

    $wideAvatar = UploadedFile::fake()->image('wide.jpg', 1920, 1080);

    $response = $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'avatar' => $wideAvatar,
        ]);

    $response->assertRedirect(route('profile'));

    $imageContent = Storage::disk('public')->get('avatars/'.$user->id.'.png');
    $imageInfo = getimagesizefromstring($imageContent);

    expect($imageInfo[0])->toBe(256);
    expect($imageInfo[1])->toBe(256);
});

test('small image is upscaled to 256x256', function () {
    $seamstressRole = Role::where('name', 'seamstress')->first();
    $user = User::factory()->create([
        'role_id' => $seamstressRole->id,
        'name' => 'Test Seamstress',
        'email' => 'seamstress@example.com',
    ]);

    $smallAvatar = UploadedFile::fake()->image('tiny.jpg', 64, 64);

    $response = $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'avatar' => $smallAvatar,
        ]);

    $response->assertRedirect(route('profile'));

    $imageContent = Storage::disk('public')->get('avatars/'.$user->id.'.png');
    $imageInfo = getimagesizefromstring($imageContent);

    expect($imageInfo[0])->toBe(256);
    expect($imageInfo[1])->toBe(256);
});

test('avatar upload requires authentication', function () {
    $avatarFile = UploadedFile::fake()->image('avatar.jpg', 800, 600);

    $response = $this->put(route('profile.update'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'avatar' => $avatarFile,
    ]);

    $response->assertRedirect(route('login'));
});

test('admin can also upload avatar', function () {
    $adminRole = Role::where('name', 'admin')->first();
    $admin = User::factory()->create([
        'role_id' => $adminRole->id,
        'name' => 'Admin User',
        'email' => 'admin@example.com',
    ]);

    $avatarFile = UploadedFile::fake()->image('avatar.jpg', 800, 600);

    $response = $this->actingAs($admin)
        ->put(route('profile.update'), [
            'name' => 'Updated Admin',
            'email' => 'updated-admin@example.com',
            'avatar' => $avatarFile,
        ]);

    $response->assertRedirect(route('profile'));

    Storage::disk('public')->assertExists('avatars/'.$admin->id.'.png');

    $admin->refresh();
    expect($admin->avatar)->toBe('avatars/'.$admin->id.'.png');
});
