@extends('layouts.shop')

@section('content')
    @forelse ($sections as $section)
        @includeIf('shop.sections.' . $section->key, ['section' => $section, 'pool' => $pool, 'featured' => $featured, 'offers' => $offers])
    @empty
        {{-- No front page set up yet: show the sarees rather than nothing. --}}
        @include('shop.sections.collection', ['section' => null, 'pool' => $pool])
    @endforelse
@endsection
