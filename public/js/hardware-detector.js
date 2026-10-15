/**
 * Hardware Auto-Detection using WebGL and Browser APIs
 * Detects GPU, CPU cores, RAM estimate, and platform
 */

class HardwareDetector {
    constructor() {
        this.detectedHardware = {
            gpu: null,
            gpuVendor: null,
            gpuRenderer: null,
            cpuCores: null,
            cpuThreads: null,
            ramEstimate: null,
            platform: null,
            userAgent: null,
            confidence: {
                gpu: 'low',
                cpu: 'high',
                ram: 'low',
                platform: 'high'
            }
        };
    }

    /**
     * Detect all hardware specifications
     */
    async detectAll() {
        this.detectGPU();
        this.detectCPU();
        this.detectRAM();
        this.detectPlatform();

        return this.detectedHardware;
    }

    /**
     * Detect GPU using WebGL
     */
    detectGPU() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');

            if (!gl) {
                console.warn('WebGL not supported - cannot detect GPU');
                this.detectedHardware.confidence.gpu = 'none';
                return;
            }

            // Get GPU information
            const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');

            if (debugInfo) {
                this.detectedHardware.gpuVendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL);
                this.detectedHardware.gpuRenderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);

                // Parse GPU name
                this.detectedHardware.gpu = this.parseGPUName(this.detectedHardware.gpuRenderer);
                this.detectedHardware.confidence.gpu = 'high';
            } else {
                // Fallback to limited info
                this.detectedHardware.gpuVendor = gl.getParameter(gl.VENDOR);
                this.detectedHardware.gpuRenderer = gl.getParameter(gl.RENDERER);
                this.detectedHardware.gpu = this.parseGPUName(this.detectedHardware.gpuRenderer);
                this.detectedHardware.confidence.gpu = 'medium';
            }

        } catch (error) {
            console.error('GPU detection error:', error);
            this.detectedHardware.confidence.gpu = 'none';
        }
    }

    /**
     * Parse GPU name from renderer string
     */
    parseGPUName(renderer) {
        if (!renderer) return null;

        // Remove common prefixes/suffixes
        let cleaned = renderer
            .replace(/ANGLE \((.*?)\)/i, '$1')  // Remove ANGLE wrapper
            .replace(/Direct3D\d+/gi, '')       // Remove Direct3D
            .replace(/OpenGL.*/gi, '')           // Remove OpenGL
            .replace(/\(.*?\)/g, '')             // Remove parentheses content
            .trim();

        // Extract GPU model patterns
        const patterns = [
            // NVIDIA patterns
            /(?:NVIDIA|GeForce)\s*(RTX|GTX)\s*(\d{4})\s*(Ti|Super)?/i,
            /(?:NVIDIA|GeForce)\s*(MX\d{3})/i,

            // AMD patterns
            /(?:AMD|Radeon)\s*(RX|R\d+|HD)\s*(\d{3,4})\s*(XT)?/i,
            /(?:AMD|Radeon)\s*(Vega|RDNA)\s*(\d+)?/i,

            // Intel patterns
            /Intel.*?(UHD|HD|Iris)\s*(?:Graphics)?\s*(\d{3,4})?/i,
            /Intel.*?(Xe)/i
        ];

        for (const pattern of patterns) {
            const match = cleaned.match(pattern);
            if (match) {
                return match[0].trim();
            }
        }

        // If no pattern matches, return cleaned version
        return cleaned.substring(0, 100);  // Limit length
    }

    /**
     * Detect CPU cores using Navigator API
     */
    detectCPU() {
        try {
            // Hardware concurrency gives logical cores (threads)
            const threads = navigator.hardwareConcurrency;

            if (threads) {
                this.detectedHardware.cpuThreads = threads;
                // Estimate physical cores (assuming 2 threads per core for modern CPUs)
                this.detectedHardware.cpuCores = Math.ceil(threads / 2);
                this.detectedHardware.confidence.cpu = 'high';
            } else {
                this.detectedHardware.confidence.cpu = 'none';
            }

            // Store user agent for CPU architecture hints
            this.detectedHardware.userAgent = navigator.userAgent;

        } catch (error) {
            console.error('CPU detection error:', error);
            this.detectedHardware.confidence.cpu = 'none';
        }
    }

    /**
     * Estimate RAM using Device Memory API
     */
    detectRAM() {
        try {
            // Device Memory API (Chrome/Edge only)
            if (navigator.deviceMemory) {
                this.detectedHardware.ramEstimate = navigator.deviceMemory;
                this.detectedHardware.confidence.ram = 'high';
            } else {
                // Fallback: Estimate based on performance
                const estimate = this.estimateRAMFromPerformance();
                this.detectedHardware.ramEstimate = estimate;
                this.detectedHardware.confidence.ram = 'low';
            }

        } catch (error) {
            console.error('RAM detection error:', error);
            this.detectedHardware.confidence.ram = 'none';
        }
    }

    /**
     * Estimate RAM from performance characteristics (fallback)
     */
    estimateRAMFromPerformance() {
        // Very rough estimate based on device class
        const ua = navigator.userAgent.toLowerCase();

        if (ua.includes('mobile') || ua.includes('android')) {
            return 4;  // Mobile devices typically 4-8GB
        }

        // Desktop - estimate based on hardware concurrency
        const threads = navigator.hardwareConcurrency || 4;
        if (threads >= 16) return 32;      // High-end desktop
        if (threads >= 12) return 16;      // Mid-high desktop
        if (threads >= 8) return 16;       // Mid-range desktop
        if (threads >= 4) return 8;        // Entry desktop
        return 4;                           // Low-end
    }

    /**
     * Detect operating system platform
     */
    detectPlatform() {
        try {
            const ua = navigator.userAgent;
            const platform = navigator.platform || '';

            if (ua.includes('Win')) {
                // Try to detect Windows version
                if (ua.includes('Windows NT 10.0')) {
                    this.detectedHardware.platform = 'windows_11';  // Windows 11 also reports as NT 10.0
                } else if (ua.includes('Windows NT 6.3') || ua.includes('Windows NT 6.2')) {
                    this.detectedHardware.platform = 'windows_10';
                } else {
                    this.detectedHardware.platform = 'windows_10';  // Default to Windows 10
                }
            } else if (ua.includes('Mac')) {
                this.detectedHardware.platform = 'macos';
            } else if (ua.includes('Linux') || ua.includes('X11')) {
                this.detectedHardware.platform = 'linux';
            } else {
                this.detectedHardware.platform = 'windows_10';  // Default
            }

            this.detectedHardware.confidence.platform = 'high';

        } catch (error) {
            console.error('Platform detection error:', error);
            this.detectedHardware.platform = 'windows_10';
            this.detectedHardware.confidence.platform = 'medium';
        }
    }

    /**
     * Get formatted detection results for display
     */
    getFormattedResults() {
        return {
            gpu: this.detectedHardware.gpu || 'Not detected',
            gpuConfidence: this.detectedHardware.confidence.gpu,
            cpu: this.detectedHardware.cpuThreads
                ? `${this.detectedHardware.cpuCores} cores / ${this.detectedHardware.cpuThreads} threads`
                : 'Not detected',
            cpuThreads: this.detectedHardware.cpuThreads,
            cpuConfidence: this.detectedHardware.confidence.cpu,
            ram: this.detectedHardware.ramEstimate
                ? `${this.detectedHardware.ramEstimate} GB (estimated)`
                : 'Not detected',
            ramValue: this.detectedHardware.ramEstimate,
            ramConfidence: this.detectedHardware.confidence.ram,
            platform: this.formatPlatformName(this.detectedHardware.platform),
            platformValue: this.detectedHardware.platform,
            platformConfidence: this.detectedHardware.confidence.platform
        };
    }

    /**
     * Format platform name for display
     */
    formatPlatformName(platform) {
        const names = {
            'windows_10': 'Windows 10',
            'windows_11': 'Windows 11',
            'macos': 'macOS',
            'linux': 'Linux'
        };
        return names[platform] || platform;
    }

    /**
     * Search for matching GPU in database
     */
    async searchGPUInDatabase(gpuName) {
        if (!gpuName) return null;

        try {
            // Send AJAX request to search for GPU
            const response = await fetch('/api/hardware/search-gpu', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({ query: gpuName })
            });

            if (response.ok) {
                return await response.json();
            }
        } catch (error) {
            console.error('GPU search error:', error);
        }

        return null;
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HardwareDetector;
} else {
    window.HardwareDetector = HardwareDetector;
}
