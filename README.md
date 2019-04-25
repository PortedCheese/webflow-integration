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

Что бы заработало меню сайта, нужен элемент nav с атрибутом navigation-menu='navigation-menu'. Если у него будет аттрибут no-bootstrap, то будет разобранно то что внутри, при условии что ссылки будут сразу, а дроп элемет будет дивом. Если не указан элемент no-bootstrap, то будет выведенно меню по бутсрапу при этом можно указать аттрибут theme(bg-{theme}), по умолчанию dark, аттрибут expand (navbar-expand-{expand}), аттрибут side(m{side}-auto)