@extends('adminlte::page')

{{-- Extend and customize the browser title --}}

@section('title')
    {{ config('adminlte.title') }}
    @hasSection('subtitle') | @yield('subtitle') @endif
@stop

{{-- Extend and customize the page content header --}}

@section('content_top_nav_right')
    @if(auth()->check())
        <li class="nav-item d-flex align-items-center ml-2">
            <a href="{{ route('tickets.create', ['url' => request()->fullUrl()]) }}"
               class="btn btn-sm btn-warning"
               title="Сообщить о проблеме"
               aria-label="Сообщить о проблеме">
                <i class="fas fa-bug"></i>
                <span class="d-none d-md-inline ml-1">Сообщить о проблеме</span>
            </a>
        </li>
    @endif
@stop

@section('content_header')
    @hasSection('content_header_title')
        <h1 class="text-muted">
            @yield('content_header_title')

            @hasSection('content_header_subtitle')
                <small class="text-dark">
                    <i class="fas fa-xs fa-angle-right text-muted"></i>
                    @yield('content_header_subtitle')
                </small>
            @endif
        </h1>
    @endif
@stop

{{-- Rename section content to content_body --}}

@section('content')
    @yield('content_body')
@stop

{{-- Create a common footer --}}

@section('footer')
{{--    <div class="float-right">--}}
{{--        Version: {{ config('app.version', '1.0.0') }}--}}
{{--    </div>--}}

{{--    <strong>--}}
{{--        <a href="{{ config('app.company_url', '#') }}">--}}
{{--            {{ config('app.company_name', 'My company') }}--}}
{{--        </a>--}}
{{--    </strong>--}}
@stop

{{-- Add common Javascript/Jquery code --}}

@push('js')
    <script>

        $(document).ready(function() {
            // Add your common script logic here...
        });

    </script>

    <!-- Подключаем JS-файл Toastr -->
    <script src="{{ asset('vendor/toastr/toastr.min.js') }}"></script>

    @if(session('success'))
        <script>
            toastr.success("{{ session('success') }}");
        </script>
    @endif

    @if(session('error'))
        <script>
            toastr.error("{{ session('error') }}");
        </script>
    @endif
@endpush

@stack('scripts')

{{-- Add common CSS customizations --}}

@push('css')
    <!-- Подключаем CSS-файл Toastr -->
    <link href="{{ asset('vendor/toastr/toastr.min.css') }}" rel="stylesheet"/>

    <style type="text/css">

        {{-- Растягиваем хедер user-menu под контент: длинное ФИО в 2 строки
             больше не обрезает роль (adminlte_desc) снизу. Селектор повторяет
             adminlte.min.css чтобы перебить специфичность (0,4,1) и отдать
             height:auto. --}}
        .navbar-nav > .user-menu > .dropdown-menu > li.user-header {
            height: auto;
            min-height: 160px;
        }


    </style>
@endpush
