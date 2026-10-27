<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageFileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_can_serve_uploaded_file_from_public_storage(): void
    {
        // Put a fake image in public storage
        Storage::disk('public')->put('branding/test-logo.png', 'fake-png-content');

        // Request via /storage route
        $response = $this->get('/storage/branding/test-logo.png');

        $response->assertStatus(200);
        $this->assertEquals('image/png', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
    }

    public function test_returns_404_when_file_does_not_exist(): void
    {
        $response = $this->get('/storage/branding/non-existent-file.png');

        $response->assertStatus(404);
    }

    public function test_prevents_directory_traversal(): void
    {
        $response = $this->get('/storage/../../etc/passwd');

        $response->assertStatus(404);
    }
}
