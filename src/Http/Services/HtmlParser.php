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
    const NO_META = [
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
    protected $menu;

    public function __construct()
    {
        $this->dom = new Dom();
        $this->head = NULL;
        $this->body = NULL;
        $this->menu = [];
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
     *
     * @param $filePath
     * @return array
     */
    public function parseIndex($filePath)
    {
        $this->dom->loadFromFile($filePath, self::CONFIG);

        $this->extendIndexHead();

        $this->extendIndexBody();

        $html = $this->dom->outerHtml;
        // Окружить body #app для VueJs.
        $html = str_replace(
            ["<body>", "VueJsReplace"],
            ["<body><div id='app'>", "</div>"],
            $html
        );
        return [$html, $this->menu];
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
                if (in_array($key, self::NO_META['names'])) {
                    $noMeta = true;
                    break;
                }
                if ($key == 'name' && !empty($value['value'])) {
                    if (in_array($value['value'], self::NO_META['nameValue'])) {
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
        $this->changeStyles();
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
    private function changeStyles()
    {
        // Обходим все стили которые есть.
        $styles = $this->head->find("link[rel='stylesheet']");
        $last = count($styles);
        $i = 0;
        $main = true;
        foreach ($styles as $item) {
            // До первого стиля добавляем свой стиль.
            if ($main) {
                // CSS по умолчанию.
                $element = new Dom();
                $element->loadStr("@include('webflow-integration::layouts.webflow.css-default')");
                $this->head->insertBefore($element->root, $item->id());
                $main = false;
            }
            // Меняем местоположение стиля (путь на сервере).
            $tag = $item->getTag();
            $value = $tag->getAttribute('href')['value'];
            // если это не сторонний стиль.
            if (
                strrpos($value, 'http:://') === FALSE &&
                strripos($value, 'https://') === FALSE
            ) {
                $tag->setAttribute('href', "{{ asset('webflow/{$value}') }}");
            }
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
        $this->changeMenu();
        $this->changeContent();
        $this->changeImages();
        $this->changeJs();
    }

    /**
     * Меню сайта.
     */
    private function changeMenu()
    {
        $navSection = $this->body->find("[navigation-menu='navigation-menu']")[0];
        if (empty($navSection)) {
            return;
        }
        $tag = $navSection->getTag();
        $attributes = $tag->getAttributes();
        if (!empty($attributes['no-bootstrap'])) {
            $this->noBootstrap($navSection);
        }
        else {
            $theme = !empty($attributes['theme']) ? $attributes['theme'] : "dark";
            $expand = !empty($attributes['expand']) ? $attributes['expand'] : "lg";
            $side = !empty($attributes['side']) ? $attributes['side'] : 'l';
            $tag->setAttribute('class', "navbar navbar-expand-{$expand} navbar-{$theme}");
            foreach ($navSection->find("*") as $item) {
                $item->delete();
            }
            // Добавляем секцию.
            $content = new Dom();
            $content->loadStr("@include('webflow-integration::layouts.webflow.menu-default', [
                'side' => '{$side}'
            ])");
            $navSection->addChild($content->root);
            $button = $this->body->find('.w-nav-button')[0];
            if (!empty($button)) {
                $button->delete();
            }
        }
    }

    /**
     * Если меню без бутстрапа.
     *
     * @param $navSection
     */
    private function noBootstrap($navSection)
    {
        $linkClasses = [];
        $dropClasses = [];
        foreach ($navSection->find('*') as $item) {
            $tag = $item->getTag();
            $name = $tag->name();
            if ($name == 'a') {
                $this->findLinkClasses($tag, $linkClasses);
            }
            else {
                $this->findDropdownClasses($item, $dropClasses);
            }
            $item->delete();
        }
        $dropClasses['links'] = implode(" ", $linkClasses);

        // Добавляем секцию.
        $content = new Dom();
        $content->loadStr("@includeIf('layouts.webflow.menu')");
        $navSection->addChild($content->root);
        $this->menu = $dropClasses;
    }

    /**
     * Классы для выпадающего меню.
     *
     * @param $item
     * @param $dropClasses
     */
    private function findDropdownClasses($item, &$dropClasses)
    {
        $tag = $item->getTag();
        $classes = $tag->getAttribute('class');
        if (empty($classes['value'])) {
            return;
        }
        $dropClasses['cover'] = $classes['value'];
        $children = $item->find("*");
        foreach ($children as $child) {
            $tag = $child->getTag();
            $classes = $tag->getAttribute('class');
            if (empty($classes['value'])) {
                continue;
            }
            if ($tag->name() == 'div') {
                $dropClasses['button'] = $classes['value'];
                // TODO: icon, text.
            }
            else {
                $dropClasses['nav'] = $classes['value'];
                $linkClasses = [];
                foreach ($child->find("*") as $item) {
                    $childTag = $item->getTag();
                    if ($childTag->name() == 'a') {
                        $this->findLinkClasses($childTag, $linkClasses);
                    }
                }
                $dropClasses['navLink'] = implode(" ", $linkClasses);
            }
        }
    }

    /**
     * Класс для ссылки.
     *
     * @param $classes
     * @param $linkClasses
     */
    private function findLinkClasses($tag, &$linkClasses)
    {
        $classes = $tag->getAttribute('class');
        if (empty($classes['value'])) {
            return;
        }
        $classes = $classes['value'];
        $exploded = explode(" ", $classes);
        if (empty($linkClasses)) {
            $linkClasses = $exploded;
            return;
        }
        $linkClasses = array_intersect($linkClasses, $exploded);
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
            $value = str_replace("images/", "/webflow/images/", $value);
            $image->getTag()->setAttribute('src', $value);

            $value = $image->getAttribute('srcset');
            $value = str_replace("images/", "/webflow/images/", $value);
            $image->getTag()->setAttribute('srcset', $value);
        }
    }

    /**
     * Заменить js.
     */
    private function changeJs()
    {
        // Обходим все js которые есть.
        $scripts = $this->body->find("script[type='text/javascript']");

        // Добавляем фразу что бы потом заменить ее на закрывающий div.
        $content = new Dom();
        $content->loadStr("VueJsReplace");
        $vue = $content->root;
        if (empty($scripts)) {
            $this->body->addChild($vue);
        }
        else {
            $this->body->insertBefore($vue, $scripts[0]->id());
        }

        // Js по умолчанию.
        $content = new Dom();
        $content->loadStr("@include('webflow-integration::layouts.webflow.js-default')");
        $jsDefault = $content->root;
        if (!empty($scripts)) {
            $this->body->insertAfter($jsDefault, $vue->id());
        }
        else {
            $this->body->addChild($jsDefault);
        }

        foreach ($scripts as $item) {
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
        }
    }
}