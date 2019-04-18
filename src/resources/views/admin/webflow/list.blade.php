@extends('admin.layout')

@section('page-title', 'Webflow список - ')
@section('header-title', 'Список страниц')

@section('admin')
    <div class="col 12">
        <div class="table-responsive mt-3">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Путь</th>
                    <th>Главная</th>
                    <th>Теги</th>
                </tr>
                </thead>
                <tbody>
                @foreach($pages as $page)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <a href="{{ $page->main ? route("home") : route("webflow.page.{$page->slug}") }}"
                               class="btn btn-outline-primary"
                               target="_blank">
                                {{ $page->slug }}
                            </a>
                        </td>
                        <td>
                            @if ($page->main)
                                <form action="{{ route('admin.webflow.unset-home', ['page' => $page]) }}" method="post">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger">
                                        Отключить главную
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.webflow.set-home', ['page' => $page]) }}" method="post">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-dark">
                                        Сделать главной
                                    </button>
                                </form>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.webflow.show', ['page' => $page]) }}"
                               class="btn btn-outline-info">
                                {{ $page->metas->count() }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
