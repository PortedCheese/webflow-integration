@extends('admin.layout')

@section('page-title', "Webflow $page->slug - ")
@section('header-title', $page->slug)

@section('admin')
    <div class="col-12">
        <a href="{{ $page->main ? route("home") : route("webflow.page.{$page->slug}") }}"
           class="btn btn-dark"
           target="_blank">
            Просмотр
        </a>
    </div>
    <div class="col-12 mt-2">
        <h2>Добавить тег</h2>
        @include("seo-integration::admin.meta.create", ['model' => 'webflow_pages', 'id' => $page->id])
    </div>
    <div class="col-12 mt-2">
        @include("seo-integration::admin.meta.table-models", ['metas' => $page->metas])
    </div>
@endsection
