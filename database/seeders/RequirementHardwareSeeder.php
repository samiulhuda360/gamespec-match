<?php

namespace Database\Seeders;

use App\Models\CPU;
use App\Models\GPU;
use Illuminate\Database\Seeder;

/**
 * Parts that Steam requirement pages name often (older desktop parts for "minimum", current
 * cards for "recommended", laptop parts and integrated graphics for the "describe your PC"
 * box), chosen from the requirement text of the sample catalogue, plus scores for low-end GPUs
 * that keep them in line with the mid-range.
 *
 * Scores are approximate and on the catalogue's existing scales: CPUs about a PassMark CPU
 * mark, GPUs about 1.55x the 3DMark Time Spy graphics score. Expect +-15%; the tool compares
 * tiers of hardware, not single frames per second.
 */
class RequirementHardwareSeeder extends Seeder
{
    /** name => [manufacturer, score, release year] */
    private const CPUS = [
        'Intel Core 2 Duo E6600' => ['intel', 1000, 2006], 'Intel Core 2 Quad Q6600' => ['intel', 2000, 2007],
        'Intel Core i5 750' => ['intel', 2700, 2009], 'Intel Core i3-2100' => ['intel', 2000, 2011],
        'Intel Core i5-2400' => ['intel', 3900, 2011], 'Intel Core i5-2500K' => ['intel', 4200, 2011],
        'Intel Core i7-2600' => ['intel', 5300, 2011], 'Intel Core i7-2600K' => ['intel', 5500, 2011],
        'Intel Core i5-3470' => ['intel', 4600, 2012], 'Intel Core i5-3570K' => ['intel', 4800, 2012],
        'Intel Core i7-3770' => ['intel', 6400, 2012], 'Intel Core i7-3770K' => ['intel', 6500, 2012],
        'Intel Core i3-4130' => ['intel', 3200, 2013], 'Intel Core i3-4170' => ['intel', 3400, 2015],
        'Intel Core i3-4330' => ['intel', 3400, 2013], 'Intel Core i5-4430' => ['intel', 4800, 2013],
        'Intel Core i5-4460' => ['intel', 5000, 2014], 'Intel Core i5-4590' => ['intel', 5200, 2014],
        'Intel Core i5-4670K' => ['intel', 5500, 2013], 'Intel Core i7-4770' => ['intel', 7000, 2013],
        'Intel Core i7-4770K' => ['intel', 7200, 2013], 'Intel Core i7-4790' => ['intel', 7200, 2014],
        'Intel Core i7-4790K' => ['intel', 8000, 2014], 'Intel Core i3-6100' => ['intel', 4000, 2015],
        'Intel Core i5-6400' => ['intel', 5000, 2015], 'Intel Core i5-6500' => ['intel', 5400, 2015],
        'Intel Core i5-6600' => ['intel', 5900, 2015], 'Intel Core i5-6600K' => ['intel', 6300, 2015],
        'Intel Core i7-6700' => ['intel', 8100, 2015], 'Intel Core i7-6700K' => ['intel', 8900, 2015],
        'Intel Core i7-6800K' => ['intel', 11500, 2016], 'Intel Core i5-7400' => ['intel', 5800, 2017],
        'Intel Core i5-7500' => ['intel', 6200, 2017], 'Intel Core i5-7600K' => ['intel', 7000, 2017],
        'Intel Core i7-7700' => ['intel', 8600, 2017], 'Intel Core i7-7700K' => ['intel', 9700, 2017],
        'Intel Core i3-8100' => ['intel', 6000, 2017], 'Intel Core i5-8400' => ['intel', 9200, 2017],
        'Intel Core i5-8600' => ['intel', 9800, 2018], 'Intel Core i5-8600K' => ['intel', 10300, 2017],
        'Intel Core i7-8700' => ['intel', 13000, 2017], 'Intel Core i7-8700K' => ['intel', 13800, 2017],
        'Intel Core i5-9600' => ['intel', 10800, 2019], 'Intel Core i5-9600K' => ['intel', 11500, 2018],
        'Intel Core i7-9700' => ['intel', 13700, 2019], 'Intel Core i7-9700K' => ['intel', 14500, 2018],
        'Intel Core i9-9900K' => ['intel', 17700, 2018], 'Intel Core i5-10500' => ['intel', 12500, 2020],
        'Intel Core i5-1135G7' => ['intel', 10000, 2020], 'Intel Core i7-1165G7' => ['intel', 10500, 2020],
        'Intel Core i7-10750H' => ['intel', 12500, 2020], 'Intel Core i5-12500H' => ['intel', 21000, 2022],
        'Intel Core i7-12700H' => ['intel', 26500, 2022], 'Intel Core i7-13700H' => ['intel', 28000, 2023],
        'AMD Phenom X3 8750' => ['amd', 1200, 2008], 'AMD Phenom II X4 965' => ['amd', 2300, 2009],
        'AMD FX-4300' => ['amd', 3000, 2012], 'AMD FX-4350' => ['amd', 3200, 2013], 'AMD FX-6300' => ['amd', 4300, 2012],
        'AMD FX-8300' => ['amd', 5500, 2014], 'AMD FX-8320' => ['amd', 5700, 2012], 'AMD FX-8350' => ['amd', 6000, 2012],
        'AMD FX-9590' => ['amd', 6900, 2013], 'AMD Ryzen 3 1200' => ['amd', 6300, 2017], 'AMD Ryzen 3 1300X' => ['amd', 7000, 2017],
        'AMD Ryzen 3 2200G' => ['amd', 6800, 2018], 'AMD Ryzen 5 1400' => ['amd', 7700, 2017], 'AMD Ryzen 5 1500X' => ['amd', 8800, 2017],
        'AMD Ryzen 5 1600' => ['amd', 12300, 2017], 'AMD Ryzen 5 1600X' => ['amd', 13000, 2017], 'AMD Ryzen 5 2600' => ['amd', 13300, 2018],
        'AMD Ryzen 5 2600X' => ['amd', 14200, 2018], 'AMD Ryzen 5 3400G' => ['amd', 9300, 2019], 'AMD Ryzen 7 1700' => ['amd', 14600, 2017],
        'AMD Ryzen 7 1800X' => ['amd', 16000, 2017], 'AMD Ryzen 7 2700X' => ['amd', 17600, 2018], 'AMD Ryzen 5 5500' => ['amd', 19500, 2022],
        'AMD Ryzen 7 5700G' => ['amd', 24500, 2021], 'AMD Ryzen 5 5600H' => ['amd', 17000, 2021], 'AMD Ryzen 7 5700U' => ['amd', 15500, 2021],
        'AMD Ryzen 7 5800H' => ['amd', 21000, 2021], 'AMD Ryzen 7 6800H' => ['amd', 23500, 2022], 'AMD Ryzen 7 7840HS' => ['amd', 29000, 2023],
        'AMD Steam Deck APU' => ['amd', 6500, 2022],
    ];

    /** name => [manufacturer, score, vram GB, release year] */
    private const GPUS = [
        // Integrated graphics, well below entry-level cards such as the GTX 750 Ti
        'Intel HD Graphics 4000' => ['intel', 450, 0, 2012], 'Intel HD Graphics 4600' => ['intel', 650, 0, 2013],
        'Intel HD Graphics 520' => ['intel', 550, 0, 2015], 'Intel HD Graphics 530' => ['intel', 650, 0, 2015],
        'Intel HD Graphics 620' => ['intel', 600, 0, 2016], 'Intel UHD Graphics 630' => ['intel', 750, 0, 2017],
        'Intel UHD Graphics 730' => ['intel', 900, 0, 2021], 'Intel Iris Xe Graphics' => ['intel', 2250, 0, 2020],
        'AMD Radeon Vega 8' => ['amd', 1600, 0, 2018], 'AMD Radeon Vega 11' => ['amd', 1900, 0, 2018],
        'AMD Radeon 680M' => ['amd', 3700, 0, 2022], 'AMD Radeon 780M' => ['amd', 4500, 0, 2023],
        'AMD Steam Deck GPU' => ['amd', 2600, 0, 2022],
        // Older NVIDIA desktop cards
        'NVIDIA GeForce 8600 GT' => ['nvidia', 300, 0, 2007], 'NVIDIA GeForce 9600 GT' => ['nvidia', 450, 1, 2008],
        'NVIDIA GeForce 9800 GT' => ['nvidia', 600, 1, 2008], 'NVIDIA GeForce GT 730' => ['nvidia', 900, 2, 2014],
        'NVIDIA GeForce GTX 460' => ['nvidia', 1850, 1, 2010], 'NVIDIA GeForce GTX 550 Ti' => ['nvidia', 1300, 1, 2011],
        'NVIDIA GeForce GTX 560' => ['nvidia', 2200, 1, 2011], 'NVIDIA GeForce GTX 560 Ti' => ['nvidia', 2600, 1, 2011],
        'NVIDIA GeForce GTX 650' => ['nvidia', 1800, 1, 2012], 'NVIDIA GeForce GTX 650 Ti' => ['nvidia', 2300, 1, 2012],
        'NVIDIA GeForce GTX 660' => ['nvidia', 3100, 2, 2012], 'NVIDIA GeForce GTX 660 Ti' => ['nvidia', 3400, 2, 2012],
        'NVIDIA GeForce GTX 670' => ['nvidia', 3900, 2, 2012], 'NVIDIA GeForce GTX 680' => ['nvidia', 4200, 2, 2012],
        'NVIDIA GeForce GTX 750' => ['nvidia', 2000, 1, 2014], 'NVIDIA GeForce GTX 750 Ti' => ['nvidia', 2300, 2, 2014],
        'NVIDIA GeForce GTX 760' => ['nvidia', 3400, 2, 2013], 'NVIDIA GeForce GTX 770' => ['nvidia', 4300, 2, 2013],
        'NVIDIA GeForce GTX 780' => ['nvidia', 5000, 3, 2013], 'NVIDIA GeForce GTX 780 Ti' => ['nvidia', 5800, 3, 2013],
        'NVIDIA GeForce GTX 950' => ['nvidia', 2900, 2, 2015], 'NVIDIA GeForce GTX 960' => ['nvidia', 3600, 2, 2015],
        'NVIDIA GeForce GTX 970' => ['nvidia', 5400, 4, 2014], 'NVIDIA GeForce GTX 980' => ['nvidia', 6500, 4, 2014],
        'NVIDIA GeForce GTX 980 Ti' => ['nvidia', 8200, 6, 2015], 'NVIDIA GeForce GTX 1060 3GB' => ['nvidia', 6200, 3, 2016],
        'NVIDIA GeForce GTX 1060' => ['nvidia', 6600, 6, 2016], 'NVIDIA GeForce GTX 1070' => ['nvidia', 9300, 8, 2016],
        'NVIDIA GeForce GTX 1070 Ti' => ['nvidia', 10700, 8, 2017], 'NVIDIA GeForce GTX 1080' => ['nvidia', 11600, 8, 2016],
        'NVIDIA GeForce GTX 1080 Ti' => ['nvidia', 15000, 11, 2017],
        // Low-end cards, scored in line with the mid-range
        'NVIDIA GeForce GT 1030' => ['nvidia', 1700, 2, 2017], 'NVIDIA GeForce GTX 1050' => ['nvidia', 2700, 2, 2016],
        'NVIDIA GeForce GTX 1050 Ti' => ['nvidia', 3700, 4, 2016], 'NVIDIA GeForce GTX 1650' => ['nvidia', 5500, 4, 2019],
        'AMD Radeon RX 550' => ['amd', 1850, 2, 2017], 'AMD Radeon RX 560' => ['amd', 2600, 4, 2017],
        // Current NVIDIA desktop cards
        'NVIDIA GeForce RTX 4060' => ['nvidia', 16500, 8, 2023], 'NVIDIA GeForce RTX 4060 Ti' => ['nvidia', 21000, 8, 2023],
        'NVIDIA GeForce RTX 4070' => ['nvidia', 27000, 12, 2023], 'NVIDIA GeForce RTX 4070 Super' => ['nvidia', 32000, 12, 2024],
        'NVIDIA GeForce RTX 4070 Ti' => ['nvidia', 35000, 12, 2023], 'NVIDIA GeForce RTX 4070 Ti Super' => ['nvidia', 37000, 16, 2024],
        'NVIDIA GeForce RTX 4080' => ['nvidia', 43500, 16, 2022], 'NVIDIA GeForce RTX 4080 Super' => ['nvidia', 44500, 16, 2024],
        'NVIDIA GeForce RTX 4090' => ['nvidia', 56000, 24, 2022],
        // Laptop GPUs (typical power limits; real laptops vary)
        'NVIDIA GeForce GTX 1650 Laptop GPU' => ['nvidia', 5100, 4, 2019], 'NVIDIA GeForce GTX 1660 Ti Laptop GPU' => ['nvidia', 8200, 6, 2019],
        'NVIDIA GeForce RTX 3050 Laptop GPU' => ['nvidia', 7800, 4, 2021], 'NVIDIA GeForce RTX 3060 Laptop GPU' => ['nvidia', 13000, 6, 2021],
        'NVIDIA GeForce RTX 3070 Laptop GPU' => ['nvidia', 16000, 8, 2021], 'NVIDIA GeForce RTX 4050 Laptop GPU' => ['nvidia', 13000, 6, 2023],
        'NVIDIA GeForce RTX 4060 Laptop GPU' => ['nvidia', 15500, 8, 2023], 'NVIDIA GeForce RTX 4070 Laptop GPU' => ['nvidia', 17800, 8, 2023],
        'NVIDIA GeForce RTX 4080 Laptop GPU' => ['nvidia', 27000, 12, 2023],
        // Older and current AMD cards
        'AMD Radeon HD 4870' => ['amd', 1000, 1, 2008], 'AMD Radeon HD 6850' => ['amd', 1600, 1, 2010],
        'AMD Radeon HD 7770' => ['amd', 1800, 1, 2012], 'AMD Radeon HD 7790' => ['amd', 2300, 2, 2013],
        'AMD Radeon HD 7850' => ['amd', 2900, 2, 2012], 'AMD Radeon HD 7870' => ['amd', 3400, 2, 2012],
        'AMD Radeon HD 7950' => ['amd', 4300, 3, 2012], 'AMD Radeon HD 7970' => ['amd', 4900, 3, 2012],
        'AMD Radeon R7 260X' => ['amd', 2300, 2, 2013], 'AMD Radeon R7 360' => ['amd', 2300, 2, 2015],
        'AMD Radeon R7 370' => ['amd', 3000, 2, 2015], 'AMD Radeon R9 270' => ['amd', 3300, 2, 2013],
        'AMD Radeon R9 270X' => ['amd', 3600, 2, 2013], 'AMD Radeon R9 280' => ['amd', 4300, 3, 2014],
        'AMD Radeon R9 280X' => ['amd', 4900, 3, 2013], 'AMD Radeon R9 290' => ['amd', 6300, 4, 2013],
        'AMD Radeon R9 290X' => ['amd', 6800, 4, 2013], 'AMD Radeon R9 380' => ['amd', 4900, 4, 2015],
        'AMD Radeon R9 390' => ['amd', 7000, 8, 2015], 'AMD Radeon RX 460' => ['amd', 2300, 2, 2016],
        'AMD Radeon RX 470' => ['amd', 5800, 4, 2016], 'AMD Radeon RX 480' => ['amd', 6600, 8, 2016],
        'AMD Radeon RX 590' => ['amd', 7300, 8, 2018], 'AMD Radeon RX Vega 56' => ['amd', 10500, 8, 2017],
        'AMD Radeon RX Vega 64' => ['amd', 11600, 8, 2017], 'AMD Radeon RX 6500 XT' => ['amd', 5900, 4, 2022],
        'AMD Radeon RX 6950 XT' => ['amd', 33000, 16, 2022], 'AMD Radeon RX 7600' => ['amd', 16700, 8, 2023],
        'AMD Radeon RX 7600 XT' => ['amd', 17500, 16, 2024], 'AMD Radeon RX 7700 XT' => ['amd', 26000, 12, 2023],
        'AMD Radeon RX 7800 XT' => ['amd', 30000, 16, 2023], 'AMD Radeon RX 7900 GRE' => ['amd', 33000, 16, 2023],
        'AMD Radeon RX 7900 XT' => ['amd', 42000, 20, 2022], 'AMD Radeon RX 7900 XTX' => ['amd', 47000, 24, 2022],
        // Intel Arc
        'Intel Arc A310' => ['intel', 4500, 4, 2022], 'Intel Arc A380' => ['intel', 6700, 6, 2022],
        'Intel Arc A580' => ['intel', 16300, 8, 2023], 'Intel Arc A750' => ['intel', 20000, 8, 2022],
        'Intel Arc A770' => ['intel', 21700, 16, 2022], 'Intel Arc B580' => ['intel', 22500, 12, 2024],
    ];

    public function run(): void
    {
        foreach (self::CPUS as $name => [$maker, $score, $year]) {
            CPU::updateOrCreate(['name' => $name], ['manufacturer' => $maker, 'benchmark_score' => $score, 'release_year' => $year]);
        }
        foreach (self::GPUS as $name => [$maker, $score, $vram, $year]) {
            GPU::updateOrCreate(['name' => $name], ['manufacturer' => $maker, 'benchmark_score' => $score, 'vram_gb' => $vram ?: null, 'release_year' => $year]);
        }
        // Two ExpandedHardwareSeeder entries, scored on the same scale as the parts above.
        GPU::whereIn('name', ['AMD Radeon Graphics (RDNA 2)'])->update(['benchmark_score' => 3700]);
        GPU::where('name', 'No Dedicated GPU (CPU Only)')->update(['benchmark_score' => 300]);
    }
}
