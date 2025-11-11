<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div>
                    @include('cashloans.partials.manage', ['loans' => $personalLoans])
                </div>
            </div>
            @if(optional(auth()->user())->is_admin)
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div>
                    @include('cashloans.partials.admin', ['loans' => $globalLoans])
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>