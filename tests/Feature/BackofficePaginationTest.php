<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class BackofficePaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_indonesian_pagination_translations_resolve(): void
    {
        $this->assertEquals('&laquo; Sebelumnya', __('pagination.previous'));
        $this->assertEquals('Berikutnya &raquo;', __('pagination.next'));
    }

    public function test_pagination_view_renders_hirosima_design_structure(): void
    {
        $items = collect(range(1, 60));
        $paginator = new LengthAwarePaginator($items->slice(0, 25), 60, 25, 1, [
            'path' => url('/backoffice/audit'),
        ]);

        $rendered = $paginator->links()->toHtml();

        $this->assertStringContainsString('class="pagination-container"', $rendered);
        $this->assertStringContainsString('class="pagination-info"', $rendered);
        $this->assertStringContainsString('Menampilkan <strong>1</strong> &ndash; <strong>25</strong> dari <strong>60</strong> data', $rendered);
        $this->assertStringContainsString('class="pagination-nav"', $rendered);
        $this->assertStringContainsString('Sebelumnya', $rendered);
        $this->assertStringContainsString('Berikutnya', $rendered);
        $this->assertStringContainsString('class="pagination-link is-active"', $rendered);
    }

    public function test_audit_log_page_contains_styled_pagination(): void
    {
        $business = \App\Models\Business::firstOrCreate(
            ['code' => 'HRS'],
            ['name' => 'Hiroshima Japanese Food']
        );

        $branch = Branch::firstOrCreate(
            ['code' => 'HRS-PAG-TEST'],
            [
                'business_id' => $business->id,
                'name' => 'Cabang Test Pag',
                'address' => 'Jl. Test Pag',
                'is_active' => true,
                'timezone' => 'Asia/Jakarta',
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'admin_pag_test@hiroshima.local'],
            [
                'name' => 'Admin Pag Test',
                'password' => bcrypt('password'),
                'role' => 'central_admin',
                'branch_id' => $branch->id,
                'is_active' => true,
            ]
        );

        $logger = app(\App\Services\AuditLogger::class);
        for ($i = 0; $i < 30; $i++) {
            $logger->record('test_action_' . $i, null, [], [], [
                'user_id' => $user->id,
                'branch_id' => $branch->id,
            ]);
        }

        $response = $this->actingAs($user)->get(route('audit.index'));

        $response->assertStatus(200);
        $response->assertSee('pagination-container');
        $response->assertSee('pagination-info');
        $response->assertSee('pagination-nav');
        $response->assertSee('Sebelumnya');
        $response->assertSee('Berikutnya');
    }
}
