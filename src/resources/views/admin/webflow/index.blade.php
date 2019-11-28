@extends('admin.layout')

@section('page-title', 'Загрузка шаблона WebFlow - ')
@section('header-title', 'Загрузка шаблона WebFlow')

@section('admin')
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.webflow.load') }}"
                      enctype="multipart/form-data"
                      method="post">
                    @csrf


                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <button type="submit" class="btn btn-success">
                                Загрузить
                            </button>
                        </div>
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
                    </div>
                </form>
            </div>
        </div>
    </div>
    @include("webflow-integration::admin.webflow.doc")
@endsection
