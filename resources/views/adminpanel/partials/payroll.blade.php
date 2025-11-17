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

        <div class="flex items-center gap-4">
            @if(session('success'))
                <div id="payrollSuccessMessage" role="status" class="text-sm text-green-600 dark:text-green-400">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div id="payrollErrorMessage" role="alert" class="text-sm text-red-600">
                    {{ session('error') }}
                </div>
            @endif

            @php
                $year = now()->year;
                $options = [];
                for ($m = 1; $m <= 12; $m++) {
                    $firstDay = \Carbon\Carbon::create($year, $m, 1);
                    $midDay = \Carbon\Carbon::create($year, $m, 15);
                    $lastDay = $firstDay->copy()->endOfMonth();

                    $firstHalfExists = \App\Models\Payslip::whereNull('payroll_id')
                        ->whereBetween('issue_date', [$firstDay->toDateString(), $midDay->toDateString()])
                        ->exists();

                    $secondHalfExists = \App\Models\Payslip::whereNull('payroll_id')
                        ->whereBetween('issue_date', [$firstDay->copy()->day(16)->toDateString(), $lastDay->toDateString()])
                        ->exists();

                    if ($firstHalfExists) {
                        $options[] = [
                            'from'  => $firstDay->toDateString(),
                            'to'    => $midDay->toDateString(),
                            'label' => $firstDay->format('F j, Y') . ' — ' . $midDay->format('F j, Y'),
                        ];
                    }
                    if ($secondHalfExists) {
                        $options[] = [
                            'from'  => $firstDay->copy()->day(16)->toDateString(),
                            'to'    => $lastDay->toDateString(),
                            'label' => $firstDay->copy()->day(16)->format('F j, Y') . ' — ' . $lastDay->format('F j, Y'),
                        ];
                    }
                }
                $hasOptions = count($options) > 0;
            @endphp

            @if($hasOptions)
                <form id="createPayrollForm" action="{{ route('admin.payrolls.batch') }}" method="POST" class="flex items-center gap-2">
                    @csrf
                    <label for="payrollPeriodSelect" class="sr-only">Payroll period</label>
                    <select id="payrollPeriodSelect" class="rounded border px-3 py-2 text-sm bg-white dark:bg-gray-900 dark:border-gray-700 text-gray-700 dark:text-gray-300">
                        @foreach($options as $opt)
                            <option value="{{ $opt['from'] }}|{{ $opt['to'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>

                    <input type="hidden" name="period_from" id="period_from_input" value="">
                    <input type="hidden" name="period_to"   id="period_to_input"   value="">

                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-700 border border-transparent 
                               rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 
                               dark:hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        id="createPayrollSubmit">
                        + Create Payroll
                    </button>
                </form>
            @else
                <div class="flex items-center gap-3">
                    <div aria-live="polite" class="flex items-center gap-2 px-3 py-2 rounded-md bg-gray-50 dark:bg-gray-800 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1 4v2m-7 0h14a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z"/></svg>
                        <span>No unassigned payslips for {{ $year }}.</span>
                    </div>
                    <button type="button" disabled aria-disabled="true" class="px-4 py-2 rounded-md bg-indigo-600 text-white opacity-50 cursor-not-allowed text-xs">
                        + Create Payroll
                    </button>
                </div>
            @endif
         </div>
    </header>

    <div id="createPayrollModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
        <div id="createPayrollBackdrop" class="absolute inset-0 bg-black opacity-50"></div>

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
                        <input type="date" class="form-control mt-1 w-full rounded border px-3 py-2 bg-white dark:bg-gray-900 dark:border-gray-700 text-gray-700 dark:text-gray-300" id="period_from" name="period_from" value="{{ old('period_from', now()->subDays(15)->toDateString()) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="period_to" class="form-label block text-sm text-gray-700 dark:text-gray-300">Period To</label>
                        <input type="date" class="form-control mt-1 w-full rounded border px-3 py-2 bg-white dark:bg-gray-900 dark:border-gray-700 text-gray-700 dark:text-gray-300" id="period_to" name="period_to" value="{{ old('period_to', now()->toDateString()) }}" required>
                    </div>
                </div>

                <div class="flex justify-end gap-2 p-4 border-t dark:border-gray-700">
                    <x-primary-button type="button" id="cancelCreatePayrollModal">Cancel</x-primary-button>
                    <x-primary-button type="submit">Create Payroll Batch</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-6 overflow-x-auto">
        <div class="flex flex-wrap items-center gap-3 mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
            <table class="rounded-lg overflow-hidden w-full divide-y divide-gray-300 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Period</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Total Amount</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Created By</th>
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
                                @if($p->user)
                                    {{ trim($p->user->first_name . ' ' . ($p->user->middle_name ?? '') . ' ' . $p->user->last_name) }}
                                @elseif(!empty($p->created_by))
                                    User #{{ $p->created_by }}
                                @else
                                    &mdash;
                                @endif
                            </td>
                            @php $status = $p->status ?? 'pending'; @endphp
                            <td class="px-4 py-2 text-center text-sm {{ $status === 'paid' ? 'text-green-600 dark:text-green-400' : ($status === 'pending' ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-gray-100') }}">
                                {{ ucfirst($status) }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <div class="flex justify-center gap-2 items-center">
                                    <!-- Delete -->
                                    @if(strtolower($p->status ?? '') !== 'paid')
                                        <!-- Delete (only when not paid) -->
                                        <form action="{{ route('admin.payrolls.destroy', $p) }}" method="POST" onsubmit="return confirm('Delete payroll batch? This cannot be undone.');" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline text-sm" title="Delete">Delete</button>
                                        </form>
                                    @else
                                        <button type="button" class="text-gray-400 text-sm" title="Paid payroll cannot be deleted" disabled>Delete</button>
                                    @endif

                                    <!-- View: open modal and show payslips for this payroll -->
                                    <button
                                        type="button"
                                        class="text-blue-600 dark:text-blue-400 hover:underline text-sm"
                                        onclick="openPayslipsModal({{ $p->id }})"
                                        title="View payslips">
                                        View
                                    </button>

                                    <!-- Set status to pending -->
                                    <form action="{{ route('admin.payrolls.updateStatus', $p) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="pending">
                                        <button type="submit" class="text-yellow-600 dark:text-yellow-400 hover:underline text-sm" title="Mark pending">Pending</button>
                                    </form>

                                    <!-- Set status to paid -->
                                    <form action="{{ route('admin.payrolls.updateStatus', $p) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="paid">
                                        <button type="submit" class="text-green-600 dark:text-green-400 hover:underline text-sm" title="Mark paid">Paid</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-6 text-center text-base text-gray-500 dark:text-gray-400">
                                No payroll batches found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<div id="viewPayslipsModal" class="hidden fixed inset-0 z-50">
    <div id="viewPayslipsBackdrop" class="absolute inset-0 bg-black opacity-50"></div>

    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-screen-2xl mx-2 sm:mx-6 lg:mx-12">
        <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
            <h5 id="viewPayslipsTitle" class="text-lg font-medium text-gray-900 dark:text-gray-100">Payslips</h5>
            <button type="button" id="closeViewPayslipsModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-300">✕</button>
        </div>

        <div class="p-4 overflow-x-auto">
            <table class="rounded-lg overflow-hidden w-full divide-y divide-gray-300 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Employee</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Bank Name</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Bank Account</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Period</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Hours Worked</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Hourly Rate</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Gross Pay</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Adjustments</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Net Pay</th>
                    </tr>
                </thead>
                <tbody id="payslipsTableBody" class="divide-gray-200 dark:bg-gray-900">
                    <!-- Filled by JS -->
                </tbody>
            </table>
        </div>

        <div class="flex justify-end gap-2 p-4 border-t dark:border-gray-700">
            <x-primary-button type="button" id="closeViewPayslipsModalFooter">Close</x-primary-button>
            <x-primary-button type="button" id="downloadPayslipsCSV">Export CSV</x-primary-button>
        </div>
    </div>
</div>

<script>
    (function () {
        // Create Payroll modal (existing)
        const openBtn = document.getElementById('openCreatePayrollModal');
        const createModal = document.getElementById('createPayrollModal');
        const createBackdrop = document.getElementById('createPayrollBackdrop');
        const closeCreateBtn = document.getElementById('closeCreatePayrollModal');
        const cancelCreateBtn = document.getElementById('cancelCreatePayrollModal');

        if (openBtn) {
            function showCreateModal() {
                createModal.classList.remove('hidden');
                createModal.classList.add('flex', 'items-center', 'justify-center');
                document.documentElement.style.overflow = 'hidden';
                document.body.style.overflow = 'hidden';
            }
            function hideCreateModal() {
                createModal.classList.add('hidden');
                createModal.classList.remove('flex', 'items-center', 'justify-center');
                document.documentElement.style.overflow = '';
                document.body.style.overflow = '';
            }
            openBtn.addEventListener('click', showCreateModal);
            closeCreateBtn?.addEventListener('click', hideCreateModal);
            cancelCreateBtn?.addEventListener('click', hideCreateModal);
            createBackdrop?.addEventListener('click', hideCreateModal);
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !createModal.classList.contains('hidden')) hideCreateModal();
            });
        }

        // View Payslips modal
        const viewModal = document.getElementById('viewPayslipsModal');
        const viewBackdrop = document.getElementById('viewPayslipsBackdrop');
        const closeViewBtn = document.getElementById('closeViewPayslipsModal');
        const closeViewFooterBtn = document.getElementById('closeViewPayslipsModalFooter');
        const payslipsBody = document.getElementById('payslipsTableBody');
        const viewTitle = document.getElementById('viewPayslipsTitle');
        const downloadPayslipsCSVBtn = document.getElementById('downloadPayslipsCSV');

        let lastLoadedPayrollId = null;
        let lastPayslipsData = [];

        function showViewModal() {
            viewModal.classList.remove('hidden');
            viewModal.classList.add('flex', 'items-center', 'justify-center');
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
        }
        function hideViewModal() {
            viewModal.classList.add('hidden');
            viewModal.classList.remove('flex', 'items-center', 'justify-center');
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
            payslipsBody.innerHTML = '';
            lastLoadedPayrollId = null;
            lastPayslipsData = [];
        }

        closeViewBtn?.addEventListener('click', hideViewModal);
        closeViewFooterBtn?.addEventListener('click', hideViewModal);
        viewBackdrop?.addEventListener('click', hideViewModal);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !viewModal.classList.contains('hidden')) hideViewModal();
        });

        // Fetch payslips for a payroll and open modal
        window.openPayslipsModal = async function (payrollId) {
            try {
                viewTitle.textContent = 'Payslips — Loading...';
                showViewModal();
                const res = await fetch(`/admin/payrolls/${payrollId}/payslips`, { headers: { 'Accept': 'application/json' }});
                if (!res.ok) throw new Error('Failed to load payslips');
                const data = await res.json(); // expect array of payslips
                lastLoadedPayrollId = payrollId;
                lastPayslipsData = data;

                // helper to format ISO date -> "January 1, 2000"
                function formatDateLong(iso) {
                    if (!iso) return '';
                    try {
                        return new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
                    } catch (e) {
                        return iso;
                    }
                }

                payslipsBody.innerHTML = data.map(s => {
                    // Prefer server-provided user_name, otherwise build from user fields (including middle_name), then email
                    const employee = s.user_name ?? ((s.user && (s.user.first_name || s.user.last_name)) ? [s.user.first_name, s.user.middle_name, s.user.last_name].filter(Boolean).join(' ') : (s.user?.email ?? '—'));
                    const bankName = s.user && s.user.bank_name ? s.user.bank_name : '';
                    const bankAccount = s.user && s.user.bank_account_number ? s.user.bank_account_number : '';
                    const period = `${formatDateLong(s.period_from)} — ${formatDateLong(s.period_to)}`;
                    return `
                        <tr>
                            <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">${escapeHtml(employee)}</td>
                            <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">${escapeHtml(bankName)}</td>
                            <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">${escapeHtml(bankAccount)}</td>
                            <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">${escapeHtml(period)}</td>
                            <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">${num(s.hours_worked)}</td>
                            <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">CA$${money(s.hourly_rate)}</td>
                            <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">CA$${money(s.gross_pay)}</td>
                            <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">CA$${money(s.adjustments)}</td>
                            <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">CA$${money(s.net_pay)}</td>
                        </tr>
                    `;
                }).join('');

                // build a clean title: "Payslips (N) — January 1, 2000 — January 15, 
                        if (s.period_from) {
                            const d = new Date(s.period_from);
                            if (!minFrom || d < minFrom) minFrom = d;
                        }
                        if (s.period_to) {
                            const d = new Date(s.period_to);
                            if (!maxTo || d > maxTo) maxTo = d;
                        }
                    });
                    if (minFrom && maxTo) {
                        rangeLabel = `${formatDateLong(minFrom.toISOString())} — ${formatDateLong(maxTo.toISOString())}`;
                    } else if (minFrom) {
                        rangeLabel = formatDateLong(minFrom.toISOString());
                    } else if (maxTo) {
                        rangeLabel = formatDateLong(maxTo.toISOString());
                    }
                }

                let title = `Payslips (${count})`;
                if (rangeLabel) title += ` — ${rangeLabel}`;
                viewTitle.textContent = title;
            } catch (err) {
                payslipsBody.innerHTML = `<tr><td colspan="7" class="px-3 py-4 text-center text-sm text-red-600">Error loading payslips</td></tr>`;
                viewTitle.textContent = 'Payslips';
                console.error(err);
            }
        };

        // Generate CSV: trigger server export for given payroll
        window.generatePayrollCSV = function (payrollId) {
            const url = `/admin/payrolls/${payrollId}/export`;
            // create link and click so auth cookies are sent and browser downloads
            const a = document.createElement('a');
            a.href = url;
            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            a.remove();
        };

        // download CSV from the open payslips modal (uses lastLoadedPayrollId) - call server export
        downloadPayslipsCSVBtn?.addEventListener('click', function () {
            if (!lastLoadedPayrollId) {
                alert('No payroll loaded.');
                return;
            }
            const url = `/admin/payrolls/${lastLoadedPayrollId}/export`;
            const a = document.createElement('a');
            a.href = url;
            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            a.remove();
        });

        // helpers
        function csvEscape(val) {
            const str = val == null ? '' : String(val);
            if (/[",\r\n]/.test(str)) {
                return '"' + str.replace(/"/g, '""') + '"';
            }
            return str;
        }
        function escapeHtml(unsafe) {
            return String(unsafe ?? '').replace(/[&<>"'`=\/]/g, function (s) {
                return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'
            });
        }
        function num(n){ return (n==null || n==='')? '': Number(n).toFixed(2); }
        function money(n){ return (n==null || n==='')? '': Number(n).toFixed(2); }

    })();
</script>

<script>
    (function () {
        const successEl = document.getElementById('payrollSuccessMessage');
        const errorEl = document.getElementById('payrollErrorMessage');

        function autoDismiss(el, timeout = 5000) {
            if (!el) return;
            // fade out then remove
            setTimeout(() => {
                el.style.transition = 'opacity 250ms ease';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            }, timeout);
        }

        autoDismiss(successEl);
        autoDismiss(errorEl);
    })();
</script>