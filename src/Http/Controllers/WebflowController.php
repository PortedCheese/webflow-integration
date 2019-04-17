<?php

namespace PortedCheese\WebflowIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPHtmlParser\Dom;
use PortedCheese\WebflowIntegration\Http\Services\FileManager;

class WebflowController extends Controller
{
    protected $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function index()
    {
//        $this->fileManager->runParser();
        return view('webflow-integration::admin.webflow.index');
    }

    public function load(Request $request)
    {
        if (!$request->hasFile('file')) {
            return redirect()
                ->back()
                ->with('danger', 'Файл не найден');
        }
        $path = $request
            ->file('file')
            ->store("webflow");
        $realPath = Storage::disk('public')->path($path);
        $this->fileManager->unzip($realPath);
        Storage::delete($path);
        $this->fileManager->runParser();
        return redirect()
            ->back()
            ->with('success', 'Загружено');
    }

    public function parse()
    {
        $dom = new Dom();
        $dom->loadFromFile(__DIR__ . '/index.html', [
            'removeScripts' => false,
            'removeStyles' => false,
            'whitespaceTextNode' => false,
        ]);
    }
}
