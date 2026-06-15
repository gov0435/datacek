<?php

namespace Database\Factories;

use App\Models\SptjmSekolah;
use App\Models\SptjmUnggahan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SptjmUnggahanFactory extends Factory
{
    protected $model = SptjmUnggahan::class;

    public function definition(): array
    {
        return [
            'sptjm_sekolah_id' => SptjmSekolah::factory(),
            'disk' => 's3',
            'file_path' => 'sptjm/'.fake()->uuid().'.pdf',
            'file_name' => fake()->word().'.pdf',
            'file_mime' => 'application/pdf',
            'file_size' => fake()->numberBetween(100000, 5000000),
            'is_valid' => true,
            'catatan' => fake()->optional()->sentence(),
            'uploaded_by' => User::factory(),
        ];
    }
}
