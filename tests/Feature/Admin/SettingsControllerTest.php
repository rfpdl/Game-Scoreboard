<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

describe('Admin Settings Index', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
    });

    it('shows settings page to admin users', function () {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Settings')
            ->has('settings', fn ($settings) => $settings
                ->has('appName')
                ->has('primaryColor')
                ->has('logoUrl')
                ->has('registrationEnabled')
                ->has('colorMode')
                ->has('streakMultiplierEnabled')
            )
        );
    });

    it('denies access to non-admin users', function () {
        $response = $this->actingAs($this->user)->get(route('admin.settings.index'));

        $response->assertForbidden();
    });

    it('denies access to guests', function () {
        $response = $this->get(route('admin.settings.index'));

        $response->assertRedirect(route('login'));
    });

    it('returns settings from database', function () {
        Setting::set('app_name', 'Test App');
        Setting::set('primary_color', '#ff0000');

        $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Settings')
            ->where('settings.appName', 'Test App')
            ->where('settings.primaryColor', '#ff0000')
        );
    });
});

describe('Admin Settings Update', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
    });

    it('allows admin to update settings', function () {
        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'appName' => 'New App Name',
            'primaryColor' => '#123456',
            'registrationEnabled' => true,
            'colorMode' => 'dark',
            'streakMultiplierEnabled' => true,
        ]);

        $response->assertRedirect();
        expect(Setting::get('app_name'))->toBe('New App Name');
        expect(Setting::get('primary_color'))->toBe('#123456');
        expect((bool) Setting::get('registration_enabled'))->toBe(true);
        expect(Setting::get('color_mode'))->toBe('dark');
        expect((bool) Setting::get('streak_multiplier_enabled'))->toBe(true);
    });

    it('prevents non-admin from updating settings', function () {
        $response = $this->actingAs($this->user)->put(route('admin.settings.update'), [
            'appName' => 'Hacked App',
            'primaryColor' => '#000000',
            'registrationEnabled' => false,
            'colorMode' => 'dark',
            'streakMultiplierEnabled' => false,
        ]);

        $response->assertForbidden();
    });

    it('validates required fields', function () {
        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), []);

        $response->assertSessionHasErrors(['appName', 'primaryColor', 'registrationEnabled', 'colorMode', 'streakMultiplierEnabled']);
    });

    it('validates primary color format', function () {
        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'appName' => 'Test App',
            'primaryColor' => 'invalid-color',
            'registrationEnabled' => true,
            'colorMode' => 'light',
            'streakMultiplierEnabled' => false,
        ]);

        $response->assertSessionHasErrors(['primaryColor']);
    });

    it('validates color mode values', function () {
        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'appName' => 'Test App',
            'primaryColor' => '#123456',
            'registrationEnabled' => true,
            'colorMode' => 'invalid',
            'streakMultiplierEnabled' => false,
        ]);

        $response->assertSessionHasErrors(['colorMode']);
    });

    it('accepts valid color modes', function () {
        foreach (['light', 'dark', 'system'] as $mode) {
            $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
                'appName' => 'Test App',
                'primaryColor' => '#123456',
                'registrationEnabled' => true,
                'colorMode' => $mode,
                'streakMultiplierEnabled' => false,
            ]);

            $response->assertRedirect();
            expect(Setting::get('color_mode'))->toBe($mode);
        }
    });
});

describe('Admin Settings Logo Upload', function () {
    beforeEach(function () {
        Storage::fake('public');
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
    });

    it('allows admin to upload logo', function () {
        $file = UploadedFile::fake()->create('logo.png', 100, 'image/png');

        $response = $this->actingAs($this->admin)->post(route('admin.settings.logo'), [
            'logo' => $file,
        ]);

        $response->assertRedirect();
        expect(Setting::get('logo_url'))->toStartWith('/storage/uploads/');
        Storage::disk('public')->assertExists('uploads/'.$file->hashName());
    });

    it('prevents non-admin from uploading logo', function () {
        $file = UploadedFile::fake()->create('logo.png', 100, 'image/png');

        $response = $this->actingAs($this->user)->post(route('admin.settings.logo'), [
            'logo' => $file,
        ]);

        $response->assertForbidden();
    });

    it('validates logo file is required', function () {
        $response = $this->actingAs($this->admin)->post(route('admin.settings.logo'), []);

        $response->assertSessionHasErrors(['logo']);
    });

    it('validates logo file type', function () {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->admin)->post(route('admin.settings.logo'), [
            'logo' => $file,
        ]);

        $response->assertSessionHasErrors(['logo']);
    });

    it('validates logo file size', function () {
        $file = UploadedFile::fake()->create('logo.png', 3000, 'image/png'); // 3MB

        $response = $this->actingAs($this->admin)->post(route('admin.settings.logo'), [
            'logo' => $file,
        ]);

        $response->assertSessionHasErrors(['logo']);
    });

    it('accepts png image format', function () {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('logo.png', 100, 'image/png');

        $response = $this->actingAs($this->admin)->post(route('admin.settings.logo'), [
            'logo' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    });

    it('deletes old logo when uploading new one', function () {
        // Upload first logo
        $oldFile = UploadedFile::fake()->create('old-logo.png', 100, 'image/png');
        $this->actingAs($this->admin)->post(route('admin.settings.logo'), ['logo' => $oldFile]);

        $oldPath = 'uploads/'.$oldFile->hashName();
        Storage::disk('public')->assertExists($oldPath);

        // Upload new logo
        $newFile = UploadedFile::fake()->create('new-logo.png', 100, 'image/png');
        $this->actingAs($this->admin)->post(route('admin.settings.logo'), ['logo' => $newFile]);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists('uploads/'.$newFile->hashName());
    });
});

describe('Admin Settings Logo Remove', function () {
    beforeEach(function () {
        Storage::fake('public');
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
    });

    it('allows admin to remove logo', function () {
        // Upload a logo first
        $file = UploadedFile::fake()->create('logo.png', 100, 'image/png');
        $this->actingAs($this->admin)->post(route('admin.settings.logo'), ['logo' => $file]);

        $path = 'uploads/'.$file->hashName();
        Storage::disk('public')->assertExists($path);

        // Remove the logo
        $response = $this->actingAs($this->admin)->delete(route('admin.settings.logo.remove'));

        $response->assertRedirect();
        expect(Setting::get('logo_url'))->toBeNull();
        Storage::disk('public')->assertMissing($path);
    });

    it('prevents non-admin from removing logo', function () {
        $response = $this->actingAs($this->user)->delete(route('admin.settings.logo.remove'));

        $response->assertForbidden();
    });

    it('handles removing logo when none exists', function () {
        Setting::set('logo_url', null);

        $response = $this->actingAs($this->admin)->delete(route('admin.settings.logo.remove'));

        $response->assertRedirect();
        expect(Setting::get('logo_url'))->toBeNull();
    });
});
