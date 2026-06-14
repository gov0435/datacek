<?php

namespace Database\Factories;

use App\Models\BeritaAcaraSekolah;
use App\Models\BeritaAcaraUnggahan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BeritaAcaraUnggahanFactory extends Factory
{
    protected $model = BeritaAcaraUnggahan::class;

    public function definition(): array
    {
        return [
            'berita_acara_sekolah_id' => BeritaAcaraSekolah::factory(),
            'disk' => 's3',
            'file_path' => 'berita-acara/'.fake()->uuid().'.pdf',
            'file_name' => fake()->word().'.pdf',
            'file_mime' => 'application/pdf',
            'file_size' => fake()->numberBetween(100000, 5000000),
            'is_valid' => true,
            'catatan' => fake()->optional()->sentence(),
            'uploaded_by' => User::factory(),
        ];
    }
}
