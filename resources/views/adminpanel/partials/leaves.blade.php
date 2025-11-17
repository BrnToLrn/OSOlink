<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            Reset Leaves Counter
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Reset the leaves counter for all employees at the beginning of a new year.
        </p>
    </header>

    <div class="mt-4 flex items-center gap-4">
        <form action="{{ route('admin.leave_counters.reset') }}" method="POST" onsubmit="return confirm('Reset used leave counters for all employees to 0? This cannot be undone.');">
            @csrf
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded text-sm hover:bg-red-700">
                Reset All Leave Counters
            </button>
        </form>
        @if(session('success'))
            <div class="text-sm text-green-600 dark:text-green-300" id="leaveResetSuccess">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="text-sm text-red-600 dark:text-red-300" id="leaveResetError">{{ session('error') }}</div>
        @endif
    </div>

</section>

<script>
    (function () {
        const s = document.getElementById('leaveResetSuccess');
        const e = document.getElementById('leaveResetError');
        function autoDismiss(el, t = 5000) {
            if (!el) return;
            setTimeout(() => {
                el.style.transition = 'opacity 250ms';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            }, t);
        }
        autoDismiss(s);
        autoDismiss(e);
    })();
 </script>
