@isset($webflowMenu)
    <button class="navbar-toggler"
            type="button"
            data-toggle="collapse"
            data-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent"
            aria-expanded="false"
            aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav m{{ $side }}-auto">
            @includeIf('admin-site-menu::layouts.index', ['menu' => $webflowMenu])
        </ul>
    </div>
@endisset