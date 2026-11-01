<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Timer from './Timer.vue';

const page = usePage();
const timer = computed(() => page.props.timer);
</script>

<template>
    <Timer :timer="timer" />
</template>