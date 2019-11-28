<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">DOCS:</h5>
        </div>
        <div class="card-body">
            <h5 class="text-danger">
                Должен быть файл index.html
            </h5>

            <h5 class="text-danger">
                Не должно быть скриптов на странице
            </h5>
            <p class="description">
                Если очень нужно добавить скрипт на страинцу, нужно окружить его <code>{{ $push }}</code>, тогда он будет добавлен в конец страницы.
            </p>
            <p class="text-info">Если нужно загрузить фав иконку, пока что сказать мне</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="embed-tab" data-toggle="tab" href="#embed" role="tab" aria-controls="embed" aria-selected="true">Embed</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" id="forms-tab" data-toggle="tab" href="#forms" role="tab" aria-controls="profile" aria-selected="false">Формы</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" id="map-tab" data-toggle="tab" href="#map" role="tab" aria-controls="map" aria-selected="false">Карта</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" id="menu-tab" data-toggle="tab" href="#menu" role="tab" aria-controls="menu" aria-selected="false">Меню</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="embed" role="tabpanel" aria-labelledby="embed-tab">
                    <p>
                        К элементу <code>w-embed</code> нужно добавить аттрибут <code>blade="blade"</code> что бы содержимое не удалилось
                    </p>
                    <p>
                        <b class="text-danger">А лучше вообще не использовать embed</b>
                    </p>
                </div>

                <div class="tab-pane fade" id="forms" role="tabpanel" aria-labelledby="forms-tab">
                    <p class="text-danger">
                        Для форм нужно добавлять checkbox политики <code>name="privacy_policy"</code> что бы пропускало отправку
                    </p>
                    <p>
                        Имя у формы берется либо из аттрибута <code>data-name</code> либо из <code>name</code>
                    </p>
                    <p>Если не получается поменять имя у инпута, можно дописать <code>data-change-name="{name}"</code> и он заменит имя</p>
                    <p>
                        Что бы добавить в форму файл <code>{{ $formFile }}</code>
                    </p>
                    <p>
                        Что бы сделать простую защиту формы нужно добавить <code>{{ $hiddenCaptcha }}</code>, добавит скрытое поле, которое роботы будут заполнять и их не будет пускать
                        <br>
                        Если нужно усилить защиту( если роботы обходят скрытое поле), то есть <code>{{ $googleCaptcha }}</code>, но ее нужно настроить
                    </p>
                </div>

                <div class="tab-pane fade" id="map" role="tabpanel" aria-labelledby="map-tab">
                    <p>
                        Что бы добавить карту на страницу <code>{{ $contactMap }}</code>
                        <br>
                        <em class="text-danger">(если написать без параметров будут поставлены те что указаны, при этом если указать параметр preset будут игнорироваться настройки иконки для каждой метки)</em>,
                        <br>
                        добавится блок с id <code>page-map</code> в нем будет карта с метками, заполненными на странице "Контакты".
                        <br>
                        <a href="https://tech.yandex.ru/maps/jsapi/doc/2.1/ref/reference/option.presetStorage-docpage/">Список возможных меток</a>
                    </p>
                </div>

                <div class="tab-pane fade" id="menu" role="tabpanel" aria-labelledby="menu-tab">
                    <ul>
                        <li>
                            Секция с аттрибутом <code>navigation-menu='navigation-menu'</code>
                        </li>
                        <li>
                            По умолчанию подключается меню <a href="https://getbootstrap.com/docs/4.1/components/navbar/">бутстрапа</a>, в него можно передать аттрибуты
                            <ul>
                                <li>
                                    <code>nav-theme</code> - формирует класс <code>navbar-{nav-theme}</code>, по умолчанию <code>navbar-light</code>
                                </li>
                                <li>
                                    <code>nav-bg</code> - формирует класс <code>bg-{nav-bg}</code>, по умолчанию <code>bg-light</code>
                                </li>
                                <li>
                                    <code>nav-bg-color</code> - можно установить цвет фона, либо через <code>rgb(255, 255, 255)</code>, либо <code>#f8f9fa</code>
                                    <br>
                                    <span class="text-info">если использовать этот аттрибут, то <code>nav-bg</code> игнорируется</span>
                                </li>
                                <li>
                                    <code>nav-expand</code> - формирует класс <code>navbar-expand-{nav-expand}</code>, по умолчанию <code>navbar-expand-lg</code>
                                </li>
                                <li>
                                    <code>nav-side</code> - формирует выравнивание меню <code>m{nav-side}-auto</code>, по умолчаию <code>ml-auto</code>
                                </li>
                                <li>
                                    <span class="text-info">Все что внутри элемента удаляется</span>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <code>no-bootstrap</code> - если поставить этот аттрибут, то приложение будет обходить все элементы меню <b>(вложенность не учитывается)</b>
                            <ol>
                                <li>Будет искать <code>a</code> элементы считая их обычными ссылками и собирать одинаковые классы</li>
                                <li>
                                    Будет искать <code>div</code> элементы считая их выпадющими списками и собирать одинаковые классы у них.
                                    <ul>
                                        <li>Будет искать <code>div</code> считая его текстом кнопки</li>
                                    </ul>
                                </li>
                            </ol>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>