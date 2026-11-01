<script setup lang="ts">
const props = defineProps<{
    second: number;
}>();
</script>

<template>
    <div class="flex h-4 w-4 flex-wrap">
        <div
            :class="'h-2 w-2 ' + (second & 1 ? 'bg-green-500' : 'bg-red-500')"
        ></div>
        <div
            :class="'h-2 w-2 ' + (second & 2 ? 'bg-green-500' : 'bg-red-500')"
        ></div>
        <div
            :class="'h-2 w-2 ' + (second & 4 ? 'bg-green-500' : 'bg-red-500')"
        ></div>
        <div
            :class="'h-2 w-2 ' + (second & 8 ? 'bg-green-500' : 'bg-red-500')"
        ></div>
    </div>
</template>
