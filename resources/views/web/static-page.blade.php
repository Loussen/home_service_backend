@extends('web.layout')

@section('title', 'My Sancho · '.$pageTitle)
@section('page', 'static-page')

@section('content')
<section class="static-page-shell">
    <h1 class="static-page-title">{{ $pageTitle }}</h1>
    <div class="static-page-body prose">
        {!! $pageBody !!}
    </div>
</section>
@endsection
