@extends('layouts.shop')

@section('title', 'Your bag — ' . \App\Support\Shop::name())

@section('content')
    @livewire('bag')
@endsection
