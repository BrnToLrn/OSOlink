<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <!-- Dashboard -->
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Dashboard
                    </x-nav-link>

                    <!-- Projects -->
                    <x-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')">
                        Projects
                    </x-nav-link>

                    <!-- Leaves -->
                    <x-nav-link :href="route('leaves.index')" :active="request()->routeIs('leaves.*')">
                        Leaves
                    </x-nav-link>

                    <!-- Cash Loans -->
                    <x-nav-link :href="route('cashloans.index')" :active="request()->routeIs('cashloans.*')">
                        Cash Loans
                    </x-nav-link>

                    <!-- Payslip -->
                    <x-nav-link :href="route('payslip.index')" :active="request()->routeIs('payslip.*')">
                        Payslip
                    </x-nav-link>

                    @if (Auth::check() && Auth::user()->is_admin)
                        <!-- Admin Panel -->
                        <x-nav-link :href="route('adminpanel.admin')" :active="request()->routeIs('adminpanel.admin')">
                            Admin Panel
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 h-12 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div class="w-10 h-10 mr-2 flex items-center justify-center flex-shrink-0 flex-grow-0">
                                @if(auth()->user()->profile_picture)
                                    <img src="{{ asset('storage/' . auth()->user()->profile_picture) }}"
                                         alt="Profile Picture"
                                         style="width: 40px !important; height: 40px !important; object-fit: cover; border-radius: 9999px;">
                                @else
                                    <img src="{{ asset('images/default-avatar.jpg') }}"
                                         alt="Default Avatar"
                                         style="width: 40px !important; height: 40px !important; object-fit: cover; border-radius: 9999px;">
                                @endif
                            </div>

                            <div class="!important; ml-1">
                                {{ Auth::user()->first_name }} {{ Auth::user()->middle_name }} {{ Auth::user()->last_name }}
                                @if(Auth::user()->is_admin)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-700 dark:text-indigo-100">
                                        Admin
                                    </span>
                                @else
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                        User
                                    </span>
                                @endif
                            </div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                          clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <!-- Profile -->
                        <x-dropdown-link :href="route('profile.edit')">
                            Profile
                        </x-dropdown-link>

                        <!-- Logout -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                Log Out
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open}" class="inline-flex"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open}" class="hidden"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Dashboard
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')">
                Projects
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('leaves.index')" :active="request()->routeIs('leaves.*')">
                Leaves
            </x-responsive-nav-link>

            <!-- Cash Loans -->
            <x-responsive-nav-link :href="route('cashloans.index')" :active="request()->routeIs('cashloans.*')">
                Cash Loans
            </x-responsive-nav-link>

            <!-- Payslip -->
            <x-responsive-nav-link :href="route('payslip.index')" :active="request()->routeIs('payslip.*')">
                Payslip
            </x-responsive-nav-link>

            @if (Auth::check() && Auth::user()->is_admin)
                <x-responsive-nav-link :href="route('adminpanel.admin')" :active="request()->routeIs('adminpanel.admin')">
                    Admin Panel
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="w-10 mb-2">
                    @if(auth()->user()->profile_picture)
                        <img src="{{ asset('storage/' . auth()->user()->profile_picture) }}"
                             alt="Profile Picture"
                             style="width: 40px !important; height: 40px !important; object-fit: cover; border-radius: 9999px;">
                    @else
                        <img src="{{ asset('images/default-avatar.jpg') }}"
                             alt="Default Avatar"
                             style="width: 40px !important; height: 40px !important; object-fit: cover; border-radius: 9999px;">
                    @endif
                </div>
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">
                    {{ Auth::user()->first_name }} {{ Auth::user()->middle_name }} {{ Auth::user()->last_name }}
                    @if(Auth::user()->is_admin)
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-700 dark:text-indigo-100">
                            Admin
                        </span>
                    @else
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                            User
                        </span>
                    @endif
                </div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Profile
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        Log Out
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>