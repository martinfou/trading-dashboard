<template>
    <div class="space-y-0.5">
        <div class="flex justify-between text-xs">
            <span class="text-gray-500">{{ label }}</span>
            <span :class="valueColor">{{ formatted }}%</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
            <div class="h-1.5 rounded-full transition-all duration-500"
                 :class="barColor"
                 :style="{ width: barWidth }"></div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        label: String,
        value: [Number, String],
        color: { type: String, default: 'blue' },
    },
    computed: {
        numValue() {
            const v = parseFloat(this.value);
            return isNaN(v) || v < 0 ? null : v;
        },
        formatted() {
            return this.numValue !== null ? this.numValue.toFixed(1) : '—';
        },
        barWidth() {
            return this.numValue !== null ? Math.min(this.numValue, 100) + '%' : '0%';
        },
        valueColor() {
            if (this.numValue === null) return 'text-gray-400';
            if (this.numValue >= 90) return 'text-red-600 font-bold';
            if (this.numValue >= 70) return 'text-orange-500';
            return 'text-green-600';
        },
        barColor() {
            if (this.numValue === null) return 'bg-gray-300';
            if (this.numValue >= 90) return 'bg-red-500';
            if (this.numValue >= 70) return 'bg-orange-400';
            return 'bg-green-400';
        },
    },
};
</script>
