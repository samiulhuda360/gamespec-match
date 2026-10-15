<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CPU;
use App\Models\GPU;

class ExpandedHardwareSeeder extends Seeder
{
    /**
     * Seed expanded hardware database with low-end, mid-range, and high-end options
     * AMD and Intel CPUs only, NVIDIA and AMD GPUs only
     */
    public function run(): void
    {
        // ===== INTEGRATED & NO GPU OPTIONS =====
        $this->seedIntegratedGPUs();

        // ===== LOW-END GPUs =====
        $this->seedLowEndGPUs();

        // ===== MID-RANGE GPUs (Expand existing) =====
        $this->seedMidRangeGPUs();

        // ===== EXPAND CPU DATABASE =====
        $this->seedExpandedCPUs();

        echo "\n✅ Expanded Hardware Database Seeded Successfully!\n";
        echo "   - Integrated GPUs: Added\n";
        echo "   - Low-end GPUs: Added\n";
        echo "   - Mid-range GPUs: Expanded\n";
        echo "   - CPUs: Expanded (Intel & AMD only)\n\n";
    }

    private function seedIntegratedGPUs()
    {
        $integratedGPUs = [
            // Intel Integrated Graphics
            ['manufacturer' => 'intel', 'name' => 'Intel UHD Graphics 630', 'vram_gb' => 0, 'benchmark_score' => 1200, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel UHD Graphics 730', 'vram_gb' => 0, 'benchmark_score' => 1400, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Iris Xe Graphics', 'vram_gb' => 0, 'benchmark_score' => 2500, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel HD Graphics 530', 'vram_gb' => 0, 'benchmark_score' => 900, 'is_popular' => false],
            ['manufacturer' => 'intel', 'name' => 'Intel HD Graphics 620', 'vram_gb' => 0, 'benchmark_score' => 1000, 'is_popular' => false],

            // AMD Integrated Graphics
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon Vega 8', 'vram_gb' => 0, 'benchmark_score' => 1800, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon Vega 11', 'vram_gb' => 0, 'benchmark_score' => 2200, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon Graphics (RDNA 2)', 'vram_gb' => 0, 'benchmark_score' => 3000, 'is_popular' => true],

            // Special "No GPU" option for very old PCs (listed under Intel)
            ['manufacturer' => 'intel', 'name' => 'No Dedicated GPU (CPU Only)', 'vram_gb' => 0, 'benchmark_score' => 500, 'is_popular' => false],
        ];

        foreach ($integratedGPUs as $gpu) {
            GPU::updateOrCreate(
                ['name' => $gpu['name']],
                $gpu
            );
        }
    }

    private function seedLowEndGPUs()
    {
        $lowEndGPUs = [
            // NVIDIA Low-End (GTX 1000 series and older)
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GT 1030', 'vram_gb' => 2, 'benchmark_score' => 3500, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GTX 1050', 'vram_gb' => 2, 'benchmark_score' => 4500, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GTX 1050 Ti', 'vram_gb' => 4, 'benchmark_score' => 5200, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GTX 1650', 'vram_gb' => 4, 'benchmark_score' => 6000, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GTX 1650 Super', 'vram_gb' => 4, 'benchmark_score' => 7500, 'is_popular' => true],

            // AMD Low-End (RX 500 series and older)
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 550', 'vram_gb' => 2, 'benchmark_score' => 3000, 'is_popular' => false],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 560', 'vram_gb' => 4, 'benchmark_score' => 3800, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 570', 'vram_gb' => 4, 'benchmark_score' => 5800, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 580', 'vram_gb' => 8, 'benchmark_score' => 6500, 'is_popular' => true],
        ];

        foreach ($lowEndGPUs as $gpu) {
            GPU::updateOrCreate(
                ['name' => $gpu['name']],
                $gpu
            );
        }
    }

    private function seedMidRangeGPUs()
    {
        $midRangeGPUs = [
            // NVIDIA Mid-Range (GTX 1600 series, RTX 2000, 3000 series)
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GTX 1660', 'vram_gb' => 6, 'benchmark_score' => 8000, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GTX 1660 Super', 'vram_gb' => 6, 'benchmark_score' => 8800, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce GTX 1660 Ti', 'vram_gb' => 6, 'benchmark_score' => 9500, 'is_popular' => true],

            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 2060', 'vram_gb' => 6, 'benchmark_score' => 11500, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 2060 Super', 'vram_gb' => 8, 'benchmark_score' => 13000, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 2070', 'vram_gb' => 8, 'benchmark_score' => 14500, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 2070 Super', 'vram_gb' => 8, 'benchmark_score' => 16000, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 2080', 'vram_gb' => 8, 'benchmark_score' => 17500, 'is_popular' => false],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 2080 Super', 'vram_gb' => 8, 'benchmark_score' => 19000, 'is_popular' => false],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 2080 Ti', 'vram_gb' => 11, 'benchmark_score' => 22000, 'is_popular' => false],

            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 3050', 'vram_gb' => 8, 'benchmark_score' => 10000, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 3060', 'vram_gb' => 12, 'benchmark_score' => 13500, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 3060 Ti', 'vram_gb' => 8, 'benchmark_score' => 17000, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 3070', 'vram_gb' => 8, 'benchmark_score' => 19500, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 3070 Ti', 'vram_gb' => 8, 'benchmark_score' => 21000, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 3080', 'vram_gb' => 10, 'benchmark_score' => 26000, 'is_popular' => true],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 3080 Ti', 'vram_gb' => 12, 'benchmark_score' => 28500, 'is_popular' => false],
            ['manufacturer' => 'nvidia', 'name' => 'NVIDIA GeForce RTX 3090', 'vram_gb' => 24, 'benchmark_score' => 31000, 'is_popular' => false],

            // AMD Mid-Range (RX 5000, 6000 series)
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 5500 XT', 'vram_gb' => 8, 'benchmark_score' => 7000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 5600 XT', 'vram_gb' => 6, 'benchmark_score' => 10500, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 5700', 'vram_gb' => 8, 'benchmark_score' => 13000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 5700 XT', 'vram_gb' => 8, 'benchmark_score' => 15000, 'is_popular' => true],

            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 6600', 'vram_gb' => 8, 'benchmark_score' => 11000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 6600 XT', 'vram_gb' => 8, 'benchmark_score' => 13500, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 6650 XT', 'vram_gb' => 8, 'benchmark_score' => 14000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 6700', 'vram_gb' => 10, 'benchmark_score' => 15500, 'is_popular' => false],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 6700 XT', 'vram_gb' => 12, 'benchmark_score' => 17500, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 6750 XT', 'vram_gb' => 12, 'benchmark_score' => 18500, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 6800', 'vram_gb' => 16, 'benchmark_score' => 22000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 6800 XT', 'vram_gb' => 16, 'benchmark_score' => 25000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Radeon RX 6900 XT', 'vram_gb' => 16, 'benchmark_score' => 28000, 'is_popular' => false],
        ];

        foreach ($midRangeGPUs as $gpu) {
            GPU::updateOrCreate(
                ['name' => $gpu['name']],
                $gpu
            );
        }
    }

    private function seedExpandedCPUs()
    {
        $expandedCPUs = [
            // ===== INTEL LOW-END =====
            ['manufacturer' => 'intel', 'name' => 'Intel Core i3-10100', 'cores' => 4, 'threads' => 8, 'benchmark_score' => 9000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i3-12100', 'cores' => 4, 'threads' => 8, 'benchmark_score' => 13000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i3-13100', 'cores' => 4, 'threads' => 8, 'benchmark_score' => 14000, 'is_popular' => true],

            // ===== INTEL MID-RANGE (i5) =====
            ['manufacturer' => 'intel', 'name' => 'Intel Core i5-9400F', 'cores' => 6, 'threads' => 6, 'benchmark_score' => 11000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i5-10400', 'cores' => 6, 'threads' => 12, 'benchmark_score' => 13500, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i5-10600K', 'cores' => 6, 'threads' => 12, 'benchmark_score' => 15000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i5-11400', 'cores' => 6, 'threads' => 12, 'benchmark_score' => 15500, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i5-11600K', 'cores' => 6, 'threads' => 12, 'benchmark_score' => 17000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i5-12400', 'cores' => 6, 'threads' => 12, 'benchmark_score' => 18000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i5-12600K', 'cores' => 10, 'threads' => 16, 'benchmark_score' => 23000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i5-13400', 'cores' => 10, 'threads' => 16, 'benchmark_score' => 22000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i5-13600K', 'cores' => 14, 'threads' => 20, 'benchmark_score' => 30000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i5-14600K', 'cores' => 14, 'threads' => 20, 'benchmark_score' => 32000, 'is_popular' => true],

            // ===== INTEL HIGH-END (i7) =====
            ['manufacturer' => 'intel', 'name' => 'Intel Core i7-10700', 'cores' => 8, 'threads' => 16, 'benchmark_score' => 17000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i7-10700K', 'cores' => 8, 'threads' => 16, 'benchmark_score' => 19000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i7-11700', 'cores' => 8, 'threads' => 16, 'benchmark_score' => 19500, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i7-11700K', 'cores' => 8, 'threads' => 16, 'benchmark_score' => 21500, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i7-12700', 'cores' => 12, 'threads' => 20, 'benchmark_score' => 28000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i7-12700K', 'cores' => 12, 'threads' => 20, 'benchmark_score' => 31000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i7-13700', 'cores' => 16, 'threads' => 24, 'benchmark_score' => 34000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i7-13700K', 'cores' => 16, 'threads' => 24, 'benchmark_score' => 38000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i7-14700K', 'cores' => 20, 'threads' => 28, 'benchmark_score' => 42000, 'is_popular' => true],

            // ===== INTEL ENTHUSIAST (i9) =====
            ['manufacturer' => 'intel', 'name' => 'Intel Core i9-10900K', 'cores' => 10, 'threads' => 20, 'benchmark_score' => 23000, 'is_popular' => false],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i9-11900K', 'cores' => 8, 'threads' => 16, 'benchmark_score' => 23500, 'is_popular' => false],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i9-12900K', 'cores' => 16, 'threads' => 24, 'benchmark_score' => 40000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i9-13900K', 'cores' => 24, 'threads' => 32, 'benchmark_score' => 50000, 'is_popular' => true],
            ['manufacturer' => 'intel', 'name' => 'Intel Core i9-14900K', 'cores' => 24, 'threads' => 32, 'benchmark_score' => 55000, 'is_popular' => true],

            // ===== AMD LOW-END (Ryzen 3) =====
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 3 3100', 'cores' => 4, 'threads' => 8, 'benchmark_score' => 10500, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 3 3300X', 'cores' => 4, 'threads' => 8, 'benchmark_score' => 12000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 3 4100', 'cores' => 4, 'threads' => 8, 'benchmark_score' => 11500, 'is_popular' => true],

            // ===== AMD MID-RANGE (Ryzen 5) =====
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 5 3600', 'cores' => 6, 'threads' => 12, 'benchmark_score' => 17500, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 5 3600X', 'cores' => 6, 'threads' => 12, 'benchmark_score' => 18500, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 5 5600', 'cores' => 6, 'threads' => 12, 'benchmark_score' => 21000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 5 5600X', 'cores' => 6, 'threads' => 12, 'benchmark_score' => 22500, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 5 5600G', 'cores' => 6, 'threads' => 12, 'benchmark_score' => 19500, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 5 7600', 'cores' => 6, 'threads' => 12, 'benchmark_score' => 25000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 5 7600X', 'cores' => 6, 'threads' => 12, 'benchmark_score' => 27000, 'is_popular' => true],

            // ===== AMD HIGH-END (Ryzen 7) =====
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 7 3700X', 'cores' => 8, 'threads' => 16, 'benchmark_score' => 23000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 7 3800X', 'cores' => 8, 'threads' => 16, 'benchmark_score' => 24500, 'is_popular' => false],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 7 5700X', 'cores' => 8, 'threads' => 16, 'benchmark_score' => 27000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 7 5800X', 'cores' => 8, 'threads' => 16, 'benchmark_score' => 29000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 7 5800X3D', 'cores' => 8, 'threads' => 16, 'benchmark_score' => 30000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 7 7700', 'cores' => 8, 'threads' => 16, 'benchmark_score' => 32000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 7 7700X', 'cores' => 8, 'threads' => 16, 'benchmark_score' => 34500, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 7 7800X3D', 'cores' => 8, 'threads' => 16, 'benchmark_score' => 35000, 'is_popular' => true],

            // ===== AMD ENTHUSIAST (Ryzen 9) =====
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 9 3900X', 'cores' => 12, 'threads' => 24, 'benchmark_score' => 34000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 9 3950X', 'cores' => 16, 'threads' => 32, 'benchmark_score' => 41000, 'is_popular' => false],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 9 5900X', 'cores' => 12, 'threads' => 24, 'benchmark_score' => 39000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 9 5950X', 'cores' => 16, 'threads' => 32, 'benchmark_score' => 47000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 9 7900', 'cores' => 12, 'threads' => 24, 'benchmark_score' => 43000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 9 7900X', 'cores' => 12, 'threads' => 24, 'benchmark_score' => 46000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 9 7950X', 'cores' => 16, 'threads' => 32, 'benchmark_score' => 58000, 'is_popular' => true],
            ['manufacturer' => 'amd', 'name' => 'AMD Ryzen 9 7950X3D', 'cores' => 16, 'threads' => 32, 'benchmark_score' => 62000, 'is_popular' => true],
        ];

        foreach ($expandedCPUs as $cpu) {
            CPU::updateOrCreate(
                ['name' => $cpu['name']],
                $cpu
            );
        }
    }
}
