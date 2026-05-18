<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

/* ------------------------------------------------------------------ */
/*  Props                                                              */
/* ------------------------------------------------------------------ */

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    byInstrument: { type: Array, default: () => [] },
    byStrategy: { type: Array, default: () => [] },
    monthlyPnl: { type: Array, default: () => [] },
    openTrades: { type: Number, default: 0 },
});

/* ------------------------------------------------------------------ */
/*  Helpers                                                            */
/* ------------------------------------------------------------------ */

function fmtNum(n) {
    if (n === null || n === undefined || n === '—') return '—';
    return Number(n).toLocaleString('fr-CA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmtInt(n) {
    if (n === null || n === undefined) return '—';
    return Number(n).toLocaleString('fr-CA', { maximumFractionDigits: 0 });
}

function fmtPct(n) {
    if (n === null || n === undefined) return '—';
    return Number(n).toLocaleString('fr-CA', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' %';
}

function fmtPnl(n) {
    if (n === null || n === undefined) return '—';
    const v = Number(n);
    const s = v.toLocaleString('fr-CA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return v >= 0 ? `+${s} $` : `${s} $`;
}

function pnlColor(n) {
    if (n === null || n === undefined) return 'text-gray-400';
    return Number(n) >= 0 ? 'text-emerald-400' : 'text-red-400';
}

function bgPnl(n) {
    if (n === null || n === undefined) return 'bg-gray-500/10';
    return Number(n) >= 0 ? 'bg-emerald-500/10' : 'bg-red-500/10';
}

/* ------------------------------------------------------------------ */
/*  Summary cards                                                      */
/* ------------------------------------------------------------------ */

const summaryCards = computed(() => {
    const s = props.stats;
    if (!s || !Object.keys(s).length) return [];
    return [
        {
            label: 'Transactions totales',
            value: fmtInt(s.totalTrades),
            sub: 'Dont ' + fmtInt(s.totalClosed) + ' fermées',
            icon: '📋',
            color: 'text-blue-400',
        },
        {
            label: 'Taux de réussite',
            value: fmtPct(s.winRate),
            sub: fmtInt(s.winningTrades) + ' gagnantes / ' + fmtInt(s.losingTrades) + ' perdantes',
            icon: '🎯',
            color: 'text-emerald-400',
        },
        {
            label: 'Facteur de profit',
            value: s.profitFactor !== null && s.profitFactor !== undefined ? Number(s.profitFactor).toFixed(2) : '—',
            sub: 'Ratio gains / pertes',
            icon: '⚖️',
            color: 'text-violet-400',
        },
        {
            label: 'P&L Total',
            value: fmtPnl(s.totalPnl),
            sub: 'Résultat cumulé toutes périodes',
            icon: '💵',
            color: pnlColor(s.totalPnl),
        },
        {
            label: 'P&L Moyen',
            value: fmtPnl(s.avgPnl),
            sub: 'Gain/perte moyen par transaction',
            icon: '📊',
            color: pnlColor(s.avgPnl),
        },
        {
            label: 'Positions ouvertes',
            value: fmtInt(props.openTrades),
            sub: 'Actuellement en cours',
            icon: '🔓',
            color: 'text-amber-400',
        },
    ];
});

/* ------------------------------------------------------------------ */
/*  Best / Worst trades                                                */
/* ------------------------------------------------------------------ */

const bestTrade = computed(() => props.stats?.bestTrade || null);
const worstTrade = computed(() => props.stats?.worstTrade || null);

/* ------------------------------------------------------------------ */
/*  Chart.js instances                                                 */
/* ------------------------------------------------------------------ */

let monthlyChartInstance = null;
let instrumentChartInstance = null;

function initCharts() {
    if (typeof Chart === 'undefined') {
        setTimeout(initCharts, 300);
        return;
    }

    // Chart.js CDN full build includes all components; just ensure Filler is registered for gradients
    if (Chart.registry) {
        try { Chart.register(Chart.Filler); } catch (_) { /* already registered */ }
    }

    initMonthlyChart();
    initInstrumentChart();
}

function initMonthlyChart() {
    const canvas = document.getElementById('monthly-pnl-chart');
    if (!canvas || !props.monthlyPnl?.length) return;

    if (monthlyChartInstance) monthlyChartInstance.destroy();

    const months = props.monthlyPnl.map(m => m.month || '');
    const values = props.monthlyPnl.map(m => Number(m.pnl) || 0);

    const gradient = canvas.getContext('2d').createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(34, 197, 94, 0.25)');
    gradient.addColorStop(0.5, 'rgba(34, 197, 94, 0.05)');
    gradient.addColorStop(1, 'rgba(34, 197, 94, 0)');

    monthlyChartInstance = new Chart(canvas, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'P&L Mensuel',
                data: values,
                borderColor: '#22c55e',
                backgroundColor: gradient,
                borderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
                pointBackgroundColor: values.map(v => v >= 0 ? '#22c55e' : '#ef4444'),
                pointBorderColor: values.map(v => v >= 0 ? '#22c55e' : '#ef4444'),
                fill: true,
                tension: 0.3,
                segment: {
                    borderColor: ctx => ctx.p0.parsed.y >= 0 ? '#22c55e' : '#ef4444',
                },
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1c2128',
                    titleColor: '#e5e7eb',
                    bodyColor: '#9ca3af',
                    borderColor: '#30363d',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: ctx => 'P&L: ' + (Number(ctx.parsed.y) >= 0 ? '+' : '') + Number(ctx.parsed.y).toFixed(2) + ' $',
                    },
                },
            },
            scales: {
                x: {
                    grid: { color: '#1a2332', drawBorder: false },
                    ticks: { color: '#6b7280', font: { size: 10 } },
                },
                y: {
                    grid: { color: '#1a2332', drawBorder: false },
                    ticks: {
                        color: '#6b7280',
                        font: { size: 10 },
                        callback: v => (v >= 0 ? '+' : '') + v.toFixed(0) + ' $',
                    },
                },
            },
        },
    });
}

function initInstrumentChart() {
    const canvas = document.getElementById('instrument-chart');
    if (!canvas || !props.byInstrument?.length) return;

    if (instrumentChartInstance) instrumentChartInstance.destroy();

    const labels = props.byInstrument.map(i => i.instrument || '');
    const values = props.byInstrument.map(i => Number(i.total_pnl) || 0);

    const colors = values.map(v => v >= 0 ? '#22c55e' : '#ef4444');
    const bgColors = values.map(v => v >= 0 ? 'rgba(34, 197, 94, 0.6)' : 'rgba(239, 68, 68, 0.6)');

    instrumentChartInstance = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'P&L par instrument',
                data: values,
                backgroundColor: bgColors,
                borderColor: colors,
                borderWidth: 1,
                borderRadius: 4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1c2128',
                    titleColor: '#e5e7eb',
                    bodyColor: '#9ca3af',
                    borderColor: '#30363d',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: ctx => {
                            const v = Number(ctx.parsed.y);
                            return 'P&L: ' + (v >= 0 ? '+' : '') + v.toFixed(2) + ' $';
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#6b7280', font: { size: 10 } },
                },
                y: {
                    grid: { color: '#1a2332', drawBorder: false },
                    ticks: {
                        color: '#6b7280',
                        font: { size: 10 },
                        callback: v => (v >= 0 ? '+' : '') + v.toFixed(0) + ' $',
                    },
                },
            },
        },
    });
}

onMounted(() => {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4';
    script.onload = () => initCharts();
    document.head.appendChild(script);
});

onUnmounted(() => {
    if (monthlyChartInstance) monthlyChartInstance.destroy();
    if (instrumentChartInstance) instrumentChartInstance.destroy();
});
</script>

<template>
    <Head title="Statistiques · Performance" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-100">
                    📈 Statistiques de performance
                </h2>
                <span class="text-xs text-gray-500">Analyse complète du portefeuille</span>
            </div>
        </template>

        <div class="bg-[#0d1117] py-6">
            <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

                <!-- ============================================================ -->
                <!--  Summary Header Cards (6)                                    -->
                <!-- ============================================================ -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <div
                        v-for="card in summaryCards"
                        :key="card.label"
                        class="rounded-xl border border-[#21262d] bg-[#161b22] p-4 transition hover:border-[#30363d] hover:bg-[#1c2128]"
                    >
                        <div class="flex items-start justify-between">
                            <span class="text-lg">{{ card.icon }}</span>
                        </div>
                        <p class="mt-2 text-xs font-medium uppercase tracking-wider text-gray-500">
                            {{ card.label }}
                        </p>
                        <p class="mt-1 text-xl font-bold tracking-tight" :class="card.color">
                            {{ card.value }}
                        </p>
                        <p class="mt-0.5 text-[11px] text-gray-600">
                            {{ card.sub }}
                        </p>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Monthly P&L Line Chart + Instrument Bar Chart (2-col)       -->
                <!-- ============================================================ -->
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <!-- Monthly P&L -->
                    <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-200">
                                📉 P&L Mensuel
                            </h3>
                            <span class="text-[10px] text-gray-600">
                                {{ monthlyPnl.length }} mois
                            </span>
                        </div>
                        <div v-if="monthlyPnl.length" class="h-[280px]">
                            <canvas id="monthly-pnl-chart"></canvas>
                        </div>
                        <div v-else class="flex h-[280px] flex-col items-center justify-center text-center">
                            <span class="mb-2 text-3xl opacity-30">📊</span>
                            <p class="text-sm font-medium text-gray-500">Aucune donnée mensuelle</p>
                            <p class="mt-1 text-xs text-gray-600">Les données apparaîtront au fil des transactions.</p>
                        </div>
                    </div>

                    <!-- By Instrument -->
                    <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-200">
                                💱 P&L par Instrument
                            </h3>
                            <span class="text-[10px] text-gray-600">
                                {{ byInstrument.length }} instruments
                            </span>
                        </div>
                        <div v-if="byInstrument.length" class="h-[280px]">
                            <canvas id="instrument-chart"></canvas>
                        </div>
                        <div v-else class="flex h-[280px] flex-col items-center justify-center text-center">
                            <span class="mb-2 text-3xl opacity-30">💱</span>
                            <p class="text-sm font-medium text-gray-500">Aucune donnée par instrument</p>
                            <p class="mt-1 text-xs text-gray-600">Les données apparaîtront au fil des transactions.</p>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  By Strategy Table + Best/Worst Trades (2-col)               -->
                <!-- ============================================================ -->
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-5">
                    <!-- By Strategy Table -->
                    <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-4 lg:col-span-3">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-200">
                                🧠 Performance par Stratégie
                            </h3>
                            <span class="text-[10px] text-gray-600">
                                {{ byStrategy.length }} stratégie(s)
                            </span>
                        </div>

                        <div v-if="byStrategy.length" class="overflow-x-auto">
                            <table class="w-full text-left text-[12px]">
                                <thead>
                                    <tr class="border-b border-[#21262d] text-gray-500 uppercase tracking-wider">
                                        <th class="py-2.5 pr-3 font-medium">Stratégie</th>
                                        <th class="py-2.5 pr-3 text-right font-medium">Transactions</th>
                                        <th class="py-2.5 pr-3 text-right font-medium">P&L Total</th>
                                        <th class="py-2.5 pr-3 text-right font-medium">P&L Moyen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(strat, idx) in byStrategy"
                                        :key="idx"
                                        class="border-b border-[#1a2332] text-gray-300 transition hover:bg-[#1c2128] last:border-0"
                                    >
                                        <td class="py-3 pr-3 font-medium text-gray-200">
                                            {{ strat.strategy_name || '—' }}
                                        </td>
                                        <td class="py-3 pr-3 text-right tabular-nums">
                                            {{ fmtInt(strat.trades) }}
                                        </td>
                                        <td class="py-3 pr-3 text-right tabular-nums font-semibold" :class="pnlColor(strat.total_pnl)">
                                            {{ fmtPnl(strat.total_pnl) }}
                                        </td>
                                        <td class="py-3 pr-3 text-right tabular-nums" :class="pnlColor(strat.total_pnl / strat.trades)">
                                            {{ fmtPnl(strat.total_pnl / strat.trades) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="flex flex-col items-center py-12 text-center">
                            <span class="mb-2 text-3xl opacity-30">🧠</span>
                            <p class="text-sm font-medium text-gray-500">Aucune donnée par stratégie</p>
                            <p class="mt-1 text-xs text-gray-600">Les stratégies apparaîtront après les premières transactions.</p>
                        </div>
                    </div>

                    <!-- Best / Worst Trades -->
                    <div class="space-y-4 lg:col-span-2">
                        <!-- Best Trade -->
                        <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-4">
                            <div class="mb-2 flex items-center gap-2">
                                <span class="text-lg">🏆</span>
                                <h3 class="text-sm font-semibold text-gray-200">Meilleure transaction</h3>
                            </div>
                            <div v-if="bestTrade" class="space-y-2">
                                <div class="flex items-baseline justify-between">
                                    <span class="text-2xl font-bold text-emerald-400">
                                        {{ fmtPnl(bestTrade.pnl) }}
                                    </span>
                                    <span class="text-xs text-gray-500">P&L</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-md border border-[#21262d] bg-[#0d1117] px-2 py-1 text-xs font-medium text-gray-300">
                                        {{ bestTrade.instrument || '—' }}
                                    </span>
                                    <span
                                        class="inline-block rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                        :class="bestTrade.side?.toUpperCase() === 'BUY' || bestTrade.side?.toUpperCase() === 'LONG'
                                            ? 'bg-emerald-500/20 text-emerald-400'
                                            : 'bg-red-500/20 text-red-400'"
                                    >
                                        {{ bestTrade.side || '—' }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="py-4 text-center">
                                <p class="text-sm text-gray-500">Aucune transaction fermée</p>
                            </div>
                        </div>

                        <!-- Worst Trade -->
                        <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-4">
                            <div class="mb-2 flex items-center gap-2">
                                <span class="text-lg">💔</span>
                                <h3 class="text-sm font-semibold text-gray-200">Pire transaction</h3>
                            </div>
                            <div v-if="worstTrade" class="space-y-2">
                                <div class="flex items-baseline justify-between">
                                    <span class="text-2xl font-bold text-red-400">
                                        {{ fmtPnl(worstTrade.pnl) }}
                                    </span>
                                    <span class="text-xs text-gray-500">P&L</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-md border border-[#21262d] bg-[#0d1117] px-2 py-1 text-xs font-medium text-gray-300">
                                        {{ worstTrade.instrument || '—' }}
                                    </span>
                                    <span
                                        class="inline-block rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                        :class="worstTrade.side?.toUpperCase() === 'BUY' || worstTrade.side?.toUpperCase() === 'LONG'
                                            ? 'bg-emerald-500/20 text-emerald-400'
                                            : 'bg-red-500/20 text-red-400'"
                                    >
                                        {{ worstTrade.side || '—' }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="py-4 text-center">
                                <p class="text-sm text-gray-500">Aucune transaction fermée</p>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-4">
                            <div class="mb-2 flex items-center gap-2">
                                <span class="text-lg">📊</span>
                                <h3 class="text-sm font-semibold text-gray-200">Résumé rapide</h3>
                            </div>
                            <div class="space-y-2 text-[12px]">
                                <div class="flex justify-between border-b border-[#1a2332] pb-1.5">
                                    <span class="text-gray-500">Gagnantes</span>
                                    <span class="font-medium text-emerald-400">{{ fmtInt(stats.winningTrades) }}</span>
                                </div>
                                <div class="flex justify-between border-b border-[#1a2332] pb-1.5">
                                    <span class="text-gray-500">Perdantes</span>
                                    <span class="font-medium text-red-400">{{ fmtInt(stats.losingTrades) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Ratio G/P</span>
                                    <span class="font-medium text-gray-200">
                                        {{ stats.winningTrades && stats.losingTrades
                                            ? (stats.winningTrades / stats.losingTrades).toFixed(2)
                                            : '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  By Instrument Details Table                                 -->
                <!-- ============================================================ -->
                <div v-if="byInstrument.length" class="rounded-xl border border-[#21262d] bg-[#161b22] p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-200">
                            💱 Détail par Instrument
                        </h3>
                        <span class="text-[10px] text-gray-600">{{ byInstrument.length }} instrument(s)</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-[12px]">
                            <thead>
                                <tr class="border-b border-[#21262d] text-gray-500 uppercase tracking-wider">
                                    <th class="py-2.5 pr-3 font-medium">Instrument</th>
                                    <th class="py-2.5 pr-3 text-right font-medium">Transactions</th>
                                    <th class="py-2.5 pr-3 text-right font-medium">P&L Total</th>
                                    <th class="py-2.5 pr-3 text-right font-medium">P&L / Trade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(inst, idx) in byInstrument"
                                    :key="idx"
                                    class="border-b border-[#1a2332] text-gray-300 transition hover:bg-[#1c2128] last:border-0"
                                >
                                    <td class="py-3 pr-3 font-medium text-gray-200">{{ inst.instrument }}</td>
                                    <td class="py-3 pr-3 text-right tabular-nums">{{ fmtInt(inst.trades) }}</td>
                                    <td class="py-3 pr-3 text-right tabular-nums font-semibold" :class="pnlColor(inst.total_pnl)">
                                        {{ fmtPnl(inst.total_pnl) }}
                                    </td>
                                    <td class="py-3 pr-3 text-right tabular-nums" :class="pnlColor(inst.total_pnl / inst.trades)">
                                        {{ fmtPnl(inst.total_pnl / inst.trades) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Footer                                                       -->
                <!-- ============================================================ -->
                <p class="pb-2 text-center text-[10px] text-gray-600">
                    Statistiques de trading · Données historiques · Généré le {{ new Date().toLocaleDateString('fr-CA') }}
                </p>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
#monthly-pnl-chart,
#instrument-chart {
    width: 100% !important;
    height: 100% !important;
}
</style>
