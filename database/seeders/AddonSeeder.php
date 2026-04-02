<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddonSeeder extends Seeder
{
    public function run(): void
{
    $addons = [
        [
            'name' => 'Extra shot',
            'price' => 2,
            'image' => '/images/addons/extra-shot.png',
            'available' => true,
        ],
        [
            'name' => 'Chantilly',
            'price' => 1.5,
            'image' => '/images/addons/chantilly.png',
            'available' => true,
        ],
        [
            'name' => 'Caramel',
            'price' => 1,
            'image' => '/images/addons/caramel.png',
            'available' => true,
        ],
    ];

    foreach ($addons as $addon) {
        \App\Models\Addon::create($addon);
    }
}
}
