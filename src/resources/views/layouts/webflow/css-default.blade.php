<!-- Fonts -->
<link rel="dns-prefetch" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" type="text/css">
<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css" integrity="sha384-5sAR7xN1Nv6T6+dT2mhtzEpVJvfS3NScPQTrOxhwjIuvcA67KV2R5Jz6kr4abQsz" crossorigin="anonymous">

<!-- Styles -->
<link href="{{ asset('css/app.css') }}?{{ siteconf()->get("base-settings", "frontendDate", "") }}" rel="stylesheet">

<style>
    .w-nav-menu[no-bootstrap] .dropdown-toggle::after {
        display: none !important;
    }
</style>