<?php

namespace PortedCheese\WebflowIntegration\Http\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPHtmlParser\Dom;
use Chumper\Zipper\Facades\Zipper;

class FileManager
{
    // Куда будет распакован архив.
    const PATH = "webflow/unzipped";
    // Куда будут сохранен главный шаблон.
    const LAYOUT = "views/layouts/webflow";
    // Куда будут падать шаблоны страниц.
    const PAGES = "views/site/webflow";

    protected $htmlParserService;

    public function __construct(HtmlParser $htmlParserService)
    {
        $this->htmlParserService = $htmlParserService;
        $this->public = Storage::disk('public');
        $this->webflow = Storage::disk('webflow');

        // Если нет директории для главного шаблона, нужно создать.
        if (! is_dir($directory = resource_path(self::LAYOUT))) {
            mkdir($directory, 0755, true);
        }
        // Если нет директории для страниц, нужно создать.
        if (! is_dir($directory = resource_path(self::PAGES))) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Распаковать архив.
     *
     * @param $path
     */
    public function unzip($path)
    {
        Zipper::make($path)->extractTo('storage/' . self::PATH);
    }

    /**
     * Запускаем парсер архива.
     *
     * @return array
     */
    public function runParser()
    {
        $check = $this->checkFiles();
        if (!$check['success']) {
            return $check;
        }
        $files = $this->public->files(self::PATH);
        foreach ($files as $file) {
            $name = str_replace(self::PATH . "/", '', $file);
            if ($name == 'index.html') {
//                $this->makeIndex($name);
            }
            else {
                $this->makePage($name);
//                break;
            }
        }
//        $this->copyFolders();
    }

    /**
     * Путь к файлу.
     *
     * @param $name
     * @return string
     */
    private function getPublicFilePath($name)
    {
        return public_path('storage/' . self::PATH) . "/$name";
    }

    private function makePage($file)
    {
        $filePath = $this->getPublicFilePath($file);

        $exploded = explode('.', $file);
        if (count($exploded) == 2) {
            $name = str_replace('_', '-', $exploded[0]);
        }
        else {
            return;
        }

        // TODO: create model.
        $model = (object) [];
        $blade = $this->htmlParserService->parsePage($filePath, $model);

        file_put_contents(
            resource_path(self::PAGES . "/{$name}.blade.php"),
            $blade
        );
    }

    /**
     * Создание файла index.
     */
    private function makeIndex($file)
    {
        $filePath = $this->getPublicFilePath($file);

        $html = $this->htmlParserService->parseIndex($filePath);

        file_put_contents(
            resource_path(self::LAYOUT . "/index.blade.php"),
            $html
        );
    }

    /**
     * Копирование файлов.
     */
    private function copyFolders()
    {
        // Фалы из архива.
        $publicDirectories = $this->public
            ->directories(self::PATH);
        // Директории webflow.
        $webflowDirectories = $this->webflow
            ->directories();
        foreach ($publicDirectories as $directory) {
            $folder = str_replace(self::PATH . "/", '', $directory);
            $this->copyFiles($folder, $webflowDirectories);
        }
//        $this->public->deleteDirectory(self::PATH);
    }

    /**
     * Копирование директорий.
     *
     * @param $dir
     */
    private function copyFiles($folder, $dir)
    {
        if (in_array($folder, $dir)) {
            $this->webflow->deleteDirectory($folder);
        }
        $this->webflow->makeDirectory($folder);
        $files = $this->public->files(self::PATH . "/$folder");
        foreach ($files as $file) {
            $source = $this->public
                ->getDriver()
                ->getAdapter()
                ->applyPathPrefix($file);
            $dest = $this->webflow
                ->getDriver()
                ->getAdapter()
                ->applyPathPrefix("/$folder/" . basename($file));
            File::copy($source, $dest);
        }
    }

    /**
     * Проверка файлов.
     *
     * @return array
     */
    private function checkFiles()
    {
        // Проверка индекса.
        $check = $this->public
            ->exists(self::PATH . "/index.html");
        if (!$check) {
            return [
                'success' => false,
                'message' => 'Index not found',
            ];
        }
        return [
            'success' => true,
        ];
    }
}