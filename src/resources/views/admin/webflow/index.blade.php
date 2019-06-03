@extends('admin.layout')

@section('page-title', 'Загрузка шаблона WebFlow - ')
@section('header-title', 'Загрузка шаблона WebFlow')

@section('admin')
    <div class="col-12">
        <form action="{{ route('admin.webflow.load') }}"
              enctype="multipart/form-data"
              method="post">
            @csrf

            <div class="custom-file">
                <input type="file"
                       class="custom-file-input{{ $errors->has('file') ? ' is-invalid' : '' }}"
                       id="custom-file-input"
                       lang="ru"
                       name="file"
                       aria-describedby="inputGroupWebflow">
                <label class="custom-file-label"
                       for="custom-file-input">
                    Выберите файл архива
                </label>
                @if ($errors->has('file'))
                    <div class="invalid-feedback">
                        <strong>{{ $errors->first('file') }}</strong>
                    </div>
                @endif
            </div>


            <div class="btn-group mt-2" role="group">
                <button type="submit" class="btn btn-success">
                    Загрузить
                </button>
            </div>
        </form>
    </div>
    <div class="col-12">
        <h2>Что можно сделать:</h2>

        <h4 class="text-danger">
            Должен быть файл index.html
        </h4>

        <h4 class="text-danger">
            Не должно быть скриптов на странице
        </h4>
        <p>
            Если очень нужно добавить скрипт на страинцу, нужно окружить его <code>{{ $push }}</code>, тогда он будет добавлен в конец страницы.
        </p>

        <h4>Embed</h4>
        <p>К элементу <code>w-embed</code> нужно добавить аттрибут <code>blade="blade"</code> что бы содержимое не удалилось</p>

        <h4>Карта</h4>
        <p>
            Должен быть скачан пакет contact-page, он включен если есть страница "Контакты". Что бы добавить карту на страницу <code>{{ $contactMap }}</code>, добавится блок с классом <code>page-map</code> в нем будет карта с метками, заполненными на странице "Контакты".
            <a href="https://tech.yandex.ru/maps/jsapi/doc/2.1/ref/reference/option.presetStorage-docpage/">Список возможных меток</a>
        </p>

        <h4>Меню</h4>
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
@endsection
