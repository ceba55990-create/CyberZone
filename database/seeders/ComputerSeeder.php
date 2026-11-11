<?php

namespace Database\Seeders;

use App\Models\Computer;
use Illuminate\Database\Seeder;

class ComputerSeeder extends Seeder
{
    public function run(): void
    {
        $computers = [
            [
                'name' => 'CyberZone PC-01',
                'cpu' => 'AMD Ryzen 5 5600',
                'gpu' => 'NVIDIA GeForce RTX 3060',
                'ram_gb' => 16, 'storage_gb' => 512, 'hourly_rate' => '45.00',
                'status' => 'available',
            ],
            [
                'name' => 'CyberZone PC-02',
                'cpu' => 'Intel Core i5-12400F',
                'gpu' => 'NVIDIA GeForce RTX 4060',
                'ram_gb' => 16, 'storage_gb' => 1000, 'hourly_rate' => '55.00',
                'status' => 'available',
            ],
            [
                'name' => 'CyberZone PC-03',
                'cpu' => 'AMD Ryzen 7 5800X',
                'gpu' => 'AMD Radeon RX 6700 XT',
                'ram_gb' => 32, 'storage_gb' => 1000, 'hourly_rate' => '60.00',
                'status' => 'available',
            ],
            [
                'name' => 'CyberZone PC-04',
                'cpu' => 'Intel Core i5-13400F',
                'gpu' => 'NVIDIA GeForce RTX 4060 Ti',
                'ram_gb' => 32, 'storage_gb' => 1000, 'hourly_rate' => '65.00',
                'status' => 'available',
            ],
            [
                'name' => 'CyberZone PC-05',
                'cpu' => 'AMD Ryzen 5 7600',
                'gpu' => 'AMD Radeon RX 7600',
                'ram_gb' => 32, 'storage_gb' => 1000, 'hourly_rate' => '60.00',
                'status' => 'available',
            ],
            [
                'name' => 'CyberZone PC-06',
                'cpu' => 'Intel Core i7-13700F',
                'gpu' => 'NVIDIA GeForce RTX 4070',
                'ram_gb' => 32, 'storage_gb' => 2000, 'hourly_rate' => '85.00',
                'status' => 'available',
            ],
            [
                'name' => 'CyberZone PC-07',
                'cpu' => 'AMD Ryzen 7 7800X3D',
                'gpu' => 'NVIDIA GeForce RTX 4070 Ti',
                'ram_gb' => 64, 'storage_gb' => 2000, 'hourly_rate' => '100.00',
                'status' => 'available',
            ],
            [
                'name' => 'CyberZone PC-08',
                'cpu' => 'Intel Core i5-11400F',
                'gpu' => 'NVIDIA GeForce GTX 1660 Super',
                'ram_gb' => 16, 'storage_gb' => 512, 'hourly_rate' => '35.00',
                'status' => 'occupied',
            ],
            [
                'name' => 'CyberZone PC-09',
                'cpu' => 'AMD Ryzen 5 7500F',
                'gpu' => 'AMD Radeon RX 7700 XT',
                'ram_gb' => 32, 'storage_gb' => 1000, 'hourly_rate' => '70.00',
                'status' => 'occupied',
            ],
            [
                'name' => 'CyberZone PC-10',
                'cpu' => 'Intel Core i7-14700F',
                'gpu' => 'NVIDIA GeForce RTX 4080 Super',
                'ram_gb' => 64, 'storage_gb' => 2000, 'hourly_rate' => '120.00',
                'status' => 'occupied',
            ],
            [
                'name' => 'CyberZone PC-11',
                'cpu' => 'AMD Ryzen 5 3600',
                'gpu' => 'NVIDIA GeForce RTX 2060',
                'ram_gb' => 16, 'storage_gb' => 512, 'hourly_rate' => '30.00',
                'status' => 'maintenance',
            ],
            [
                'name' => 'CyberZone PC-12',
                'cpu' => 'Intel Core i9-13900K',
                'gpu' => 'NVIDIA GeForce RTX 4090',
                'ram_gb' => 64, 'storage_gb' => 4000, 'hourly_rate' => '150.00',
                'status' => 'maintenance',
            ],
        ];

        foreach ($computers as $computer) {
            Computer::firstOrCreate(['name' => $computer['name']], $computer);
        }
    }
}
