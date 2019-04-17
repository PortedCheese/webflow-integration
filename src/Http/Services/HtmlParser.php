<?php

namespace PortedCheese\WebflowIntegration\Http\Services;

use PHPHtmlParser\Dom;

class HtmlParser
{
    public function __construct()
    {
        $this->dom = NULL;
        $this->head = NULL;
        $this->body = NULL;
    }

    public function parseIndex($dom)
    {
        $this->dom = $dom;

        $this->extendHead();

        $this->extendBody();

        $this->saveIndexFile();
    }

    /**
     * Меняем head.
     */
    private function extendHead()
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
        foreach ($metas as $meta) {
            if ($main) {
                $this->head->insertBefore($metaDefault, $meta->id());
                $main = false;
            }
            $meta->delete();
        }
        // Удаляем заголовок.
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
    private function extendBody()
    {
        $this->body = $this->dom->find('body');
        $this->changeContent();
        $this->changeImages();
        $this->changeJs();
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
            // До первого стиля добавляем свой стиль.
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

    /**
     * Расширяем контент.
     */
    private function changeContent()
    {
        // Удаляем все что есть в main-section.
        $mainSection = $this->body->find("[main-section='main-section]")[0];
        foreach ($mainSection->find('*') as $item) {
            $item->delete();
        }
        // Добавляем секцию.
        $content = new Dom();
        $content->loadStr("@yield('content')");
        $mainSection->addChild($content->root);
        // Расширения.
        $embed = $this->body->find(".w-embed");
        foreach ($embed as $item) {
            $item->delete();
        }
    }

    /**
     * Сохраняем файл.
     * @param $html
     */
    private function saveIndexFile()
    {
        $html = $this->dom->outerHtml;
        $html = "<!DOCTYPE html>" . $html;
//        $pattern = "/{{(#|\/)(.*?)}}/";
//        $html = preg_replace($pattern, '', $html);
        $file = fopen(base_path("resources/views/webflow/index.blade.php"), 'w');
        fwrite($file, $html);
        fclose($file);
    }
}