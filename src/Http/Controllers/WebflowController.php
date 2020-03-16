<?php

namespace PortedCheese\WebflowIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Meta;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use PortedCheese\WebflowIntegration\Http\Requests\WebflowLoadRequest;
use PortedCheese\WebflowIntegration\Http\Services\FileManager;
use PortedCheese\WebflowIntegration\Models\WebflowPage;

class WebflowController extends Controller
{
    protected $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * Вывести страницу.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function page()
    {
        $uri = Route::current()->uri();
        try {
            $page = WebflowPage::where('slug', $uri)
                ->where('main', 0)
                ->firstOrFail();
        }
        catch (\Exception $e) {
            abort(404);
        }
        $pageMetas = Meta::getByModelKey($page);
        return view("site.webflow.$uri", [
            'pageMetas' => $pageMetas,
        ]);
    }

    /**
     * Форма загрузки.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        if (env("WEBFLOW_DEBUG", false)) {
            $this->fileManager->runParser(true);
        }
        return view('webflow-integration::admin.webflow.index', [
            'push' => "@push('more-js')@endpush",
            'contactMap' => "@includeIf('contact-page::site.map', ['size' => 400, 'zoom' => 14, 'preset' => 'islands#blueIcon'])",
            'formFile' => "@includeIf('webflow-integration::layouts.file-input', ['name' => 'file'])",
            'hiddenCaptcha' => "@hiddenCaptcha (старый вариант: @hCaptcha @endhCaptcha)",
            'googleCaptcha' => "@googleCaptcha2 (старый вариант: @gCaptcha @endgCaptcha)",
        ]);
    }

    /**
     * Загрузка архива.
     *
     * @param WebflowLoadRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function load(WebflowLoadRequest $request)
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
        $unzip = $this->fileManager->unzip($realPath);
        Storage::delete($path);
        if (! $unzip) {
            return redirect()
                ->back()
                ->with('danger', 'Ошибка при распаковке архива');
        }

        $result = [
            'success' => true,
            'message' => "debug",
        ];

        if (! env("WEBFLOW_DEBUG", false)) {
            $result = $this->fileManager->runParser();
        }

        return redirect()
            ->back()
            ->with($result['success'] ? 'success' : "danger", $result['message']);
    }

    /**
     * Список загруженных страниц.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function list()
    {
        $pages = WebflowPage::all();
        return view('webflow-integration::admin.webflow.list', [
            'pages' => $pages,
        ]);
    }

    /**
     * Просмотр, тут страница с метатегами.
     *
     * @param WebflowPage $page
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(WebflowPage $page)
    {
        return view("webflow-integration::admin.webflow.show", [
            'page' => $page,
        ]);
    }

    /**
     * Сделать главной.
     *
     * @param WebflowPage $page
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setHome(WebflowPage $page)
    {
        $homes = WebflowPage::where('main', 1)->get();
        foreach ($homes as $home) {
            $home->main = 0;
            $home->save();
        }
        $page->main = 1;
        $page->save();
        return redirect()
            ->back()
            ->with('success', 'Обновленно');
    }

    /**
     * Убрать главную.
     *
     * @param WebflowPage $page
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unsetHome(WebflowPage $page)
    {
        $page->main = 0;
        $page->save();
        return redirect()
            ->back()
            ->with('success', 'Обновленно');
    }
}
