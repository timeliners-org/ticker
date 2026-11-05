<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Check, Pause, Play, Plus, Square, Target } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { pause, start, stop, unpause, update } from '@/routes/timer';
import type { Timer } from '@/types/timer';
import TimerReaction from '@/components/TimerReaction.vue';
import QuotaDialog from '@/components/QuotaDialog.vue';

const props = defineProps<{
    timer: Timer;
    size?: 'compact' | 'full';
}>();

const size = props.size ?? 'compact';

const page = usePage();
const quota = computed(() => page.props.quota);
const quotaDialogOpen = ref(false);

const quotaDisplay = computed(() => {
    const total = quota.value?.quota ?? 0;
    const hh = String(Math.floor(total / 3600)).padStart(2, '0');
    const mm = String(Math.floor((total % 3600) / 60)).padStart(2, '0');

    return `${hh}:${mm}`;
});

const status = computed(() => props.timer.status);
const isActive = computed(() => status.value !== null);
const isPaused = computed(() => status.value?.pausedAt != null);
const isRunning = computed(() => isActive.value && !isPaused.value);

const selectedId = ref<number | null>(
    status.value?.shiftCategoryId ?? props.timer.categories[0]?.id ?? null,
);

watch(
    () => status.value?.shiftCategoryId,
    (id) => {
        if (id !== undefined) {
            selectedId.value = id;
        }
    },
);

const now = ref(Date.now());
let interval: ReturnType<typeof setInterval> | undefined;

const mounted = ref(false);

onMounted(() => {
    mounted.value = true;
    now.value = Date.now();
    interval = setInterval(() => (now.value = Date.now()), 1000);
});
onBeforeUnmount(() => clearInterval(interval));

const display = computed(() => {
    if (!status.value) {
        return '00:00';
    }

    // While paused, show how long the break has lasted
    const from = new Date(
        status.value.pausedAt ?? status.value.startedAt,
    ).getTime();
    const minutes = Math.max(0, Math.floor((now.value - from) / 60000));
    const hh = String(Math.floor(minutes / 60)).padStart(2, '0');
    const mm = String(minutes % 60).padStart(2, '0');

    return `${hh}:${mm}`;
});

const sessionDisplay = computed(() => {
    if (!status.value) {
        return '00:00';
    }

    const minutes = Math.max(
        0,
        Math.floor(
            (now.value - new Date(status.value.createdAt).getTime()) / 60000,
        ),
    );

    return `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`;
});

const sessionTooltip = computed(() => {
    if (!status.value) {
        return undefined;
    }

    const created = new Date(status.value.createdAt);
    const sameDay =
        created.toDateString() === new Date(now.value).toDateString();
    const time = created.toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
    });

    return sameDay
        ? `Session since ${time}`
        : `Session since ${created.toLocaleDateString('en-GB')}, ${time}`;
});

const activeCategory = computed(() =>
    props.timer.categories.find(
        (category) => category.id === status.value?.shiftCategoryId,
    ),
);

const seconds = computed(() =>
    Math.floor(
        (now.value - new Date(status.value?.startedAt ?? 0).getTime()) / 1000,
    ),
);

// While running, a different selection must be confirmed explicitly
const categoryChanged = computed(
    () => isRunning.value && selectedId.value !== status.value?.shiftCategoryId,
);

const options = { preserveScroll: true } as const;

function onPlay() {
    if (selectedId.value === null) {
        return;
    }

    if (isPaused.value) {
        router.post(unpause.url({ category: selectedId.value }), {}, options);
    } else {
        router.post(start.url({ category: selectedId.value }), {}, options);
    }
}

function onPause() {
    router.post(pause.url(), {}, options);
}

function onStop() {
    router.post(stop.url(), {}, options);
}

function onSaveCategory() {
    if (selectedId.value !== null) {
        router.post(update.url({ category: selectedId.value }), {}, options);
    }
}

const selectedValue = computed({
    get: () =>
        selectedId.value === null ? undefined : String(selectedId.value),
    set: (value) => {
        selectedId.value = value ? Number(value) : null;
    },
});
</script>

<template>
    <div v-if="size === 'full'" class="grid w-full grid-cols-1 gap-4">
        <div class="flex items-center justify-between gap-2 px-1 text-base">
            <span class="flex items-center gap-1.5 text-muted-foreground">
                <Target class="size-4" />
                Today's quota
            </span>
            <span v-if="quota" class="font-mono tabular-nums">
                {{ quotaDisplay }} h
            </span>
            <Button
                v-else
                variant="outline"
                class="h-10 gap-2"
                @click="quotaDialogOpen = true"
            >
                <Plus class="size-4" />
                Set quota
            </Button>
        </div>
        <QuotaDialog v-model:open="quotaDialogOpen" />

        <div class="h-px bg-sidebar-border/70 dark:bg-sidebar-border" />

        <div
            class="flex items-center justify-between gap-2 px-1 py-1 text-base text-muted-foreground"
        >
            <span class="flex min-w-0 items-center gap-1.5">
                <template v-if="activeCategory">
                    <span
                        class="size-2 shrink-0 rounded-full"
                        :style="{ backgroundColor: activeCategory.color }"
                    />
                    <span class="truncate">{{ activeCategory.name }}</span>
                    <span v-if="isPaused">· paused</span>
                </template>
                <span v-else>No active shift</span>
            </span>
            <span
                class="shrink-0 font-mono tabular-nums"
                :title="sessionTooltip"
            >
                {{ sessionDisplay }}
            </span>
        </div>

        <div class="h-px bg-sidebar-border/70 dark:bg-sidebar-border" />

        <div
            class="flex min-w-0 flex-col items-center justify-center gap-3 py-6 text-center"
        >
            <div v-if="isActive && mounted" class="size-8 shrink-0">
                <TimerReaction :second="seconds" />
            </div>
            <span
                class="font-mono text-5xl font-semibold tabular-nums sm:text-6xl"
            >
                {{ display }}
            </span>
        </div>

        <Button
            v-if="isRunning"
            class="h-14 w-full gap-3 rounded bg-blue-500 text-base text-white hover:bg-blue-600 [&_svg]:size-5"
            aria-label="Pause timer"
            @click="onPause"
        >
            <Pause />
            Pause timer
        </Button>
        <Button
            v-else
            class="h-14 w-full gap-3 rounded bg-green-500 text-base text-white hover:bg-green-600 [&_svg]:size-5"
            aria-label="Start timer"
            :disabled="selectedId === null"
            @click="onPlay"
        >
            <Play />
            {{ isPaused ? 'Resume timer' : 'Start timer' }}
        </Button>
        <Button
            v-if="isActive"
            class="h-14 w-full gap-3 rounded bg-red-500 text-base text-white hover:bg-red-600 [&_svg]:size-5"
            aria-label="Stop timer"
            @click="onStop"
        >
            <Square />
            Stop timer
        </Button>

        <Select v-model="selectedValue">
            <SelectTrigger class="h-14 w-full text-base">
                <SelectValue placeholder="Select category" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="category in timer.categories"
                    :key="category.id"
                    :value="String(category.id)"
                >
                    <span class="flex items-center gap-2">
                        <span
                            class="size-2.5 rounded-full"
                            :style="{ backgroundColor: category.color }"
                        />
                        {{ category.name }}
                    </span>
                </SelectItem>
            </SelectContent>
        </Select>

        <Button
            v-if="categoryChanged"
            variant="outline"
            class="h-14 w-full gap-3 text-base [&_svg]:size-5"
            aria-label="Save category"
            @click="onSaveCategory"
        >
            <Check />
            Save category
        </Button>
    </div>

    <div
        v-else
        class="grid w-full grid-cols-1 gap-1 rounded-xl border border-sidebar-border/70 p-2 dark:border-sidebar-border"
    >
        <div class="flex items-center justify-between gap-2 px-1 text-xs">
            <span class="flex items-center gap-1.5 text-muted-foreground">
                <Target class="size-3.5" />
                Today's quota
            </span>
            <span v-if="quota" class="font-mono tabular-nums">
                {{ quotaDisplay }} h
            </span>
            <Button
                v-else
                variant="outline"
                size="sm"
                class="h-6 gap-1 px-2 text-xs"
                @click="quotaDialogOpen = true"
            >
                <Plus class="size-3" />
                Set quota
            </Button>
        </div>
        <QuotaDialog v-model:open="quotaDialogOpen" />

        <div class="h-px bg-sidebar-border/70 dark:bg-sidebar-border" />

        <div
            class="flex items-center justify-between gap-2 px-1 text-xs text-muted-foreground"
        >
            <span class="flex min-w-0 items-center gap-1.5">
                <template v-if="activeCategory">
                    <span
                        class="size-2 shrink-0 rounded-full"
                        :style="{ backgroundColor: activeCategory.color }"
                    />
                    <span class="truncate">{{ activeCategory.name }}</span>
                    <span v-if="isPaused">· paused</span>
                </template>
                <span v-else>No active shift</span>
            </span>
            <span
                class="shrink-0 font-mono tabular-nums"
                :title="sessionTooltip"
            >
                {{ sessionDisplay }}
            </span>
        </div>

        <div class="h-px bg-sidebar-border/70 dark:bg-sidebar-border" />

        <div class="flex min-w-0 items-center justify-between gap-2">
            <div class="flex min-w-0 items-center gap-2 px-1">
                <div class="size-4 shrink-0">
                    <TimerReaction
                        v-if="isActive && mounted"
                        :second="seconds"
                    />
                </div>
                <span class="font-mono text-3xl font-semibold tabular-nums">
                    {{ display }}
                </span>
            </div>

            <div class="flex shrink-0 items-center gap-1">
                <Button
                    v-if="isRunning"
                    size="icon"
                    class="rounded bg-blue-500 text-white hover:bg-blue-600"
                    aria-label="Pause"
                    @click="onPause"
                >
                    <Pause />
                </Button>
                <Button
                    v-else
                    size="icon"
                    class="rounded bg-green-500 text-white hover:bg-green-600"
                    aria-label="Start"
                    :disabled="selectedId === null"
                    @click="onPlay"
                >
                    <Play />
                </Button>
                <Button
                    v-if="isActive"
                    size="icon"
                    class="rounded bg-red-500 text-white hover:bg-red-600"
                    aria-label="Stop"
                    @click="onStop"
                >
                    <Square />
                </Button>
            </div>
        </div>

        <div class="flex min-w-0 items-center gap-2">
            <Select v-model="selectedValue" class="min-w-0 flex-1">
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="Select category" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="category in timer.categories"
                        :key="category.id"
                        :value="String(category.id)"
                    >
                        <span class="flex items-center gap-2">
                            <span
                                class="size-2.5 rounded-full"
                                :style="{ backgroundColor: category.color }"
                            />
                            {{ category.name }}
                        </span>
                    </SelectItem>
                </SelectContent>
            </Select>
            <Button
                v-if="categoryChanged"
                size="icon"
                variant="outline"
                class="rounded"
                aria-label="Confirm category change"
                @click="onSaveCategory"
            >
                <Check />
            </Button>
        </div>
    </div>
</template>
