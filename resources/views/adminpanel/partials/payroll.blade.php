<section>
    <header class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Payroll Management
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage and generate payroll reports.
            </p>
        </div>

        <!-- Trigger: use a button that opens the custom modal via JS -->
        <button type="button" id="openCreatePayrollModal"
           class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-700 border border-transparent 
           rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 
           dark:hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
            + Create Payroll
        </button>
    </header>

    <!-- Custom modal (no Bootstrap). Initially hidden via 'hidden' class. -->
    <div id="createPayrollModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
        <!-- Backdrop -->
        <div id="createPayrollBackdrop" class="absolute inset-0 bg-black opacity-50"></div>

        <!-- Modal panel -->
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-md mx-4">
            <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
                <h5 class="text-lg font-medium text-gray-900 dark:text-gray-100">Create New Payroll Batch</h5>
                <button type="button" id="closeCreatePayrollModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-300">
                    ✕
                </button>
            </div>

            <form action="{{ route('admin.payrolls.batch') }}" method="POST">
                @csrf
                <div class="p-4">
                    <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">Select the date range to gather all unassigned payslips.</p>

                    <div class="mb-3">
                        <label for="period_from" class="form-label block text-sm text-gray-700 dark:text-gray-300">Period From</label>
                        <input type="date" class="form-control mt-1 w-full rounded border px-3 py-2 bg-white dark:bg-gray-900 dark:border-gray-700 text-gray-700 dark:text-gray-300" id="period_from" name="period_from" required>
                    </div>

                    <div class="mb-3">
                        <label for="period_to" class="form-label block text-sm text-gray-700 dark:text-gray-300">Period To</label>
                        <input type="date" class="form-control mt-1 w-full rounded border px-3 py-2 bg-white dark:bg-gray-900 dark:border-gray-700 text-gray-700 dark:text-gray-300" id="period_to" name="period_to" required>
                    </div>
                </div>

                <div class="flex justify-end gap-2 p-4 border-t dark:border-gray-700">
                    <x-primary-button type="button" id="cancelCreatePayrollModal">Cancel</x-primary-button>
                    <x-primary-button type="submit">Create Payroll Batch</x-primary-button>
                </div>
            </form>
        </div>
    </div>
    <!-- end modal -->

    <div class="mt-6 overflow-x-auto">
        <div class="flex flex-wrap items-center gap-3 mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
            <table class="rounded-lg overflow-hidden w-full divide-y divide-gray-300 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Period</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Total Amount</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Status</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-gray-200 dark:bg-gray-900">
                    @forelse ($payrolls as $p)
                        <tr>
                            <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">
                                {{ $p->period_from?->format('F j, Y') }} — {{ $p->period_to?->format('F j, Y') }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">
                                CA${{ number_format($p->total_amount ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">
                                {{ ucfirst($p->status ?? 'pending') }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <div class="flex justify-center gap-2">
                                    <!-- Add actions here when routes/controllers are available -->
                                    <span class="text-sm text-gray-500">—</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-6 text-center text-base text-gray-500 dark:text-gray-400">
                                No payroll batches found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- JS to open/close the modal (place at end of this file or in a central JS file) -->
<script>
    (function () {
        const openBtn = document.getElementById('openCreatePayrollModal');
        const modal = document.getElementById('createPayrollModal');
        const backdrop = document.getElementById('createPayrollBackdrop');
        const closeBtn = document.getElementById('closeCreatePayrollModal');
        const cancelBtn = document.getElementById('cancelCreatePayrollModal');

        if (!openBtn || !modal) return;

        function showModal() {
            modal.classList.remove('hidden');
            // prevent background scroll
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
        }

        function hideModal() {
            modal.classList.add('hidden');
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
        }

        openBtn.addEventListener('click', showModal);
        closeBtn?.addEventListener('click', hideModal);
        cancelBtn?.addEventListener('click', hideModal);

        // Click on backdrop closes modal
        backdrop?.addEventListener('click', hideModal);

        // Close on ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                hideModal();
            }
        });
    })();
</script>