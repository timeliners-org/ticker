<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft, ChevronRight, Clock3 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

type Shift = {
    id: number;
    start_time: string;
    end_time: string | null;
    connected_shift_id: number | null;
    category: {
        name: string;
        color: string;
    };
};

const props = defineProps<{
    datesWithShifts: string[];
}>();

function dateKey(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function parseDate(key: string): Date {
    return new Date(`${key}T12:00:00`);
}

const today = dateKey(new Date());
const sortedDates = [...props.datesWithShifts].sort();
const selectedDate = ref(
    sortedDates.includes(today) ? today : (sortedDates.at(-1) ?? today),
);
const visibleMonth = ref(parseDate(selectedDate.value));
const shifts = ref<Shift[]>([]);
const isLoading = ref(false);
const loadError = ref('');

const availableDates = computed(() => new Set(props.datesWithShifts));
const monthLabel = computed(() =>
    new Intl.DateTimeFormat(undefined, {
        month: 'long',
        year: 'numeric',
    }).format(visibleMonth.value),
);
const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const calendarDays = computed(() => {
    const year = visibleMonth.value.getFullYear();
    const month = visibleMonth.value.getMonth();
    const firstWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
    const dayCount = new Date(year, month + 1, 0).getDate();

    return [
        ...Array.from({ length: firstWeekday }, () => null),
        ...Array.from({ length: dayCount }, (_, index) =>
            dateKey(new Date(year, month, index + 1)),
        ),
    ];
});

const formattedSelectedDate = computed(() =>
    new Intl.DateTimeFormat(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(parseDate(selectedDate.value)),
);

function changeMonth(offset: number) {
    visibleMonth.value = new Date(
        visibleMonth.value.getFullYear(),
        visibleMonth.value.getMonth() + offset,
        1,
    );
}

watch(
    selectedDate,
    async (date, _previousDate, onCleanup) => {
        const controller = new AbortController();
        onCleanup(() => controller.abort());
        isLoading.value = true;
        loadError.value = '';

        try {
            const response = await fetch(
                `/shifts/day?date=${encodeURIComponent(date)}`,
                {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                },
            );

            if (!response.ok) {
                throw new Error('Unable to load shifts for this date.');
            }

            shifts.value = await response.json();
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return;
            }

            loadError.value = 'Unable to load shifts for this date.';
            shifts.value = [];
        } finally {
            isLoading.value = false;
        }
    },
    { immediate: true },
);

const groupedShifts = computed(() => {
    const parents = new Map<number, number>();
    const shiftsById = new Map(shifts.value.map((shift) => [shift.id, shift]));

    for (const shift of shifts.value) {
        parents.set(shift.id, shift.id);
    }

    function findRoot(id: number): number {
        let root = id;
        while (parents.get(root) !== root) {
            root = parents.get(root)!;
        }

        let current = id;
        while (parents.get(current) !== root) {
            const parent = parents.get(current)!;
            parents.set(current, root);
            current = parent;
        }

        return root;
    }

    for (const shift of shifts.value) {
        if (
            shift.connected_shift_id &&
            shiftsById.has(shift.connected_shift_id)
        ) {
            parents.set(findRoot(shift.connected_shift_id), findRoot(shift.id));
        }
    }

    const groups = new Map<number, Shift[]>();
    for (const shift of shifts.value) {
        const root = findRoot(shift.id);
        const group = groups.get(root) ?? [];
        group.push(shift);
        groups.set(root, group);
    }

    return [...groups.values()];
});

function formatTime(value: string | null): string {
    if (!value) return 'In progress';

    return new Intl.DateTimeFormat(undefined, {
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Shifts', href: '/shifts' }],
    },
});
</script>

<template>
    <Head title="Shifts" />

    <main class="space-y-6 p-4 md:p-8">
        <header class="space-y-1">
            <p class="text-sm font-medium text-muted-foreground">History</p>
            <h1 class="text-3xl font-semibold tracking-tight">Shifts</h1>
        </header>

        <div class="grid gap-8 lg:grid-cols-[17rem_minmax(0,1fr)]">
            <aside class="space-y-4" aria-label="Shift calendar">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold">{{ monthLabel }}</h2>
                    <div class="flex items-center">
                        <button
                            type="button"
                            class="flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                            aria-label="Previous month"
                            @click="changeMonth(-1)"
                        >
                            <ChevronLeft class="size-4" />
                        </button>
                        <button
                            type="button"
                            class="flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                            aria-label="Next month"
                            @click="changeMonth(1)"
                        >
                            <ChevronRight class="size-4" />
                        </button>
                    </div>
                </div>

                <div
                    class="grid grid-cols-7 text-center text-xs text-muted-foreground"
                >
                    <span
                        v-for="weekday in weekdays"
                        :key="weekday"
                        class="py-2"
                    >
                        {{ weekday }}
                    </span>
                </div>
                <div class="grid grid-cols-7 gap-y-1">
                    <span
                        v-for="(day, index) in calendarDays"
                        :key="day ?? `empty-${index}`"
                        class="flex aspect-square items-center justify-center"
                    >
                        <button
                            v-if="day"
                            type="button"
                            class="relative flex size-9 items-center justify-center rounded-md text-sm transition-colors disabled:cursor-default disabled:text-muted-foreground/50"
                            :class="[
                                day === selectedDate
                                    ? 'bg-primary font-medium text-primary-foreground'
                                    : availableDates.has(day)
                                      ? 'font-medium hover:bg-muted'
                                      : '',
                            ]"
                            :disabled="!availableDates.has(day)"
                            :aria-pressed="day === selectedDate"
                            :aria-label="parseDate(day).toLocaleDateString()"
                            @click="selectedDate = day"
                        >
                            {{ Number(day.slice(-2)) }}
                            <span
                                v-if="
                                    availableDates.has(day) &&
                                    day !== selectedDate
                                "
                                class="absolute bottom-1 size-1 rounded-full bg-emerald-500"
                                aria-hidden="true"
                            />
                        </button>
                    </span>
                </div>

                <div
                    class="flex items-center gap-2 border-t pt-4 text-xs text-muted-foreground"
                >
                    <span
                        class="size-1.5 rounded-full bg-emerald-500"
                        aria-hidden="true"
                    />
                    Dates with shifts
                </div>
            </aside>

            <section
                class="min-w-0 border-t pt-5 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-8"
                aria-live="polite"
            >
                <div
                    class="mb-6 flex flex-wrap items-end justify-between gap-3"
                >
                    <div>
                        <p class="text-sm text-muted-foreground">
                            Daily activity
                        </p>
                        <h2 class="mt-1 text-xl font-semibold">
                            {{ formattedSelectedDate }}
                        </h2>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ shifts.length }}
                        {{ shifts.length === 1 ? 'shift' : 'shifts' }}
                    </p>
                </div>

                <p v-if="loadError" class="py-8 text-sm text-destructive">
                    {{ loadError }}
                </p>
                <p
                    v-else-if="isLoading"
                    class="py-8 text-sm text-muted-foreground"
                >
                    Loading shifts...
                </p>
                <div v-else-if="groupedShifts.length" class="space-y-8">
                    <div
                        v-for="(group, groupIndex) in groupedShifts"
                        :key="group[0].id"
                        class="space-y-3"
                        :class="groupIndex > 0 ? 'border-t pt-6' : ''"
                    >
                        <article
                            v-for="shift in group"
                            :key="shift.id"
                            class="border-l-[3px] py-1 pl-4 bg-black/20 hover:bg-gray-800"
                            :style="{ borderColor: shift.category.color }"
                        >
                            <div
                                class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 "
                            >
                                <h3 class="font-medium">
                                    {{ shift.category.name }}
                                </h3>
                                <p
                                    class="flex items-center gap-1.5 text-sm text-muted-foreground tabular-nums"
                                >
                                    <Clock3
                                        class="size-3.5"
                                        aria-hidden="true"
                                    />
                                    {{ formatTime(shift.start_time) }}
                                    <span aria-hidden="true">–</span>
                                    {{ formatTime(shift.end_time) }}
                                </p>
                            </div>
                        </article>
                    </div>
                </div>
                <div
                    v-else
                    class="flex min-h-56 flex-col items-center justify-center border-y px-6 text-center"
                >
                    <CalendarDays
                        class="mb-3 size-5 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <p class="font-medium">No shifts on this date</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Choose a marked date to view recorded activity.
                    </p>
                </div>
            </section>
        </div>
    </main>
</template>
