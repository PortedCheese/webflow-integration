<?php

namespace PortedCheese\WebflowIntegration\Console\Commands;

use App\Menu;
use App\MenuItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PortedCheese\WebflowIntegration\Http\Services\FileManager;

class WebdlowMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:webflow
                                {--menu : Only config menu}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make folder for webflow';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $this->option('menu')) {
            $publickWebflow = public_path('webflow');
            exec("chown -R www-data:www-data {$publickWebflow}");
            // Если нет директории для главного шаблона, нужно создать.
            if (! is_dir($directory = resource_path(FileManager::LAYOUT))) {
                mkdir($directory, 0755, true);
            }
            exec("chown -R www-data:www-data {$directory}");
            // Если нет директории для страниц, нужно создать.
            if (! is_dir($directory = resource_path(FileManager::PAGES))) {
                mkdir($directory, 0755, true);
            }
            exec("chown -R www-data:www-data {$directory}");
        }
        $this->makeMenu();
    }

    protected function makeMenu()
    {
        try {
            $menu = Menu::where('key', 'admin')->firstOrFail();
        }
        catch (\Exception $e) {
            return;
        }

        $title = "Webflow";
        $itemData = [
            'title' => $title,
            'template' => "webflow-integration::admin.webflow.menu",
            'url' => "#",
            'class' => '@fas fa-ruler-combined',
            'menu_id' => $menu->id,
            'middleware' => "admin",
        ];

        try {
            $menuItem = MenuItem::where('title', $title)->firstOrFail();
            $menuItem->update($itemData);
            $this->info("Элемент меню '$title' обновлен");
        }
        catch (\Exception $e) {
            $menuItem = MenuItem::create($itemData);
            $this->info("Элемент меню '$title' создан");
        }
    }
}
