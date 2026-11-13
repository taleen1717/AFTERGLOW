<?php

use App\Models\FavoriteSong;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('keeps existing and newly created songs private by default', function () {
    $owner = User::factory()->create();
    $private = $owner->favoriteSongs()->create([
        'title' => 'Private Song', 'artist' => 'Artist', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'mood' => 'Night Drive',
    ]);
    expect($private->fresh()->is_public)->toBeFalse();
    $this->get(route('soundtrack.discover'))->assertOk()->assertDontSee('Private Song');
});

it('shows only public songs and lets only the owner change visibility', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $song = $owner->favoriteSongs()->create([
        'title' => 'Community Track', 'artist' => 'Artist', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'mood' => 'Night Drive', 'is_public' => true,
    ]);
    $this->get(route('soundtrack.discover'))->assertOk()->assertSee('Community Track');
    $this->actingAs($stranger)->put(route('soundtrack.update', $song), [
        'title' => 'Hacked', 'artist' => 'Artist', 'mood' => 'Night Drive', 'is_public' => 0,
    ])->assertForbidden();
    $this->actingAs($owner)->put(route('soundtrack.update', $song), [
        'title' => 'Community Track', 'artist' => 'Artist', 'mood' => 'Night Drive', 'is_public' => 0,
    ])->assertRedirect();
    $this->get(route('soundtrack.discover'))->assertDontSee('Community Track');
});

it('lets a member edit their own name email and bio', function () {
    $member = User::factory()->create();
    $this->actingAs($member)->patch(route('profile.update'), [
        'name' => 'New Name', 'email' => 'new-name@example.test', 'bio' => 'Making beautiful things.',
    ])->assertRedirect();
    $this->assertDatabaseHas('users', ['id' => $member->id, 'name' => 'New Name', 'email' => 'new-name@example.test']);
});

it('rejects duplicate emails when updating a profile', function () {
    $other = User::factory()->create();
    $member = User::factory()->create();
    $this->actingAs($member)->patch(route('profile.update'), [
        'name' => 'New Name', 'email' => $other->email,
    ])->assertSessionHasErrors('email');
});

it('requires the current password before changing passwords', function () {
    $member = User::factory()->create(['password' => 'oldpassword123']);
    $this->actingAs($member)->patch(route('profile.password'), [
        'current_password' => 'wrong', 'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
    ])->assertSessionHasErrors('current_password');
    $this->actingAs($member)->patch(route('profile.password'), [
        'current_password' => 'oldpassword123', 'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
    ])->assertRedirect();
    expect(Hash::check('newpassword123', $member->fresh()->password))->toBeTrue();
});

it('accepts a valid profile avatar and rejects oversized files', function () {
    config(['afterglow_visual_storage.driver' => 'public']);
    Storage::fake('public');
    $member = User::factory()->create();
    $this->actingAs($member)->post(route('profile.avatar'), ['avatar' => UploadedFile::fake()->image('avatar.jpg', 150, 150)])->assertRedirect();
    expect($member->fresh()->avatar_path)->not->toBeNull();
    $this->actingAs($member)->post(route('profile.avatar'), ['avatar' => UploadedFile::fake()->image('huge.jpg')->size(3000)])->assertSessionHasErrors('avatar');
});
