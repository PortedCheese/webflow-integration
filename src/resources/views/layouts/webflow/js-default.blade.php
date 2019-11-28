<!-- APP JS -->
<script src="{{ asset('js/app.js') }}?{{ siteconf()->get("base-settings", "frontendDate", "") }}" type="text/javascript"></script>
{{--<script src="{{ asset('js/form.js') }}" type="text/javascript"></script>--}}
@stack('more-scripts')