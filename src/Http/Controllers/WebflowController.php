<?php

namespace PortedCheese\WebflowIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use PHPHtmlParser\Dom;
use PortedCheese\SeoIntegration\Models\Meta;
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
//        $this->fileManager->runParser();
        return view('webflow-integration::admin.webflow.index');
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

    /**
     * Загрузка архива.
     *
     * @param Request $request
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
        $this->fileManager->unzip($realPath);
        Storage::delete($path);
        
        $this->fileManager->runParser();

        return redirect()
            ->back()
            ->with('success', 'Загружено');
    }
}
