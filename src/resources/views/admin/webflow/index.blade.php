@extends('admin.layout')

@section('page-title', 'Webflow - ')
@section('header-title', 'Webflow')

@section('admin')
    <div class="col-4">
        <form action="{{ route('admin.webflow.load') }}"
              enctype="multipart/form-data"
              method="post">
            @csrf
            <div class="form-group">
                <label for="file">Файл</label>
                <input type="file"
                       class="form-control-file{{ $errors->has('file') ? ' is-invalid' : '' }}"
                       id="file"
                       name="file">
                @if ($errors->has('file'))
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $errors->first('file') }}</strong>
                    </span>
                @endif
            </div>

            <div class="btn-group" role="group">
                <button type="submit" class="btn btn-success">
                    Загрузить
                </button>
            </div>
        </form>
    </div>
@endsection
