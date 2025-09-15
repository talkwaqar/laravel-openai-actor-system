<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Actor Management System') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="font-sans antialiased">
    <div id="app" class="min-h-screen bg-background">
        <!-- Sidebar Layout Container -->
        <div id="sidebar-layout-app" class="min-h-screen">
            <!-- Content will be rendered by Vue SidebarProvider -->
            <div class="flex min-h-screen">
                <!-- Sidebar placeholder - will be replaced by Vue -->
                <div class="w-64 bg-sidebar border-r border-sidebar-border">
                    <div class="p-4">
                        <div class="animate-pulse">
                            <div class="h-8 bg-sidebar-accent rounded mb-4"></div>
                            <div class="space-y-2">
                                <div class="h-6 bg-sidebar-accent rounded"></div>
                                <div class="h-6 bg-sidebar-accent rounded"></div>
                                <div class="h-6 bg-sidebar-accent rounded"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main content area -->
                <div class="flex-1 flex flex-col">
                    <!-- Flash Messages -->
                    @if(session('success'))
                        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md mx-4 mt-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                {{ session('success') }}
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md mx-4 mt-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                {{ session('error') }}
                            </div>
                        </div>
                    @endif

                    <!-- Main Content -->
                    <main class="flex-1 p-6">
                        @yield('content')
                    </main>
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
