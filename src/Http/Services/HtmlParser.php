<?php

namespace PortedCheese\WebflowIntegration\Http\Services;

use Illuminate\Support\Facades\Log;
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
    protected $comments;

    public function __construct()
    {
        $this->dom = new Dom();
        $this->head = NULL;
        $this->body = NULL;
        $this->menu = [];
        $this->comments = [];
    }

    /**
     * Разобрать дополнительную страницу.
     *
     * @param $filePath
     * @param $model
     * @return mixed
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     */
    public function parsePage($filePath, $model)
    {
        $content = file_get_contents($filePath);
        $this->removeFrame($content);
        $this->fixQuot($content);

        $this->dom->loadStr($content, self::CONFIG);

        $this->pageMetas($model);
        $this->extendPageBody();
        $html = "Не найден элемент main-section";
        if (!empty($this->body)) {
            $html = $this->body->outerHtml;
            $this->removeFrame($html, true);
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
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     */
    public function parseIndex($filePath)
    {
        $content = file_get_contents($filePath);
        $content = str_replace("<!DOCTYPE html>", "doctype", $content);
        $this->removeComments($content);
        $this->removeFrame($content);
        $this->fixQuot($content);

        $this->dom->loadStr($content, self::CONFIG);

        $this->extendIndexHead();

        $this->extendIndexBody();

        $html = $this->dom->outerHtml;
        $this->removeFrame($html, true);
        $this->removeComments($html, true);
        // Окружить body #app для VueJs.
        $html = str_replace(
            ["VueJsReplaceBegin", "VueJsReplace"],
            ["<div id='app'>", "</div>"],
            $html
        );
        $html = str_replace("doctype", "<!doctype html>", $html);
        return [$html, $this->menu];
    }

    /**
     * Удалить комментарии.
     * 
     * @param $html
     * @param bool $restore
     */
    private function removeComments(&$html, $restore = false)
    {
        if (! $restore) {
            $this->comments = [];
            // Убрать все коменты.
            $matches = [];
            preg_match_all("<!--(.*?)-->", $html, $matches);
            if (!empty($matches[0])) {
                foreach ($matches[0] as $match) {
                    $forReplace = "<$match>";
                    $this->comments[] = $forReplace;
                    $count = count($this->comments) - 1;
                    $html = str_replace($forReplace, "replacedComment$count", $html);
                }
            }
        }
        elseif (! empty($this->comments)) {
            foreach ($this->comments as $key => $comment) {
                $html = str_replace("replacedComment$key", $comment, $html);
            }
            $this->comments = [];
        }
    }

    /**
     * Заменить iframe.
     *
     * @param $html
     * @param bool $restore
     */
    private function removeFrame(&$html, $restore = false)
    {
        if (! $restore) {
            // Убрать iframe, потому что тег может быть в json,
            // и парсер воспринимает это как отдельный тэг, а не часть строки.
            $html = str_replace(
                ['<iframe class=', "<iframe src=", "></iframe>"],
                ["replaceFrame", "replaceSrcFrame", "replaceCloseFrame"],
                $html
            );
        }
        else {
            // Вернуть iframe.
            $html = str_replace(
                ["replaceFrame", "replaceSrcFrame", "replaceCloseFrame"],
                ['<iframe class=', "<iframe src=", "></iframe>"],
                $html
            );
        }
    }

    /**
     * Перезаписать кавычки.
     *
     * @param $html
     */
    private function fixQuot(&$html)
    {
        $html = str_replace(
            ["&quot;", "&#x27;", "&gt;"],
            ["\"", "'", ">"],
            $html
        );
    }

    /**
     * Теги для страницы.
     *
     * @param $page
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
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
        if (empty($this->head->find('title')[0])) {
            return;
        }
        $title = $this->head->find('title')[0];
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
     * Меняем head.
     */
    private function extendIndexHead()
    {
        $this->head = $this->dom->find('head');
        $this->changeStyles();
        $this->changeMeta();
    }

    /**
     * Меняем body.
     */
    private function extendIndexBody()
    {
        $this->body = $this->dom->find('body');
        $this->changeMenu();
        $this->changeContent();

        $this->changeJsonScripts();
        $this->changeJs();

        $this->changeImages();
        $this->changeDocumentsLinks();
        $this->changeLinksHtmlHref();
        $this->changeInputNames();

        $this->deleteIncludes();
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
        $this->changeJsonScripts();

        $this->changeImages();
        $this->changeDocumentsLinks();
        $this->changeLinksHtmlHref();
        $this->changeInputNames();

        $this->deleteIncludes();
    }

    /**
     * Изменить имена инпутов.
     */
    private function changeInputNames()
    {
        $inputs = $this->body->find("input[data-change-name]");
        foreach ($inputs as $input) {
            $value = $input->getAttribute("data-change-name");
            $input->getTag()->setAttribute('name', $value);
        }
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
        $content = new Dom();
        $content->loadStr("@stack('more-meta')");
        $metaMore = $content->root;
        // Обойти все мета.
        $metas = $this->head->find("meta");
        $main = true;
        // Когда обрабатываем индекс, нам не нужны никакие мета из тех что там есть.
        foreach ($metas as $meta) {
            if ($main) {
                $this->head->insertBefore($metaDefault, $meta->id());
                $this->head->insertBefore($metaMore, $meta->id());
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
        // Удалить иконки.
        $icon = $this->head->find("link[rel='shortcut icon']");
        foreach ($icon as $item) {
            $item->delete();
        }
        $icon = $this->head->find("link[rel='apple-touch-icon']");
        foreach ($icon as $item) {
            $item->delete();
        }
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
                // Добавляем библиотеки js.
                $element = new Dom();
                $element->loadStr("@stack('js-lib')");
                $this->head->insertAfter($element->root, $item->id());

                $content = new Dom();
                $content->loadStr("@stack('more-css')");
                $this->head->insertAfter($content->root, $item->id());
            }
        }
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
        $content->loadStr("@include('base-settings::layouts.main-section')");
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
            if (empty($item->getAttribute('blade'))) {
                $item->delete();
            }
        }
        $embed = $this->body->find("._w-embed");
        foreach ($embed as $item) {
            $item->delete();
        }
    }

    /**
     * Исправить ссылки на документы.
     */
    private function changeDocumentsLinks()
    {
        $links = $this->body->find("a[href*='documents\/']");
        foreach ($links as $link) {
            $value = $link->getAttribute('href');
            $value = str_replace("documents/", "/webflow/documents/", $value);
            $link->getTag()->setAttribute('href', $value);
        }
    }

    /**
     * Удалить html из ссылок.
     */
    private function changeLinksHtmlHref()
    {
        $links = $this->body->find("a[href*=.html]");
        foreach ($links as $link) {
            $value = $link->getAttribute('href');
            if ($value == "index.html") {
                $value = "/";
            }
            else {
                $value = str_replace(".html", "", $value);
            }
            $link->getTag()->setAttribute('href', $value);
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

        // Добавляем фразу что бы потом заменить ее на открывающий div.
        if (! empty($this->body->firstChild())) {
            $content = new Dom();
            $content->loadStr("VueJsReplaceBegin");
            $vue = $content->root;
            $this->body->insertBefore($vue, $this->body->firstChild()->id());

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

    /**
     * Изменить скрипты лайтбокса.
     */
    private function changeJsonScripts()
    {
        $json = $this->body->find('script.w-json');
        if (count($json)) {
            foreach ($json as $element) {
                foreach ($element->getChildren() as &$child) {
                    $decoded = json_decode($child->text, true);
                    if (!empty($decoded['items'])) {
                        foreach ($decoded['items'] as &$item) {
                            if (! empty($item['url']) && !empty($item['type']) && $item['type'] == 'image') {
                                $item['url'] = "webflow/" . $item['url'];
                            }
                        }
                        $child->setText(json_encode($decoded));
                    }
                }
            }
        }
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
            foreach ($navSection->find("*") as $item) {
                $item->delete();
            }

            $class = ["navbar"];

            if (! empty($attributes['nav-theme']['value'])) {
                $value = $attributes['nav-theme']['value'];
                $class[] = "navbar-$value";
            }
            else {
                $class[] = "navbar-light";
            }

            if (! empty($attributes['nav-bg-color']['value'])) {
                $value = $attributes['nav-bg-color']['value'];
                $tag->setAttribute('style', "background-color: $value;");
            }
            elseif (! empty($attributes['nav-bg']['value'])) {
                $value = empty($attributes['nav-bg']['value']);
                $class[] = "bg-$value";
            }
            else {
                $class[] = "bg-light";
            }

            if (! empty($attributes['nav-expand']['value'])) {
                $value = $attributes['nav-expand']['value'];
                $class[] = "navbar-expand-$value";
            }
            else {
                $class[] = "navbar-expand-lg";
            }
            $tag->setAttribute('class', implode(' ', $class));

            $side = !empty($attributes['nav-side']['value']) ? $attributes['nav-side']['value'] : 'l';
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
        $dropClasses = [
            'cover' => [],
            'button' => [],
            'nav' => [],
            'navLink' => [],
        ];
        foreach ($navSection->find('*') as $item) {
            $tag = $item->getTag();
            $name = $tag->name();
            if ($name == 'a') {
                $this->findLinkClasses($tag, $linkClasses);
            }
            elseif ($name == 'div') {
                $this->findDropdownClasses($item, $dropClasses);
            }
            $item->delete();
        }
        foreach ($dropClasses as &$dropClass) {
            if (is_array($dropClass)) {
                $dropClass = implode(" ", $dropClass);
            }
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
        $this->intersectClasses($dropClasses['cover'], $classes['value']);
        $children = $item->find("*");
        foreach ($children as $child) {
            $tag = $child->getTag();
            $classes = $tag->getAttribute('class');
            if (empty($classes['value'])) {
                continue;
            }
            if ($tag->name() == 'div') {
                $this->intersectClasses($dropClasses['button'], $classes['value']);
                // TODO: icon, text.
            }
            elseif ($tag->name() == 'nav') {
                $this->intersectClasses($dropClasses['nav'], $classes['value']);
                $linkClasses = [];
                foreach ($child->find("*") as $item) {
                    $childTag = $item->getTag();
                    if ($childTag->name() == 'a') {
                        $this->findLinkClasses($childTag, $linkClasses);
                    }
                }
                $linkClasses = implode(" ", $linkClasses);
                $this->intersectClasses($dropClasses['navLink'], $linkClasses);
            }
        }
    }

    /**
     * Найти уникальные класссы.
     *
     * @param $array
     * @param $str
     */
    private function intersectClasses(&$array, $str)
    {
        $exploded = explode(" ", $str);
        if (empty($array)) {
            $array = $exploded;
        }
        else {
            $array = array_intersect($array, $exploded);
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
}