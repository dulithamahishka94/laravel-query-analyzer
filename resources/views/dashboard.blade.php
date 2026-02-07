<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Query Lens Dashboard</title>
    @include('query-lens::partials.styles')
</head>
<body class="text-slate-300 min-h-screen">
    <div id="app" class="flex flex-col h-screen">
        @include('query-lens::partials.header')

        <main class="flex-1 flex min-h-0">
            @include('query-lens::partials.sidebar')

            <div class="flex-1 flex flex-col overflow-hidden">
                @include('query-lens::partials.overview')

                @include('query-lens::partials.tabs_nav')

                <!-- Tab Content -->
                <div class="flex-1 overflow-hidden p-6 flex flex-col">
                    @include('query-lens::partials.tabs.trends')
                    @include('query-lens::partials.tabs.top_queries')
                    @include('query-lens::partials.tabs.queries')
                    @include('query-lens::partials.tabs.waterfall')
                    @include('query-lens::partials.tabs.alerts')
                </div>
            </div>

            @include('query-lens::partials.detail_panel')
        </main>
    </div>

    @include('query-lens::partials.modals.create_alert')
    @include('query-lens::partials.modals.trigger_details')
    @include('query-lens::partials.scripts')
</body>
</html>
