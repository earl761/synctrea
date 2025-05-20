<?php

namespace App\Models\Blog;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Tags\HasTags;

class Post extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia, HasTags, HasUlids;

    /**
     * @var string
     */
    protected $table = 'blog_posts';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'blog_author_id',
        'blog_category_id',
        'title',
        'slug',
        'content',
        'content_overview',
        'published_at',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'is_featured',
        'reading_time'
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean'
    ];

    /** @return BelongsTo<User,self> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blog_author_id');
    }

    /** @return BelongsTo<Category,self> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'blog_category_id');
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public static function calculateReadingTime($content)
    {
        // Average reading speed (words per minute)
        $wordsPerMinute = 200;
        
        // Count words in content (strip HTML tags first)
        $wordCount = str_word_count(strip_tags($content));
        
        // Calculate reading time in minutes, rounded up
        $readingTime = ceil($wordCount / $wordsPerMinute);
        
        // Ensure minimum reading time is 1 minute
        return max(1, $readingTime);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($post) {
            if ($post->isDirty('content')) {
                $post->reading_time = self::calculateReadingTime($post->content);
            }
        });
    }

    public function getRelatedPosts($limit = 3)
    {
        return self::with(['category', 'author'])
            ->where('id', '!=', $this->id)
            ->where('blog_category_id', $this->blog_category_id)
            ->published()
            ->latest('published_at')
            ->take($limit)
            ->get();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')
            ->singleFile()
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->width(400)
                    ->height(300);

                $this->addMediaConversion('medium')
                    ->width(800)
                    ->height(600);
            });
    }
}
