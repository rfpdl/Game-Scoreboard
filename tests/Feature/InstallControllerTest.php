<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

describe('Install Wizard Index', function () {
    it('shows install wizard when no admin exists', function () {
        // Ensure no admin users exist
        User::where('is_admin', true)->delete();

        $response = $this->get(route('install.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Install/Index')
            ->has('settings', fn ($settings) => $settings
                ->has('appName')
                ->has('primaryColor')
                ->has('logoUrl')
            )
        );
    });

    it('redirects to login when admin exists', function () {
        User::factory()->create(['is_admin' => true]);

        $response = $this->get(route('install.index'));

        $response->assertRedirect(route('login'));
    });

    it('returns current settings values', function () {
        User::where('is_admin', true)->delete();
        Setting::set('app_name', 'Test App');
        Setting::set('primary_color', '#ff0000');

        $response = $this->get(route('install.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('settings.appName', 'Test App')
            ->where('settings.primaryColor', '#ff0000')
        );
    });
});

describe('Install Settings Store', function () {
    it('saves branding settings', function () {
        $response = $this->post(route('install.settings'), [
            'appName' => 'My Game Hub',
            'primaryColor' => '#123456',
        ]);

        $response->assertRedirect(route('install.index'));
        expect(Setting::get('app_name'))->toBe('My Game Hub');
        expect(Setting::get('primary_color'))->toBe('#123456');
    });

    it('validates app name is required', function () {
        $response = $this->post(route('install.settings'), [
            'primaryColor' => '#123456',
        ]);

        $response->assertSessionHasErrors(['appName']);
    });

    it('validates primary color is required', function () {
        $response = $this->post(route('install.settings'), [
            'appName' => 'Test App',
        ]);

        $response->assertSessionHasErrors(['primaryColor']);
    });

    it('validates primary color format', function () {
        $response = $this->post(route('install.settings'), [
            'appName' => 'Test App',
            'primaryColor' => 'not-a-color',
        ]);

        $response->assertSessionHasErrors(['primaryColor']);
    });

    it('accepts valid hex colors', function () {
        $response = $this->post(route('install.settings'), [
            'appName' => 'Test App',
            'primaryColor' => '#abcdef',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        expect(Setting::get('primary_color'))->toBe('#abcdef');
    });
});

describe('Install Logo Upload', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('uploads logo successfully', function () {
        $file = UploadedFile::fake()->image('logo.png', 100, 100);

        $response = $this->post(route('install.logo'), [
            'logo' => $file,
        ]);

        $response->assertRedirect(route('install.index'));
        expect(Setting::get('logo_url'))->toStartWith('/storage/uploads/');
        Storage::disk('public')->assertExists('uploads/'.$file->hashName());
    });

    it('validates logo is required', function () {
        $response = $this->post(route('install.logo'), []);

        $response->assertSessionHasErrors(['logo']);
    });

    it('validates logo is an image', function () {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->post(route('install.logo'), [
            'logo' => $file,
        ]);

        $response->assertSessionHasErrors(['logo']);
    });

    it('validates logo file size', function () {
        $file = UploadedFile::fake()->create('logo.png', 3000, 'image/png'); // 3MB, limit is 2MB

        $response = $this->post(route('install.logo'), [
            'logo' => $file,
        ]);

        $response->assertSessionHasErrors(['logo']);
    });

    it('accepts png format', function () {
        $file = UploadedFile::fake()->image('logo.png', 100, 100);

        $response = $this->post(route('install.logo'), ['logo' => $file]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    });

    it('deletes old logo when uploading new one', function () {
        // Upload first logo
        $oldFile = UploadedFile::fake()->image('old-logo.png', 100, 100);
        $this->post(route('install.logo'), ['logo' => $oldFile]);

        $oldPath = 'uploads/'.$oldFile->hashName();
        Storage::disk('public')->assertExists($oldPath);

        // Upload new logo
        $newFile = UploadedFile::fake()->image('new-logo.png', 100, 100);
        $this->post(route('install.logo'), ['logo' => $newFile]);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists('uploads/'.$newFile->hashName());
    });
});

describe('Install Logo Remove', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('removes logo successfully', function () {
        // Upload a logo first
        $file = UploadedFile::fake()->image('logo.png', 100, 100);
        $this->post(route('install.logo'), ['logo' => $file]);

        $path = 'uploads/'.$file->hashName();
        Storage::disk('public')->assertExists($path);

        // Remove it
        $response = $this->delete(route('install.logo.remove'));

        $response->assertRedirect(route('install.index'));
        expect(Setting::get('logo_url'))->toBeNull();
        Storage::disk('public')->assertMissing($path);
    });

    it('handles removing logo when none exists', function () {
        Setting::set('logo_url', null);

        $response = $this->delete(route('install.logo.remove'));

        $response->assertRedirect(route('install.index'));
        expect(Setting::get('logo_url'))->toBeNull();
    });
});

describe('Install Admin Creation', function () {
    beforeEach(function () {
        // Ensure no admin exists
        User::where('is_admin', true)->delete();
    });

    it('creates first admin user', function () {
        $response = $this->post(route('install.admin'), [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $admin = User::where('email', 'admin@example.com')->first();
        expect($admin)->not->toBeNull();
        expect($admin->is_admin)->toBeTrue();
        expect($admin->email_verified_at)->not->toBeNull();
    });

    it('prevents creating admin when one exists', function () {
        User::factory()->create(['is_admin' => true]);

        $response = $this->post(route('install.admin'), [
            'name' => 'Another Admin',
            'email' => 'another@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        expect(User::where('email', 'another@example.com')->exists())->toBeFalse();
    });

    it('validates name is required', function () {
        $response = $this->post(route('install.admin'), [
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['name']);
    });

    it('validates email is required', function () {
        $response = $this->post(route('install.admin'), [
            'name' => 'Admin User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    it('validates email format', function () {
        $response = $this->post(route('install.admin'), [
            'name' => 'Admin User',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    it('validates email is unique', function () {
        User::factory()->create(['email' => 'existing@example.com', 'is_admin' => false]);

        $response = $this->post(route('install.admin'), [
            'name' => 'Admin User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    it('validates password is required', function () {
        $response = $this->post(route('install.admin'), [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $response->assertSessionHasErrors(['password']);
    });

    it('validates password confirmation matches', function () {
        $response = $this->post(route('install.admin'), [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors(['password']);
    });
});
