<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CPU;
use App\Models\GPU;

class HardwareSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCPUs();
        $this->seedGPUs();
    }

    private function seedCPUs(): void
    {
        $cpus = [
            // Intel - Latest Gen (Popular)
            ['manufacturer' => 'intel', 'name' => 'Intel Core i9-14900K', 'generation' => '14th Gen', 'cores' => 24, 'threads' => 32, 'base_clock' => 3.20, 'benchmark_score' => 58500, 'release_year' => 2023, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i9-13900K', 'generation' => '13th Gen', 'cores' => 24, 'threads' => 32, 'base_clock' => 3.00, 'benchmark_score' => 56000, 'release_year' => 2022, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i7-14700K', 'generation' => '14th Gen', 'cores' => 20, 'threads' => 28, 'base_clock' => 3.40, 'benchmark_score' => 50500, 'release_year' => 2023, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i7-13700K', 'generation' => '13th Gen', 'cores' => 16, 'threads' => 24, 'base_clock' => 3.40, 'benchmark_score' => 48000, 'release_year' => 2022, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i5-14600K', 'generation' => '14th Gen', 'cores' => 14, 'threads' => 20, 'base_clock' => 3.50, 'benchmark_score' => 42000, 'release_year' => 2023, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i5-13600K', 'generation' => '13th Gen', 'cores' => 14, 'threads' => 20, 'base_clock' => 3.50, 'benchmark_score' => 40500, 'release_year' => 2022, 'is_popular' => true],

            // Intel - 12th Gen
            ['manufacturer' => 'intel', 'name' => 'Intel Core i9-12900K', 'generation' => '12th Gen', 'cores' => 16, 'threads' => 24, 'base_clock' => 3.20, 'benchmark_score' => 44500, 'release_year' => 2021, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i7-12700K', 'generation' => '12th Gen', 'cores' => 12, 'threads' => 20, 'base_clock' => 3.60, 'benchmark_score' => 38000, 'release_year' => 2021, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i5-12600K', 'generation' => '12th Gen', 'cores' => 10, 'threads' => 16, 'base_clock' => 3.70, 'benchmark_score' => 32000, 'release_year' => 2021, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i5-12400F', 'generation' => '12th Gen', 'cores' => 6, 'threads' => 12, 'base_clock' => 2.50, 'benchmark_score' => 22000, 'release_year' => 2021, 'is_popular' => true],

            // Intel - 11th Gen
            ['manufacturer' => 'intel', 'name' => 'Intel Core i9-11900K', 'generation' => '11th Gen', 'cores' => 8, 'threads' => 16, 'base_clock' => 3.50, 'benchmark_score' => 26000, 'release_year' => 2021, 'is_popular' => false],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i7-11700K', 'generation' => '11th Gen', 'cores' => 8, 'threads' => 16, 'base_clock' => 3.60, 'benchmark_score' => 24500, 'release_year' => 2021, 'is_popular' => false],

            // Intel - 10th Gen
            ['manufacturer' => 'intel', 'name' => 'Intel Core i9-10900K', 'generation' => '10th Gen', 'cores' => 10, 'threads' => 20, 'base_clock' => 3.70, 'benchmark_score' => 25500, 'release_year' => 2020, 'is_popular' => false],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i7-10700K', 'generation' => '10th Gen', 'cores' => 8, 'threads' => 16, 'base_clock' => 3.80, 'benchmark_score' => 21500, 'release_year' => 2020, 'is_popular' => false],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i5-10600K', 'generation' => '10th Gen', 'cores' => 6, 'threads' => 12, 'base_clock' => 4.10, 'benchmark_score' => 17500, 'release_year' => 2020, 'is_popular' => false],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i5-10400F', 'generation' => '10th Gen', 'cores' => 6, 'threads' => 12, 'base_clock' => 2.90, 'benchmark_score' => 14500, 'release_year' => 2020, 'is_popular' => true],

            // AMD Ryzen - 7000 Series (Popular)
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 9 7950X', 'generation' => 'Ryzen 7000', 'cores' => 16, 'threads' => 32, 'base_clock' => 4.50, 'benchmark_score' => 60000, 'release_year' => 2022, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 9 7900X', 'generation' => 'Ryzen 7000', 'cores' => 12, 'threads' => 24, 'base_clock' => 4.70, 'benchmark_score' => 50000, 'release_year' => 2022, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 7 7800X3D', 'generation' => 'Ryzen 7000', 'cores' => 8, 'threads' => 16, 'base_clock' => 4.20, 'benchmark_score' => 38000, 'release_year' => 2023, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 7 7700X', 'generation' => 'Ryzen 7000', 'cores' => 8, 'threads' => 16, 'base_clock' => 4.50, 'benchmark_score' => 39000, 'release_year' => 2022, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 5 7600X', 'generation' => 'Ryzen 7000', 'cores' => 6, 'threads' => 12, 'base_clock' => 4.70, 'benchmark_score' => 30000, 'release_year' => 2022, 'is_popular' => true],

            // AMD Ryzen - 5000 Series
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 9 5950X', 'generation' => 'Ryzen 5000', 'cores' => 16, 'threads' => 32, 'base_clock' => 3.40, 'benchmark_score' => 46000, 'release_year' => 2020, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 9 5900X', 'generation' => 'Ryzen 5000', 'cores' => 12, 'threads' => 24, 'base_clock' => 3.70, 'benchmark_score' => 40500, 'release_year' => 2020, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 7 5800X3D', 'generation' => 'Ryzen 5000', 'cores' => 8, 'threads' => 16, 'base_clock' => 3.40, 'benchmark_score' => 34500, 'release_year' => 2022, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 7 5800X', 'generation' => 'Ryzen 5000', 'cores' => 8, 'threads' => 16, 'base_clock' => 3.80, 'benchmark_score' => 33000, 'release_year' => 2020, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 7 5700X', 'generation' => 'Ryzen 5000', 'cores' => 8, 'threads' => 16, 'base_clock' => 3.40, 'benchmark_score' => 30000, 'release_year' => 2022, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 5 5600X', 'generation' => 'Ryzen 5000', 'cores' => 6, 'threads' => 12, 'base_clock' => 3.70, 'benchmark_score' => 23500, 'release_year' => 2020, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 5 5600', 'generation' => 'Ryzen 5000', 'cores' => 6, 'threads' => 12, 'base_clock' => 3.50, 'benchmark_score' => 21000, 'release_year' => 2022, 'is_popular' => true],

            // AMD Ryzen - 3000 Series
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 9 3900X', 'generation' => 'Ryzen 3000', 'cores' => 12, 'threads' => 24, 'base_clock' => 3.80, 'benchmark_score' => 33000, 'release_year' => 2019, 'is_popular' => false],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 7 3700X', 'generation' => 'Ryzen 3000', 'cores' => 8, 'threads' => 16, 'base_clock' => 3.60, 'benchmark_score' => 25000, 'release_year' => 2019, 'is_popular' => false],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 5 3600', 'generation' => 'Ryzen 3000', 'cores' => 6, 'threads' => 12, 'base_clock' => 3.60, 'benchmark_score' => 18000, 'release_year' => 2019, 'is_popular' => false],

            // Apple Silicon (Popular for MacBooks)
            ['manufacturer' => 'apple_silicon', 'name' => 'Apple M4 Max', 'generation' => 'M4', 'cores' => 16, 'threads' => 16, 'base_clock' => 3.20, 'benchmark_score' => 55000, 'release_year' => 2024, 'is_popular' => true],
            ['manufacturer' => 'apple_silicon', 'name' => 'Apple M4 Pro', 'generation' => 'M4', 'cores' => 14, 'threads' => 14, 'base_clock' => 3.20, 'benchmark_score' => 48000, 'release_year' => 2024, 'is_popular' => true],
            ['manufacturer' => 'apple_silicon', 'name' => 'Apple M4', 'generation' => 'M4', 'cores' => 10, 'threads' => 10, 'base_clock' => 3.20, 'benchmark_score' => 42000, 'release_year' => 2024, 'is_popular' => true],
            ['manufacturer' => 'apple_silicon', 'name' => 'Apple M3 Max', 'generation' => 'M3', 'cores' => 16, 'threads' => 16, 'base_clock' => 3.00, 'benchmark_score' => 50000, 'release_year' => 2023, 'is_popular' => true],
            ['manufacturer' => 'apple_silicon', 'name' => 'Apple M3 Pro', 'generation' => 'M3', 'cores' => 12, 'threads' => 12, 'base_clock' => 3.00, 'benchmark_score' => 43000, 'release_year' => 2023, 'is_popular' => true],
            ['manufacturer' => 'apple_silicon', 'name' => 'Apple M3', 'generation' => 'M3', 'cores' => 8, 'threads' => 8, 'base_clock' => 3.00, 'benchmark_score' => 35000, 'release_year' => 2023, 'is_popular' => true],
            ['manufacturer' => 'apple_silicon', 'name' => 'Apple M2', 'generation' => 'M2', 'cores' => 8, 'threads' => 8, 'base_clock' => 2.50, 'benchmark_score' => 28000, 'release_year' => 2022, 'is_popular' => true],
            ['manufacturer' => 'apple_silicon', 'name' => 'Apple M1', 'generation' => 'M1', 'cores' => 8, 'threads' => 8, 'base_clock' => 2.50, 'benchmark_score' => 23000, 'release_year' => 2020, 'is_popular' => false],
        ];

        foreach ($cpus as $cpu) {
            CPU::create($cpu);
        }
    }

    private function seedGPUs(): void
    {
        $gpus = [
            // NVIDIA RTX 40 Series (Popular)
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 4090', 'series' => 'RTX 40', 'vram_gb' => 24, 'benchmark_score' => 35000, 'release_year' => 2022, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 4080', 'series' => 'RTX 40', 'vram_gb' => 16, 'benchmark_score' => 28000, 'release_year' => 2022, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 4070 Ti', 'series' => 'RTX 40', 'vram_gb' => 12, 'benchmark_score' => 24000, 'release_year' => 2023, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 4070', 'series' => 'RTX 40', 'vram_gb' => 12, 'benchmark_score' => 21000, 'release_year' => 2023, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 4060 Ti', 'series' => 'RTX 40', 'vram_gb' => 8, 'benchmark_score' => 17000, 'release_year' => 2023, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 4060', 'series' => 'RTX 40', 'vram_gb' => 8, 'benchmark_score' => 15000, 'release_year' => 2023, 'is_popular' => true],

            // NVIDIA RTX 30 Series
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 3090', 'series' => 'RTX 30', 'vram_gb' => 24, 'benchmark_score' => 24500, 'release_year' => 2020, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 3080 Ti', 'series' => 'RTX 30', 'vram_gb' => 12, 'benchmark_score' => 23000, 'release_year' => 2021, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 3080', 'series' => 'RTX 30', 'vram_gb' => 10, 'benchmark_score' => 21500, 'release_year' => 2020, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 3070 Ti', 'series' => 'RTX 30', 'vram_gb' => 8, 'benchmark_score' => 18500, 'release_year' => 2021, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 3070', 'series' => 'RTX 30', 'vram_gb' => 8, 'benchmark_score' => 17000, 'release_year' => 2020, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 3060 Ti', 'series' => 'RTX 30', 'vram_gb' => 8, 'benchmark_score' => 14500, 'release_year' => 2020, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 3060', 'series' => 'RTX 30', 'vram_gb' => 12, 'benchmark_score' => 12500, 'release_year' => 2021, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 3050', 'series' => 'RTX 30', 'vram_gb' => 8, 'benchmark_score' => 9000, 'release_year' => 2022, 'is_popular' => true],

            // NVIDIA GTX 16 Series
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GTX 1660 Ti', 'series' => 'GTX 16', 'vram_gb' => 6, 'benchmark_score' => 8500, 'release_year' => 2019, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GTX 1660 Super', 'series' => 'GTX 16', 'vram_gb' => 6, 'benchmark_score' => 8000, 'release_year' => 2019, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GTX 1650', 'series' => 'GTX 16', 'vram_gb' => 4, 'benchmark_score' => 6000, 'release_year' => 2019, 'is_popular' => false],

            // NVIDIA GTX 10 Series
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GTX 1080 Ti', 'series' => 'GTX 10', 'vram_gb' => 11, 'benchmark_score' => 11500, 'release_year' => 2017, 'is_popular' => false],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GTX 1080', 'series' => 'GTX 10', 'vram_gb' => 8, 'benchmark_score' => 9500, 'release_year' => 2016, 'is_popular' => false],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GTX 1070 Ti', 'series' => 'GTX 10', 'vram_gb' => 8, 'benchmark_score' => 8500, 'release_year' => 2017, 'is_popular' => false],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GTX 1070', 'series' => 'GTX 10', 'vram_gb' => 8, 'benchmark_score' => 8000, 'release_year' => 2016, 'is_popular' => false],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GTX 1060 6GB', 'series' => 'GTX 10', 'vram_gb' => 6, 'benchmark_score' => 6000, 'release_year' => 2016, 'is_popular' => false],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GTX 1050 Ti', 'series' => 'GTX 10', 'vram_gb' => 4, 'benchmark_score' => 4500, 'release_year' => 2016, 'is_popular' => false],

            // AMD RX 7000 Series (Popular)
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 7900 XTX', 'series' => 'RX 7000', 'vram_gb' => 24, 'benchmark_score' => 27000, 'release_year' => 2022, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 7900 XT', 'series' => 'RX 7000', 'vram_gb' => 20, 'benchmark_score' => 24000, 'release_year' => 2022, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 7800 XT', 'series' => 'RX 7000', 'vram_gb' => 16, 'benchmark_score' => 20000, 'release_year' => 2023, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 7700 XT', 'series' => 'RX 7000', 'vram_gb' => 12, 'benchmark_score' => 17000, 'release_year' => 2023, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 7600', 'series' => 'RX 7000', 'vram_gb' => 8, 'benchmark_score' => 13000, 'release_year' => 2023, 'is_popular' => true],

            // AMD RX 6000 Series
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 6900 XT', 'series' => 'RX 6000', 'vram_gb' => 16, 'benchmark_score' => 21000, 'release_year' => 2020, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 6800 XT', 'series' => 'RX 6000', 'vram_gb' => 16, 'benchmark_score' => 19500, 'release_year' => 2020, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 6800', 'series' => 'RX 6000', 'vram_gb' => 16, 'benchmark_score' => 18000, 'release_year' => 2020, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 6700 XT', 'series' => 'RX 6000', 'vram_gb' => 12, 'benchmark_score' => 14500, 'release_year' => 2021, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 6600 XT', 'series' => 'RX 6000', 'vram_gb' => 8, 'benchmark_score' => 11000, 'release_year' => 2021, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 6600', 'series' => 'RX 6000', 'vram_gb' => 8, 'benchmark_score' => 9500, 'release_year' => 2021, 'is_popular' => true],

            // AMD RX 5000 Series
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 5700 XT', 'series' => 'RX 5000', 'vram_gb' => 8, 'benchmark_score' => 9500, 'release_year' => 2019, 'is_popular' => false],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 5700', 'series' => 'RX 5000', 'vram_gb' => 8, 'benchmark_score' => 8500, 'release_year' => 2019, 'is_popular' => false],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 5600 XT', 'series' => 'RX 5000', 'vram_gb' => 6, 'benchmark_score' => 7500, 'release_year' => 2020, 'is_popular' => false],

            // AMD RX 500 Series
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 580', 'series' => 'RX 500', 'vram_gb' => 8, 'benchmark_score' => 6000, 'release_year' => 2017, 'is_popular' => false],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 570', 'series' => 'RX 500', 'vram_gb' => 4, 'benchmark_score' => 5000, 'release_year' => 2017, 'is_popular' => false],

            // Intel Arc (Popular for budget)
            ['manufacturer' => 'intel', 'name' => 'Intel Arc A770', 'series' => 'Arc A7', 'vram_gb' => 16, 'benchmark_score' => 14000, 'release_year' => 2022, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Arc A750', 'series' => 'Arc A7', 'vram_gb' => 8, 'benchmark_score' => 12000, 'release_year' => 2022, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Arc A580', 'series' => 'Arc A5', 'vram_gb' => 8, 'benchmark_score' => 10000, 'release_year' => 2023, 'is_popular' => false],

            // Apple GPU (for M-series Macs)
            ['manufacturer' => 'apple', 'name' => 'Apple M4 Max GPU (40-core)', 'series' => 'M4', 'vram_gb' => 0, 'benchmark_score' => 22000, 'release_year' => 2024, 'is_popular' => true],
            ['manufacturer' => 'apple', 'name' => 'Apple M3 Max GPU (40-core)', 'series' => 'M3', 'vram_gb' => 0, 'benchmark_score' => 18000, 'release_year' => 2023, 'is_popular' => true],
            ['manufacturer' => 'apple', 'name' => 'Apple M2 GPU (10-core)', 'series' => 'M2', 'vram_gb' => 0, 'benchmark_score' => 11000, 'release_year' => 2022, 'is_popular' => true],
            ['manufacturer' => 'apple', 'name' => 'Apple M1 GPU (8-core)', 'series' => 'M1', 'vram_gb' => 0, 'benchmark_score' => 9000, 'release_year' => 2020, 'is_popular' => false],
        ];

        foreach ($gpus as $gpu) {
            GPU::create($gpu);
        }
    }
}
