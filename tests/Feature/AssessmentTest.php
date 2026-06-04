<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Database\Seeders\FuzzyRuleSeeder;
use App\Models\Assessment;

class AssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed fuzzy rules so they exist in database when formatting payload
        $this->seed(FuzzyRuleSeeder::class);
    }

    /**
     * Test listing assessments
     */
    public function test_can_list_assessments(): void
    {
        Assessment::create([
            'laptop_name' => 'Laptop A',
            'lcd_input' => 80,
            'battery_input' => 75,
            'processor_input' => 8000,
            'keyboard_input' => 90,
            'final_score' => 85,
            'status' => 'Bagus',
            'market_price' => 5000000,
            'estimated_price' => 4250000,
            'description' => 'Mulus',
            'ai_conclusion' => 'Laptop direkomendasikan'
        ]);

        $response = $this->getJson('/api/assessments');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'data' => [
                         'data',
                         'current_page',
                         'last_page'
                     ]
                 ])
                 ->assertJsonFragment(['laptop_name' => 'Laptop A']);
    }

    /**
     * Test creating assessment
     */
    public function test_can_create_assessment_with_mocked_services(): void
    {
        Http::fake([
            '*/api/evaluator' => Http::response([
                'status' => 'success',
                'data' => [
                    'nilaiKelayakan' => 85,
                    'statusKelayakan' => 'Bagus'
                ]
            ], 200),
            '*generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'AI recommendation text here.']
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/assessments', [
            'laptop_name' => 'Asus ROG',
            'lcd' => 90,
            'battery' => 85,
            'processor' => 12000,
            'keyboard' => 95,
            'market_price' => 10000000,
            'description' => 'Kondisi fisik sangat prima.'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Penilaian berhasil dihitung dan disimpan.'
                 ]);

        $this->assertDatabaseHas('assessments', [
            'laptop_name' => 'Asus ROG',
            'final_score' => 85,
            'status' => 'Bagus',
            'ai_conclusion' => 'AI recommendation text here.'
        ]);
    }

    /**
     * Test validation during creation
     */
    public function test_create_assessment_requires_fields(): void
    {
        $response = $this->postJson('/api/assessments', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'laptop_name',
                     'lcd',
                     'battery',
                     'processor',
                     'keyboard',
                     'market_price'
                 ]);
    }

    /**
     * Test viewing single assessment
     */
    public function test_can_show_single_assessment(): void
    {
        $assessment = Assessment::create([
            'laptop_name' => 'MacBook Pro',
            'lcd_input' => 95,
            'battery_input' => 90,
            'processor_input' => 15000,
            'keyboard_input' => 95,
            'final_score' => 95,
            'status' => 'Bagus',
            'market_price' => 15000000,
            'estimated_price' => 14250000,
            'description' => 'Mulus lengkap',
            'ai_conclusion' => 'Sangat direkomendasikan'
        ]);

        $response = $this->getJson("/api/assessments/{$assessment->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'data' => [
                         'laptop_name' => 'MacBook Pro'
                     ]
                 ]);
    }

    /**
     * Test deleting assessment
     */
    public function test_can_delete_assessment(): void
    {
        $assessment = Assessment::create([
            'laptop_name' => 'Dell XPS',
            'lcd_input' => 80,
            'battery_input' => 70,
            'processor_input' => 9000,
            'keyboard_input' => 80,
            'final_score' => 80,
            'status' => 'Bagus',
            'market_price' => 12000000,
            'estimated_price' => 9600000,
            'description' => 'Ada lecet dikit',
            'ai_conclusion' => 'Layak dibeli'
        ]);

        $response = $this->deleteJson("/api/assessments/{$assessment->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Data penilaian berhasil dihapus.'
                 ]);

        $this->assertDatabaseMissing('assessments', [
            'id' => $assessment->id
        ]);
    }
}
