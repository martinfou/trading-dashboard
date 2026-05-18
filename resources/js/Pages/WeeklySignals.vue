<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

/* ------------------------------------------------------------------ */
/*  Props                                                              */
/* ------------------------------------------------------------------ */

const props = defineProps({
    signals: { type: Array, default: () => [] },
});

/* ------------------------------------------------------------------ */
/*  Helpers                                                            */
/* ------------------------------------------------------------------ */

function fmtDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('fr-CA', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
}

function fmtDateTime(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('fr-CA', {
        day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit',
    });
}

function fmtNum(n) {
    if (n === null || n === undefined || n === '—') return '—';
    return Number(n).toLocaleString('fr-CA', {
        minimumFractionDigits: 2, maximumFractionDigits: 5,
    });
}

/* ------------------------------------------------------------------ */
/*  Priority helpers                                                   */
/* ------------------------------------------------------------------ */

const PRIORITY_META = {
    1: { label: 'Priorité #1', icon: '🥇', color: 'bg-yellow-500/20 text-yellow-300 ring-yellow-500/30' },
    2: { label: 'Priorité #2', icon: '🥈', color: 'bg-slate-400/20 text-slate-300 ring-slate-400/30' },
    3: { label: 'Priorité #3', icon: '🥉', color: 'bg-amber-700/20 text-amber-500 ring-amber-700/30' },
};

function priorityMeta(priority) {
    return PRIORITY_META[priority] || PRIORITY_META[3];
}

/* ------------------------------------------------------------------ */
/*  Direction / Status helpers                                         */
/* ------------------------------------------------------------------ */

function directionClass(dir) {
    const d = (dir || '').toUpperCase();
    if (d === 'LONG') return 'text-emerald-400';
    if (d === 'SHORT') return 'text-red-400';
    return 'text-gray-400';
}

function directionArrow(dir) {
    const d = (dir || '').toUpperCase();
    if (d === 'LONG') return '↑';
    if (d === 'SHORT') return '↓';
    return '→';
}

function directionBg(dir) {
    const d = (dir || '').toUpperCase();
    if (d === 'LONG') return 'bg-emerald-500/10 border-emerald-500/25';
    if (d === 'SHORT') return 'bg-red-500/10 border-red-500/25';
    return 'bg-gray-500/10 border-gray-500/25';
}

function statusBadge(status) {
    if (!status) return '';
    const s = status.toUpperCase();
    if (s === 'PENDING') return 'bg-amber-500/20 text-amber-400 ring-1 ring-amber-500/30';
    if (s === 'EXECUTED') return 'bg-emerald-500/20 text-emerald-400 ring-1 ring-emerald-500/30';
    if (s === 'CANCELLED') return 'bg-red-500/20 text-red-400 ring-1 ring-red-500/30';
    return 'bg-gray-500/20 text-gray-400 ring-1 ring-gray-500/30';
}

function statusLabel(status) {
    if (!status) return '—';
    const s = status.toUpperCase();
    if (s === 'PENDING') return '⏳ En attente';
    if (s === 'EXECUTED') return '✅ Exécuté';
    if (s === 'CANCELLED') return '❌ Annulé';
    return status;
}

/* ------------------------------------------------------------------ */
/*  Signals grouped by week_label                                      */
/* ------------------------------------------------------------------ */

const groupedSignals = computed(() => {
    const groups = {};
    if (!props.signals || !props.signals.length) return [];

    for (const sig of props.signals) {
        const label = sig.week_label || 'Semaine inconnue';
        if (!groups[label]) groups[label] = { week_label: label, signals: [] };
        groups[label].signals.push(sig);
    }

    // Sort weeks descending (most recent first)
    return Object.values(groups).sort((a, b) => {
        if (a.week_label < b.week_label) return 1;
        if (a.week_label > b.week_label) return -1;
        return 0;
    });
});

/* ------------------------------------------------------------------ */
/*  Add signal form                                                    */
/* ------------------------------------------------------------------ */

const showForm = ref(false);

const form = ref({
    week_label: '',
    instrument: '',
    direction: 'LONG',
    entry_price: '',
    stop_loss: '',
    take_profit: '',
    priority: '1',
    catalyst: '',
    analysis: '',
});

const formSubmitting = ref(false);
const formErrors = ref({});

function resetForm() {
    form.value = {
        week_label: '',
        instrument: '',
        direction: 'LONG',
        entry_price: '',
        stop_loss: '',
        take_profit: '',
        priority: '1',
        catalyst: '',
        analysis: '',
    };
    formErrors.value = {};
}

function toggleForm() {
    showForm.value = !showForm.value;
    if (showForm.value) {
        // Suggest current week label
        const now = new Date();
        const weekNum = getWeekNumber(now);
        form.value.week_label = `S${weekNum} ${now.getFullYear()}`;
    }
}

function getWeekNumber(d) {
    d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
    d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
}

function submitForm() {
    formErrors.value = {};

    // Basic client-side validation
    const errors = {};
    if (!form.value.week_label.trim()) errors.week_label = 'La semaine est requise.';
    if (!form.value.instrument.trim()) errors.instrument = "L'instrument est requis.";
    if (!form.value.entry_price || isNaN(Number(form.value.entry_price))) errors.entry_price = 'Prix d\'entrée invalide.';
    if (!form.value.stop_loss || isNaN(Number(form.value.stop_loss))) errors.stop_loss = 'Stop loss invalide.';
    if (!form.value.take_profit || isNaN(Number(form.value.take_profit))) errors.take_profit = 'Take profit invalide.';

    if (Object.keys(errors).length) {
        formErrors.value = errors;
        return;
    }

    formSubmitting.value = true;

    router.post('/weekly-signals', form.value, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            resetForm();
            showForm.value = false;
            formSubmitting.value = false;
        },
        onError: (errs) => {
            formErrors.value = errs;
            formSubmitting.value = false;
        },
        onFinish: () => {
            formSubmitting.value = false;
        },
    });
}

/* ------------------------------------------------------------------ */
/*  Session week description                                           */
/* ------------------------------------------------------------------ */

const weekDescription = computed(() => {
    const now = new Date();
    const weekNum = getWeekNumber(now);
    const year = now.getFullYear();
    return `Analyse technique et fondamentale — Semaine ${weekNum} (${year})`;
});
</script>

<template>
    <Head title="Signaux Hebdomadaires · Trading" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-100">
                    📡 Signaux Hebdomadaires
                </h2>
                <button
                    @click="toggleForm"
                    class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-all"
                    :class="showForm
                        ? 'bg-red-500/20 text-red-400 ring-1 ring-red-500/30 hover:bg-red-500/30'
                        : 'bg-emerald-500/20 text-emerald-400 ring-1 ring-emerald-500/30 hover:bg-emerald-500/30'"
                >
                    <svg v-if="!showForm" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                    </svg>
                    {{ showForm ? 'Fermer' : 'Ajouter un signal' }}
                </button>
            </div>
        </template>

        <div class="bg-[#0d1117] py-6">
            <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

                <!-- ============================================================ -->
                <!--  Add Signal Form (collapsible)                               -->
                <!-- ============================================================ -->
                <div
                    v-if="showForm"
                    class="rounded-xl border border-[#21262d] bg-[#161b22] p-5 transition-all"
                >
                    <h3 class="mb-4 text-sm font-semibold text-gray-200">
                        ➕ Nouveau Signal
                    </h3>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <!-- Week label -->
                            <div>
                                <label class="mb-1 block text-[11px] font-medium uppercase tracking-wider text-gray-500">
                                    Semaine
                                </label>
                                <input
                                    v-model="form.week_label"
                                    type="text"
                                    placeholder="ex: S21 2026"
                                    class="w-full rounded-lg border bg-[#0d1117] px-3 py-2 text-xs text-gray-200 placeholder-gray-600 transition-colors"
                                    :class="formErrors.week_label
                                        ? 'border-red-500/50 focus:border-red-400 focus:ring-1 focus:ring-red-400/30'
                                        : 'border-[#21262d] focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/30'"
                                />
                                <p v-if="formErrors.week_label" class="mt-1 text-[10px] text-red-400">{{ formErrors.week_label }}</p>
                            </div>

                            <!-- Instrument -->
                            <div>
                                <label class="mb-1 block text-[11px] font-medium uppercase tracking-wider text-gray-500">
                                    Instrument
                                </label>
                                <input
                                    v-model="form.instrument"
                                    type="text"
                                    placeholder="ex: EUR/USD"
                                    class="w-full rounded-lg border bg-[#0d1117] px-3 py-2 text-xs text-gray-200 placeholder-gray-600 transition-colors"
                                    :class="formErrors.instrument
                                        ? 'border-red-500/50 focus:border-red-400 focus:ring-1 focus:ring-red-400/30'
                                        : 'border-[#21262d] focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/30'"
                                />
                                <p v-if="formErrors.instrument" class="mt-1 text-[10px] text-red-400">{{ formErrors.instrument }}</p>
                            </div>

                            <!-- Direction -->
                            <div>
                                <label class="mb-1 block text-[11px] font-medium uppercase tracking-wider text-gray-500">
                                    Direction
                                </label>
                                <select
                                    v-model="form.direction"
                                    class="w-full rounded-lg border bg-[#0d1117] px-3 py-2 text-xs text-gray-200 transition-colors"
                                    :class="form.direction === 'LONG'
                                        ? 'border-emerald-500/50 text-emerald-400'
                                        : 'border-red-500/50 text-red-400'"
                                >
                                    <option value="LONG">LONG ↑</option>
                                    <option value="SHORT">SHORT ↓</option>
                                </select>
                            </div>

                            <!-- Entry Price -->
                            <div>
                                <label class="mb-1 block text-[11px] font-medium uppercase tracking-wider text-gray-500">
                                    Prix d'entrée
                                </label>
                                <input
                                    v-model="form.entry_price"
                                    type="number"
                                    step="any"
                                    placeholder="0.00000"
                                    class="w-full rounded-lg border bg-[#0d1117] px-3 py-2 text-xs text-gray-200 placeholder-gray-600 transition-colors"
                                    :class="formErrors.entry_price
                                        ? 'border-red-500/50 focus:border-red-400 focus:ring-1 focus:ring-red-400/30'
                                        : 'border-[#21262d] focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/30'"
                                />
                                <p v-if="formErrors.entry_price" class="mt-1 text-[10px] text-red-400">{{ formErrors.entry_price }}</p>
                            </div>

                            <!-- Stop Loss -->
                            <div>
                                <label class="mb-1 block text-[11px] font-medium uppercase tracking-wider text-gray-500">
                                    Stop Loss
                                </label>
                                <input
                                    v-model="form.stop_loss"
                                    type="number"
                                    step="any"
                                    placeholder="0.00000"
                                    class="w-full rounded-lg border bg-[#0d1117] px-3 py-2 text-xs text-gray-200 placeholder-gray-600 transition-colors"
                                    :class="formErrors.stop_loss
                                        ? 'border-red-500/50 focus:border-red-400 focus:ring-1 focus:ring-red-400/30'
                                        : 'border-[#21262d] focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/30'"
                                />
                                <p v-if="formErrors.stop_loss" class="mt-1 text-[10px] text-red-400">{{ formErrors.stop_loss }}</p>
                            </div>

                            <!-- Take Profit -->
                            <div>
                                <label class="mb-1 block text-[11px] font-medium uppercase tracking-wider text-gray-500">
                                    Take Profit
                                </label>
                                <input
                                    v-model="form.take_profit"
                                    type="number"
                                    step="any"
                                    placeholder="0.00000"
                                    class="w-full rounded-lg border bg-[#0d1117] px-3 py-2 text-xs text-gray-200 placeholder-gray-600 transition-colors"
                                    :class="formErrors.take_profit
                                        ? 'border-red-500/50 focus:border-red-400 focus:ring-1 focus:ring-red-400/30'
                                        : 'border-[#21262d] focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/30'"
                                />
                                <p v-if="formErrors.take_profit" class="mt-1 text-[10px] text-red-400">{{ formErrors.take_profit }}</p>
                            </div>

                            <!-- Priority -->
                            <div>
                                <label class="mb-1 block text-[11px] font-medium uppercase tracking-wider text-gray-500">
                                    Priorité
                                </label>
                                <select
                                    v-model="form.priority"
                                    class="w-full rounded-lg border border-[#21262d] bg-[#0d1117] px-3 py-2 text-xs text-gray-200 transition-colors focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/30"
                                >
                                    <option value="1">🥇 #1 — Haute priorité</option>
                                    <option value="2">🥈 #2 — Priorité moyenne</option>
                                    <option value="3">🥉 #3 — Faible priorité</option>
                                </select>
                            </div>
                        </div>

                        <!-- Catalyst -->
                        <div>
                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wider text-gray-500">
                                Catalyseur
                            </label>
                            <input
                                v-model="form.catalyst"
                                type="text"
                                placeholder="Ex: Décision BCE, NFP, support technique majeur…"
                                class="w-full rounded-lg border border-[#21262d] bg-[#0d1117] px-3 py-2 text-xs text-gray-200 placeholder-gray-600 transition-colors focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/30"
                            />
                        </div>

                        <!-- Analysis -->
                        <div>
                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wider text-gray-500">
                                Analyse
                            </label>
                            <textarea
                                v-model="form.analysis"
                                rows="3"
                                placeholder="Analyse technique / fondamentale détaillée…"
                                class="w-full rounded-lg border border-[#21262d] bg-[#0d1117] px-3 py-2 text-xs text-gray-200 placeholder-gray-600 transition-colors focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/30 resize-y"
                            ></textarea>
                        </div>

                        <!-- Submit -->
                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                @click="toggleForm"
                                class="rounded-lg border border-[#21262d] bg-[#0d1117] px-4 py-2 text-xs font-medium text-gray-400 transition hover:bg-[#1c2128] hover:text-gray-200"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="formSubmitting"
                                class="flex items-center gap-2 rounded-lg bg-emerald-500/20 px-4 py-2 text-xs font-medium text-emerald-400 ring-1 ring-emerald-500/30 transition hover:bg-emerald-500/30 disabled:opacity-50"
                            >
                                <svg v-if="formSubmitting" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                {{ formSubmitting ? 'Enregistrement…' : 'Enregistrer le signal' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ============================================================ -->
                <!--  Header + Week description                                   -->
                <!-- ============================================================ -->
                <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-lg">📡</span>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-200">
                                Signaux de Trading
                            </h3>
                            <p class="mt-0.5 text-[11px] text-gray-500">
                                {{ weekDescription }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Empty State                                                 -->
                <!-- ============================================================ -->
                <div
                    v-if="!props.signals || !props.signals.length"
                    class="flex flex-col items-center py-20 text-center"
                >
                    <span class="mb-4 text-4xl opacity-30">📭</span>
                    <p class="text-base font-medium text-gray-500">
                        Aucun signal pour le moment
                    </p>
                    <p class="mt-1.5 text-sm text-gray-600">
                        Ajoutez votre premier signal hebdomadaire en cliquant sur
                        <span class="text-emerald-400">« Ajouter un signal »</span>.
                    </p>
                    <button
                        @click="showForm = true"
                        class="mt-6 flex items-center gap-2 rounded-lg bg-emerald-500/20 px-4 py-2.5 text-sm font-medium text-emerald-400 ring-1 ring-emerald-500/30 transition hover:bg-emerald-500/30"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Ajouter un signal
                    </button>
                </div>

                <!-- ============================================================ -->
                <!--  Signal Cards — Grouped by Week                              -->
                <!-- ============================================================ -->
                <div v-else class="space-y-6">
                    <div
                        v-for="group in groupedSignals"
                        :key="group.week_label"
                        class="space-y-3"
                    >
                        <!-- Week header -->
                        <div class="flex items-center gap-2">
                            <div class="h-px flex-1 bg-gradient-to-r from-[#21262d] to-transparent"></div>
                            <span class="text-xs font-semibold uppercase tracking-widest text-gray-500">
                                📅 {{ group.week_label }}
                            </span>
                            <div class="h-px flex-1 bg-gradient-to-l from-[#21262d] to-transparent"></div>
                        </div>

                        <!-- Signal cards -->
                        <div class="grid gap-3 lg:grid-cols-3">
                            <div
                                v-for="sig in group.signals"
                                :key="sig.id"
                                class="group rounded-xl border transition-all hover:shadow-md"
                                :class="[
                                    directionBg(sig.direction),
                                    'bg-[#161b22] hover:bg-[#1c2128]',
                                ]"
                            >
                                <!-- Card Header — Priority + Date -->
                                <div class="flex items-center justify-between border-b border-[#21262d] px-4 py-2.5">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1"
                                        :class="priorityMeta(sig.priority).color"
                                    >
                                        {{ priorityMeta(sig.priority).icon }}
                                        {{ priorityMeta(sig.priority).label }}
                                    </span>
                                    <span class="text-[10px] text-gray-600">
                                        {{ fmtDate(sig.created_at) }}
                                    </span>
                                </div>

                                <!-- Card Body -->
                                <div class="p-4">
                                    <!-- Instrument + Direction -->
                                    <div class="mb-3 flex items-center gap-2">
                                        <span class="text-lg font-bold tracking-tight text-gray-100">
                                            {{ sig.instrument }}
                                        </span>
                                        <span
                                            class="inline-flex items-center gap-0.5 text-lg font-bold"
                                            :class="directionClass(sig.direction)"
                                        >
                                            {{ sig.direction }}
                                            <span class="text-xl">{{ directionArrow(sig.direction) }}</span>
                                        </span>
                                    </div>

                                    <!-- Prices Grid -->
                                    <div class="mb-3 grid grid-cols-3 gap-2 rounded-lg bg-[#0d1117]/50 p-2.5">
                                        <div class="text-center">
                                            <p class="text-[10px] font-medium uppercase tracking-wider text-gray-500">
                                                Entrée
                                            </p>
                                            <p class="mt-0.5 text-sm font-bold tabular-nums text-gray-200">
                                                {{ fmtNum(sig.entry_price) }}
                                            </p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-[10px] font-medium uppercase tracking-wider text-gray-500">
                                                SL
                                            </p>
                                            <p class="mt-0.5 text-sm font-bold tabular-nums text-red-400">
                                                {{ fmtNum(sig.stop_loss) }}
                                            </p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-[10px] font-medium uppercase tracking-wider text-gray-500">
                                                TP
                                            </p>
                                            <p class="mt-0.5 text-sm font-bold tabular-nums text-emerald-400">
                                                {{ fmtNum(sig.take_profit) }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Catalyst -->
                                    <div v-if="sig.catalyst" class="mb-3">
                                        <p class="text-[10px] font-medium uppercase tracking-wider text-gray-500">
                                            Catalyseur
                                        </p>
                                        <p class="mt-0.5 text-xs leading-relaxed text-gray-300">
                                            {{ sig.catalyst }}
                                        </p>
                                    </div>

                                    <!-- Analysis (expandable) -->
                                    <div v-if="sig.analysis" class="mb-3">
                                        <p class="text-[10px] font-medium uppercase tracking-wider text-gray-500">
                                            Analyse
                                        </p>
                                        <p class="mt-0.5 text-xs leading-relaxed text-gray-400 line-clamp-3">
                                            {{ sig.analysis }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Card Footer — Status -->
                                <div class="flex items-center justify-between border-t border-[#21262d] px-4 py-2.5">
                                    <span
                                        class="inline-block rounded px-2 py-0.5 text-[10px] font-semibold"
                                        :class="statusBadge(sig.status)"
                                    >
                                        {{ statusLabel(sig.status) }}
                                    </span>
                                    <span class="text-[10px] text-gray-600">
                                        Créé le {{ fmtDateTime(sig.created_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Footer                                                       -->
                <!-- ============================================================ -->
                <p class="pb-2 text-center text-[10px] text-gray-600">
                    Signaux générés par analyse technique &amp; fondamentale · Propulsé par C-3PO 🤖
                </p>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.bg-\[\#0d1117\] {
    min-height: calc(100vh - 4rem);
}
</style>
