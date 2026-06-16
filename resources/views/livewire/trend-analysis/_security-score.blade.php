@props(['overallSecurityScore'])

{{-- Security score full-width card --}}
<div
    class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-6"
    wire:ignore
>
    <div class="flex items-center gap-8">
        {{-- Circle gauge --}}
        <div
            class="relative flex-shrink-0"
            x-data="{ score: {{ $overallSecurityScore }} }"
            x-init="const circle = $refs.circle;
            const circumference = 2 * Math.PI * 36;
            circle.style.strokeDasharray = circumference;
            const targetFrac = score / 100;
            let current = 0;
            let iv = setInterval(() => {
                const step = Math.max(targetFrac / 35, 0.002);
                current = Math.min(current + step, targetFrac);
                circle.style.strokeDashoffset = circumference - current * circumference;
                if (current >= targetFrac) clearInterval(iv);
            }, 16);"
        >
            <svg
                class="size-24 -rotate-90"
                viewBox="0 0 80 80"
            >
                <circle
                    cx="40"
                    cy="40"
                    r="36"
                    fill="none"
                    stroke="rgb(30,41,59)"
                    stroke-width="8"
                />
                <circle
                    x-ref="circle"
                    cx="40"
                    cy="40"
                    r="36"
                    fill="none"
                    stroke-width="8"
                    stroke-linecap="round"
                    x-show="score > 0"
                    style="stroke-dashoffset: 226; transition: stroke-dashoffset 0.05s linear;"
                    @class([
                        'text-green-400' => $overallSecurityScore >= 80,
                        'text-yellow-400' =>
                            $overallSecurityScore >= 50 && $overallSecurityScore < 80,
                        'text-red-400' => $overallSecurityScore < 50,
                    ])
                    stroke="currentColor"
                />
            </svg>
        </div>

        {{-- Score --}}
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Overall security score</p>
            <p
                @class([
                    'mt-2 text-5xl font-bold tabular-nums',
                    'text-green-400' => $overallSecurityScore >= 80,
                    'text-yellow-400' =>
                        $overallSecurityScore >= 50 && $overallSecurityScore < 80,
                    'text-red-400' => $overallSecurityScore < 50,
                ])
                x-data="{ displayed: 0 }"
                x-init="let target = {{ $overallSecurityScore }};
                let step = Math.max(1, Math.ceil(target / 40));
                let iv = setInterval(() => {
                    displayed = Math.min(displayed + step, target);
                    if (displayed >= target) clearInterval(iv);
                }, 20);"
                x-text="displayed + '/100'"
            ></p>
            {{-- Formula --}}
            <p class="mt-2 text-xs text-slate-500">
                Calculation: score = 100 − (critical × 10 + high × 5 + medium × 2 + low × 1) − (SLA breaches × 5)
            </p>
        </div>

        {{-- Legend --}}
        <div class="ml-auto flex flex-col gap-2">
            <div class="flex items-center gap-2">
                <span class="size-2.5 rounded-full bg-green-400"></span>
                <span class="text-xs text-slate-400">Good <span class="text-slate-500">(≥ 80)</span></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="size-2.5 rounded-full bg-yellow-400"></span>
                <span class="text-xs text-slate-400">Moderate <span class="text-slate-500">(50 – 79)</span></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="size-2.5 rounded-full bg-red-400"></span>
                <span class="text-xs text-slate-400">Critical <span class="text-slate-500">(< 50)</span></span>
            </div>
        </div>
    </div>

</div>
