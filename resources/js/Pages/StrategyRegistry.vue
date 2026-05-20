<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

/* ------------------------------------------------------------------ */
/*  Props                                                              */
/* ------------------------------------------------------------------ */

const props = defineProps({
    strategies: { type: Array, default: () => [] },
});

/* ------------------------------------------------------------------ */
/*  Family / Status helpers                                            */
/* ------------------------------------------------------------------ */

const FAMILY_META = {
    TR: { color: 'bg-blue-500', label: 'Trend', emoji: '🔵' },
    MR: { color: 'bg-green-500', label: 'Mean Reversion', emoji: '🟢' },
    BT: { color: 'bg-yellow-500', label: 'Breakout', emoji: '🟡' },
    MM: { color: 'bg-red-500', label: 'Momentum', emoji: '🔴' },
    NW: { color: 'bg-purple-500', label: 'Network', emoji: '🟣' },
    CT: { color: 'bg-gray-400', label: 'Custom', emoji: '⚪' },
};

const STATUS_CONFIG = {
    live:    { icon: '✅',   label: 'LIVE',    order: 0, badge: 'bg-emerald-500/20 text-emerald-400' },
    paper:   { icon: '⏳',   label: 'PAPER',   order: 1, badge: 'bg-amber-500/20 text-amber-400' },
    backtest:{ icon: '🔬',   label: 'BACKTEST',order: 2, badge: 'bg-blue-500/20 text-blue-400' },
    dev:     { icon: '🏗️',  label: 'DEV',     order: 3, badge: 'bg-violet-500/20 text-violet-400' },
    paused:  { icon: '⏸️',  label: 'PAUSED',  order: 4, badge: 'bg-gray-500/20 text-gray-400' },
    retired: { icon: '💤',   label: 'RETIRED', order: 5, badge: 'bg-red-500/20 text-red-400' },
    failed:  { icon: '❌',   label: 'FAILED',  order: 6, badge: 'bg-red-700/20 text-red-600' },
};

const STATUSES = ['all', 'live', 'paper', 'backtest', 'dev', 'retired'];

const WF_CONFIG = {
    ok:  { icon: '✅', label: 'WF ok',      cls: 'text-emerald-400' },
    due: { icon: '🔔', label: 'WF due!',    cls: 'text-amber-400 font-bold animate-pulse' },
};

function familyMeta(f) {
    return FAMILY_META[f] || { color: 'bg-gray-600', label: f, emoji: '🔘' };
}

function statusMeta(s) {
    return STATUS_CONFIG[s] || { icon: '❓', label: s, order: 99, badge: 'bg-gray-500/20 text-gray-400' };
}

function wfMeta(s) {
    return WF_CONFIG[s] || { icon: '—', label: '—', cls: 'text-gray-500' };
}

/* ------------------------------------------------------------------ */
/*  Filter state                                                       */
/* ------------------------------------------------------------------ */

const activeFilter = ref('all');
const sortColumn = ref('status');
const sortDir = ref('asc');

function setFilter(s) { activeFilter.value = s; }
function setSort(col) {
    if (sortColumn.value === col) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = col;
        sortDir.value = col === 'status' ? 'asc' : 'desc';
    }
}

function sortIcon(col) {
    if (sortColumn.value !== col) return '↕';
    return sortDir.value === 'asc' ? '↑' : '↓';
}

/* ------------------------------------------------------------------ */
/*  Filtered + sorted strategies                                       */
/* ------------------------------------------------------------------ */

const filteredStrategies = computed(() => {
    let list = [...props.strategies];

    // Filter by status tab
    if (activeFilter.value !== 'all') {
        list = list.filter(s => s.status === activeFilter.value);
    }

    // Sort
    list.sort((a, b) => {
        const dir = sortDir.value === 'asc' ? 1 : -1;
        let cmp = 0;

        switch (sortColumn.value) {
            case 'status':
                cmp = (STATUS_CONFIG[a.status]?.order ?? 99) - (STATUS_CONFIG[b.status]?.order ?? 99);
                break;
            case 'sharpe':
                cmp = (a.sharpe ?? 0) - (b.sharpe ?? 0);
                break;
            case 'profitFactor':
                cmp = (a.profitFactor ?? 0) - (b.profitFactor ?? 0);
                break;
            case 'winRate':
                cmp = (a.winRate ?? 0) - (b.winRate ?? 0);
                break;
            case 'maxDrawdown':
                cmp = (a.maxDrawdown ?? 0) - (b.maxDrawdown ?? 0);
                break;
            case 'cumulativePnl':
                cmp = (a.cumulativePnl ?? 0) - (b.cumulativePnl ?? 0);
                break;
            case 'family':
                cmp = (a.family ?? '').localeCompare(b.family ?? '');
                break;
            case 'symbol':
                cmp = (a.symbol ?? '').localeCompare(b.symbol ?? '');
                break;
            case 'direction':
                cmp = (a.direction ?? '').localeCompare(b.direction ?? '');
                break;
            case 'wfStatus':
                cmp = (a.wfStatus ?? '').localeCompare(b.wfStatus ?? '');
                break;
            case 'shortName':
            default:
                cmp = (a.shortName ?? '').localeCompare(b.shortName ?? '');
                break;
        }
        return cmp * dir;
    });

    // LIVE always first (secondary sort)
    if (sortColumn.value !== 'status') {
        list.sort((a, b) => {
            const aLive = a.status === 'live' ? 0 : 1;
            const bLive = b.status === 'live' ? 0 : 1;
            return aLive - bLive;
        });
    }

    return list;
});

/* ------------------------------------------------------------------ */
/*  Stats for dashboard cards                                          */
/* ------------------------------------------------------------------ */

const registryStats = computed(() => {
    const total = props.strategies.length;
    const counts = {};
    let livePnl = 0;
    let totalPnl = 0;

    for (const s of props.strategies) {
        counts[s.status] = (counts[s.status] || 0) + 1;
        totalPnl += s.cumulativePnl || 0;
        if (s.status === 'live') livePnl += s.cumulativePnl || 0;
    }

    const liveStrategies = props.strategies.filter(s => s.status === 'live');
    const avgSharpe = liveStrategies.length
        ? (liveStrategies.reduce((sum, s) => sum + (s.sharpe || 0), 0) / liveStrategies.length).toFixed(2)
        : '—';

    return {
        total,
        live: counts.live || 0,
        paper: counts.paper || 0,
        backtest: counts.backtest || 0,
        dev: counts.dev || 0,
        retired: counts.retired || 0,
        paused: counts.paused || 0,
        livePnl,
        totalPnl,
        avgSharpe,
    };
});

/* ------------------------------------------------------------------ */
/*  Formatters                                                         */
/* ------------------------------------------------------------------ */

function fmtPnl(n) {
    if (n === null || n === undefined) return '—';
    const v = Number(n);
    const s = v.toLocaleString('fr-CA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return v >= 0 ? `+${s}` : s;
}

function pnlColor(n) {
    if (n === null || n === undefined) return 'text-gray-400';
    return Number(n) >= 0 ? 'text-emerald-400' : 'text-red-400';
}

function fmtRatio(n) {
    if (n === null || n === undefined) return '—';
    return Number(n).toFixed(2);
}

function fmtPct(n) {
    if (n === null || n === undefined) return '—';
    return Number(n).toFixed(1) + '%';
}

function fmtNum(n) {
    if (n === null || n === undefined) return '—';
    return Number(n).toLocaleString('fr-CA');
}
</script>

<template>
    <Head title="Strategy Registry · Tableau de bord" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-100">
                    🏛️ Strategy Registry
                </h2>
                <span class="text-xs text-gray-500">{{ registryStats.total }} stratégies</span>
            </div>
        </template>

        <div class="trading-page-bg bg-[#0d1117] py-6">
            <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

                <!-- ============================================================ -->
                <!--  Dashboard summary cards                                      -->
                <!-- ============================================================ -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                    <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-3 transition hover:border-[#30363d]">
                        <p class="text-[10px] font-medium uppercase tracking-wider text-gray-500">Total</p>
                        <p class="mt-1 text-2xl font-bold text-gray-100">{{ registryStats.total }}</p>
                        <p class="text-[10px] text-gray-600">stratégies enregistrées</p>
                    </div>
                    <div class="rounded-xl border border-emerald-500/30 bg-[#0d2b1a] p-3 transition hover:border-emerald-500/50">
                        <p class="text-[10px] font-medium uppercase tracking-wider text-emerald-400">✅ LIVE</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-400">{{ registryStats.live }}</p>
                        <p class="text-[10px] text-emerald-500/60">
                            P&L: <span :class="pnlColor(registryStats.livePnl)">{{ fmtPnl(registryStats.livePnl) }}$</span>
                        </p>
                    </div>
                    <div class="rounded-xl border border-amber-500/30 bg-[#1a180d] p-3 transition hover:border-amber-500/50">
                        <p class="text-[10px] font-medium uppercase tracking-wider text-amber-400">⏳ PAPER</p>
                        <p class="mt-1 text-2xl font-bold text-amber-400">{{ registryStats.paper }}</p>
                        <p class="text-[10px] text-amber-500/60">en observation</p>
                    </div>
                    <div class="rounded-xl border border-blue-500/30 bg-[#0d1a2b] p-3 transition hover:border-blue-500/50">
                        <p class="text-[10px] font-medium uppercase tracking-wider text-blue-400">🔬 BACKTEST</p>
                        <p class="mt-1 text-2xl font-bold text-blue-400">{{ registryStats.backtest }}</p>
                        <p class="text-[10px] text-blue-500/60">validation en cours</p>
                    </div>
                    <div class="rounded-xl border border-violet-500/30 bg-[#1a0d2b] p-3 transition hover:border-violet-500/50">
                        <p class="text-[10px] font-medium uppercase tracking-wider text-violet-400">🏗️ DEV</p>
                        <p class="mt-1 text-2xl font-bold text-violet-400">{{ registryStats.dev }}</p>
                        <p class="text-[10px] text-violet-500/60">en développement</p>
                    </div>
                    <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-3 transition hover:border-[#30363d]">
                        <p class="text-[10px] font-medium uppercase tracking-wider text-gray-500">⏸️ / 💤</p>
                        <p class="mt-1 text-2xl font-bold text-gray-400">{{ registryStats.paused + registryStats.retired }}</p>
                        <p class="text-[10px] text-gray-600">paused + retirées</p>
                    </div>
                    <div class="rounded-xl border border-violet-500/20 bg-[#161b22] p-3 transition hover:border-violet-500/40">
                        <p class="text-[10px] font-medium uppercase tracking-wider text-violet-400">Sharpe Ø</p>
                        <p class="mt-1 text-2xl font-bold text-violet-400">{{ registryStats.avgSharpe }}</p>
                        <p class="text-[10px] text-gray-600">LIVE uniquement</p>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Filter tabs                                                  -->
                <!-- ============================================================ -->
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="s in STATUSES"
                        :key="s"
                        @click="setFilter(s)"
                        class="rounded-lg px-3.5 py-1.5 text-[11px] font-semibold transition-all"
                        :class="activeFilter === s
                            ? 'bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-500/30'
                            : 'bg-[#161b22] text-gray-400 hover:bg-[#1c2128] hover:text-gray-300 ring-1 ring-[#21262d]'"
                    >
                        <span v-if="s === 'all'">📋 All</span>
                        <span v-else>{{ STATUS_CONFIG[s]?.icon }} {{ STATUS_CONFIG[s]?.label }}</span>
                        <span class="ml-1.5 text-[10px] opacity-60">
                            ({{ s === 'all' ? registryStats.total : registryStats[s] || 0 }})
                        </span>
                    </button>
                </div>

                <!-- ============================================================ -->
                <!--  Main strategies table                                        -->
                <!-- ============================================================ -->
                <div class="rounded-xl border border-[#21262d] bg-[#161b22] overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-[11px]">
                            <thead>
                                <tr class="border-b border-[#21262d] text-[10px] font-semibold uppercase tracking-widest text-gray-500 bg-[#0d1117]">
                                    <th
                                        @click="setSort('shortName')"
                                        class="cursor-pointer py-3 pr-3 pl-4 hover:text-gray-300 select-none whitespace-nowrap"
                                    >
                                        {{ sortIcon('shortName') }} Stratégie
                                    </th>
                                    <th
                                        @click="setSort('family')"
                                        class="cursor-pointer py-3 pr-3 hover:text-gray-300 select-none whitespace-nowrap"
                                    >
                                        {{ sortIcon('family') }} Famille
                                    </th>
                                    <th
                                        @click="setSort('symbol')"
                                        class="cursor-pointer py-3 pr-3 hover:text-gray-300 select-none whitespace-nowrap"
                                    >
                                        {{ sortIcon('symbol') }} Paire / TF
                                    </th>
                                    <th
                                        @click="setSort('direction')"
                                        class="cursor-pointer py-3 pr-3 hover:text-gray-300 select-none whitespace-nowrap"
                                    >
                                        {{ sortIcon('direction') }} Direction
                                    </th>
                                    <th
                                        @click="setSort('sharpe')"
                                        class="cursor-pointer py-3 pr-3 hover:text-gray-300 select-none whitespace-nowrap"
                                    >
                                        {{ sortIcon('sharpe') }} Sharpe
                                    </th>
                                    <th
                                        @click="setSort('profitFactor')"
                                        class="cursor-pointer py-3 pr-3 hover:text-gray-300 select-none whitespace-nowrap"
                                    >
                                        {{ sortIcon('profitFactor') }} PF
                                    </th>
                                    <th
                                        @click="setSort('winRate')"
                                        class="cursor-pointer py-3 pr-3 hover:text-gray-300 select-none whitespace-nowrap"
                                    >
                                        {{ sortIcon('winRate') }} Win%
                                    </th>
                                    <th
                                        @click="setSort('maxDrawdown')"
                                        class="cursor-pointer py-3 pr-3 hover:text-gray-300 select-none whitespace-nowrap"
                                    >
                                        {{ sortIcon('maxDrawdown') }} DD%
                                    </th>
                                    <th
                                        @click="setSort('cumulativePnl')"
                                        class="cursor-pointer py-3 pr-3 hover:text-gray-300 select-none whitespace-nowrap"
                                    >
                                        {{ sortIcon('cumulativePnl') }} P&L
                                    </th>
                                    <th
                                        @click="setSort('status')"
                                        class="cursor-pointer py-3 pr-3 hover:text-gray-300 select-none whitespace-nowrap"
                                    >
                                        {{ sortIcon('status') }} Statut
                                    </th>
                                    <th
                                        @click="setSort('wfStatus')"
                                        class="cursor-pointer py-3 pr-3 hover:text-gray-300 select-none whitespace-nowrap"
                                    >
                                        {{ sortIcon('wfStatus') }} WF
                                    </th>
                                    <th class="py-3 pr-4 whitespace-nowrap">Trades</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="s in filteredStrategies"
                                    :key="s.id"
                                    class="border-b border-[#1a2332] transition"
                                    :class="s.status === 'live'
                                        ? 'bg-emerald-500/[0.04] hover:bg-emerald-500/[0.07]'
                                        : 'hover:bg-[#1c2128]'"
                                >
                                    <!-- Strategy name -->
                                    <td class="py-3 pr-3 pl-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="inline-block h-2.5 w-2.5 rounded-full shrink-0"
                                                :class="familyMeta(s.family).color"
                                            ></span>
                                            <span class="font-semibold text-gray-200">{{ s.shortName }}</span>
                                        </div>
                                    </td>

                                    <!-- Family -->
                                    <td class="py-3 pr-3 whitespace-nowrap">
                                        <span class="text-xs">{{ familyMeta(s.family).emoji }}</span>
                                        <span class="text-gray-400 ml-1">{{ familyMeta(s.family).label }}</span>
                                    </td>

                                    <!-- Symbol + Timeframe -->
                                    <td class="py-3 pr-3 whitespace-nowrap">
                                        <span class="font-medium text-gray-200">{{ s.symbol }}</span>
                                        <span class="text-gray-500 ml-1.5">·</span>
                                        <span class="text-gray-400 ml-1">{{ s.timeframe }}</span>
                                    </td>

                                    <!-- Direction -->
                                    <td class="py-3 pr-3 whitespace-nowrap">
                                        <span
                                            class="inline-block rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                            :class="s.direction === 'LONG'
                                                ? 'bg-emerald-500/20 text-emerald-400'
                                                : s.direction === 'SHORT'
                                                ? 'bg-red-500/20 text-red-400'
                                                : 'bg-blue-500/20 text-blue-400'"
                                        >
                                            {{ s.direction }}
                                        </span>
                                    </td>

                                    <!-- Sharpe -->
                                    <td class="py-3 pr-3 tabular-nums"
                                        :class="(s.sharpe ?? 0) >= 1.5 ? 'text-emerald-400' : (s.sharpe ?? 0) >= 0.5 ? 'text-amber-400' : 'text-gray-500'">
                                        {{ fmtRatio(s.sharpe) }}
                                    </td>

                                    <!-- Profit Factor -->
                                    <td class="py-3 pr-3 tabular-nums"
                                        :class="(s.profitFactor ?? 0) >= 1.5 ? 'text-emerald-400' : (s.profitFactor ?? 0) >= 1.0 ? 'text-amber-400' : 'text-red-400'">
                                        {{ fmtRatio(s.profitFactor) }}
                                    </td>

                                    <!-- Win Rate -->
                                    <td class="py-3 pr-3 tabular-nums text-gray-300">
                                        {{ fmtPct(s.winRate) }}
                                    </td>

                                    <!-- Max Drawdown -->
                                    <td class="py-3 pr-3 tabular-nums"
                                        :class="(s.maxDrawdown ?? 0) <= 10 ? 'text-emerald-400' : (s.maxDrawdown ?? 0) <= 20 ? 'text-amber-400' : 'text-red-400'">
                                        {{ fmtPct(s.maxDrawdown) }}
                                    </td>

                                    <!-- Cumulative P&L -->
                                    <td class="py-3 pr-3 tabular-nums font-semibold whitespace-nowrap"
                                        :class="pnlColor(s.cumulativePnl)">
                                        {{ fmtPnl(s.cumulativePnl) }}$
                                    </td>

                                    <!-- Status -->
                                    <td class="py-3 pr-3 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                            :class="statusMeta(s.status).badge">
                                            {{ statusMeta(s.status).icon }}
                                            {{ statusMeta(s.status).label }}
                                        </span>
                                    </td>

                                    <!-- Walk-Forward Status -->
                                    <td class="py-3 pr-3 whitespace-nowrap">
                                        <span :class="wfMeta(s.wfStatus).cls" class="text-[10px]">
                                            {{ wfMeta(s.wfStatus).icon }}
                                            {{ wfMeta(s.wfStatus).label }}
                                        </span>
                                        <span v-if="s.wfDays > 0" class="text-gray-600 text-[10px] ml-1">
                                            ({{ s.wfDays }}j)
                                        </span>
                                    </td>

                                    <!-- Trades count -->
                                    <td class="py-3 pr-4 tabular-nums text-gray-500">
                                        {{ fmtNum(s.tradesCount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty state -->
                    <div
                        v-if="!filteredStrategies.length"
                        class="flex flex-col items-center py-16 text-center"
                    >
                        <span class="mb-2 text-4xl opacity-30">📭</span>
                        <p class="text-sm font-medium text-gray-500">
                            Aucune stratégie {{ activeFilter !== 'all' ? `avec le statut « ${STATUS_CONFIG[activeFilter]?.label} »` : '' }}
                        </p>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Summary footer                                               -->
                <!-- ============================================================ -->
                <div class="flex items-center justify-between rounded-xl border border-[#21262d] bg-[#161b22] p-3">
                    <div class="flex flex-wrap gap-4 text-[10px] text-gray-500">
                        <span>📊 Total P&L: <span :class="pnlColor(registryStats.totalPnl)" class="font-semibold">{{ fmtPnl(registryStats.totalPnl) }}$</span></span>
                        <span>⚡ Sharpe μ LIVE: <span class="text-violet-400 font-semibold">{{ registryStats.avgSharpe }}</span></span>
                        <span>🔄 Walk-forward dû: <span class="text-amber-400 font-semibold">{{ props.strategies.filter(s => s.wfStatus === 'due').length }}</span></span>
                    </div>
                    <span class="text-[10px] text-gray-600">⏱️ Mise à jour en temps réel</span>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.trading-page-bg {
    min-height: calc(100vh - 4rem);
}
</style>
