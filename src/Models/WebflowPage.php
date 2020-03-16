<?php

namespace PortedCheese\WebflowIntegration\Models;

use App\Meta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use PortedCheese\WebflowIntegration\Http\Services\FileManager;

class WebflowPage extends Model
{
    protected $fillable = [
        'slug',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($page) {
            FileManager::deletePage($page->slug);
            $page->clearMetas();
            Cache::forget("webflow-pages");
        });

        static::updated(function ($page) {
            Cache::forget("webflow-pages");
        });

        static::created(function ($page) {
            Cache::forget("webflow-pages");
        });
    }

    /**
     * Получить главную страницу.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|null
     */
    public static function getHomePage()
    {
        try {
            $page = WebflowPage::where('main', 1)->firstOrFail();
        }
        catch (\Exception $e) {
            return NULL;
        }
        $pageMetas = Meta::getByModelKey($page);
        $uri = $page->slug;
        return view("site.webflow.$uri", [
            'pageMetas' => $pageMetas,
            'customTheme' => 'webflow.index',
        ]);
    }

    /**
     * Ссылка на тег.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function metas() {
        return $this->morphMany('PortedCheese\SeoIntegration\Models\Meta', 'metable');
    }

    /**
     * Подгружать по slug.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Удаляем созданные теги.
     */
    private function clearMetas()
    {
        foreach ($this->metas as $meta) {
            $meta->delete();
        }
    }
}
