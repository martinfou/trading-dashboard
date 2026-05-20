<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

/* ------------------------------------------------------------------ */
/*  Props                                                              */
/* ------------------------------------------------------------------ */

const props = defineProps({
    strategyTypes: { type: Array, default: () => [] },
    riskLevels:    { type: Array, default: () => [] },
});

/* ------------------------------------------------------------------ */
/*  Form state                                                         */
/* ------------------------------------------------------------------ */

const type       = ref('trend');
const name       = ref('MyStrategy');
const riskLevel  = ref('moderate');
const population = ref(50);
const generations = ref(20);

/* ------------------------------------------------------------------ */
/*  Output / status                                                    */
/* ------------------------------------------------------------------ */

const loading  = ref(false);
const output   = ref('');
const success  = ref(null);
const errorMsg = ref('');

/* ------------------------------------------------------------------ */
/*  Generate                                                           */
/* ------------------------------------------------------------------ */

async function handleGenerate() {
    loading.value   = true;
    success.value   = null;
    errorMsg.value  = '';
    output.value    = '';

    try {
        const res = await axios.post('/strategy/generate', {
            type:        type.value,
            name:        name.value,
            riskLevel:   riskLevel.value,
            population:  population.value,
            generations: generations.value,
        });

        success.value = res.data.success;
        output.value  = res.data.output;

        if (!res.data.success && res.data.error) {
            errorMsg.value = res.data.error;
        }
    } catch (err) {
        success.value = false;
        if (err.response?.data?.error) {
            errorMsg.value = err.response.data.error;
        } else if (err.response?.data?.message) {
            errorMsg.value = err.response.data.message;
        } else {
            errorMsg.value = err.message || 'Erreur de connexion au serveur.';
        }
    } finally {
        loading.value = false;
    }
}

/* ------------------------------------------------------------------ */
/*  Helpers                                                            */
/* ------------------------------------------------------------------ */

function scrollToOutput() {
    setTimeout(() => {
        const el = document.getElementById('strategy-output');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
}
</script>

<template>
    <Head title="🧬 Strategy Generator" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-100">
                    🧬 Strategy Generator
                </h2>
            </div>
        </template>

        <div class="bg-[#0d1117] py-6">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

                <!-- ============================================================ -->
                <!--  Description Card                                            -->
                <!-- ============================================================ -->
                <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-5">
                    <p class="text-sm leading-relaxed text-gray-400">
                        Générez des stratégies de trading algorithmiques à l'aide du
                        <strong class="text-gray-200">GeneticEngine Java</strong>.
                        Configurez les paramètres ci-dessous et lancez la génération.
                        Le moteur produira une classe Java compilée et un rapport de backtest.
                    </p>
                </div>

                <!-- ============================================================ -->
                <!--  Form Card                                                   -->
                <!-- ============================================================ -->
                <div class="rounded-xl border border-[#21262d] bg-[#161b22] p-6">
                    <form @submit.prevent="handleGenerate" class="space-y-5">

                        <!-- Row 1: Type & Name -->
                        <div class="grid gap-5 sm:grid-cols-2">
                            <!-- Type -->
                            <div>
                                <label for="type" class="mb-1.5 block text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Type de stratégie
                                </label>
                                <select
                                    id="type"
                                    v-model="type"
                                    class="w-full rounded-lg border border-[#30363d] bg-[#0d1117] px-3 py-2.5 text-sm text-gray-200 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                >
                                    <option
                                        v-for="t in strategyTypes"
                                        :key="t.value"
                                        :value="t.value"
                                    >
                                        {{ t.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Name -->
                            <div>
                                <label for="name" class="mb-1.5 block text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Nom de la stratégie
                                </label>
                                <input
                                    id="name"
                                    v-model="name"
                                    type="text"
                                    placeholder="MyStrategy"
                                    class="w-full rounded-lg border border-[#30363d] bg-[#0d1117] px-3 py-2.5 text-sm text-gray-200 placeholder-gray-600 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                />
                            </div>
                        </div>

                        <!-- Row 2: Risk Level & Population -->
                        <div class="grid gap-5 sm:grid-cols-2">
                            <!-- Risk Level -->
                            <div>
                                <label for="riskLevel" class="mb-1.5 block text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Niveau de risque
                                </label>
                                <select
                                    id="riskLevel"
                                    v-model="riskLevel"
                                    class="w-full rounded-lg border border-[#30363d] bg-[#0d1117] px-3 py-2.5 text-sm text-gray-200 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                >
                                    <option
                                        v-for="r in riskLevels"
                                        :key="r.value"
                                        :value="r.value"
                                    >
                                        {{ r.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Population -->
                            <div>
                                <label for="population" class="mb-1.5 block text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Taille de la population : <span class="text-emerald-400">{{ population }}</span>
                                </label>
                                <input
                                    id="population"
                                    v-model.number="population"
                                    type="range"
                                    min="10"
                                    max="100"
                                    step="5"
                                    class="w-full h-2 rounded-lg cursor-pointer appearance-none bg-[#30363d] accent-emerald-500"
                                />
                                <div class="mt-1 flex justify-between text-[10px] text-gray-600">
                                    <span>10</span>
                                    <span>100</span>
                                </div>
                            </div>
                        </div>

                        <!-- Row 3: Generations -->
                        <div>
                            <label for="generations" class="mb-1.5 block text-xs font-medium uppercase tracking-wider text-gray-500">
                                Générations : <span class="text-emerald-400">{{ generations }}</span>
                            </label>
                            <input
                                id="generations"
                                v-model.number="generations"
                                type="range"
                                min="5"
                                max="100"
                                step="5"
                                class="w-full h-2 rounded-lg cursor-pointer appearance-none bg-[#30363d] accent-emerald-500"
                            />
                            <div class="mt-1 flex justify-between text-[10px] text-gray-600">
                                <span>5</span>
                                <span>100</span>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="flex items-center gap-4 pt-2">
                            <button
                                type="submit"
                                :disabled="loading || !name.trim()"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <!-- Spinner when loading -->
                                <svg
                                    v-if="loading"
                                    class="h-4 w-4 animate-spin text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <span v-else>🚀</span>
                                {{ loading ? 'Génération en cours…' : 'Generate Strategy' }}
                            </button>

                            <span v-if="loading" class="text-xs text-gray-500">
                                Le GeneticEngine compile le projet et exécute le backtest…
                            </span>
                        </div>
                    </form>
                </div>

                <!-- ============================================================ -->
                <!--  Output Section                                               -->
                <!-- ============================================================ -->
                <div
                    v-if="output || errorMsg || success !== null"
                    id="strategy-output"
                    class="rounded-xl border border-[#21262d] bg-[#161b22] p-5"
                >
                    <!-- Success banner -->
                    <div
                        v-if="success === true"
                        class="mb-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3"
                    >
                        <p class="text-sm font-medium text-emerald-400">
                            ✅ Génération terminée avec succès !
                        </p>
                    </div>

                    <!-- Error banner -->
                    <div
                        v-else-if="success === false"
                        class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3"
                    >
                        <p class="text-sm font-medium text-red-400">
                            ❌ La génération a échoué.
                        </p>
                        <p v-if="errorMsg" class="mt-1 text-xs text-red-300">
                            {{ errorMsg }}
                        </p>
                    </div>

                    <!-- Output text -->
                    <div v-if="output">
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wider text-gray-500">
                            Sortie du moteur
                        </label>
                        <pre class="max-h-96 overflow-auto rounded-lg border border-[#30363d] bg-[#0d1117] p-4 text-xs leading-relaxed text-gray-300"><code>{{ output }}</code></pre>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!--  Footer                                                       -->
                <!-- ============================================================ -->
                <p class="pb-2 text-center text-[10px] text-gray-600">
                    Propulsé par le GeneticEngine Java · trading-bridge 🧬 · C-3PO 🤖
                </p>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Style the range input slider */
input[type='range']::-webkit-slider-thumb {
    appearance: none;
    height: 1rem;
    width: 1rem;
    border-radius: 9999px;
    background: #10b981;
    cursor: pointer;
    border: 2px solid #0d1117;
}

input[type='range']::-moz-range-thumb {
    height: 1rem;
    width: 1rem;
    border-radius: 9999px;
    background: #10b981;
    cursor: pointer;
    border: 2px solid #0d1117;
}

/* Output textarea scrolling */
pre code {
    white-space: pre-wrap;
    word-break: break-all;
}
</style>
