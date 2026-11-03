<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppUpdateTest extends TestCase
{
    public function test_check_update_endpoint_returns_success_and_metadata(): void
    {
        $response = $this->getJson('/api/v1/app/check-update');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'has_update',
                'latest_version',
                'latest_build_number',
                'download_url',
                'changelog',
                'is_mandatory',
                'release_date',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('latest_version'));
        $this->assertNotEmpty($response->json('download_url'));
    }

    public function test_check_update_indicates_update_for_older_build(): void
    {
        $response = $this->getJson('/api/v1/app/check-update?build_number=1&version_name=1.0.0');

        $response->assertStatus(200);
        $this->assertTrue($response->json('has_update'));
    }

    public function test_check_update_indicates_no_update_for_equal_or_higher_build(): void
    {
        $latestBuild = 3;
        $response = $this->getJson('/api/v1/app/check-update?build_number=' . ($latestBuild + 1) . '&version_name=2.0.0');

        $response->assertStatus(200);
        $this->assertFalse($response->json('has_update'));
    }
}
