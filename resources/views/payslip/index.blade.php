<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div>
                    @include('payslip.partials.manage')
                </div>
            </div>
            @if(optional(auth()->user())->is_admin)
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div>
                    @include('payslip.partials.manageglobal', ['payslips' => $allPayslips])
                </div>
            </div>
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div>
                    @include('payslip.partials.admin')
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>