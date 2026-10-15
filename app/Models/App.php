<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class App extends Model
{
    protected $table = 'apps';
    protected $primaryKey = 'app_id';
    public $incrementing = false;
    protected $keyType = 'int';

    public $timestamps = true;
    
    protected $fillable = [
        'app_id','captured_at','type','name','slug','steam_appid','required_age','is_free',
        'short_description','detailed_description','about_the_game','supported_languages',
        'website','release_date','coming_soon','developers','publishers',
        'header_image','background_image','content_descriptor_notes','total_recommendations',
        // New Steam API fields
        'metacritic_score','metacritic_url','total_achievements','controller_support',
        'dlc_count','dlc_ids','has_adult_content','has_adult_content_sex','has_adult_content_violence',
        'package_ids','support_url','support_email','legal_notice','drm_notice','ext_account_notice',
        'supports_windows','supports_mac','supports_linux'
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'coming_soon' => 'boolean',
        'release_date' => 'date',
        'total_recommendations' => 'integer',
        'total_views' => 'integer',
        'developers' => 'array',  // ✅ NEW: Auto-cast JSON to array
        'publishers' => 'array',  // ✅ NEW: Auto-cast JSON to array
        // New Steam API field casts
        'metacritic_score' => 'integer',
        'total_achievements' => 'integer',
        'dlc_count' => 'integer',
        'dlc_ids' => 'array',
        'package_ids' => 'array',
        'has_adult_content' => 'boolean',
        'has_adult_content_sex' => 'boolean',
        'has_adult_content_violence' => 'boolean',
        'supports_windows' => 'boolean',
        'supports_mac' => 'boolean',
        'supports_linux' => 'boolean',
    ];

    // ========================================
    // BOOT METHOD - Auto-generate slugs
    // ========================================
    
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($app) {
            if ($app->isDirty('name') && !empty($app->name)) {
                $slug = Str::slug($app->name);
                
                // Handle empty slugs (special characters only)
                if (empty($slug)) {
                    $slug = 'game-' . $app->app_id;
                }
                
                // Check for duplicate slugs
                $originalSlug = $slug;
                $count = 1;
                
                while (static::where('slug', $slug)
                    ->where('app_id', '!=', $app->app_id)
                    ->exists()) {
                    $slug = $originalSlug . '-' . $count;
                    $count++;
                }
                
                $app->slug = $slug;
            }
        });
    }

    // ========================================
    // ROUTE MODEL BINDING
    // ========================================
    
    public function getRouteKeyName()
    {
        return 'slug';
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================
    
    public function genres() {
        return $this->belongsToMany(Genre::class, 'app_genres', 'app_id', 'genre_id');
    }

    public function categories() {
        return $this->belongsToMany(Category::class, 'app_categories', 'app_id', 'category_id');
    }

    public function sysRequirements() {
        return $this->hasMany(SysRequirement::class, 'app_id', 'app_id');
    }

    public function screenshots() {
        return $this->hasMany(Screenshot::class, 'app_id', 'app_id');
    }

    public function movies() {
        return $this->hasMany(Movie::class, 'app_id', 'app_id');
    }

    public function reviews() {
        return $this->hasOne(Review::class, 'app_id', 'app_id');
    }

    public function pricing() {
        return $this->hasOne(Pricing::class, 'app_id', 'app_id');
    }

    // ✅ NEW: GameMetric relationship
    public function metrics() {
        return $this->hasOne(GameMetric::class, 'app_id', 'app_id');
    }

    // ✅ NEW: Stats tracking relationship
    public function stats() {
        return $this->hasMany(StatsTracking::class, 'app_id', 'app_id');
    }

    // ✅ NEW: Compatibility cache relationship
    public function compatibilityCache() {
        return $this->hasMany(CompatibilityCache::class, 'app_id', 'app_id');
    }

    // ✅ NEW: Content ratings relationship
    public function contentRatings() {
        return $this->hasMany(ContentRating::class, 'app_id', 'app_id');
    }

    // ========================================
    // HELPER METHODS - Pricing
    // ========================================
    
    /**
     * Get formatted price in dollars
     */
    public function getPriceAttribute()
    {
        if ($this->is_free) {
            return 'Free';
        }
        
        if (!$this->pricing) {
            return 'N/A';
        }
        
        $price = $this->pricing->final_price / 100;
        return '$' . number_format($price, 2);
    }

    /**
     * Get original price before discount
     */
    public function getOriginalPriceAttribute()
    {
        if (!$this->pricing || $this->pricing->initial_price === 0) {
            return null;
        }
        
        $price = $this->pricing->initial_price / 100;
        return '$' . number_format($price, 2);
    }

    /**
     * Check if game has a discount
     */
    public function hasDiscount()
    {
        return $this->pricing && $this->pricing->discount_percent > 0;
    }

    /**
     * Get discount badge text
     */
    public function getDiscountBadge()
    {
        if (!$this->hasDiscount()) {
            return null;
        }
        
        return '-' . $this->pricing->discount_percent . '%';
    }

    // ========================================
    // HELPER METHODS - Reviews
    // ========================================
    
    /**
     * Get review percentage (positive/total)
     */
    public function getReviewPercentageAttribute()
    {
        if (!$this->reviews || $this->reviews->total_reviews === 0) {
            return null;
        }
        
        return round(($this->reviews->total_positive / $this->reviews->total_reviews) * 100);
    }

    /**
     * Get review sentiment badge color
     */
    public function getReviewColorAttribute()
    {
        $percentage = $this->review_percentage;
        
        if ($percentage === null) {
            return 'gray';
        }
        
        if ($percentage >= 80) return 'green';
        if ($percentage >= 60) return 'yellow';
        return 'red';
    }

    /**
     * Check if game has positive reviews
     */
    public function hasPositiveReviews()
    {
        return $this->review_percentage && $this->review_percentage >= 70;
    }

    // ========================================
    // HELPER METHODS - System Requirements
    // ========================================
    
    /**
     * Get system requirements for specific platform
     */
    public function getRequirementsFor($platform)
    {
        return $this->sysRequirements->firstWhere('platform', strtolower($platform));
    }

    /**
     * Check if game has requirements for platform
     */
    public function hasRequirementsFor($platform)
    {
        return $this->sysRequirements->contains('platform', strtolower($platform));
    }

    /**
     * Get available platforms
     */
    public function getAvailablePlatformsAttribute()
    {
        return $this->sysRequirements->pluck('platform')->unique()->values();
    }

    // ========================================
    // HELPER METHODS - Release Date
    // ========================================
    
    /**
     * Check if game was released in last X days
     */
    public function isNewRelease($days = 30)
    {
        if (!$this->release_date || $this->coming_soon) {
            return false;
        }
        
        return $this->release_date->isAfter(now()->subDays($days));
    }

    /**
     * Get human-readable release date
     */
    public function getReleaseDateHumanAttribute()
    {
        if ($this->coming_soon) {
            return 'Coming Soon';
        }
        
        if (!$this->release_date) {
            return 'TBA';
        }
        
        return $this->release_date->format('M d, Y');
    }

    // ========================================
    // HELPER METHODS - Genres & Categories
    // ========================================
    
    /**
     * Get primary genre (first one)
     */
    public function getPrimaryGenreAttribute()
    {
        return $this->genres->first();
    }

    /**
     * Get genre names as comma-separated string
     */
    public function getGenreNamesAttribute()
    {
        return $this->genres->pluck('name')->join(', ');
    }

    /**
     * Check if game belongs to genre
     */
    public function hasGenre($genreName)
    {
        return $this->genres->contains('name', $genreName);
    }

    /**
     * Check if game belongs to category
     */
    public function hasCategory($categoryName)
    {
        return $this->categories->contains('name', $categoryName);
    }

    // ========================================
    // HELPER METHODS - Developers & Publishers
    // ========================================
    
    /**
     * Get first developer
     */
    public function getPrimaryDeveloperAttribute()
    {
        if (!$this->developers || !is_array($this->developers)) {
            return null;
        }
        
        return $this->developers[0] ?? null;
    }

    /**
     * Get first publisher
     */
    public function getPrimaryPublisherAttribute()
    {
        if (!$this->publishers || !is_array($this->publishers)) {
            return null;
        }
        
        return $this->publishers[0] ?? null;
    }

    // ========================================
    // HELPER METHODS - New Steam API Fields
    // ========================================

    /**
     * Check if game has Metacritic score
     */
    public function getHasMetacriticScoreAttribute()
    {
        return !is_null($this->metacritic_score) && $this->metacritic_score > 0;
    }

    /**
     * Get Metacritic badge color based on score
     */
    public function getMetacriticColorAttribute()
    {
        if (!$this->has_metacritic_score) {
            return 'gray';
        }

        if ($this->metacritic_score >= 75) return 'green';
        if ($this->metacritic_score >= 50) return 'yellow';
        return 'red';
    }

    /**
     * Check if game has achievements
     */
    public function getHasAchievementsAttribute()
    {
        return $this->total_achievements > 0;
    }

    /**
     * Check if game has controller support
     */
    public function getHasControllerSupportAttribute()
    {
        return $this->controller_support !== 'none';
    }

    /**
     * Get controller support icon class
     */
    public function getControllerIconAttribute()
    {
        switch ($this->controller_support) {
            case 'full':
                return 'text-green-500';
            case 'partial':
                return 'text-yellow-500';
            default:
                return 'text-gray-400';
        }
    }

    /**
     * Check if game has DLC
     */
    public function getHasDlcAttribute()
    {
        return $this->dlc_count > 0;
    }

    /**
     * Get platform support array
     */
    public function getSupportedPlatformsAttribute()
    {
        $platforms = [];
        if ($this->supports_windows) $platforms[] = 'Windows';
        if ($this->supports_mac) $platforms[] = 'Mac';
        if ($this->supports_linux) $platforms[] = 'Linux';
        return $platforms;
    }

    /**
     * Check if game supports specific platform
     */
    public function supportsPlatform($platform)
    {
        $platform = strtolower($platform);
        switch ($platform) {
            case 'windows':
                return $this->supports_windows;
            case 'mac':
                return $this->supports_mac;
            case 'linux':
                return $this->supports_linux;
            default:
                return false;
        }
    }

    // ========================================
    // SCOPES - Query Filters
    // ========================================
    
    /**
     * Scope for free games
     */
    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    /**
     * Scope for paid games
     */
    public function scopePaid($query)
    {
        return $query->where('is_free', false);
    }

    /**
     * Scope for released games (not coming soon)
     */
    public function scopeReleased($query)
    {
        return $query->where(function($q) {
            $q->where('coming_soon', false)
              ->orWhereNull('coming_soon');
        });
    }

    /**
     * Scope for games with positive reviews
     */
    public function scopeWellReviewed($query, $minPercentage = 70)
    {
        return $query->whereHas('reviews', function($q) use ($minPercentage) {
            $q->whereRaw('(total_positive / total_reviews * 100) >= ?', [$minPercentage]);
        });
    }

    /**
     * Scope for games with deals
     */
    public function scopeOnSale($query, $minDiscount = 10)
    {
        return $query->whereHas('pricing', function($q) use ($minDiscount) {
            $q->where('discount_percent', '>=', $minDiscount);
        });
    }

    /**
     * Scope for games released in specific year
     */
    public function scopeReleasedInYear($query, $year)
    {
        return $query->whereYear('release_date', $year);
    }

    /**
     * Scope for popular games
     */
    public function scopePopular($query, $minRecommendations = 1000)
    {
        return $query->where('total_recommendations', '>=', $minRecommendations);
    }

    /**
     * Scope for search by name or description
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->whereRaw("MATCH(name, short_description) AGAINST (? IN NATURAL LANGUAGE MODE)", [$search])
              ->orWhere('name', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for games with minimum Metacritic score
     */
    public function scopeMetacriticAbove($query, $minScore = 70)
    {
        return $query->where('metacritic_score', '>=', $minScore);
    }

    /**
     * Scope for games with achievements
     */
    public function scopeWithAchievements($query)
    {
        return $query->where('total_achievements', '>', 0);
    }

    /**
     * Scope for games with controller support
     */
    public function scopeWithControllerSupport($query, $level = null)
    {
        if ($level) {
            return $query->where('controller_support', $level);
        }
        return $query->whereIn('controller_support', ['partial', 'full']);
    }

    /**
     * Scope for games supporting specific platform
     */
    public function scopeForPlatform($query, $platform)
    {
        $platform = strtolower($platform);
        switch ($platform) {
            case 'windows':
                return $query->where('supports_windows', true);
            case 'mac':
                return $query->where('supports_mac', true);
            case 'linux':
                return $query->where('supports_linux', true);
            default:
                return $query;
        }
    }

    /**
     * Scope for family-friendly games (no adult content)
     */
    public function scopeFamilyFriendly($query)
    {
        return $query->where('has_adult_content', false);
    }

    /**
     * Scope for games with DLC
     */
    public function scopeWithDLC($query)
    {
        return $query->where('dlc_count', '>', 0);
    }

    // ========================================
    // HELPER METHODS - Media
    // ========================================
    
    /**
     * Get featured trailer
     */
    public function getFeaturedTrailerAttribute()
    {
        return $this->movies->firstWhere('highlight', 1) ?? $this->movies->first();
    }

    /**
     * Check if game has media
     */
    public function hasMedia()
    {
        return $this->screenshots->isNotEmpty() || $this->movies->isNotEmpty();
    }

    // ========================================
    // HELPER METHODS - SEO
    // ========================================
    
    /**
     * Get meta description for SEO
     */
    public function getMetaDescriptionAttribute()
    {
        if (!$this->short_description) {
            return null;
        }
        
        $description = strip_tags($this->short_description);
        return Str::limit($description, 160);
    }

    /**
     * Get meta keywords for SEO
     */
    public function getMetaKeywordsAttribute()
    {
        $keywords = [];
        
        // Add genres
        $keywords = array_merge($keywords, $this->genres->pluck('name')->toArray());
        
        // Add game name
        $keywords[] = $this->name;
        
        // Add developer
        if ($this->primary_developer) {
            $keywords[] = $this->primary_developer;
        }
        
        // Add common keywords
        $keywords[] = 'steam game';
        $keywords[] = 'pc game';
        $keywords[] = 'system requirements';
        
        return implode(', ', array_unique($keywords));
    }
}