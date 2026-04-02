<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CoffeeSeeder extends Seeder
{
    public function run(): void
{
    $coffees = [
        [
            'name' => 'Espresso',
            'category' => 'Classiques',
            'image' => '/images/espresso1.jpg',
            'available' => true,
            'is_new' => false,
            'description' => 'Un café intense et corsé, parfait pour les amateurs de saveurs pures.',
            'ingredients' => ['Espresso', 'Eau'],
            'sizes' => [
                ['key' => 'S', 'label' => 'S', 'price' => 4],
                ['key' => 'M', 'label' => 'M', 'price' => 5],
            ],
        ],
        [
            'name' => 'Cappuccino',
            'category' => 'Lattés',
            'image' => '/images/cappuccino.jpg',
            'available' => false,
            'is_new' => false,
            'description' => 'Un cappuccino onctueux avec une mousse légère et un goût équilibré.',
            'ingredients' => ['Espresso', 'Lait', 'Mousse de lait'],
            'sizes' => [
                ['key' => 'S', 'label' => 'S', 'price' => 6],
                ['key' => 'M', 'label' => 'M', 'price' => 7.5],
                ['key' => 'L', 'label' => 'L', 'price' => 9],
            ],
        ],
    ];

    foreach ($coffees as $item) {
        $coffee = \App\Models\Coffee::create([
            'name' => $item['name'],
            'category' => $item['category'],
            'image' => $item['image'],
            'available' => $item['available'],
            'is_new' => $item['is_new'],
            'description' => $item['description'],
        ]);

        foreach ($item['ingredients'] as $ingredient) {
            $coffee->ingredients()->create([
                'name' => $ingredient,
            ]);
        }

        foreach ($item['sizes'] as $size) {
            $coffee->sizes()->create($size);
        }
    }
    


    
}
}
