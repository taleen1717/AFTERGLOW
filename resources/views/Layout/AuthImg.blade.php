@extends('Layout.app')

@section('content')
<div class="grid h-full grid-cols-1 md:grid-cols-2">

    @yield('form')

    <div class="hidden md:block">
        <div class="mt-20 hidden md:flex md:items-center md:justify-center">
            <img
                src="{{ asset('images/signup.jpeg') }}"
                alt="Authentication"
                class="w-full max-w-sm rounded-2xl object-contain">
        </div>
    </div>

</div>
@endsection