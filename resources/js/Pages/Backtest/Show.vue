<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Filler,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Filler, Title, Tooltip, Legend);

const props = defineProps({
    report: { type: Object, default: () => ({}) },
    strategyName: { type: String, default: '' },
    htmlContent: { type: String, default: null },
});

const metrics = computed(() => props.report.metrics || {});

const formatValue = (val) => {
    if (val === null || val === undefined) return '—';
    return Number(val).toLocaleString('fr-CA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// ==================== CHART DATA ====================

/** Build equity curve labels + data from the report's equityCurve array */
const equityChartData = computed(() => {
    const curve = props.report.equityCurve || [];
    if (!curve.length) {
        return { labels: [], datasets: [] };
    }

    // Labels: show every Nth point to avoid crowding, use bar indices
    const step = Math.max(1, Math.floor(curve.length / 60));
    const labels = curve.map((_, i) => {
        if (i === 0 || i === curve.length - 1) return `#${i}`;
        return i % step === 0 ? `#${i}` : '';
    });

    return {
        labels,
        datasets: [
            {
                label: 'Équity',
                data: curve,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.08)',
                fill: true,
                tension: 0.15,
                pointRadius: 0,
                pointHitRadius: 6,
                borderWidth: 2,
            },
        ],
    };
});

const equityChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 800 },
    interaction: {
        intersect: false,
        mode: 'index',
    },
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#1c2128',
            titleColor: '#e5e7eb',
            bodyColor: '#d1d5db',
            borderColor: '#30363d',
            borderWidth: 1,
            padding: 10,
            cornerRadius: 8,
            callbacks: {
                title: (items) => `Barre ${items[0].label.replace('#', '')}`,
                label: (ctx) => `Équity: ${Number(ctx.raw).toLocaleString('fr-CA', { minimumFractionDigits: 2 })} $`,
            },
        },
    },
    scales: {
        x: {
            display: true,
            grid: { color: '#21262d', drawBorder: false },
            ticks: {
                color: '#6b7280',
                font: { size: 9 },
                maxTicksLimit: 15,
                autoSkip: true,
                callback(value, index) {
                    const label = this.getLabelForValue(value);
                    return label || '';
                },
            },
        },
        y: {
            display: true,
            grid: { color: '#21262d', drawBorder: false },
            ticks: {
                color: '#6b7280',
                font: { size: 10 },
                callback(value) {
                    return value.toLocaleString('fr-CA', { minimumFractionDigits: 0 }) + ' $';
                },
            },
        },
    },
}));

/** Build drawdown chart from equity curve */
const drawdownChartData = computed(() => {
    const curve = props.report.equityCurve || [];
    if (!curve.length) {
        return { labels: [], datasets: [] };
    }

    let peak = curve[0];
    const ddValues = curve.map((eq) => {
        if (eq > peak) peak = eq;
        return peak > 0 ? ((peak - eq) / peak) * 100 : 0;
    });

    const step = Math.max(1, Math.floor(curve.length / 50));
    const labels = curve.map((_, i) => {
        if (i === 0 || i === curve.length - 1) return `#${i}`;
        return i % step === 0 ? `#${i}` : '';
    });

    return {
        labels,
        datasets: [
            {
                label: 'Drawdown',
                data: ddValues,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.15)',
                fill: true,
                tension: 0.15,
                pointRadius: 0,
                pointHitRadius: 6,
                borderWidth: 1.5,
            },
        ],
    };
});

const drawdownChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 800 },
    interaction: {
        intersect: false,
        mode: 'index',
    },
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#1c2128',
            titleColor: '#e5e7eb',
            bodyColor: '#fca5a5',
            borderColor: '#30363d',
            borderWidth: 1,
            padding: 10,
            cornerRadius: 8,
            callbacks: {
                title: (items) => `Barre ${items[0].label.replace('#', '')}`,
                label: (ctx) => `Drawdown: ${Number(ctx.raw).toFixed(2)}%`,
            },
        },
    },
    scales: {
        x: {
            display: true,
            grid: { color: '#21262d', drawBorder: false },
            ticks: {
                color: '#6b7280',
                font: { size: 9 },
                maxTicksLimit: 12,
                autoSkip: true,
                callback(value) {
                    return this.getLabelForValue(value) || '';
                },
            },
        },
        y: {
            display: true,
            grid: { color: '#21262d', drawBorder: false },
            reverse: true,
            ticks: {
                color: '#6b7280',
                font: { size: 10 },
                callback(value) {
                    return value.toFixed(1) + '%';
                },
            },
        },
    },
}));

// ==================== METRICS CARDS ====================

const metricCards = computed(() => [
    { label: 'Trades', value: metrics.value.totalTrades ?? '—', color: 'text-gray-100', icon: '🔄', border: 'border-gray-600' },
    {
        label: 'Win Rate',
        value: (metrics.value.winRate ?? '—') + '%',
        color: metrics.value.winRate >= 50 ? 'text-emerald-400' : 'text-red-400',
        icon: '🎯',
        border: metrics.value.winRate >= 50 ? 'border-emerald-600' : 'border-red-600',
    },
    {
        label: 'Return Total',
        value: ((metrics.value.totalReturn ?? 0) >= 0 ? '+' : '') + (metrics.value.totalReturn ?? '—') + '%',
        color: metrics.value.totalReturn >= 0 ? 'text-emerald-400' : 'text-red-400',
        icon: '📈',
        border: metrics.value.totalReturn >= 0 ? 'border-emerald-600' : 'border-red-600',
    },
    { label: 'Max Drawdown', value: (metrics.value.maxDrawdown ?? '—') + '%', color: 'text-red-400', icon: '📉', border: 'border-red-600' },
    { label: 'Profit Factor', value: formatValue(metrics.value.profitFactor), color: 'text-blue-400', icon: '⚡', border: 'border-blue-600' },
    { label: 'Sharpe Ratio', value: formatValue(metrics.value.sharpeRatio), color: 'text-violet-400', icon: '📊', border: 'border-violet-600' },
    { label: 'Avg Win', value: formatValue(metrics.value.avgWin) + ' $', color: 'text-emerald-400', icon: '✅', border: 'border-emerald-600' },
    { label: 'Avg Loss', value: formatValue(metrics.value.avgLoss) + ' $', color: 'text-red-400', icon: '❌', border: 'border-red-600' },
    { label: 'Max Cons. Wins', value: metrics.value.maxConsecutiveWins ?? '—', color: 'text-emerald-400', icon: '🏆', border: 'border-emerald-600' },
    { label: 'Max Cons. Losses', value: metrics.value.maxConsecutiveLosses ?? '—', color: 'text-red-400', icon: '💀', border: 'border-red-600' },
    { label: 'Balance initiale', value: formatValue(metrics.value.initialBalance) + ' $', color: 'text-gray-400', icon: '💵', border: 'border-gray-600' },
    {
        label: 'Balance finale',
        value: formatValue(metrics.value.finalBalance) + ' $',
        color: (metrics.value.finalBalance ?? 0) >= (metrics.value.initialBalance ?? 0) ? 'text-amber-400' : 'text-red-400',
        icon: '💰',
        border: (metrics.value.finalBalance ?? 0) >= (metrics.value.initialBalance ?? 0) ? 'border-amber-600' : 'border-red-600',
    },
]);

// ==================== TRADES TABLE ====================

const trades = computed(() => props.report.trades || []);

function pnlClass(pnl) {
    return Number(pnl) >= 0 ? 'text-emerald-400' : 'text-red-400';
}

function directionBadge(dir) {
    if (!dir) return '';
    const d = dir.toUpperCase();
    if (d === 'LONG' || d === 'BUY') return 'bg-emerald-500/20 text-emerald-400';
    return 'bg-red-500/20 text-red-400';
}

function exitReasonBadge(reason) {
    if (!reason) return 'bg-gray-500/20 text-gray-400';
    const map = {
        SL: 'bg-red-500/20 text-red-400',
        PT: 'bg-emerald-500/20 text-emerald-400',
        TRAILING: 'bg-blue-500/20 text-blue-400',
        EXPIRATION: 'bg-amber-500/20 text-amber-400',
        SIGNAL: 'bg-violet-500/20 text-violet-400',
    };
    return map[reason] || 'bg-gray-500/20 text-gray-400';
}
</script>

<template>
    <Head :title="'Backtest · ' + strategyName" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('backtest.index')"
                        class="group flex items-center gap-1 text-sm text-gray-500 transition hover:text-gray-300"
                    >
                        <svg class="h-4 w-4 transition group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        Retour
                    </Link>
                    <h2 class="text-lg font-semibold text-gray-100">
                        🧪 Backtest — {{ strategyName }}
                    </h2>
                </div>
                <span v-if="report.period" class="text-xs text-gray-500">
                    {{ report.period.from }} → {{ report.period.to }}
                </span>
            </div>
        </template>

        <div class="bg-[#0d1117] py-6">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- ==================== METRICS GRID ==================== -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                    <div
                        v-for="card in metricCards"
                        :key="card.label"
                        class="rounded-xl border bg-[#161b22] p-4 transition hover:shadow-md"
                        :class="card.border"
                    >
                        <div class="mb-1.5 flex items-center justify-between">
                            <span class="text-xs">{{ card.icon }}</span>
                            <span class="text-[9px] font-medium uppercase tracking-widest text-gray-500">
                                {{ card.label }}
                            </span>
                        </div>
                        <p class="text-lg font-extrabold tracking-tight" :class="card.color">
                            {{ card.value }}
                        </p>
                    </div>
                </div>

                <!-- ==================== CHARTS SECTION ==================== -->
                <div class="grid gap-5 lg:grid-cols-1 xl:grid-cols-1">
                    <!-- Equity Curve -->
                    <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-5">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-200">
                                <span class="h-3 w-3 rounded-full bg-blue-500" />
                                Courbe d'équity
                            </h3>
                            <span v-if="report.equityCurve" class="text-[10px] text-gray-500">
                                {{ report.equityCurve.length }} points
                            </span>
                        </div>
                        <div v-if="equityChartData.labels.length" class="h-[340px]">
                            <Line :data="equityChartData" :options="equityChartOptions" />
                        </div>
                        <div
                            v-else
                            class="flex h-[200px] items-center justify-center rounded-lg border border-dashed border-[#21262d]"
                        >
                            <span class="text-sm text-gray-500">Aucune donnée d'équity disponible</span>
                        </div>
                    </div>

                    <!-- Drawdown -->
                    <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-5">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-200">
                                <span class="h-3 w-3 rounded-full bg-red-500" />
                                Drawdown
                            </h3>
                        </div>
                        <div v-if="drawdownChartData.labels.length" class="h-[200px]">
                            <Line :data="drawdownChartData" :options="drawdownChartOptions" />
                        </div>
                        <div
                            v-else
                            class="flex h-[150px] items-center justify-center rounded-lg border border-dashed border-[#21262d]"
                        >
                            <span class="text-sm text-gray-500">Aucune donnée de drawdown disponible</span>
                        </div>
                    </div>
                </div>

                <!-- ==================== HTML REPORT (if available) ==================== -->
                <div v-if="htmlContent" class="rounded-xl border border-[#21262d] bg-[#161b22] p-5">
                    <h3 class="mb-4 text-sm font-semibold text-gray-200">📄 Rapport HTML original</h3>
                    <div
                        class="backtest-html-report overflow-x-auto rounded-lg bg-white p-4 text-gray-900"
                        v-html="htmlContent"
                    />
                </div>

                <!-- ==================== TRADES TABLE ==================== -->
                <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-5">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-200">
                            📋 Trades exécutés
                        </h3>
                        <span class="rounded-full bg-white/5 px-3 py-1 text-[10px] font-medium text-gray-400">
                            {{ trades.length }} trade{{ trades.length !== 1 ? 's' : '' }}
                        </span>
                    </div>

                    <div v-if="!trades.length" class="flex flex-col items-center py-16 text-center">
                        <span class="mb-3 text-4xl opacity-30">📭</span>
                        <p class="text-sm font-semibold text-gray-400">Aucun trade exécuté</p>
                        <p class="mt-1 text-xs text-gray-500">
                            Les données de test ne contiennent peut-être pas assez de barres.
                        </p>
                    </div>

                    <div v-else class="overflow-x-auto rounded-lg">
                        <table class="w-full text-left text-[11px]">
                            <thead>
                                <tr class="border-b border-[#21262d] text-[10px] font-semibold uppercase tracking-widest text-gray-500">
                                    <th class="sticky left-0 bg-[#161b22] py-3 pr-3 pl-1">Entrée</th>
                                    <th class="py-3 pr-3">Sortie</th>
                                    <th class="py-3 pr-3">Direction</th>
                                    <th class="py-3 pr-3">Prix Entrée</th>
                                    <th class="py-3 pr-3">Prix Sortie</th>
                                    <th class="py-3 pr-3">SL</th>
                                    <th class="py-3 pr-3">PT</th>
                                    <th class="py-3 pr-3">Raison</th>
                                    <th class="py-3 pr-3">P&amp;L ($)</th>
                                    <th class="py-3 pr-3">P&amp;L (pips)</th>
                                    <th class="py-3 pr-3">Barres</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(t, idx) in trades"
                                    :key="idx"
                                    class="border-b border-[#1a2332] text-gray-300 transition hover:bg-[#1c2128]"
                                >
                                    <td class="sticky left-0 bg-[#161b22] py-2.5 pr-3 pl-1 whitespace-nowrap text-gray-400">
                                        {{ t.entryTime ? t.entryTime.substring(0, 19).replace('T', ' ') : '—' }}
                                    </td>
                                    <td class="py-2.5 pr-3 whitespace-nowrap text-gray-400">
                                        {{ t.exitTime ? t.exitTime.substring(0, 19).replace('T', ' ') : '—' }}
                                    </td>
                                    <td class="py-2.5 pr-3">
                                        <span
                                            class="inline-block rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase"
                                            :class="directionBadge(t.direction)"
                                        >
                                            {{ t.direction }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 pr-3 tabular-nums">{{ t.entryPrice ?? '—' }}</td>
                                    <td class="py-2.5 pr-3 tabular-nums">{{ t.exitPrice ?? '—' }}</td>
                                    <td class="py-2.5 pr-3 tabular-nums text-red-400">{{ t.sl ?? '—' }}</td>
                                    <td class="py-2.5 pr-3 tabular-nums text-emerald-400">{{ t.pt ?? '—' }}</td>
                                    <td class="py-2.5 pr-3">
                                        <span
                                            class="inline-block rounded-md px-1.5 py-0.5 text-[10px] font-medium"
                                            :class="exitReasonBadge(t.exitReason)"
                                        >
                                            {{ t.exitReason }}
                                        </span>
                                    </td>
                                    <td
                                        class="py-2.5 pr-3 tabular-nums font-semibold"
                                        :class="pnlClass(t.pnlDollars)"
                                    >
                                        {{ t.pnlDollars != null ? (t.pnlDollars >= 0 ? '+' : '') + Number(t.pnlDollars).toFixed(2) : '—' }}
                                    </td>
                                    <td
                                        class="py-2.5 pr-3 tabular-nums"
                                        :class="pnlClass(t.pnlPips)"
                                    >
                                        {{ t.pnlPips != null ? (t.pnlPips >= 0 ? '+' : '') + Number(t.pnlPips).toFixed(2) : '—' }}
                                    </td>
                                    <td class="py-2.5 pr-3 tabular-nums text-gray-500">{{ t.barsHeld ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
