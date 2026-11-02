<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    Check,
    ChevronLeft,
    ChevronRight,
    Clock3,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ShiftCategory } from '@/types/timer';

type Shift = {
    id: number;
    start_time: string;
    end_time: string | null;
    connected_shift_id: number | null;
    shift_category_id: number;
    category: {
        id: number;
        name: string;
        color: string;
    };
};

const props = defineProps<{
    datesWithShifts: string[];
}>();
const page = usePage();

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
const shiftDialogOpen = ref(false);
const shiftLoading = ref(false);
const shiftSaving = ref(false);
const shiftDeleting = ref(false);
const confirmDelete = ref(false);
const shiftError = ref('');
const activeShift = ref<Shift | null>(null);
const shiftForm = reactive({
    start_time: '',
    end_time: '',
    shift_category_id: '',
});

const availableCategories = computed<ShiftCategory[]>(() => {
    const categories = [...(page.props.timer.categories ?? [])];
    const currentCategory = activeShift.value?.category;

    if (
        activeShift.value &&
        currentCategory &&
        !categories.some(
            (category) => category.id === activeShift.value?.shift_category_id,
        )
    ) {
        categories.push({
            id: activeShift.value.shift_category_id,
            name: currentCategory.name,
            color: currentCategory.color,
        });
    }

    return categories;
});

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

async function loadDay(date: string, signal?: AbortSignal) {
    isLoading.value = true;
    loadError.value = '';

    try {
        const response = await fetch(
            `/shifts/day?date=${encodeURIComponent(date)}`,
            {
                headers: { Accept: 'application/json' },
                signal,
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
}

watch(
    selectedDate,
    (date, _previousDate, onCleanup) => {
        const controller = new AbortController();
        onCleanup(() => controller.abort());
        void loadDay(date, controller.signal);
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

function toLocalDateTime(value: string | null): string {
    if (!value) return '';

    const date = new Date(value);
    const pad = (part: number) => String(part).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function toUtcDateTime(value: string): string {
    return new Date(value).toISOString().slice(0, 19).replace('T', ' ');
}

function xsrfToken(): string {
    const token = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
        ?.slice('XSRF-TOKEN='.length);

    return token ? decodeURIComponent(token) : '';
}

async function shiftRequest<T>(
    url: string,
    method: 'GET' | 'PUT' | 'DELETE',
    data?: Record<string, string | number | null>,
): Promise<T> {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        ...(data ? { body: JSON.stringify(data) } : {}),
    });
    const result = await response.json().catch(() => null);

    if (!response.ok) {
        throw new Error(result?.message ?? 'The shift could not be saved.');
    }

    return result as T;
}

async function openShift(shift: Shift) {
    shiftDialogOpen.value = true;
    shiftLoading.value = true;
    shiftError.value = '';
    confirmDelete.value = false;
    activeShift.value = null;

    try {
        activeShift.value = await shiftRequest<Shift>(
            `/shifts/detail?id=${shift.id}`,
            'GET',
        );
        shiftForm.start_time = toLocalDateTime(activeShift.value.start_time);
        shiftForm.end_time = toLocalDateTime(activeShift.value.end_time);
        shiftForm.shift_category_id = String(
            activeShift.value.shift_category_id,
        );
    } catch (error) {
        shiftError.value =
            error instanceof Error ? error.message : 'Unable to load shift.';
    } finally {
        shiftLoading.value = false;
    }
}

async function saveShift() {
    if (!activeShift.value) return;

    shiftSaving.value = true;
    shiftError.value = '';

    try {
        const updatedShift = await shiftRequest<Shift>(
            '/shifts/update',
            'PUT',
            {
                id: activeShift.value.id,
                start_time: toUtcDateTime(shiftForm.start_time),
                end_time: toUtcDateTime(shiftForm.end_time),
                shift_category_id: Number(shiftForm.shift_category_id),
            },
        );
        const updatedDate = dateKey(new Date(updatedShift.start_time));

        shiftDialogOpen.value = false;
        if (updatedDate !== selectedDate.value) {
            selectedDate.value = updatedDate;
        } else {
            await loadDay(updatedDate);
        }
        router.reload({ only: ['datesWithShifts'] });
    } catch (error) {
        shiftError.value =
            error instanceof Error ? error.message : 'Unable to save shift.';
    } finally {
        shiftSaving.value = false;
    }
}

async function deleteShift() {
    if (!activeShift.value) return;

    shiftDeleting.value = true;
    shiftError.value = '';

    try {
        await shiftRequest<{ deleted: boolean }>('/shifts/delete', 'DELETE', {
            id: activeShift.value.id,
        });
        shiftDialogOpen.value = false;
        confirmDelete.value = false;
        await loadDay(selectedDate.value);
        router.reload({ only: ['datesWithShifts'] });
    } catch (error) {
        shiftError.value =
            error instanceof Error ? error.message : 'Unable to delete shift.';
    } finally {
        shiftDeleting.value = false;
    }
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
                            class="rounded-sm border-l-[3px] transition-colors"
                            :style="{ borderColor: shift.category.color }"
                        >
                            <button
                                type="button"
                                class="flex w-full flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-2 pr-3 pl-4 text-left hover:bg-accent/70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                :aria-label="`${shift.category.name}, ${formatTime(shift.start_time)} to ${formatTime(shift.end_time)}. Edit shift`"
                                @click="openShift(shift)"
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
                            </button>
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

        <Dialog v-model:open="shiftDialogOpen">
            <DialogContent>
                <form class="space-y-6" @submit.prevent="saveShift">
                    <DialogHeader>
                        <DialogTitle>Edit shift</DialogTitle>
                        <DialogDescription>
                            Update the time or category for this shift.
                        </DialogDescription>
                    </DialogHeader>

                    <p
                        v-if="shiftLoading"
                        class="py-4 text-sm text-muted-foreground"
                    >
                        Loading shift...
                    </p>
                    <div v-else-if="activeShift" class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="shift-category">Category</Label>
                            <div class="flex items-center gap-2">
                                <span
                                    class="size-3 shrink-0 rounded-full"
                                    :style="{
                                        backgroundColor:
                                            availableCategories.find(
                                                (category) =>
                                                    category.id ===
                                                    Number(
                                                        shiftForm.shift_category_id,
                                                    ),
                                            )?.color,
                                    }"
                                    aria-hidden="true"
                                />
                                <select
                                    id="shift-category"
                                    v-model="shiftForm.shift_category_id"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50"
                                    required
                                >
                                    <option
                                        v-for="category in availableCategories"
                                        :key="category.id"
                                        :value="String(category.id)"
                                    >
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="shift-start">Start</Label>
                                <Input
                                    id="shift-start"
                                    v-model="shiftForm.start_time"
                                    type="datetime-local"
                                    step="60"
                                    required
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="shift-end">End</Label>
                                <Input
                                    id="shift-end"
                                    v-model="shiftForm.end_time"
                                    type="datetime-local"
                                    step="60"
                                    :min="shiftForm.start_time"
                                    required
                                />
                            </div>
                        </div>
                    </div>
                    <p v-if="shiftError" class="text-sm text-destructive">
                        {{ shiftError }}
                    </p>

                    <DialogFooter class="gap-2 sm:justify-between">
                        <div class="flex flex-wrap items-center gap-2">
                            <template v-if="confirmDelete">
                                <span class="text-sm text-muted-foreground">
                                    Delete this shift permanently?
                                </span>
                                <Button
                                    type="button"
                                    variant="destructive"
                                    :disabled="shiftDeleting"
                                    @click="deleteShift"
                                >
                                    <Trash2 class="size-4" />
                                    Confirm delete
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="confirmDelete = false"
                                >
                                    Cancel
                                </Button>
                            </template>
                            <Button
                                v-else
                                type="button"
                                variant="destructive"
                                :disabled="shiftLoading || shiftSaving"
                                @click="confirmDelete = true"
                            >
                                <Trash2 class="size-4" />
                                Delete shift
                            </Button>
                        </div>

                        <div v-if="!confirmDelete" class="flex gap-2">
                            <DialogClose as-child>
                                <Button type="button" variant="outline">
                                    <X class="size-4" />
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                :disabled="
                                    shiftLoading || shiftSaving || !activeShift
                                "
                            >
                                <Check class="size-4" />
                                {{ shiftSaving ? 'Saving...' : 'Save changes' }}
                            </Button>
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </main>
</template>
