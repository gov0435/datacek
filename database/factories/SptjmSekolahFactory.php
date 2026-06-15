<?php

namespace Database\Factories;

use App\Models\SptjmSekolah;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SptjmSekolahFactory extends Factory
{
    protected $model = SptjmSekolah::class;

    public function definition(): array
    {
        return [
            'sekolah_npsn' => fake()->unique()->numerify('#######'),
            'sekolah_nama' => fake()->company(),
            'sekolah_jenjang' => fake()->randomElement(['SD', 'SMP', 'SMA']),
            'sekolah_kota' => fake()->randomElement(['Kab. Gorontalo', 'Kota Gorontalo']),
            'sekolah_propinsi' => 'Gorontalo',
            'scope' => 'kabkota',
            'jumlah_guru' => fake()->numberBetween(1, 20),
            'generated_by' => User::factory(),
        ];
    }
}
