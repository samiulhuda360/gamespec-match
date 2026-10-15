<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\CPU;
use App\Models\GPU;
use App\Models\User;
use App\Models\UserHardware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HardwareProfileTest extends TestCase
{
    use RefreshDatabase;

    private function form(): array
    {
        return [
            'profile_name' => 'Desk PC',
            'cpu_id' => CPU::where('name', 'Intel Core i5-4460')->value('id'),
            'gpu_id' => GPU::where('name', 'NVIDIA GeForce GTX 1060 6GB')->value('id'),
            'ram_gb' => 16,
            'platform' => 'windows_10',
        ];
    }

    public function test_a_guest_can_save_a_pc_and_check_a_game(): void
    {
        $id = $this->postJson('/hardware', $this->form())->assertOk()->json('hardware_id');

        $pc = UserHardware::findOrFail($id);
        $this->assertSame('Intel Core i5-4460', $pc->cpu_model);
        $this->assertGreaterThan(0, $pc->overall_score);

        $this->postJson('/hardware/check/'.App::where('slug', 'cyberpunk-2077')->value('app_id'))
            ->assertOk()
            ->assertJsonStructure(['can_run', 'verdict', 'details' => ['component_comparison' => ['cpu', 'gpu', 'ram']]]);
    }

    public function test_saving_again_updates_the_active_profile(): void
    {
        $first = $this->postJson('/hardware', $this->form())->json('hardware_id');
        $second = $this->postJson('/hardware', ['ram_gb' => 32] + $this->form())->json('hardware_id');

        $this->assertSame($first, $second);
        $this->assertSame(32, UserHardware::find($first)->ram_gb);
    }

    public function test_someone_elses_profile_is_not_found(): void
    {
        $other = $this->pc('Intel Core i5-4460', 'NVIDIA GeForce GTX 1060 6GB', sessionId: 'another-browser');
        $game = App::where('slug', 'cyberpunk-2077')->value('app_id');

        $this->get("/hardware/{$other->id}")->assertNotFound();
        $this->get("/hardware/{$other->id}/edit")->assertNotFound();
        $this->putJson("/hardware/{$other->id}", $this->form())->assertNotFound();
        $this->postJson("/hardware/check/{$game}", ['hardware_id' => $other->id])->assertNotFound();

        $user = User::factory()->create();
        $owned = $this->pc('Intel Core i5-4460', 'NVIDIA GeForce GTX 1060 6GB', userId: $user->id);
        $this->get("/hardware/{$owned->id}")->assertNotFound();
        $this->actingAs($user)->get("/hardware/{$owned->id}")->assertOk();
    }

    public function test_signing_in_moves_the_guest_profile_to_the_account(): void
    {
        $id = $this->postJson('/hardware', $this->form())->json('hardware_id');

        $this->post('/login', ['email' => 'demo@example.com', 'password' => 'password'])->assertRedirect('/account');

        $demo = User::where('email', 'demo@example.com')->first();
        $this->assertSame($demo->id, UserHardware::find($id)->user_id);
        $this->get('/account')->assertOk()->assertSee('Desk PC')->assertSee('What to upgrade');
    }

    public function test_deleting_the_account_removes_its_pcs(): void
    {
        $user = User::factory()->create();
        $pc = $this->pc('Intel Core i5-4460', 'NVIDIA GeForce GTX 1060 6GB', userId: $user->id);

        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertRedirect('/');

        $this->assertNull(UserHardware::find($pc->id));
    }

    public function test_describe_your_pc_fills_parts_from_text_without_ai(): void
    {
        $this->postJson('/hardware/describe', ['text' => 'i5 4460, gtx 1060 6gb, 16gb ram, windows 10'])
            ->assertOk()
            ->assertJsonPath('cpu.name', 'Intel Core i5-4460')
            ->assertJsonPath('cpu.how', 'text')
            ->assertJsonPath('gpu.name', 'NVIDIA GeForce GTX 1060 6GB')
            ->assertJsonPath('ram_gb', 16)
            ->assertJsonPath('platform', 'windows_10')
            ->assertJsonPath('source', 'rules');

        $this->postJson('/hardware/describe', ['text' => ''])->assertUnprocessable();
    }
}
