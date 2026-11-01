<script setup lang="ts">
const props = defineProps<{
    second: number;
}>();
</script>

<template>
    <div class="flex w-4 h-4 flex-wrap">
        <div
            :class="
                'w-2 h-2 ' + (second & 1 ? 'bg-green-500' : 'bg-red-500')
            "
        ></div>
        <div
            :class="
                'w-2 h-2 ' + (second & 2 ? 'bg-green-500' : 'bg-red-500')
            "
        ></div>
        <div
            :class="
                'w-2 h-2 ' + (second & 4 ? 'bg-green-500' : 'bg-red-500')
            "
        ></div>
        <div
            :class="
                'w-2 h-2 ' + (second & 8 ? 'bg-green-500' : 'bg-red-500')
            "
        ></div>
    </div>
</template>
