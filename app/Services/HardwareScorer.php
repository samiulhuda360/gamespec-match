<?php

namespace App\Services;

use App\Models\UserHardware;
use App\Models\CPU;
use App\Models\GPU;

class HardwareScorer
{
    // ========================================
    // CPU SCORING DATABASE
    // ========================================
    
    private array $cpu_scores = [
        // Intel - Modern (12th-14th gen)
        'i3-14' => 560, 'i3-13' => 540, 'i3-12' => 520,
        'i5-14' => 700, 'i5-13' => 680, 'i5-12' => 650,
        'i7-14' => 850, 'i7-13' => 820, 'i7-12' => 780,
        'i9-14' => 980, 'i9-13' => 950, 'i9-12' => 900,
        
        // Intel - 10th-11th gen
        'i3-11' => 450, 'i3-10' => 400,
        'i5-11' => 600, 'i5-10' => 550,
        'i7-11' => 740, 'i7-10' => 700,
        'i9-11' => 850, 'i9-10' => 800, 'i9-9' => 750,
        
        // Intel - 6th-9th gen
        'i3-9' => 380, 'i3-8' => 350, 'i3-7' => 300, 'i3-6' => 250,
        'i5-9' => 530, 'i5-8' => 500, 'i5-7' => 450, 'i5-6' => 400,
        'i7-9' => 650, 'i7-8' => 600, 'i7-7' => 550, 'i7-6' => 500,
        
        // Intel - Older
        'i3-4' => 200, 'i3-3' => 180, 'i3-2' => 150,
        'i5-4' => 300, 'i5-3' => 250, 'i5-2' => 200,
        'i7-4' => 400, 'i7-3' => 350, 'i7-2' => 300,
        
        // Intel - Legacy
        'core 2 quad' => 180, 'core 2 duo' => 100, 'core duo' => 80,
        'pentium 4' => 50, 'pentium' => 40, 'celeron' => 60,
        
        // AMD - Ryzen 7000/5000
        'ryzen 3 7' => 580, 'ryzen 3 5' => 500,
        'ryzen 5 7' => 760, 'ryzen 5 5' => 700,
        'ryzen 7 7' => 880, 'ryzen 7 5' => 850,
        'ryzen 9 7' => 950, 'ryzen 9 5' => 900,
        
        // AMD - Ryzen 3000/2000/1000
        'ryzen 3 3' => 420, 'ryzen 3 2' => 380, 'ryzen 3 1' => 350,
        'ryzen 5 3' => 580, 'ryzen 5 2' => 500, 'ryzen 5 1' => 450,
        'ryzen 7 3' => 750, 'ryzen 7 2' => 650, 'ryzen 7 1' => 600,
        'ryzen 9 3' => 800,
        
        // AMD - Legacy
        'fx-8' => 250, 'fx-6' => 200, 'fx-4' => 150,
        'phenom' => 150, 'athlon' => 100,
    ];

    // ========================================
    // GPU SCORING DATABASE
    // ========================================
    
    private array $gpu_scores = [
        // NVIDIA RTX 40 series
        'rtx 4090' => 1000, 'rtx 4080' => 980, 'rtx 4070 ti' => 920,
        'rtx 4070' => 900, 'rtx 4060 ti' => 820, 'rtx 4060' => 800,
        
        // NVIDIA RTX 30 series
        'rtx 3090' => 970, 'rtx 3080 ti' => 950, 'rtx 3080' => 950,
        'rtx 3070 ti' => 870, 'rtx 3070' => 850, 
        'rtx 3060 ti' => 770, 'rtx 3060' => 750, 'rtx 3050' => 650,
        
        // NVIDIA RTX 20 series
        'rtx 2080 ti' => 850, 'rtx 2080' => 800, 
        'rtx 2070' => 700, 'rtx 2060' => 600,
        
        // NVIDIA GTX 16 series
        'gtx 1660 ti' => 550, 'gtx 1660 super' => 540, 'gtx 1660' => 520,
        'gtx 1650 super' => 480, 'gtx 1650' => 450,
        
        // NVIDIA GTX 10 series
        'gtx 1080 ti' => 650, 'gtx 1080' => 600,
        'gtx 1070 ti' => 520, 'gtx 1070' => 500,
        'gtx 1060' => 400, 'gtx 1050 ti' => 240, 'gtx 1050' => 220,
        
        // NVIDIA GTX 900 series
        'gtx 980 ti' => 420, 'gtx 980' => 380,
        'gtx 970' => 320, 'gtx 960' => 250,
        
        // NVIDIA - Old
        'geforce 9' => 80, 'geforce 8' => 50,
        
        // AMD RX 7000 series
        'rx 7900' => 920, 'rx 7800' => 880, 'rx 7700' => 800, 'rx 7600' => 720,
        
        // AMD RX 6000 series
        'rx 6950' => 900, 'rx 6900' => 880, 'rx 6800' => 850,
        'rx 6700' => 750, 'rx 6600' => 680, 'rx 6500' => 580,
        
        // AMD RX 5000 series
        'rx 5700' => 650, 'rx 5600' => 520, 'rx 5500' => 450,
        
        // AMD RX 500 series
        'rx 590' => 440, 'rx 580' => 420, 'rx 570' => 260,
        'rx 560' => 190, 'rx 550' => 160,
        
        // AMD RX 400 series
        'rx 490' => 350, 'rx 480' => 300, 'rx 470' => 250, 'rx 460' => 180,
        
        // AMD R9/R7 series
        'r9 fury' => 380, 'r9 390' => 360, 'r9 290' => 350, 'r9 280' => 300,
        'r7 370' => 200, 'r7 360' => 150,
        
        // AMD HD series
        'hd 7970' => 300, 'hd 7870' => 280, 'hd 7850' => 240,
        'hd 6970' => 220, 'hd 6950' => 200,
        'hd 5870' => 150, 'hd 4870' => 120,
        'hd 3600' => 50, 'hd 2600' => 40, 'hd 2400' => 30,
        'radeon hd' => 100,
        
        // Intel Arc
        'arc a770' => 730, 'arc a750' => 650, 'arc a380' => 480,
        
        // Integrated Graphics - Intel
        'iris xe' => 50, 
        'uhd 770' => 45, 'uhd 730' => 40, 'uhd 630' => 35, 'uhd 620' => 30,
        'hd 5000' => 80, 'hd 4000' => 50, 'hd 3000' => 30, 'hd 2500' => 25,
        
        // Integrated Graphics - AMD
        'vega 11' => 55, 'vega 8' => 45, 'vega 7' => 40, 'vega 6' => 35,
        'radeon vega' => 45, // Generic Vega
        
        // Fallback for integrated/onboard
        'integrated graphics' => 30,
        'integrated' => 30,
        'no dedicated gpu' => 20,
        'no gpu' => 20,
        'onboard' => 20,
        
        // Generic old tech
        'gcn' => 150, 'kepler' => 180,
    ];

    // ========================================
    // CPU PARSING
    // ========================================
    
    public function parseCpu(string $cpu): float
    {
        $cpu = strtolower(trim($cpu));

        // Try pattern matching (check longest patterns first)
        foreach ($this->cpu_scores as $key => $score) {
            if (str_contains($cpu, $key)) {
                // Extract specific model number for precision
                preg_match('/(\d{4})/', $cpu, $match);
                if ($match) {
                    return $score + (intval($match[1]) % 1000) / 10;
                }
                return $score;
            }
        }

        // Fallback: GHz estimation
        preg_match('/(\d+\.?\d*)\s*ghz/i', $cpu, $match);
        if ($match) {
            return max(50, min(400, floatval($match[1]) * 80));
        }

        // Fallback: thread count
        preg_match('/(\d+)\s*hardware cpu threads/i', $cpu, $match);
        if ($match) {
            return max(100, intval($match[1]) * 60);
        }

        return 100; // Default unknown CPU
    }

    // ========================================
    // RAM PARSING
    // ========================================
    
    public function parseRam(string $ram): float
    {
        $ram = strtolower(trim($ram));

        // Match GB
        preg_match('/(\d+)\s*gb/i', $ram, $match);
        if ($match) {
            return floatval($match[1]);
        }

        // Match MB
        preg_match('/(\d+)\s*mb/i', $ram, $match);
        if ($match) {
            return floatval($match[1]) / 1024;
        }

        return 2; // Default 2GB
    }

    // ========================================
    // GPU PARSING
    // ========================================
    
    public function parseGpu(string $gpu): float
    {
        $gpu = strtolower(trim($gpu));

        // Integrated graphics first
        $integratedKeywords = [
            'integrated graphics',
            'no dedicated gpu',
            'no gpu',
            'onboard graphics',
            'integrated',
        ];
        
        foreach ($integratedKeywords as $keyword) {
            if (str_contains($gpu, $keyword)) {
                return 20;
            }
        }

        // Sort by length (longest first) to match specific models before generic ones
        $sortedScores = $this->gpu_scores;
        uksort($sortedScores, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        // Try pattern matching (longest first for specificity)
        $maxScore = 0;
        foreach ($sortedScores as $key => $score) {
            if (str_contains($gpu, $key)) {
                $maxScore = max($maxScore, $score);
            }
        }
        
        if ($maxScore > 0) {
            return $maxScore;
        }

        // Estimate from VRAM
        preg_match('/(\d+)\s*gb/i', $gpu, $match);
        if ($match) {
            $vram = intval($match[1]);
            // VRAM to score mapping
            if ($vram >= 12) return 750; // High-end
            if ($vram >= 8) return 600;  // Upper mid-range
            if ($vram >= 6) return 450;  // Mid-range
            if ($vram >= 4) return 300;  // Entry level
            if ($vram >= 2) return 150;  // Old/low-end
            return 100;
        }

        // Fallback: DirectX level
        if (str_contains($gpu, 'directx 12') || str_contains($gpu, 'dx12')) return 300;
        if (str_contains($gpu, 'directx 11') || str_contains($gpu, 'dx11')) return 200;
        if (str_contains($gpu, 'directx 10') || str_contains($gpu, 'dx10')) return 150;

        return 100; // Default unknown GPU
    }

    // ========================================
    // OVERALL SCORE CALCULATION
    // ========================================
    
    /**
     * CRITICAL: This MUST match the Python script's weights
     * Python uses: GPU 50%, CPU 35%, RAM 15%, Storage 0%
     */
    public function calculateOverallScore(float $cpu, float $ram, float $gpu): float
    {
        // Same formula as the requirement side (ScoreScale), so the two compare directly.
        return \App\Services\Scoring\ScoreScale::overall($cpu, $ram, $gpu);
    }

    // ========================================
    // MAIN SCORING METHOD
    // ========================================

    public function scoreHardware(UserHardware $hardware): void
    {
        // Priority 1: the catalogue CPU/GPU, when the profile has one
        $catalog = app(\App\Services\Scoring\RequirementScorer::class);
        $cpu = $hardware->cpu_id ? CPU::find($hardware->cpu_id) : null;
        $hardware->cpu_score = $cpu && $cpu->benchmark_score
            ? \App\Services\Scoring\ScoreScale::cpu($cpu->benchmark_score)
            : $catalog->component('cpu', (string) ($hardware->cpu ?? $hardware->cpu_model ?? ''))['score'];

        $gpu = $hardware->gpu_id ? GPU::find($hardware->gpu_id) : null;
        $hardware->gpu_score = $gpu && $gpu->benchmark_score
            ? \App\Services\Scoring\ScoreScale::gpu($gpu->benchmark_score)
            : $catalog->component('gpu', (string) ($hardware->gpu ?? $hardware->gpu_model ?? ''))['score'];

        // Priority: Use ram_gb if available, fallback to ram text field
        if ($hardware->ram_gb) {
            $hardware->ram_score = floatval($hardware->ram_gb);
        } else {
            $hardware->ram_score = $this->parseRam($hardware->ram ?? '');
        }

        // Calculate overall score WITHOUT storage (matching Python: 50% GPU, 35% CPU, 15% RAM)
        $hardware->overall_score = $this->calculateOverallScore(
            $hardware->cpu_score,
            $hardware->ram_score,
            $hardware->gpu_score
        );

        $hardware->save();
    }

    // ========================================
    // HELPER METHODS - Get Hardware Tier
    // ========================================
    
    /**
     * Get hardware performance tier
     */
    public function getHardwareTier(float $overallScore): string
    {
        if ($overallScore >= 700) return 'Ultra High-End';
        if ($overallScore >= 450) return 'High-End';
        if ($overallScore >= 250) return 'Mid-Range';
        if ($overallScore >= 120) return 'Entry Level';
        return 'Low-End / Integrated';
    }

    /**
     * Get component tier
     */
    public function getComponentTier(string $component, float $score): string
    {
        $componentLower = strtolower($component);
        
        switch ($componentLower) {
            case 'gpu':
                if ($score >= 1000) return 'Flagship';
                if ($score >= 700) return 'High-End';
                if ($score >= 450) return 'Upper Mid-Range';
                if ($score >= 250) return 'Mid-Range';
                if ($score >= 120) return 'Entry Level';
                if ($score >= 40) return 'Integrated Graphics';
                return 'Very Low / No GPU';
                
            case 'cpu':
                if ($score >= 450) return 'Flagship';
                if ($score >= 300) return 'High-End';
                if ($score >= 200) return 'Upper Mid-Range';
                if ($score >= 120) return 'Mid-Range';
                if ($score >= 60) return 'Entry Level';
                return 'Very Low';
                
            case 'ram':
                if ($score >= 32) return 'Enthusiast (32GB+)';
                if ($score >= 16) return 'Recommended (16GB)';
                if ($score >= 8) return 'Acceptable (8GB)';
                if ($score >= 4) return 'Minimum (4GB)';
                return 'Insufficient (<4GB)';
                
            default:
                return 'Unknown';
        }
    }

    // ========================================
    // VALIDATION HELPERS
    // ========================================
    
    /**
     * Check if GPU is integrated
     */
    public function isIntegratedGpu(string $gpu): bool
    {
        $gpu = strtolower(trim($gpu));
        
        $integratedIndicators = [
            'integrated', 'uhd', 'hd graphics', 'iris', 'vega',
            'no dedicated', 'onboard', 'no gpu'
        ];
        
        foreach ($integratedIndicators as $indicator) {
            if (str_contains($gpu, $indicator)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get hardware recommendations based on score
     */
    public function getUpgradeRecommendations(UserHardware $hardware): array
    {
        $recommendations = [];
        
        // GPU recommendations
        if ($hardware->gpu_score < 300) {
            $recommendations[] = [
                'component' => 'GPU',
                'priority' => 'high',
                'message' => 'Your GPU is the main bottleneck. Consider upgrading to at least a GTX 1060 or RX 580 for modern gaming.',
                'suggestions' => [
                    'Budget: GTX 1650 / RX 5500',
                    'Mid-Range: RTX 3060 / RX 6600',
                    'High-End: RTX 4070 / RX 7800'
                ]
            ];
        }
        
        // CPU recommendations
        if ($hardware->cpu_score < 300) {
            $recommendations[] = [
                'component' => 'CPU',
                'priority' => $hardware->gpu_score < 300 ? 'medium' : 'high',
                'message' => 'Your CPU may limit performance in CPU-intensive games.',
                'suggestions' => [
                    'Budget: Ryzen 5 3600 / i5-10400',
                    'Mid-Range: Ryzen 5 5600X / i5-12400',
                    'High-End: Ryzen 7 5800X / i7-12700'
                ]
            ];
        }
        
        // RAM recommendations
        if ($hardware->ram_score < 16) {
            $recommendations[] = [
                'component' => 'RAM',
                'priority' => 'medium',
                'message' => 'Consider upgrading to 16GB RAM for better multitasking and modern games.',
                'suggestions' => [
                    'Target: 16GB DDR4 (2x8GB dual channel)',
                    'Future-proof: 32GB DDR4'
                ]
            ];
        }
        
        return $recommendations;
    }
}