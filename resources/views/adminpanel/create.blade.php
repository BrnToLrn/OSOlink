<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <header>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Create Payroll
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Make a new payroll report.
                    </p>
                </header>

                <form method="POST" action="{{ route('admin.payrolls.generate') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">From</label>
                        <input type="date" name="from" required class="mt-1 block w-full" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">To</label>
                        <input type="date" name="to" required class="mt-1 block w-full" />
                    </div>
                    <div>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md">
                            Generate PDF
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</x-app-layout>