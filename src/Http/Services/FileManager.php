<?php

namespace PortedCheese\WebflowIntegration\Http\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPHtmlParser\Dom;
use Chumper\Zipper\Facades\Zipper;

class FileManager
{
    const PATH = "webflow/unzipped";

    protected $htmlParserService;

    public function __construct(HtmlParser $htmlParserService)
    {
        $this->htmlParserService = $htmlParserService;
        $this->public = Storage::disk('public');
        $this->webflow = Storage::disk('webflow');
    }

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
        $files = $this->public->files(self::PATH);
        $check = $this->checkFiles();
        if (!$check['success']) {
            return $check;
        }
        $this->makeIndex();
        $this->copyFolders();
    }

    /**
     * Копирование файлов.
     */
    private function copyFolders()
    {
        $publicDirectories = $this->public->directories(self::PATH);
        $webflowDirectories = $this->webflow->directories();
        foreach ($publicDirectories as $directory) {
            $folder = str_replace(self::PATH . "/", '', $directory);
            $this->copyFiles($folder, $webflowDirectories);
        }
        $this->public->deleteDirectory(self::PATH);
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
     * Создание файла index.
     */
    private function makeIndex()
    {
        $dom = new Dom();
        $dom->loadFromFile(public_path('storage/' . self::PATH) . '/index.html', [
            'strict' => false,
            'cleanupInput' => false,
            'removeScripts' => false,
            'removeStyles' => false,
            'whitespaceTextNode' => false,
            'removeDoubleSpace' => false,
        ]);

        $this->htmlParserService->parseIndex($dom);
    }

    /**
     * Проверка файлов.
     *
     * @return array
     */
    private function checkFiles()
    {
        // Проверка индекса.
        $check = Storage::disk('public')
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