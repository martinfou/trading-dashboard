<template>
  <div class="min-h-screen bg-[#0d1117] text-gray-200 p-6">
    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Page heading -->
      <h1 class="text-2xl font-semibold text-gray-100 tracking-tight">Historique des trades</h1>

      <!-- Stats header -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-[#161b22] border border-[#30363d] rounded-lg p-5">
          <p class="text-xs font-medium uppercase tracking-widest text-gray-500 mb-1">P&L Total</p>
          <p
            v-if="stats.totalPnl !== null && stats.totalPnl !== undefined"
            class="text-2xl font-bold"
            :class="stats.totalPnl >= 0 ? 'text-green-400' : 'text-red-400'"
          >
            {{ formatPnl(stats.totalPnl) }}
          </p>
          <p v-else class="text-2xl font-bold text-gray-500">—</p>
        </div>
        <div class="bg-[#161b22] border border-[#30363d] rounded-lg p-5">
          <p class="text-xs font-medium uppercase tracking-widest text-gray-500 mb-1">Taux de réussite</p>
          <p class="text-2xl font-bold text-blue-400">
            {{ stats.winRate !== null && stats.winRate !== undefined ? `${Number(stats.winRate).toFixed(1)}%` : '—' }}
          </p>
        </div>
        <div class="bg-[#161b22] border border-[#30363d] rounded-lg p-5">
          <p class="text-xs font-medium uppercase tracking-widest text-gray-500 mb-1">Trades clôturés</p>
          <p class="text-2xl font-bold text-gray-100">
            {{ stats.totalClosed ?? '—' }}
          </p>
        </div>
      </div>

      <!-- Filters bar -->
      <div class="bg-[#161b22] border border-[#30363d] rounded-lg p-4">
        <div class="flex flex-wrap items-center gap-3">
          <!-- Status -->
          <select
            :value="currentFilters.status ?? 'ALL'"
            @change="applyFilter('status', $event.target.value)"
            class="bg-[#0d1117] border border-[#30363d] text-gray-300 text-sm rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[130px]"
          >
            <option value="ALL">Tous les statuts</option>
            <option value="PENDING">En attente</option>
            <option value="OPEN">Ouvert</option>
            <option value="CLOSED">Clôturé</option>
            <option value="CANCELLED">Annulé</option>
          </select>

          <!-- Instrument -->
          <select
            :value="currentFilters.instrument ?? 'ALL'"
            @change="applyFilter('instrument', $event.target.value)"
            class="bg-[#0d1117] border border-[#30363d] text-gray-300 text-sm rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[150px]"
          >
            <option value="ALL">Tous les instruments</option>
            <option v-for="inst in filters.instruments" :key="inst" :value="inst">{{ inst }}</option>
          </select>

          <!-- Side -->
          <select
            :value="currentFilters.side ?? 'ALL'"
            @change="applyFilter('side', $event.target.value)"
            class="bg-[#0d1117] border border-[#30363d] text-gray-300 text-sm rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[110px]"
          >
            <option value="ALL">Tous les sens</option>
            <option value="BUY">Achat</option>
            <option value="SELL">Vente</option>
          </select>

          <!-- Search -->
          <div class="relative flex-1 min-w-[200px]">
            <svg
              class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"
              />
            </svg>
            <input
              :value="currentFilters.search ?? ''"
              @input="debouncedSearch"
              type="text"
              placeholder="Rechercher (stratégie, catalyseur, notes…)"
              class="w-full bg-[#0d1117] border border-[#30363d] text-gray-300 text-sm rounded-md pl-9 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-600"
            />
          </div>
        </div>
      </div>

      <!-- Trades table -->
      <div class="bg-[#161b22] border border-[#30363d] rounded-lg overflow-hidden">
        <div v-if="trades.data && trades.data.length" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-[#30363d] bg-[#0d1117]/40">
                <th class="text-left px-4 py-3 font-medium text-gray-500 uppercase tracking-wider text-xs">Date</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500 uppercase tracking-wider text-xs">Instrument</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500 uppercase tracking-wider text-xs">Sens</th>
                <th class="text-right px-4 py-3 font-medium text-gray-500 uppercase tracking-wider text-xs">Entrée</th>
                <th class="text-right px-4 py-3 font-medium text-gray-500 uppercase tracking-wider text-xs">Sortie</th>
                <th class="text-right px-4 py-3 font-medium text-gray-500 uppercase tracking-wider text-xs">P&amp;L</th>
                <th class="text-center px-4 py-3 font-medium text-gray-500 uppercase tracking-wider text-xs">Statut</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500 uppercase tracking-wider text-xs">Stratégie</th>
                <th class="text-center px-4 py-3 font-medium text-gray-500 uppercase tracking-wider text-xs w-16"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#30363d]">
              <template v-for="trade in trades.data" :key="trade.id">
                <!-- Summary row -->
                <tr
                  class="hover:bg-[#1c2128] cursor-pointer transition-colors"
                  :class="{ 'bg-[#1c2128] border-l-2 border-l-blue-500': expandedId === trade.id }"
                  @click="toggleExpand(trade.id)"
                >
                  <td class="px-4 py-3 text-gray-400 whitespace-nowrap text-xs">
                    {{ formatDate(trade.entry_time || trade.created_at) }}
                  </td>
                  <td class="px-4 py-3 font-medium text-gray-200 whitespace-nowrap">
                    {{ trade.instrument }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap">
                    <span
                      class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded"
                      :class="trade.side === 'BUY'
                        ? 'bg-green-900/50 text-green-400 border border-green-700/50'
                        : 'bg-red-900/50 text-red-400 border border-red-700/50'"
                    >
                      <span v-if="trade.side === 'BUY'" class="text-xs">▲</span>
                      <span v-else class="text-xs">▼</span>
                      {{ trade.side === 'BUY' ? 'BUY' : 'SELL' }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-right text-gray-300 tabular-nums whitespace-nowrap">
                    {{ trade.entry_price ? Number(trade.entry_price).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 5 }) : '—' }}
                  </td>
                  <td class="px-4 py-3 text-right text-gray-300 tabular-nums whitespace-nowrap">
                    {{ trade.exit_price ? Number(trade.exit_price).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 5 }) : '—' }}
                  </td>
                  <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap font-medium">
                    <span v-if="trade.pnl !== null && trade.pnl !== undefined" :class="trade.pnl >= 0 ? 'text-green-400' : 'text-red-400'">
                      {{ formatPnl(trade.pnl) }}
                    </span>
                    <span v-else class="text-gray-600">—</span>
                  </td>
                  <td class="px-4 py-3 text-center whitespace-nowrap">
                    <span
                      class="inline-block text-xs font-semibold px-2.5 py-0.5 rounded-full"
                      :class="statusBadgeClass(trade.status)"
                    >
                      {{ statusLabel(trade.status) }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-gray-400 text-xs max-w-[140px] truncate whitespace-nowrap">
                    {{ trade.strategy_name || '—' }}
                  </td>
                  <td class="px-4 py-3 text-center">
                    <svg
                      class="w-4 h-4 mx-auto text-gray-500 transition-transform"
                      :class="{ 'rotate-180': expandedId === trade.id }"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </td>
                </tr>

                <!-- Expanded detail row -->
                <tr v-if="expandedId === trade.id">
                  <td colspan="9" class="px-0 py-0">
                    <div class="bg-[#1c2128] border-t border-[#30363d] px-4 py-4">
                      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Catalyst -->
                        <div>
                          <p class="text-xs font-medium uppercase tracking-wider text-gray-500 mb-1">Catalyseur</p>
                          <p class="text-sm text-gray-300">{{ trade.catalyst || '—' }}</p>
                        </div>

                        <!-- Entry time -->
                        <div>
                          <p class="text-xs font-medium uppercase tracking-wider text-gray-500 mb-1">Date d'entrée</p>
                          <p class="text-sm text-gray-300">{{ trade.entry_time ? formatDateTime(trade.entry_time) : '—' }}</p>
                        </div>

                        <!-- Exit time -->
                        <div>
                          <p class="text-xs font-medium uppercase tracking-wider text-gray-500 mb-1">Date de sortie</p>
                          <p class="text-sm text-gray-300">{{ trade.exit_time ? formatDateTime(trade.exit_time) : '—' }}</p>
                        </div>

                        <!-- Stop loss -->
                        <div>
                          <p class="text-xs font-medium uppercase tracking-wider text-gray-500 mb-1">Stop Loss</p>
                          <p class="text-sm text-gray-300">{{ trade.stop_loss ? Number(trade.stop_loss).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 5 }) : '—' }}</p>
                        </div>

                        <!-- Take profit -->
                        <div>
                          <p class="text-xs font-medium uppercase tracking-wider text-gray-500 mb-1">Take Profit</p>
                          <p class="text-sm text-gray-300">{{ trade.take_profit ? Number(trade.take_profit).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 5 }) : '—' }}</p>
                        </div>

                        <!-- Confidence -->
                        <div>
                          <p class="text-xs font-medium uppercase tracking-wider text-gray-500 mb-1">Confiance</p>
                          <p class="text-sm text-gray-300">{{ trade.confidence !== null && trade.confidence !== undefined ? `${trade.confidence}/10` : '—' }}</p>
                        </div>

                        <!-- Tags -->
                        <div class="md:col-span-2 lg:col-span-3">
                          <p class="text-xs font-medium uppercase tracking-wider text-gray-500 mb-1">Tags</p>
                          <div v-if="trade.tags && trade.tags.length" class="flex flex-wrap gap-1.5">
                            <span
                              v-for="tag in trade.tags"
                              :key="tag"
                              class="text-xs bg-[#0d1117] text-gray-400 border border-[#30363d] px-2 py-0.5 rounded-full"
                            >
                              #{{ tag }}
                            </span>
                          </div>
                          <p v-else class="text-sm text-gray-600">—</p>
                        </div>

                        <!-- Notes (editable when OPEN) -->
                        <div class="md:col-span-2 lg:col-span-3">
                          <div class="flex items-center justify-between mb-1">
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Notes</p>
                            <button
                              v-if="trade.status === 'OPEN' && editingNotesId !== trade.id"
                              @click.stop="startEditNotes(trade, $event)"
                              class="text-xs text-gray-400 hover:text-gray-200 transition-colors"
                            >
                              Modifier
                            </button>
                            <button
                              v-if="trade.status === 'OPEN' && editingNotesId === trade.id"
                              @click.stop="saveNotes(trade)"
                              class="text-xs text-blue-400 hover:text-blue-300 transition-colors"
                            >
                              Enregistrer
                            </button>
                          </div>
                          <div
                            v-if="trade.status === 'OPEN' && editingNotesId === trade.id"
                            @click.stop
                          >
                            <textarea
                              v-model="editNotes"
                              rows="3"
                              class="w-full bg-[#0d1117] border border-[#30363d] text-gray-300 text-sm rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-600 resize-none"
                              placeholder="Ajouter des notes…"
                            ></textarea>
                            <button
                              @click.stop="cancelEditNotes()"
                              class="mt-1 text-xs text-gray-500 hover:text-gray-400 transition-colors"
                            >
                              Annuler
                            </button>
                          </div>
                          <p
                            v-else-if="trade.notes"
                            class="text-sm text-gray-400 leading-relaxed whitespace-pre-wrap"
                          >
                            {{ trade.notes }}
                          </p>
                          <p v-else class="text-sm text-gray-600">—</p>
                        </div>

                        <!-- P&L edit (when OPEN) -->
                        <div class="md:col-span-2 lg:col-span-3">
                          <div class="flex items-center justify-between mb-1">
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">P&amp;L</p>
                            <button
                              v-if="trade.status === 'OPEN' && editingPnlId !== trade.id"
                              @click.stop="startEditPnl(trade, $event)"
                              class="text-xs text-gray-400 hover:text-gray-200 transition-colors"
                            >
                              Modifier
                            </button>
                          </div>
                          <div v-if="trade.status === 'OPEN' && editingPnlId === trade.id" @click.stop>
                            <div class="flex items-center gap-2">
                              <input
                                v-model="editPnl"
                                type="number"
                                step="0.01"
                                class="w-40 bg-[#0d1117] border border-[#30363d] text-gray-300 text-sm rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              />
                              <button
                                @click.stop="savePnl(trade)"
                                class="text-xs bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded-md transition-colors"
                              >
                                Mettre à jour
                              </button>
                              <button
                                @click.stop="cancelEditPnl()"
                                class="text-xs text-gray-500 hover:text-gray-400 transition-colors"
                              >
                                Annuler
                              </button>
                            </div>
                          </div>
                          <p v-else class="text-sm text-gray-300 tabular-nums font-medium">{{ trade.pnl !== null && trade.pnl !== undefined ? formatPnl(trade.pnl) : '—' }}</p>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Empty state -->
        <div v-else class="py-16 text-center">
          <svg
            class="w-12 h-12 mx-auto text-gray-600 mb-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 14l2 2 4-4"
            />
          </svg>
          <p class="text-gray-500 text-base font-medium">Aucun trade trouvé</p>
          <p class="text-gray-600 text-sm mt-1">Essayez de modifier vos filtres pour élargir la recherche.</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="trades.data && trades.data.length && trades.links" class="flex justify-center">
        <div class="inline-flex items-center gap-1 bg-[#161b22] border border-[#30363d] rounded-lg p-1">
          <template v-for="link in trades.links" :key="link.label">
            <!-- Spacer label -->
            <span
              v-if="link.label === '...'"
              class="px-2 py-1 text-gray-500 text-sm select-none"
            >…</span>

            <!-- Previous/Next arrows -->
            <Link
              v-else-if="link.label === 'pagination.previous' || link.label === '&laquo; Previous'"
              :href="link.url || '#'"
              :class="[
                'px-3 py-1.5 text-sm rounded-md transition-colors',
                link.url
                  ? 'text-gray-400 hover:text-gray-200 hover:bg-[#30363d]'
                  : 'text-gray-600 cursor-default pointer-events-none'
              ]"
              :preserve-state="true"
              :preserve-scroll="true"
            >
              <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </Link>

            <Link
              v-else-if="link.label === 'pagination.next' || link.label === 'Next &raquo;'"
              :href="link.url || '#'"
              :class="[
                'px-3 py-1.5 text-sm rounded-md transition-colors',
                link.url
                  ? 'text-gray-400 hover:text-gray-200 hover:bg-[#30363d]'
                  : 'text-gray-600 cursor-default pointer-events-none'
              ]"
              :preserve-state="true"
              :preserve-scroll="true"
            >
              <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </Link>

            <!-- Page numbers -->
            <Link
              v-else
              :href="link.url || '#'"
              :class="[
                'px-3 py-1.5 text-sm rounded-md transition-colors min-w-[32px] text-center',
                link.active
                  ? 'bg-blue-600 text-white font-medium'
                  : link.url
                    ? 'text-gray-400 hover:text-gray-200 hover:bg-[#30363d]'
                    : 'text-gray-600 cursor-default pointer-events-none'
              ]"
              :preserve-state="true"
              :preserve-scroll="true"
            >
              {{ link.label }}
            </Link>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  trades: { type: Object, required: true },
  stats: { type: Object, required: true },
  filters: { type: Object, required: true },
})

// ── Current filter values ──────────────────────────────────
const currentFilters = ref({ ...(props.filters.current || {}) })

// ── Expanded row ───────────────────────────────────────────
const expandedId = ref(null)

function toggleExpand(tradeId) {
  expandedId.value = expandedId.value === tradeId ? null : tradeId
  // Reset inline editing state on collapse
  if (expandedId.value !== tradeId || expandedId.value === null) {
    editingNotesId.value = null
    editingPnlId.value = null
    editNotes.value = ''
    editPnl.value = ''
  }
}

// ── Inline edit: notes ────────────────────────────────────
const editingNotesId = ref(null)
const editNotes = ref('')

function startEditNotes(trade, event) {
  if (event) event.stopPropagation()
  if (trade.status !== 'OPEN') return
  editingNotesId.value = trade.id
  editingPnlId.value = null
  editNotes.value = trade.notes || ''
}

function saveNotes(trade) {
  router.put(
    route('trades.update', trade.id),
    { notes: editNotes.value },
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        editingNotesId.value = null
        editNotes.value = ''
      },
    }
  )
}

// ── Inline edit: P&L ──────────────────────────────────────
const editingPnlId = ref(null)
const editPnl = ref('')

function startEditPnl(trade, event) {
  if (event) event.stopPropagation()
  if (trade.status !== 'OPEN') return
  editingPnlId.value = trade.id
  editingNotesId.value = null
  editPnl.value = trade.pnl ?? ''
}

function cancelEditNotes() {
  editingNotesId.value = null
  editNotes.value = ''
}

function savePnl(trade) {
  router.put(
    route('trades.update', trade.id),
    { pnl: editPnl.value },
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        editingPnlId.value = null
        editPnl.value = ''
      },
    }
  )
}

function cancelEditPnl() {
  editingPnlId.value = null
  editPnl.value = ''
}

// ── Filters ────────────────────────────────────────────────
function applyFilter(key, value) {
  const params = { ...currentFilters.value }
  params[key] = value === 'ALL' ? null : value

  // Clean nulls
  const query = {}
  for (const [k, v] of Object.entries(params)) {
    if (v !== null && v !== undefined && v !== '') {
      query[k] = v
    }
  }

  router.visit(window.location.pathname, {
    data: query,
    preserveState: true,
    preserveScroll: true,
  })
}

let debounceTimer = null
function debouncedSearch(event) {
  const value = event.target.value
  currentFilters.value.search = value || null

  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    const params = { ...currentFilters.value }
    const query = {}
    for (const [k, v] of Object.entries(params)) {
      if (v !== null && v !== undefined && v !== '') {
        query[k] = v
      }
    }
    router.visit(window.location.pathname, {
      data: query,
      preserveState: true,
      preserveScroll: true,
    })
  }, 350)
}

// ── Helpers ────────────────────────────────────────────────
function formatPnl(value) {
  const n = Number(value)
  const formatted = n.toLocaleString('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
  return n >= 0 ? `+${formatted} €` : `${formatted} €`
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
  })
}

function formatDateTime(dateStr) {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function statusLabel(status) {
  const labels = {
    PENDING: 'En attente',
    OPEN: 'Ouvert',
    CLOSED: 'Clôturé',
    CANCELLED: 'Annulé',
  }
  return labels[status] || status
}

function statusBadgeClass(status) {
  const classes = {
    PENDING: 'bg-gray-700/50 text-gray-300 border border-gray-600/50',
    OPEN: 'bg-blue-900/50 text-blue-400 border border-blue-700/50',
    CLOSED: 'bg-green-900/50 text-green-400 border border-green-700/50',
    CANCELLED: 'bg-red-900/50 text-red-400 border border-red-700/50',
  }
  return classes[status] || 'bg-gray-700/50 text-gray-300 border border-gray-600/50'
}
</script>
