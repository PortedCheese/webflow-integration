<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- CSRF Token -->
<meta name="csrf-token" content="{{ csrf_token() }}">

@section('tag-title')
    @empty($pageMetas['title'])
        <title>@yield('page-title'){{ config('app.name', 'Laravel') }}</title>
    @endempty
    @empty($pageMetas['og:title'])
        <meta content="@yield('page-title'){{ config('app.name', 'Laravel') }}" property="og:title" >
    @endempty
@show

@isset($pageMetas)
    @foreach($pageMetas as $key => $meta)
        {!! $meta !!}
    @endforeach
@endisset

{{--<link href="{{ asset("favicon.png") }}" rel="apple-touch-icon" type="image/x-icon">--}}
<link href="{{ asset("favicon.ico") }}" rel="shortcut icon" type="image/x-icon">