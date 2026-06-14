<?php

namespace Database\Factories;

use App\Models\DokumenDinas;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DokumenDinasFactory extends Factory
{
    protected $model = DokumenDinas::class;

    public function definition(): array
    {
        return [
            'kabkota' => fake()->randomElement(['Kab. Gorontalo', 'Kota Gorontalo', 'Provinsi']),
            'jenis' => fake()->randomElement(['Berita Acara', 'Dokumen Lain']),
            'disk' => 's3',
            'file_path' => 'dokumen-dinas/'.fake()->uuid().'.pdf',
            'file_name' => fake()->word().'.pdf',
            'file_mime' => 'application/pdf',
            'file_size' => fake()->numberBetween(100000, 5000000),
            'is_valid' => true,
            'catatan' => fake()->optional()->sentence(),
            'uploaded_by' => User::factory(),
        ];
    }
}
