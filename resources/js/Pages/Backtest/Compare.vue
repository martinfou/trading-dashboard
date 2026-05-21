<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    ⚔️ Backtest Comparison
                </h2>
                <div class="flex gap-2 items-center">
                    <span v-if="lastRun" class="text-xs text-gray-400">
                        Last run: {{ lastRun }}
                    </span>
                    <button @click="runBatch"
                            :disabled="loading"
                            class="px-4 py-1.5 text-xs font-medium bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        <span v-if="loading">⏳ Running...</span>
                        <span v-else>▶ Run All Backtests</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- ⚠️ No data -->
                <div v-if="!comparison && !loading" class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
                    <div class="text-4xl mb-4">⚔️</div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                        No comparison data yet
                    </h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Click <strong>Run All Backtests</strong> to test ALL strategies against historical data
                        and see which one performs best.
                    </p>
                    <button @click="runBatch"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm">
                        ▶ Run All Backtests
                    </button>
                </div>

                <!-- 🏆 Leaderboard -->
                <div v-if="comparison && comparison.comparison" class="space-y-6">
                    <!-- Summary cards -->
                    <div class="grid grid-cols-4 gap-4">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                            <div class="text-2xl font-bold text-indigo-600">{{ comparison.comparison.length }}</div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Strategies</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                            <div class="text-2xl font-bold text-green-600">
                                {{ bestSharpe.name }}
                            </div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide">
                                Best Sharpe ({{ bestSharpe.sharpe }})
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                            <div class="text-2xl font-bold" :class="bestReturn.returnPct >= 0 ? 'text-green-600' : 'text-red-600'">
                                {{ bestReturn.returnPct.toFixed(1) }}%
                            </div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide">
                                Best Return ({{ bestReturn.name }})
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                            <div class="text-2xl font-bold" :class="bestPf.pf >= 1 ? 'text-green-600' : 'text-red-600'">
                                {{ bestPf.pf.toFixed(1) }}x
                            </div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide">
                                Best PF ({{ bestPf.name }})
                            </div>
                        </div>
                    </div>

                    <!-- 📊 Comparison Table -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <h3 class="font-semibold text-sm">📊 Ranked by Sharpe Ratio</h3>
                            <span class="text-xs text-gray-400">{{ displayData }} bars · $50,000 capital</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900/50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Strategy</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Return %</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Sharpe</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Sortino</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">PF</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Win Rate</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Max DD</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Trades</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Time</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="(s, i) in sorted" :key="s.name"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                        :class="{'bg-indigo-50 dark:bg-indigo-900/10': i === 0}">
                                        <td class="px-3 py-2 font-mono text-xs">
                                            <span v-if="i === 0" class="text-lg">🥇</span>
                                            <span v-else-if="i === 1" class="text-lg">🥈</span>
                                            <span v-else-if="i === 2" class="text-lg">🥉</span>
                                            <span v-else>{{ i + 1 }}</span>
                                        </td>
                                        <td class="px-3 py-2 font-medium">
                                            <span class="font-semibold">{{ s.name }}</span>
                                            <span class="text-xs text-gray-400 ml-1 font-mono">{{ s.class }}</span>
                                            <span v-if="i === 0"
                                                  class="ml-2 text-xs bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded-full font-bold">
                                                BEST
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono"
                                            :class="s.returnPct >= 0 ? 'text-green-600' : 'text-red-600'">
                                            {{ s.returnPct.toFixed(2) }}%
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono"
                                            :class="s.sharpe >= 1 ? 'text-green-600' : s.sharpe >= 0 ? 'text-yellow-600' : 'text-red-600'">
                                            {{ s.sharpe.toFixed(2) }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono text-gray-600">
                                            {{ s.sortino.toFixed(2) }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono"
                                            :class="s.profitFactor >= 1 ? 'text-green-600' : 'text-red-600'">
                                            {{ s.profitFactor > 0 ? s.profitFactor.toFixed(2) : '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono"
                                            :class="s.winRate >= 50 ? 'text-green-600' : 'text-red-600'">
                                            {{ s.winRate.toFixed(1) }}%
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono text-orange-600">
                                            {{ s.maxDd > 0 ? s.maxDd.toFixed(2) + '%' : '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono text-gray-600">
                                            {{ s.trades }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono text-gray-400 text-xs">
                                            {{ s.elapsedMs }}ms
                                        </td>
                                    </tr>
                                    <tr v-if="!sorted.length">
                                        <td colspan="10" class="px-4 py-8 text-center text-gray-400">
                                            No strategies ran. Run a batch backtest to see results.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 📋 Strategy Details -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-sm">📋 Full JSON Output</h3>
                        </div>
                        <div class="p-4">
                            <pre class="text-xs font-mono bg-gray-50 dark:bg-gray-900 rounded-lg p-3 overflow-x-auto max-h-64">{{ prettyJson }}</pre>
                        </div>
                    </div>
                </div>

                <!-- ❌ Error -->
                <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <span class="text-red-500 text-lg">⚠️</span>
                        <div>
                            <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Error</h3>
                            <p class="text-xs text-red-600 dark:text-red-300 mt-1">{{ error }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

export default {
    components: { AuthenticatedLayout },
    props: {
        comparison: Object,
        lastRun: String,
        displayData: { type: String, default: '6223' },
    },
    data() {
        return {
            loading: false,
            error: null,
            localComparison: this.comparison,
            localLastRun: this.lastRun,
        };
    },
    computed: {
        list() {
            return this.localComparison?.comparison || [];
        },
        sorted() {
            return [...this.list].sort((a, b) => b.sharpe - a.sharpe);
        },
        bestSharpe() {
            if (!this.sorted.length) return { name: '—', sharpe: 0 };
            return this.sorted[0];
        },
        bestReturn() {
            if (!this.list.length) return { name: '—', returnPct: 0 };
            return [...this.list].reduce((a, b) => a.returnPct > b.returnPct ? a : b);
        },
        bestPf() {
            if (!this.list.length) return { name: '—', pf: 0 };
            return [...this.list].reduce((a, b) => a.profitFactor > b.profitFactor ? a : b);
        },
        prettyJson() {
            return JSON.stringify(this.localComparison, null, 2);
        },
    },
    methods: {
        async runBatch() {
            this.loading = true;
            this.error = null;
            try {
                const resp = await axios.post('/backtest-compare/run');
                if (resp.data.success) {
                    this.localComparison = resp.data.comparison;
                    this.localLastRun = resp.data.lastRun;
                } else {
                    this.error = resp.data.error || 'Unknown error';
                }
            } catch (e) {
                this.error = e.response?.data?.error || e.message || 'Failed to run batch backtest';
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
