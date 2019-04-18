<?php

namespace PortedCheese\WebflowIntegration\Http\Services;

use PHPHtmlParser\Dom;
use PortedCheese\SeoIntegration\Models\Meta;

class HtmlParser
{
    /**
     * Настройки парсера.
     */
    const CONFIG = [
        'strict' => false,
        'cleanupInput' => false,
        'removeScripts' => false,
        'removeStyles' => false,
        'whitespaceTextNode' => false,
        'removeDoubleSpace' => false,
    ];

    /**
     * Теги которые не нужны.
     */
    const NOMETA = [
        'names' => [
            'charset',
            'property',
        ],
        'nameValue' => [
            'viewport',
            'generator',
        ],
    ];

    protected $dom;
    protected $head;
    protected $body;

    public function __construct()
    {
        $this->dom = new Dom();
        $this->head = NULL;
        $this->body = NULL;
    }

    /**
     * Разобрать дополнительную страницу.
     *
     * @param $filePath
     * @param $model
     * @return mixed
     */
    public function parsePage($filePath, $model)
    {
        $this->dom->loadFromFile($filePath, self::CONFIG);
        $this->pageMetas($model);
        $this->extendPageBody();
        $html = "Не найден элемент main-section";
        if (!empty($this->body)) {
            $html = $this->body->outerHtml;
        }
        $blade = str_replace(
            '{{html}}',
            $html,
            file_get_contents(__DIR__.'/stubs/views/page.stub')
        );
        return $blade;
    }

    /**
     * Разобрать индексную старницу.
     * @param $dom
     */
    public function parseIndex($filePath)
    {
        $this->dom->loadFromFile($filePath, self::CONFIG);

        $this->extendIndexHead();

        $this->extendIndexBody();

        $html = $this->dom->outerHtml;
        $html = "<!DOCTYPE html>" . $html;
        return $html;
    }

    /**
     * Теги для страницы.
     *
     * @param $page
     */
    private function pageMetas($page)
    {
        if ($page->metas->count()) {
            return;
        }
        $this->head = $this->dom->find('head');
        $this->createTitleMeta($page);
        // Обойти все мета.
        $metas = $this->head->find("meta");
        foreach ($metas as $meta) {
            $tag = $meta->getTag();
            $attributes = $tag->getAttributes();
            $noMeta = false;
            foreach ($attributes as $key => $value) {
                if (in_array($key, self::NOMETA['names'])) {
                    $noMeta = true;
                    break;
                }
                if ($key == 'name' && !empty($value['value'])) {
                    if (in_array($value['value'], self::NOMETA['nameValue'])) {
                        $noMeta = true;
                        break;
                    }
                }
            }
            if ($noMeta) {
                continue;
            }
            $this->createOtherMeta($page, $attributes);
        }
    }

    /**
     * Создать тег.
     *
     * @param $page
     * @param $attributes
     */
    private function createOtherMeta($page, $attributes)
    {
        if (
            empty($attributes['name']) ||
            empty($attributes['content'])
        ) {
            return;
        }
        $data = [
            'name' => $attributes['name']['value'],
            'content' => $attributes['content']['value'],
        ];
        $result = Meta::getModel('webflow_pages', $page->id, $data['name']);
        if (!$result['success']) {
            return;
        }
        $meta = Meta::create($data);
        $meta->metable()->associate($page);
        $meta->save();
    }

    /**
     * Создать тег заголовка.
     */
    private function createTitleMeta($page)
    {
        $title = $this->head->find('title')[0];
        if (empty($title)) {
            return;
        }
        $tag = $title->getTag();
        $result = Meta::getModel('webflow_pages', $page->id, "title");
        if (!$result['success']) {
            return;
        }
        $meta = Meta::create([
            'name' => 'title',
            'content' => $title->innerHtml,
        ]);
        $meta->metable()->associate($page);
        $meta->save();
    }

    /**
     * Меняем страницу.
     */
    private function extendPageBody()
    {
        $body = $this->dom->find('body');
        $this->body = $body->find("[main-section='main-section]")[0];
        if (empty($this->body)) {
            return;
        }
        $this->changeImages();
        $this->deleteIncludes();
    }

    /**
     * Меняем head.
     */
    private function extendIndexHead()
    {
        $this->head = $this->dom->find('head');
        $this->chageStyles();
        $this->changeMeta();
    }

    /**
     * Меняем мета.
     */
    private function changeMeta()
    {
        // Мета по умолчанию.
        $content = new Dom();
        $content->loadStr("@include('webflow-integration::layouts.webflow.meta-default')");
        $metaDefault = $content->root;
        // Обойти все мета.
        $metas = $this->head->find("meta");
        $main = true;
        // Когда обрабатываем индекс, нам не нужны никакие мета из тех что там есть.
        foreach ($metas as $meta) {
            if ($main) {
                $this->head->insertBefore($metaDefault, $meta->id());
                $main = false;
            }
            $meta->delete();
        }
        // Удаляем заголовок, в шаблоне он есть нормальный.
        $title = $this->head->find("title");
        foreach ($title as $item) {
            $item->delete();
        }
    }

    /**
     * Переделываем стили.
     */
    private function chageStyles()
    {
        // Создаем новый элемент для подключения стилей.
        $element = new Dom();
        $element->loadStr('<link href="{{ asset(\'css/app.css\') }}" rel="stylesheet">');
        $link = $element->root;
        // Обходим все стили которые есть.
        $styles = $this->head->find("link[rel='stylesheet']");
        $last = count($styles);
        $i = 0;
        $main = true;
        foreach ($styles as $item) {
            // До первого стиля добавляем свой стиль.
            if ($main) {
                $this->head->insertBefore($link, $item->id());
                $main = false;
            }
            // Меняем местоположение стиля.
            $tag = $item->getTag();
            $value = $tag->getAttribute('href')['value'];
            // если это не сторонний стиль.
            if (
                strrpos($value, 'http:://') === FALSE &&
                strripos($value, 'https://') === FALSE
            ) {
                $tag->setAttribute('href', "{{ asset('webflow/{$value}') }}");
            }
            $css = $value;
            // Для последнего добавляем доп. стили.
            if (++$i === $last) {
                $content = new Dom();
                $content->loadStr("@stack('more-css')");
                $this->head->insertAfter($content->root, $item->id());
            }
        }
    }

    /**
     * Меняем body.
     */
    private function extendIndexBody()
    {
        $this->body = $this->dom->find('body');
        $this->changeContent();
        $this->changeImages();
        $this->changeJs();
    }

    /**
     * Расширяем контент.
     */
    private function changeContent()
    {
        // Удаляем все что есть в main-section.
        $mainSection = $this->body->find("[main-section='main-section]")[0];
        if (empty($mainSection)) {
            return;
        }
        foreach ($mainSection->find('*') as $item) {
            $item->delete();
        }
        // Добавляем секцию.
        $content = new Dom();
        $content->loadStr("@yield('content')");
        $mainSection->addChild($content->root);
        $this->deleteIncludes();
    }

    /**
     * Расширения для гиса.
     */
    private function deleteIncludes()
    {
        $embed = $this->body->find(".w-embed");
        foreach ($embed as $item) {
            $item->delete();
        }
    }

    /**
     * Изменить ссылки у изображений.
     */
    private function changeImages()
    {
        $images = $this->body->find("img[src*='images\/']");
        foreach ($images as $image) {
            $value = $image->getAttribute('src');
            $value = str_replace("images/", "webflow/images/", $value);
            $image->getTag()->setAttribute('src', $value);

            $value = $image->getAttribute('srcset');
            $value = str_replace("images/", "webflow/images/", $value);
            $image->getTag()->setAttribute('srcset', $value);
        }
    }

    /**
     * Заменить js.
     */
    private function changeJs()
    {
        // Создаем новый элемент для подключения js.
        $element = new Dom();
        $element->loadStr('<script src="{{ asset(\'js/app.js\') }}" defer></script>');
        $script = $element->root;
        // Обходим все js которые есть.
        $scripts = $this->body->find("script[type='text/javascript']");
        $last = count($scripts);
        $i = 0;
        $main = true;
        foreach ($scripts as $item) {
            // До первого стиля добавляем свой скрипт.
            if ($main) {
                $this->body->insertBefore($script, $item->id());
                $main = false;
            }
            // Меняем местоположение стиля.
            $tag = $item->getTag();
            $value = $tag->getAttribute('src')['value'];
            // если это не сторонний стиль.
            if (
                strrpos($value, 'http:://') === FALSE &&
                strripos($value, 'https://') === FALSE
            ) {
                $tag->setAttribute('src', "{{ asset('webflow/{$value}') }}");
            }
            $css = $value;
            // Для последнего добавляем доп. стили.
            if (++$i === $last) {
                $content = new Dom();
                $content->loadStr("@stack('more-js')");
                $this->head->insertAfter($content->root, $item->id());
            }
        }
    }
}