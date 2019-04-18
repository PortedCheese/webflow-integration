# WebFlow integration

Что бы сделать вывод главной, можно в контроллер главной страницы дописать:

`
if (class_exists(WebflowPage::class)) {
    $page = WebflowPage::getHomePage();
    if (!empty($page)) {
        return $page;
    }
}
`

Меню для админки

`@includeIf("webflow-integration::admin.webflow.menu")`