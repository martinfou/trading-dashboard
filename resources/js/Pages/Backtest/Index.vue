<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    reports: { type: Array, default: () => [] },
});

const running = ref(false);

function runBacktest() {
    if (running.value) return;
    if (!confirm('Lancer un nouveau backtest ? Cela peut prendre quelques minutes.')) return;
    running.value = true;
    router.post(route('backtest.run'), {}, {
        onFinish: () => { running.value = false; },
    });
}

function fmtMetric(val) {
    if (val === null || val === undefined) return '—';
    return Number(val).toLocaleString('fr-CA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function metricsLabel(value) {
    if (value >= 0) return 'text-emerald-400';
    return 'text-red-400';
}

/** Plus grand Return% parmi les stratégies (pour les barres de comparaison) */
const maxReturn = computed(() => {
    if (!props.reports.length) return 0;
    return Math.max(...props.reports.map((r) => Math.abs(r.metrics.totalReturn ?? 0)), 1);
});

/** Stratégies triées par Return% descendant */
const sortedByReturn = computed(() => {
    return [...props.reports].sort((a, b) => (b.metrics.totalReturn ?? 0) - (a.metrics.totalReturn ?? 0));
});

/** Icône de métrique */
function metricIcon(label) {
    const map = {
        Return: '📈',
        Sharpe: '📊',
        'Win Rate': '🎯',
        Drawdown: '📉',
    };
    return map[label] || '📌';
}
</script>

<template>
    <Head title="Backtest · Résultats" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-semibold text-gray-100">
                    🧪 Backtest — Résultats des stratégies
                </h2>
                <button
                    @click="runBacktest"
                    :disabled="running"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-900/30 transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <!-- Spinner -->
                    <svg
                        v-if="running"
                        class="h-4 w-4 animate-spin"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ running ? 'Exécution en cours…' : 'Lancer le backtest' }}
                </button>
            </div>
        </template>

        <div class="bg-[#0d1117] py-6">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- ===================== EMPTY STATE ===================== -->
                <div
                    v-if="!reports.length"
                    class="flex flex-col items-center rounded-xl border border-[#21262d] bg-[#161b22] p-12 text-center"
                >
                    <span class="text-5xl opacity-30">📭</span>
                    <p class="mt-4 text-base font-semibold text-gray-400">Aucun rapport de backtest disponible</p>
                    <p class="mt-1 text-sm text-gray-500">
                        Cliquez sur <span class="font-medium text-emerald-400">« Lancer le backtest »</span> pour exécuter
                        le moteur Java et générer les rapports.
                    </p>
                </div>

                <!-- ===================== STRATEGY CARDS ===================== -->
                <div v-if="reports.length" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="r in sortedByReturn"
                        :key="r.strategyName"
                        class="group relative overflow-hidden rounded-xl border border-[#30363d] transition hover:scale-[1.02] hover:shadow-xl"
                        :class="
                            (r.metrics.totalReturn ?? 0) >= 0
                                ? 'bg-gradient-to-br from-[#0d2b1a] to-[#161b22]'
                                : 'bg-gradient-to-br from-[#2d0f14] to-[#161b22]'
                        "
                    >
                        <!-- Glow top edge -->
                        <div
                            class="absolute inset-x-0 top-0 h-[2px]"
                            :class="
                                (r.metrics.totalReturn ?? 0) >= 0
                                    ? 'bg-gradient-to-r from-emerald-600 to-emerald-400'
                                    : 'bg-gradient-to-r from-red-600 to-red-400'
                            "
                        />

                        <div class="relative p-5">
                            <!-- Header -->
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-base font-bold text-gray-100">
                                    {{ r.strategyName }}
                                </h3>
                                <a
                                    :href="route('backtest.show', r.strategyName)"
                                    class="rounded-md bg-white/5 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-emerald-400 opacity-0 transition hover:bg-white/10 group-hover:opacity-100"
                                >
                                    Détails →
                                </a>
                            </div>

                            <!-- Main metric: Return % -->
                            <div class="mb-3 flex items-baseline gap-2">
                                <span
                                    class="text-3xl font-extrabold tabular-nums tracking-tight"
                                    :class="metricsLabel(r.metrics.totalReturn)"
                                >
                                    {{ (r.metrics.totalReturn ?? 0) >= 0 ? '+' : '' }}{{ fmtMetric(r.metrics.totalReturn) }}%
                                </span>
                                <span class="text-[10px] font-medium uppercase tracking-wider text-gray-500">Return</span>
                            </div>

                            <!-- Secondary metrics row -->
                            <div class="mb-4 grid grid-cols-3 gap-2 text-center">
                                <div class="rounded-lg bg-white/[0.03] p-2">
                                    <p class="text-[9px] font-medium uppercase tracking-wider text-gray-500">Sharpe</p>
                                    <p class="text-sm font-bold tabular-nums text-violet-400">
                                        {{ fmtMetric(r.metrics.sharpeRatio) }}
                                    </p>
                                </div>
                                <div class="rounded-lg bg-white/[0.03] p-2">
                                    <p class="text-[9px] font-medium uppercase tracking-wider text-gray-500">Win Rate</p>
                                    <p class="text-sm font-bold tabular-nums text-emerald-400">
                                        {{ r.metrics.winRate ?? '—' }}%
                                    </p>
                                </div>
                                <div class="rounded-lg bg-white/[0.03] p-2">
                                    <p class="text-[9px] font-medium uppercase tracking-wider text-gray-500">DD Max</p>
                                    <p class="text-sm font-bold tabular-nums text-red-400">
                                        {{ fmtMetric(r.metrics.maxDrawdown) }}%
                                    </p>
                                </div>
                            </div>

                            <!-- Mini sparkline: win/loss proportion bar -->
                            <div class="mt-2">
                                <div class="flex h-2 overflow-hidden rounded-full bg-[#21262d]">
                                    <div
                                        class="h-full rounded-l-full transition-all duration-500"
                                        :style="{ width: (r.metrics.winRate ?? 0) + '%' }"
                                        :class="(r.metrics.winRate ?? 0) >= 50 ? 'bg-emerald-500' : 'bg-emerald-500/60'"
                                    />
                                    <div
                                    class="h-full rounded-r-full bg-red-500/60 transition-all duration-500"
                                    :style="{ width: (100 - (r.metrics.winRate ?? 0)) + '%' }"
                                />
                                </div>
                                <div class="mt-1 flex justify-between text-[9px] text-gray-500">
                                    <span>{{ r.metrics.winningTrades ?? 0 }} gagnants</span>
                                    <span>{{ r.metrics.losingTrades ?? 0 }} perdants</span>
                                </div>
                            </div>

                            <!-- Footer: trades count + period -->
                            <div class="mt-3 flex items-center justify-between border-t border-[#21262d] pt-3 text-[10px] text-gray-500">
                                <span>{{ r.tradesCount ?? 0 }} trades</span>
                                <span>{{ r.period.from }} → {{ r.period.to }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===================== COMPARISON SECTION ===================== -->
                <div
                    v-if="reports.length >= 2"
                    class="rounded-xl border border-[#21262d] bg-[#161b22] p-5"
                >
                    <h3 class="mb-4 text-sm font-semibold text-gray-200">
                        📊 Comparaison — Return total (%)
                    </h3>
                    <div class="space-y-3">
                        <div
                            v-for="r in sortedByReturn"
                            :key="'cmp-' + r.strategyName"
                            class="flex items-center gap-3"
                        >
                            <span class="w-28 shrink-0 truncate text-xs font-medium text-gray-400">
                                {{ r.strategyName }}
                            </span>
                            <div class="flex flex-1 items-center gap-2">
                                <div class="h-5 flex-1 overflow-hidden rounded-md bg-[#21262d]">
                                    <div
                                        class="h-full rounded-md transition-all duration-700"
                                        :style="{
                                            width: (Math.abs(r.metrics.totalReturn ?? 0) / maxReturn * 100) + '%',
                                        }"
                                        :class="
                                            (r.metrics.totalReturn ?? 0) >= 0
                                                ? 'bg-gradient-to-r from-emerald-700 to-emerald-500'
                                                : 'bg-gradient-to-r from-red-700 to-red-500'
                                        "
                                    />
                                </div>
                                <span
                                    class="w-20 text-right text-xs font-bold tabular-nums"
                                    :class="metricsLabel(r.metrics.totalReturn)"
                                >
                                    {{ (r.metrics.totalReturn ?? 0) >= 0 ? '+' : '' }}{{ fmtMetric(r.metrics.totalReturn) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===================== COMPARATIVE TABLE ===================== -->
                <div v-if="reports.length" class="rounded-xl border border-[#21262d] bg-[#161b22] p-4 pb-3">
                    <h3 class="mb-3 text-sm font-semibold text-gray-200">
                        📋 Tableau détaillé des stratégies
                    </h3>
                    <div class="overflow-x-auto rounded-lg">
                        <table class="w-full text-left text-[11px]">
                            <thead>
                                <tr class="border-b border-[#21262d] text-[10px] font-semibold uppercase tracking-widest text-gray-500">
                                    <th class="sticky left-0 bg-[#161b22] py-3 pr-3 pl-2">Stratégie</th>
                                    <th class="py-3 pr-3">Trades</th>
                                    <th class="py-3 pr-3">Win Rate</th>
                                    <th class="py-3 pr-3">Return</th>
                                    <th class="py-3 pr-3">DD Max</th>
                                    <th class="py-3 pr-3">P.Factor</th>
                                    <th class="py-3 pr-3">Sharpe</th>
                                    <th class="py-3 pr-3">Avg Win</th>
                                    <th class="py-3 pr-3">Avg Loss</th>
                                    <th class="py-3 pr-3">Rapport</th>
                                    <th class="py-3 pr-3">Créé le</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="r in sortedByReturn"
                                    :key="'tbl-' + r.strategyName"
                                    class="border-b border-[#1a2332] text-gray-300 transition hover:bg-[#1c2128]"
                                >
                                    <td class="sticky left-0 bg-[#161b22] py-2.5 pr-3 pl-2 font-medium text-gray-200">
                                        <a
                                            :href="route('backtest.show', r.strategyName)"
                                            class="text-emerald-400 hover:text-emerald-300 hover:underline"
                                        >
                                            {{ r.strategyName }}
                                        </a>
                                    </td>
                                    <td class="py-2.5 pr-3 tabular-nums">{{ r.metrics.totalTrades ?? '—' }}</td>
                                    <td class="py-2.5 pr-3 tabular-nums">{{ r.metrics.winRate ?? '—' }}%</td>
                                    <td
                                        class="py-2.5 pr-3 tabular-nums font-semibold"
                                        :class="metricsLabel(r.metrics.totalReturn)"
                                    >
                                        {{ (r.metrics.totalReturn ?? 0) >= 0 ? '+' : '' }}{{ fmtMetric(r.metrics.totalReturn) }}%
                                    </td>
                                    <td class="py-2.5 pr-3 tabular-nums text-red-400">{{ fmtMetric(r.metrics.maxDrawdown) }}%</td>
                                    <td class="py-2.5 pr-3 tabular-nums">{{ fmtMetric(r.metrics.profitFactor) }}</td>
                                    <td class="py-2.5 pr-3 tabular-nums">{{ fmtMetric(r.metrics.sharpeRatio) }}</td>
                                    <td class="py-2.5 pr-3 tabular-nums text-emerald-400">{{ fmtMetric(r.metrics.avgWin) }}$</td>
                                    <td class="py-2.5 pr-3 tabular-nums text-red-400">{{ fmtMetric(r.metrics.avgLoss) }}$</td>
                                    <td class="py-2.5 pr-3">
                                        <a
                                            v-if="r.hasHtml"
                                            :href="route('backtest.show', r.strategyName)"
                                            class="inline-flex items-center gap-1 rounded bg-emerald-500/10 px-2 py-0.5 text-emerald-400 hover:bg-emerald-500/20"
                                        >
                                            📈 Voir
                                        </a>
                                        <span v-else class="text-gray-600">—</span>
                                    </td>
                                    <td class="py-2.5 pr-3 whitespace-nowrap text-gray-500">{{ r.createdAt }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ===================== CONSECUTIVE STATS ===================== -->
                <div v-if="reports.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="r in sortedByReturn"
                        :key="'stats-' + r.strategyName"
                        class="rounded-xl border border-[#21262d] bg-[#161b22] p-4 transition hover:border-[#30363d]"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-gray-200">{{ r.strategyName }}</h4>
                            <span class="rounded-full bg-white/5 px-2 py-0.5 text-[10px] font-medium text-gray-500">
                                {{ r.tradesCount }} trades
                            </span>
                        </div>
                        <div class="space-y-2 text-xs">
                            <div class="flex items-center justify-between border-b border-[#21262d] pb-1.5">
                                <span class="text-gray-500">Max Cons. Wins</span>
                                <span class="font-bold tabular-nums text-emerald-400">
                                    {{ r.metrics.maxConsecutiveWins ?? 0 }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between border-b border-[#21262d] pb-1.5">
                                <span class="text-gray-500">Max Cons. Losses</span>
                                <span class="font-bold tabular-nums text-red-400">
                                    {{ r.metrics.maxConsecutiveLosses ?? 0 }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between border-b border-[#21262d] pb-1.5">
                                <span class="text-gray-500">Balance finale</span>
                                <span class="font-bold tabular-nums text-gray-200">
                                    {{ fmtMetric(r.metrics.finalBalance) }}$
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Période</span>
                                <span class="tabular-nums text-gray-400">{{ r.period.from }} → {{ r.period.to }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
