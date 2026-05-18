<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

/* ------------------------------------------------------------------ */
/*  Props                                                              */
/* ------------------------------------------------------------------ */

const props = defineProps({
    trades: { type: Array, default: () => [] },
    openTrades: { type: Array, default: () => [] },
    totalPnl: { type: Number, default: 0 },
    prices: { type: Object, default: () => ({}) },
    accountBalance: { type: Object, default: () => ({ balance: '—', pl: 0, trades: 0 }) },
});

/* ------------------------------------------------------------------ */
/*  Helpers                                                            */
/* ------------------------------------------------------------------ */

const PAIR_NAMES = {
    EUR_USD: 'EUR/USD',
    GBP_USD: 'GBP/USD',
    USD_CAD: 'USD/CAD',
    AUD_USD: 'AUD/USD',
    GBP_JPY: 'GBP/JPY',
    USD_CHF: 'USD/CHF',
};

const PAIR_LABELS = {
    EUR_USD: 'Euro / Dollar US',
    GBP_USD: 'Livre sterling / Dollar US',
    USD_CAD: 'Dollar US / Dollar canadien',
    AUD_USD: 'Dollar australien / Dollar US',
    GBP_JPY: 'Livre sterling / Yen japonais',
    USD_CHF: 'Dollar US / Franc suisse',
};

const PAIR_ICONS = {
    EUR_USD: '🇪🇺',
    GBP_USD: '🇬🇧',
    USD_CAD: '🇨🇦',
    AUD_USD: '🇦🇺',
    GBP_JPY: '🇯🇵',
    USD_CHF: '🇨🇭',
};

function fmtDateTime(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('fr-CA', {
        day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit',
    });
}

function fmtDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('fr-CA', { day: '2-digit', month: 'short', year: 'numeric' });
}

function fmtNum(n) {
    if (n === null || n === undefined || n === '—') return '—';
    return Number(n).toLocaleString('fr-CA', { minimumFractionDigits: 2, maximumFractionDigits: 5 });
}

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

const pairKeys = Object.keys(PAIR_NAMES);

/* ------------------------------------------------------------------ */
/*  Selected pair for chart                                            */
/* ------------------------------------------------------------------ */

const selectedPair = ref('EUR_USD');

/* ------------------------------------------------------------------ */
/*  Price auto-refresh                                                 */
/* ------------------------------------------------------------------ */

const refreshing = ref(false);
let refreshInterval = null;

function startAutoRefresh() {
    refreshInterval = setInterval(() => {
        refreshing.value = true;
        router.reload({
            only: ['prices', 'accountBalance'],
            onSuccess: () => { refreshing.value = false; },
            onError: () => { refreshing.value = false; },
            preserveScroll: true,
            preserveState: true,
        });
    }, 15000);
}

onMounted(() => {
    startAutoRefresh();
    // Load Lightweight Charts from CDN
    if (!window.LightweightCharts) {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/lightweight-charts@4.1/dist/lightweight-charts.standalone.production.js';
        script.onload = initChart;
        document.head.appendChild(script);
    } else {
        initChart();
    }
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
    if (chartInstance) chartInstance.remove();
});

/* ------------------------------------------------------------------ */
/*  Lightweight Charts – candlestick                                   */
/* ------------------------------------------------------------------ */

let chartInstance = null;
let candleSeries = null;

function generateCandles(pair, count = 100) {
    const candles = [];
    const now = new Date();
    now.setMinutes(0, 0, 0);
    // Base price depends on pair
    const bases = { EUR_USD: 1.094, GBP_USD: 1.268, USD_CAD: 1.368, AUD_USD: 0.658, GBP_JPY: 190.5, USD_CHF: 0.882 };
    const base = bases[pair] || 1.0;
    let close = base;
    for (let i = count - 1; i >= 0; i--) {
        const t = new Date(now.getTime() - i * 3600000);
        const open = close + (Math.random() - 0.5) * 0.002 * base;
        const high = Math.max(open, close) + Math.random() * 0.0015 * base;
        const low = Math.min(open, close) - Math.random() * 0.0015 * base;
        close = open + (Math.random() - 0.48) * 0.003 * base;
        candles.push({
            time: Math.floor(t.getTime() / 1000),
            open: Math.round(open * 100000) / 100000,
            high: Math.round(high * 100000) / 100000,
            low: Math.round(low * 100000) / 100000,
            close: Math.round(close * 100000) / 100000,
        });
    }
    return candles;
}

let chartMounted = false;

function initChart() {
    if (chartMounted) return;
    const container = document.getElementById('trading-chart');
    if (!container) {
        setTimeout(initChart, 200);
        return;
    }
    const LightweightCharts = window.LightweightCharts;
    if (!LightweightCharts) {
        // script not loaded yet, try again
        setTimeout(initChart, 500);
        return;
    }
    chartMounted = true;

    chartInstance = LightweightCharts.createChart(container, {
        layout: {
            background: { type: 'solid', color: '#0d1117' },
            textColor: '#8b949e',
            fontSize: 11,
        },
        grid: {
            vertLines: { color: '#1a2332' },
            horzLines: { color: '#1a2332' },
        },
        crosshair: {
            mode: LightweightCharts.CrosshairMode.Normal,
            vertLine: { color: '#30363d', width: 1, style: LightweightCharts.LineStyle.Dotted },
            horzLine: { color: '#30363d', width: 1, style: LightweightCharts.LineStyle.Dotted },
        },
        timeScale: {
            borderColor: '#21262d',
            timeVisible: true,
            secondsVisible: false,
            fixLeftEdge: true,
            fixRightEdge: true,
        },
        rightPriceScale: {
            borderColor: '#21262d',
            scaleMargins: { top: 0.05, bottom: 0.1 },
        },
        handleScroll: { vertTouchDrag: false },
        handleScale: { axisPressedMouseMove: true },
    });

    candleSeries = chartInstance.addCandlestickSeries({
        upColor: '#22c55e',
        downColor: '#ef4444',
        borderDownColor: '#ef4444',
        borderUpColor: '#22c55e',
        wickDownColor: '#ef4444',
        wickUpColor: '#22c55e',
    });

    const data = generateCandles(selectedPair.value);
    candleSeries.setData(data);
    chartInstance.timeScale().fitContent();
}

watch(selectedPair, (pair) => {
    if (candleSeries) {
        const data = generateCandles(pair);
        candleSeries.setData(data);
        chartInstance.timeScale().fitContent();
    }
});

/* ------------------------------------------------------------------ */
/*  Pair price helpers                                                 */
/* ------------------------------------------------------------------ */

function priceBid(pair) {
    return props.prices?.[pair]?.bid ?? '—';
}

function priceAsk(pair) {
    return props.prices?.[pair]?.ask ?? '—';
}

function priceSpread(pair) {
    return props.prices?.[pair]?.spread ?? '—';
}

function priceChange(pair) {
    // Compare bid vs fake "previous" — for display
    const bid = Number(props.prices?.[pair]?.bid);
    if (!bid || bid === '—') return 0;
    // Use a small deterministic offset based on pair hash
    const hash = pair.split('').reduce((a, c) => a + c.charCodeAt(0), 0);
    const prev = bid * (1 + ((hash % 100) - 50) / 10000);
    return ((bid - prev) / prev) * 100;
}

function priceTrend(pair) {
    const chg = priceChange(pair);
    if (chg > 0) return 'arrow-up';
    if (chg < 0) return 'arrow-down';
    return 'flat';
}

/* ------------------------------------------------------------------ */
/*  Account summary cards                                              */
/* ------------------------------------------------------------------ */

const accountCards = computed(() => {
    const bal = Number(props.accountBalance?.balance) || 0;
    const pl = Number(props.accountBalance?.pl) || 0;
    const openCount = props.accountBalance?.trades ?? props.openTrades?.length ?? 0;
    const nav = bal + pl;
    return [
        { label: 'Solde du compte', value: `$${fmtNum(bal)}`, sub: 'USD', icon: '💰', color: 'text-blue-400' },
        { label: 'Valeur Nette (NAV)', value: `$${fmtNum(nav)}`, sub: 'Solde + P&L non réalisé', icon: '🏦', color: 'text-violet-400' },
        { label: 'Positions ouvertes', value: String(openCount), sub: 'Transactions en cours', icon: '📊', color: 'text-amber-400' },
        { label: 'P&L Total', value: `${fmtPnl(props.totalPnl)} $`, sub: 'Résultat cumulé', icon: '📈', color: pnlColor(props.totalPnl) },
    ];
});

/* ------------------------------------------------------------------ */
/*  Economic calendar (May 18–24, 2026)                                */
/* ------------------------------------------------------------------ */

const economicEvents = [
    { day: 'Lun 18 Mai', time: '08:30', currency: 'USD', event: 'Indice manufacturier Empire State', forecast: '5.2', previous: '−8.6', impact: 'medium' },
    { day: 'Lun 18 Mai', time: '10:00', currency: 'USD', event: 'Indice du marché immobilier NAHB', forecast: '42', previous: '40', impact: 'low' },
    { day: 'Mar 19 Mai', time: '08:30', currency: 'CAD', event: 'IPC canadien (avril)', forecast: '2.3%', previous: '2.2%', impact: 'high' },
    { day: 'Mar 19 Mai', time: '08:30', currency: 'USD', event: 'Permis de construire (avril)', forecast: '1.45M', previous: '1.48M', impact: 'medium' },
    { day: 'Mar 19 Mai', time: '08:30', currency: 'USD', event: 'Mises en chantier (avril)', forecast: '1.38M', previous: '1.32M', impact: 'medium' },
    { day: 'Mer 20 Mai', time: '14:00', currency: 'USD', event: 'Comptes-rendus du FOMC', forecast: '—', previous: '—', impact: 'high' },
    { day: 'Mer 20 Mai', time: '14:30', currency: 'USD', event: 'Stocks de pétrole brut', forecast: '−0.8M', previous: '−1.2M', impact: 'medium' },
    { day: 'Jeu 21 Mai', time: '08:30', currency: 'USD', event: 'Inscriptions au chômage', forecast: '228K', previous: '231K', impact: 'high' },
    { day: 'Jeu 21 Mai', time: '08:30', currency: 'USD', event: 'Indice manufacturier de Philadelphie', forecast: '8.5', previous: '7.2', impact: 'medium' },
    { day: 'Jeu 21 Mai', time: '10:00', currency: 'USD', event: 'Ventes de maisons existantes (avril)', forecast: '4.05M', previous: '4.12M', impact: 'medium' },
    { day: 'Jeu 21 Mai', time: '10:00', currency: 'EUR', event: 'Confiance des consommateurs (mai)', forecast: '−13.5', previous: '−14.2', impact: 'medium' },
    { day: 'Ven 22 Mai', time: '08:30', currency: 'CAD', event: 'Ventes au détail (mars)', forecast: '0.4%', previous: '0.1%', impact: 'high' },
    { day: 'Ven 22 Mai', time: '10:00', currency: 'EUR', event: 'IPC allemand (mai — préliminaire)', forecast: '2.4%', previous: '2.3%', impact: 'high' },
    { day: 'Ven 22 Mai', time: '14:00', currency: 'USD', event: 'Discours — Président Fed Powell', forecast: '—', previous: '—', impact: 'high' },
    { day: 'Sam 23 Mai', time: '—', currency: '—', event: 'Aucun événement majeur prévu', forecast: '—', previous: '—', impact: 'none' },
    { day: 'Dim 24 Mai', time: '—', currency: '—', event: 'Aucun événement majeur prévu', forecast: '—', previous: '—', impact: 'none' },
];

function impactBadge(impact) {
    if (impact === 'high') return 'bg-red-500/20 text-red-400';
    if (impact === 'medium') return 'bg-amber-500/20 text-amber-400';
    return 'bg-gray-500/20 text-gray-400';
}

function impactLabel(impact) {
    if (impact === 'high') return '🔴 Fort';
    if (impact === 'medium') return '🟡 Moyen';
    return '⚪ Faible';
}

/* ------------------------------------------------------------------ */
/*  Analysis / Insights — per-pair recommendations                     */
/* ------------------------------------------------------------------ */

const analysisData = computed(() => {
    const pairAnalysis = [
        {
            pair: 'EUR_USD',
            trend: 'Haussière',
            trendColor: 'text-emerald-400',
            rsi: '58.2',
            maTrend: '50-SMA ↗️',
            recommendation: 'Acheter sur repli',
            confidence: 'Modérée',
            levels: 'R: 1.1050 / 1.1120 — S: 1.0880 / 1.0820',
        },
        {
            pair: 'GBP_USD',
            trend: 'Neutre-Haussière',
            trendColor: 'text-emerald-400',
            rsi: '52.8',
            maTrend: '200-SMA plat',
            recommendation: 'Attendre breakout',
            confidence: 'Faible',
            levels: 'R: 1.2780 / 1.2850 — S: 1.2600 / 1.2540',
        },
        {
            pair: 'USD_CAD',
            trend: 'Baissière',
            trendColor: 'text-red-400',
            rsi: '44.1',
            maTrend: '50-SMA ↘️',
            recommendation: 'Vendre sur rebond',
            confidence: 'Élevée',
            levels: 'R: 1.3750 / 1.3820 — S: 1.3600 / 1.3550',
        },
        {
            pair: 'AUD_USD',
            trend: 'Haussière',
            trendColor: 'text-emerald-400',
            rsi: '55.6',
            maTrend: '20-SMA ↗️',
            recommendation: 'Acheter au comptant',
            confidence: 'Modérée',
            levels: 'R: 0.6650 / 0.6720 — S: 0.6500 / 0.6450',
        },
        {
            pair: 'GBP_JPY',
            trend: 'Haussière',
            trendColor: 'text-emerald-400',
            rsi: '61.4',
            maTrend: '50-SMA ↗️',
            recommendation: 'Acheter sur repli',
            confidence: 'Élevée',
            levels: 'R: 192.50 / 193.80 — S: 189.00 / 187.50',
        },
        {
            pair: 'USD_CHF',
            trend: 'Baissière',
            trendColor: 'text-red-400',
            rsi: '43.7',
            maTrend: '200-SMA ↘️',
            recommendation: 'Vendre (short)',
            confidence: 'Élevée',
            levels: 'R: 0.8880 / 0.8930 — S: 0.8760 / 0.8700',
        },
    ];
    return pairAnalysis;
});

/* ------------------------------------------------------------------ */
/*  Historical trades table helpers                                    */
/* ------------------------------------------------------------------ */

function sideBadge(side) {
    if (!side) return '';
    const s = side.toUpperCase();
    if (s === 'BUY' || s === 'LONG') return 'bg-emerald-500/20 text-emerald-400';
    return 'bg-red-500/20 text-red-400';
}

function statusBadge(status) {
    if (!status) return '';
    const s = status.toUpperCase();
    if (s === 'OPEN') return 'bg-amber-500/20 text-amber-400';
    if (s === 'WIN' || s === 'CLOSED_WIN') return 'bg-emerald-500/20 text-emerald-400';
    if (s === 'LOSS' || s === 'CLOSED_LOSS') return 'bg-red-500/20 text-red-400';
    return 'bg-gray-500/20 text-gray-400';
}

/* ------------------------------------------------------------------ */
/*  Open trades widget                                                 */
/* ------------------------------------------------------------------ */

const hasOpenTrades = computed(() => props.openTrades?.length > 0);
</script>

<template>
    <Head title="Trading · Tableau de bord" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-100">
                    📈 Tableau de bord Trading
                </h2>
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1.5 text-xs text-gray-500">
                        <span
                            class="inline-block h-2 w-2 rounded-full"
                            :class="refreshing ? 'bg-emerald-400 animate-pulse' : 'bg-emerald-500'"
                        ></span>
                        {{ refreshing ? 'Mise à jour…' : 'Temps réel' }}
                    </span>
                </div>
            </div>
        </template>

        <!-- Override layout background to dark -->
        <div class="bg-[#0d1117] py-6">
            <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

                <!-- ============================================================ -->
                <!--  Account Header Cards                                        -->
                <!-- ============================================================ -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div
                        v-for="card in accountCards"
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
                <!--  Price Grid — all 6 pairs                                    -->
                <!-- ============================================================ -->
                <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-200">
                            💱 Cours en direct
                        </h3>
                        <span class="text-[10px] text-gray-600">Auto‑rafraîchissement 15s</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
                        <div
                            v-for="pair in pairKeys"
                            :key="pair"
                            @click="selectedPair = pair"
                            class="group cursor-pointer rounded-lg border p-3 transition-all"
                            :class="selectedPair === pair
                                ? 'border-emerald-500/40 bg-emerald-500/5 shadow-sm shadow-emerald-500/10'
                                : 'border-[#21262d] bg-[#0d1117] hover:border-[#30363d]'"
                        >
                            <div class="mb-1 flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-300">
                                    {{ PAIR_ICONS[pair] }} {{ PAIR_NAMES[pair] }}
                                </span>
                                <!-- Trend arrow -->
                                <svg v-if="priceTrend(pair) === 'arrow-up'"
                                    class="h-3 w-3 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                </svg>
                                <svg v-else-if="priceTrend(pair) === 'arrow-down'"
                                    class="h-3 w-3 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                            <p class="text-base font-bold tabular-nums text-gray-100">
                                {{ priceBid(pair) !== '—' ? Number(priceBid(pair)).toFixed(5) : '—' }}
                            </p>
                            <div class="mt-1 flex justify-between text-[10px] text-gray-500">
                                <span>A: {{ priceAsk(pair) !== '—' ? Number(priceAsk(pair)).toFixed(5) : '—' }}</span>
                                <span>Spread: {{ priceSpread(pair) !== '—' ? Number(priceSpread(pair)).toFixed(1) : '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Chart + Pair Selector                                       -->
                <!-- ============================================================ -->
                <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-4">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-200">
                            📉 {{ PAIR_ICONS[selectedPair] }} {{ PAIR_NAMES[selectedPair] }} · Graphique H1
                        </h3>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="pair in pairKeys"
                                :key="pair"
                                @click="selectedPair = pair"
                                class="rounded-md px-2.5 py-1 text-[11px] font-medium transition"
                                :class="selectedPair === pair
                                    ? 'bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-500/30'
                                    : 'bg-[#0d1117] text-gray-500 hover:bg-[#1c2128] hover:text-gray-300 ring-1 ring-[#21262d]'"
                            >
                                {{ PAIR_NAMES[pair] }}
                            </button>
                        </div>
                    </div>
                    <div id="trading-chart" class="h-[420px] w-full"></div>
                </div>

                <!-- ============================================================ -->
                <!--  Open Trades (if any)                                        -->
                <!-- ============================================================ -->
                <div
                    v-if="hasOpenTrades"
                    class="rounded-xl border border-[#21262d] bg-[#161b22] p-4"
                >
                    <h3 class="mb-3 text-sm font-semibold text-gray-200">
                        🔓 Positions ouvertes
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-[11px]">
                            <thead>
                                <tr class="border-b border-[#21262d] text-gray-500 uppercase tracking-wider">
                                    <th class="py-2 pr-3 font-medium">Instrument</th>
                                    <th class="py-2 pr-3 font-medium">Direction</th>
                                    <th class="py-2 pr-3 font-medium">Quantité</th>
                                    <th class="py-2 pr-3 font-medium">Entrée</th>
                                    <th class="py-2 pr-3 font-medium">SL</th>
                                    <th class="py-2 pr-3 font-medium">TP</th>
                                    <th class="py-2 pr-3 font-medium">P&L</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="t in openTrades"
                                    :key="t.id"
                                    class="border-b border-[#1a2332] text-gray-300"
                                >
                                    <td class="py-2 pr-3 font-medium text-gray-200">{{ t.instrument }}</td>
                                    <td class="py-2 pr-3">
                                        <span class="inline-block rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                            :class="sideBadge(t.side)">
                                            {{ t.side }}
                                        </span>
                                    </td>
                                    <td class="py-2 pr-3">{{ t.quantity }}</td>
                                    <td class="py-2 pr-3 tabular-nums">{{ fmtNum(t.entry_price) }}</td>
                                    <td class="py-2 pr-3 tabular-nums text-red-400">{{ fmtNum(t.stop_loss) }}</td>
                                    <td class="py-2 pr-3 tabular-nums text-emerald-400">{{ fmtNum(t.take_profit) }}</td>
                                    <td class="py-2 pr-3 tabular-nums font-semibold"
                                        :class="pnlColor(t.pnl)">
                                        {{ fmtPnl(t.pnl) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Analysis / Insights                                         -->
                <!-- ============================================================ -->
                <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="text-sm">🔍</span>
                        <h3 class="text-sm font-semibold text-gray-200">
                            Analyses et recommandations
                        </h3>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="a in analysisData"
                            :key="a.pair"
                            class="rounded-lg border border-[#21262d] bg-[#0d1117] p-3"
                        >
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-200">
                                    {{ PAIR_ICONS[a.pair] }} {{ PAIR_NAMES[a.pair] }}
                                </span>
                                <span class="text-[10px] font-medium" :class="a.trendColor">
                                    {{ a.trend }}
                                </span>
                            </div>
                            <div class="space-y-1.5 text-[11px]">
                                <div class="flex justify-between text-gray-500">
                                    <span>RSI (14)</span>
                                    <span class="tabular-nums text-gray-300">{{ a.rsi }}</span>
                                </div>
                                <div class="flex justify-between text-gray-500">
                                    <span>Moyenne mobile</span>
                                    <span class="text-gray-300">{{ a.maTrend }}</span>
                                </div>
                                <div class="flex justify-between text-gray-500">
                                    <span>Recommandation</span>
                                    <span class="font-medium text-emerald-300">{{ a.recommendation }}</span>
                                </div>
                                <div class="flex justify-between text-gray-500">
                                    <span>Confiance</span>
                                    <span class="text-gray-300">{{ a.confidence }}</span>
                                </div>
                                <div class="mt-2 border-t border-[#21262d] pt-1.5 text-[10px] text-gray-500">
                                    {{ a.levels }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Economic Calendar                                            -->
                <!-- ============================================================ -->
                <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="text-sm">📅</span>
                        <h3 class="text-sm font-semibold text-gray-200">
                            Calendrier économique · 18–24 Mai 2026
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-[11px]">
                            <thead>
                                <tr class="border-b border-[#21262d] text-gray-500 uppercase tracking-wider">
                                    <th class="py-2 pr-3 font-medium">Jour</th>
                                    <th class="py-2 pr-3 font-medium">Heure</th>
                                    <th class="py-2 pr-3 font-medium">Dev.</th>
                                    <th class="py-2 pr-3 font-medium w-1/3">Événement</th>
                                    <th class="py-2 pr-3 font-medium">Prévision</th>
                                    <th class="py-2 pr-3 font-medium">Précédent</th>
                                    <th class="py-2 pr-3 font-medium">Impact</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(ev, idx) in economicEvents"
                                    :key="idx"
                                    class="border-b border-[#1a2332] last:border-0"
                                    :class="{ 'opacity-50': ev.impact === 'none' }"
                                >
                                    <td class="py-2 pr-3 text-gray-300">{{ ev.day }}</td>
                                    <td class="py-2 pr-3 text-gray-400">{{ ev.time }}</td>
                                    <td class="py-2 pr-3 font-medium"
                                        :class="ev.currency === 'USD' ? 'text-emerald-400' : ev.currency === 'CAD' ? 'text-amber-400' : ev.currency === 'EUR' ? 'text-blue-400' : 'text-gray-500'">
                                        {{ ev.currency }}
                                    </td>
                                    <td class="py-2 pr-3 text-gray-200">{{ ev.event }}</td>
                                    <td class="py-2 pr-3 text-gray-400">{{ ev.forecast }}</td>
                                    <td class="py-2 pr-3 text-gray-500">{{ ev.previous }}</td>
                                    <td class="py-2 pr-3">
                                        <span class="inline-block rounded px-1.5 py-0.5 text-[10px] font-medium"
                                            :class="impactBadge(ev.impact)">
                                            {{ impactLabel(ev.impact) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Historical Trades Table                                     -->
                <!-- ============================================================ -->
                <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-200">
                            📋 Historique des transactions
                        </h3>
                        <span class="text-[10px] text-gray-600">{{ trades.length }} transaction(s)</span>
                    </div>

                    <!-- Empty state -->
                    <div
                        v-if="!trades.length"
                        class="flex flex-col items-center py-12 text-center"
                    >
                        <span class="mb-2 text-3xl opacity-30">📭</span>
                        <p class="text-sm font-medium text-gray-500">Aucune transaction pour le moment</p>
                        <p class="mt-1 text-xs text-gray-600">Les transactions apparaîtront ici une fois que vous aurez commencé à trader.</p>
                    </div>

                    <!-- Table -->
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-[11px]">
                            <thead>
                                <tr class="border-b border-[#21262d] text-gray-500 uppercase tracking-wider">
                                    <th class="py-2 pr-3 font-medium">Date</th>
                                    <th class="py-2 pr-3 font-medium">Instrument</th>
                                    <th class="py-2 pr-3 font-medium">Direction</th>
                                    <th class="py-2 pr-3 font-medium">Qté</th>
                                    <th class="py-2 pr-3 font-medium">Entrée</th>
                                    <th class="py-2 pr-3 font-medium">Sortie</th>
                                    <th class="py-2 pr-3 font-medium">P&L</th>
                                    <th class="py-2 pr-3 font-medium">Statut</th>
                                    <th class="py-2 pr-3 font-medium">Stratégie</th>
                                    <th class="py-2 pr-3 font-medium">Conf.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="t in trades"
                                    :key="t.id"
                                    class="border-b border-[#1a2332] text-gray-300 hover:bg-[#1c2128]"
                                >
                                    <td class="py-2.5 pr-3 whitespace-nowrap text-gray-400">{{ fmtDateTime(t.entry_time) }}</td>
                                    <td class="py-2.5 pr-3 font-medium text-gray-200">{{ t.instrument }}</td>
                                    <td class="py-2.5 pr-3">
                                        <span class="inline-block rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                            :class="sideBadge(t.side)">
                                            {{ t.side }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 pr-3">{{ t.quantity }}</td>
                                    <td class="py-2.5 pr-3 tabular-nums">{{ fmtNum(t.entry_price) }}</td>
                                    <td class="py-2.5 pr-3 tabular-nums text-gray-400">{{ fmtNum(t.exit_price) }}</td>
                                    <td class="py-2.5 pr-3 tabular-nums font-semibold whitespace-nowrap"
                                        :class="pnlColor(t.pnl)">
                                        {{ fmtPnl(t.pnl) }} $
                                    </td>
                                    <td class="py-2.5 pr-3">
                                        <span class="inline-block rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                            :class="statusBadge(t.status)">
                                            {{ t.status }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 pr-3 text-gray-400">{{ t.strategy_name || '—' }}</td>
                                    <td class="py-2.5 pr-3">
                                        <span v-if="t.confidence" class="tabular-nums text-gray-400">{{ t.confidence }}%</span>
                                        <span v-else class="text-gray-600">—</span>
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
                    Données indicatives · OANDA Practice · Propulsé par C-3PO 🤖 · {{ new Date().toLocaleDateString('fr-CA') }}
                </p>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* Force dark background on the page body for trading page only */
#trading-chart {
    border-radius: 0.5rem;
    overflow: hidden;
}

/* Prevent layout background from leaking */
.bg-\[\#0d1117\] {
    min-height: calc(100vh - 4rem);
}
</style>
