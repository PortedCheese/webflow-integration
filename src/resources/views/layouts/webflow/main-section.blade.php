@hasSection('breadcrumb')
    <div class="row">
        @yield('breadcrumb')
    </div>
@endif
@hasSection('content')
    <div class="row my-5">
        @hasSection('sidebar')
            <aside class="col-3">
                <div class="row">
                    @yield('sidebar')
                </div>
            </aside>
            <section class="col-9">
                <div class="row">
                    @include('layouts.content')
                </div>
            </section>
        @else
            <section class="col-12">
                <div class="row">
                    @include('layouts.content')
                </div>
            </section>
        @endif
    </div>
@endif
@hasSection('rowContent')
    @yield('rowContent')
@endif