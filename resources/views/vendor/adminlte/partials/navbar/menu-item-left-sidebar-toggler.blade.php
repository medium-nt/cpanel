<li class="nav-item">
    <a class="nav-link" data-widget="pushmenu" href="#"
        @if(config('adminlte.sidebar_collapse_remember'))
            data-enable-remember="true"
        @endif
        @if(!config('adminlte.sidebar_collapse_remember_no_transition'))
            data-no-transition-after-reload="false"
        @endif
        @if(config('adminlte.sidebar_collapse_auto_size'))
            data-auto-collapse-size="{{ config('adminlte.sidebar_collapse_auto_size') }}"
        @endif>
        <i class="fas fa-bars"></i>
        <span class="sr-only">{{ __('adminlte::adminlte.toggle_navigation') }}</span>
    </a>
</li>

{{--само модальное окно в файле page.blade.php--}}
<li class="nav-item">
    <a class="btn btn-outline-secondary" href="#"
       data-toggle="modal" data-target="#barcodeModal" data-widget="control-sidebar">
        <i class="fas fa-barcode"></i>
    </a>
</li>
