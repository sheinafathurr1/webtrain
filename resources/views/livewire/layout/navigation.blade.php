<?php

use App\Livewire\Actions\Logout;

$logout = function (Logout $logout) {
    $logout();

    $this->redirect('/', navigate: true);
};

?>

<nav x-data="{ open: false }" class="bg-surface border-b border-border-subtle">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" wire:navigate>
                        <x-application-logo class="text-lg" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('courses.index')" :active="request()->routeIs('courses.*')" wire:navigate>
                        {{ __('Courses') }}
                    </x-nav-link>

                    @auth
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                            {{ __('Dashboard') }}
                        </x-nav-link>

                        @can('access-admin-panel')
                            <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')" wire:navigate>
                                {{ __('Admin Panel') }}
                            </x-nav-link>
                        @endcan
                    @endauth
                </div>
            </div>

            <div class="flex items-center gap-1">
                <button
                    type="button"
                    x-data="{ dark: document.documentElement.classList.contains('dark') }"
                    x-init="$watch('dark', value => {
                        document.documentElement.classList.toggle('dark', value);
                        try { localStorage.setItem('theme', value ? 'dark' : 'light'); } catch (e) {}
                    })"
                    @click="dark = ! dark"
                    class="font-mono text-xs px-2.5 py-1.5 rounded border border-border-interactive text-ink-secondary hover:border-ink-primary hover:text-ink-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-primary transition-colors duration-150"
                    :aria-label="dark ? '{{ __('Aktifkan mode terang') }}' : '{{ __('Aktifkan mode gelap') }}'"
                ><span x-text="dark ? '[gelap]' : '[terang]'"></span></button>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-4">
                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center gap-1.5 px-3 py-2 border border-transparent text-sm font-mono rounded text-ink-secondary hover:text-ink-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-primary transition-colors duration-150">
                                <span x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span><span class="text-ink-muted">@webtrain</span>
                                <svg class="fill-current h-3 w-3 text-ink-muted" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile')" wire:navigate>
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <button wire:click="logout" class="w-full text-start">
                                <x-dropdown-link>
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </button>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="text-sm text-ink-secondary hover:text-ink-primary transition-colors duration-150">{{ __('Log in') }}</a>
                    <a href="{{ route('register') }}" wire:navigate class="ms-4 text-sm text-ink-secondary hover:text-ink-primary transition-colors duration-150">{{ __('Register') }}</a>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded text-ink-muted hover:text-ink-primary hover:bg-canvas focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-primary transition-colors duration-150">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('courses.index')" :active="request()->routeIs('courses.*')" wire:navigate>
                {{ __('Courses') }}
            </x-responsive-nav-link>

            @auth
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>

                @can('access-admin-panel')
                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')" wire:navigate>
                        {{ __('Admin Panel') }}
                    </x-responsive-nav-link>
                @endcan
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-border-subtle">
            @auth
                <div class="px-4">
                    <div class="font-mono text-sm text-ink-primary" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                    <div class="font-mono text-xs text-ink-muted">{{ auth()->user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile')" wire:navigate>
                        {{ __('Profile') }}
                    </x-responsive-nav-link>

                    <!-- Authentication -->
                    <button wire:click="logout" class="w-full text-start">
                        <x-responsive-nav-link>
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </button>
                </div>
            @else
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('login')" wire:navigate>
                        {{ __('Log in') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('register')" wire:navigate>
                        {{ __('Register') }}
                    </x-responsive-nav-link>
                </div>
            @endauth
        </div>
    </div>
</nav>
