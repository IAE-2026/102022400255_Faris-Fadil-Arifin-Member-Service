<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_be_created_with_student_profile_data(): void
    {
        $payload = [
            'name' => 'Budi Santoso',
            'student_number' => 'SISWA-00001',
            'email' => 'budi@example.com',
            'phone' => '08123456789',
            'address' => 'Jl. Merdeka No. 1',
        ];

        $response = $this->postJson('/api/v1/members', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Member created successfully')
            ->assertJsonPath('data.name', 'Budi Santoso')
            ->assertJsonPath('data.student_number', 'SISWA-00001')
            ->assertJsonPath('data.email', 'budi@example.com')
            ->assertJsonPath('data.status', Member::STATUS_ACTIVE)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('members', [
            'student_number' => 'SISWA-00001',
            'email' => 'budi@example.com',
            'status' => Member::STATUS_ACTIVE,
        ]);
    }

    public function test_create_member_validates_required_and_unique_fields(): void
    {
        $this->postJson('/api/v1/members', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonValidationErrors(['name', 'student_number', 'email']);

        Member::factory()->create([
            'student_number' => 'SISWA-00002',
            'email' => 'siti@example.com',
        ]);

        $this->postJson('/api/v1/members', [
            'name' => 'Siti Aminah',
            'student_number' => 'SISWA-00002',
            'email' => 'siti@example.com',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['student_number', 'email']);
    }

    public function test_members_can_be_listed_with_pagination(): void
    {
        Member::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/members?per_page=2');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Members retrieved successfully')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(2, 'data');
    }

    public function test_member_detail_includes_status_and_active_flag(): void
    {
        $member = Member::factory()->inactive()->create();

        $response = $this->getJson("/api/v1/members/{$member->id}");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Member retrieved successfully')
            ->assertJsonPath('data.id', $member->id)
            ->assertJsonPath('data.status', Member::STATUS_INACTIVE)
            ->assertJsonPath('data.is_active', false);
    }

    public function test_unknown_member_returns_not_found_response(): void
    {
        $this->getJson('/api/v1/members/999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Member not found')
            ->assertJsonPath('data', null);
    }
}
