# WebFlow integration

## Установка

`composer require portedcheese/webflow-integration`

## Настройка

Пройтись по процессу установки
[Base |](https://packagist.org/packages/portedcheese/base-settings)
[Seo |](https://packagist.org/packages/portedcheese/seo-integration)
[Ajax forms |](https://packagist.org/packages/portedcheese/ajax-forms)
[Contact](https://packagist.org/packages/portedcheese/contact-page)

    php artisan migrate
    
    php artisan make:webflow

## Описание

Добавляет на страницы с шаблоном WebFlow меню сайта (main)

Что бы сделать вывод главной, можно в контроллер главной страницы дописать:

    if (class_exists(WebflowPage::class)) {
        $page = WebflowPage::getHomePage();
        if (!empty($page)) {
            return $page;
        }
    }

Меню для админки

`@includeIf("webflow-integration::admin.webflow.menu")`

На странице должен быть блок с аттрибутом `main-section="main-section"`

Что бы заработало меню сайта, нужен элемент nav с атрибутом `navigation-menu='navigation-menu'`.
