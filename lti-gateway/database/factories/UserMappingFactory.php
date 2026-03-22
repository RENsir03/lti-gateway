<?php

namespace Database\Factories;

use App\Models\ToolConfig;
use App\Models\UserMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserMappingFactory extends Factory
{
    protected $model = UserMapping::class;

    public function definition(): array
    {
        $studentId = 'STU' . $this->faker->unique()->numberBetween(100000, 999999);
        
        return [
            'source_student_id' => $studentId,
            'tool_config_id' => ToolConfig::factory(),
            'target_user_id' => (string) $this->faker->numberBetween(1, 999999),
            'target_username' => strtolower($studentId),
            'virtual_email' => strtolower($studentId) . '@proxy.university.edu',
            'last_synced_at' => now(),
            'metadata' => [
                'firstname' => $this->faker->firstName,
                'lastname' => $this->faker->lastName,
            ],
        ];
    }
}
