<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    🚀 Mission Control
                </h2>
                <div class="flex gap-2 items-center">
                    <span class="text-sm text-gray-500">
                        {{ summary.up }}/{{ summary.total }} machines UP
                    </span>
                    <button @click="refresh"
                            class="px-3 py-1 text-xs bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                        ↻ Refresh
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- ⚡ Summary Bar -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div class="text-2xl font-bold text-green-600">{{ summary.up }}</div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Machines UP</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div v-if="summary.down > 0" class="text-2xl font-bold text-red-600">
                            ⚠️ {{ summary.down }}
                        </div>
                        <div v-else class="text-2xl font-bold text-green-600">0</div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Machines DOWN</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div class="text-2xl font-bold text-indigo-600">{{ strategies.length }}</div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Stratégies Actives</div>
                    </div>
                </div>

                <!-- 🖥️ Machine Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div v-for="m in machines" :key="m.id"
                         class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">

                        <!-- Header -->
                        <div class="px-4 py-3 flex justify-between items-center"
                             :class="m.color === 'green' ? 'bg-green-50 dark:bg-green-900/20' :
                                    m.color === 'red' ? 'bg-red-50 dark:bg-red-900/20' :
                                    'bg-orange-50 dark:bg-orange-900/20'">
                            <div>
                                <span class="font-semibold text-sm">{{ formatRole(m.role) }}</span>
                                <span class="text-xs text-gray-500 ml-2">{{ m.name }}</span>
                            </div>
                            <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full"
                                  :class="m.color === 'green' ? 'bg-green-100 text-green-800' :
                                          m.color === 'red' ? 'bg-red-100 text-red-800' :
                                          'bg-orange-100 text-orange-800'">
                                <span class="w-1.5 h-1.5 rounded-full inline-block"
                                      :class="m.color === 'green' ? 'bg-green-500' :
                                              m.color === 'red' ? 'bg-red-500' :
                                              'bg-orange-500'"></span>
                                {{ m.status }}
                            </span>
                        </div>

                        <!-- Body -->
                        <div class="px-4 py-3 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Git</span>
                                <code class="text-xs font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">
                                    {{ m.git_commit || '—' }}
                                </code>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Version</span>
                                <span>{{ m.version || '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Uptime</span>
                                <span>{{ m.uptime || '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Last Check</span>
                                <span>{{ m.last_check || '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">OANDA</span>
                                <span :class="m.oanda_status === 'ok' ? 'text-green-600' : 'text-red-600'">
                                    {{ m.oanda_status || 'unknown' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Errors (24h)</span>
                                <span :class="m.errors_24h > 0 ? 'text-red-600 font-bold' : ''">
                                    {{ m.errors_24h }}
                                </span>
                            </div>

                            <!-- Resource bars -->
                            <div class="pt-2 space-y-1">
                                <ResourceBar label="CPU" :value="m.cpu" color="blue" />
                                <ResourceBar label="RAM" :value="m.memory" color="indigo" />
                                <ResourceBar label="Disk" :value="m.disk" color="purple" />
                            </div>

                            <!-- Active strategies -->
                            <div v-if="m.strategies && m.strategies.length" class="pt-2">
                                <div class="text-xs text-gray-500 mb-1">Active Strategies:</div>
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="s in m.strategies" :key="s"
                                          class="text-xs bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded">
                                        {{ s }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="pt-2 text-xs text-gray-400 italic">
                                No active strategies
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 📊 Strategy Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-sm">📈 Active Strategies — Lifecycle</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Strategy</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Phase</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">P&L</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Trades</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Win Rate</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Max DD</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="s in strategies" :key="s.name"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-2 font-medium">{{ s.name }}</td>
                                    <td class="px-4 py-2">
                                        <span class="text-xs px-2 py-0.5 rounded-full"
                                              :class="phaseClass(s.phase)">
                                            {{ s.phase }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-right"
                                        :class="s.pnl >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ formatPnl(s.pnl) }}
                                    </td>
                                    <td class="px-4 py-2 text-right">{{ s.trades }}</td>
                                    <td class="px-4 py-2 text-right"
                                        :class="s.win_rate >= 50 ? 'text-green-600' : 'text-red-600'">
                                        {{ s.win_rate }}%
                                    </td>
                                    <td class="px-4 py-2 text-right text-orange-600">
                                        {{ s.max_dd ? s.max_dd + '%' : '—' }}
                                    </td>
                                </tr>
                                <tr v-if="!strategies.length">
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                        No active strategies. Deploy one via <code class="text-xs bg-gray-100 px-1 rounded">deploy.sh promote</code>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 📡 API Status Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-sm">🔗 API Endpoints</h3>
                    </div>
                    <div class="px-4 py-3 text-xs space-y-1">
                        <div><code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">POST /api/health/ping</code> — Machine health report</div>
                        <div><code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">GET /api/health</code> — All machines status (JSON)</div>
                        <div><code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">POST /api/deployments</code> — Strategy promotion</div>
                        <div><code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">GET /api/strategies</code> — All strategies (JSON)</div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router } from '@inertiajs/vue3';

export default {
    components: { AuthenticatedLayout },
    props: {
        machines: Array,
        summary: Object,
        strategies: Array,
        refreshUrl: String,
    },
    methods: {
        refresh() {
            router.visit(this.refreshUrl, {
                preserveState: true,
                preserveScroll: true,
                only: ['machines', 'summary', 'strategies'],
            });
        },
        formatRole(role) {
            const labels = { backtest: '🧪 Backtest', paper: '📄 Paper', live: '💰 Live' };
            return labels[role] || role;
        },
        formatPnl(pnl) {
            if (pnl === null || pnl === undefined) return '—';
            const sign = pnl >= 0 ? '+' : '';
            return `${sign}$${Number(pnl).toFixed(2)}`;
        },
        phaseClass(phase) {
            return {
                backtest: 'bg-gray-100 text-gray-700',
                paper: 'bg-yellow-100 text-yellow-800',
                live: 'bg-green-100 text-green-800',
                retired: 'bg-red-100 text-red-800',
            }[phase] || 'bg-gray-100 text-gray-700';
        },
    },
};
</script>

<script setup>
import ResourceBar from '@/Components/ResourceBar.vue';
</script>
