<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import PlaceholderPattern from "@/components/PlaceholderPattern.vue";
import { dashboard } from "@/routes";
import { computed, ref } from "vue";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { VisAxis, VisStackedBar, VisXYContainer } from "@unovis/vue";
import {
    ChartContainer,
    ChartCrosshair,
    ChartTooltip,
    ChartTooltipContent,
    componentToString,
} from "@/components/ui/chart";
import type { ChartConfig } from "@/components/ui/chart";
import type { DashboardShifts } from "@/types";

const props = defineProps<{
    shifts: DashboardShifts;
    todayWorkingTime: number;
}>();

const hideBreaks = ref(false);

const visibleShifts = computed(() =>
    Object.fromEntries(
        Object.entries(props.shifts).map(([date, day]) => [
            date,
            hideBreaks.value ? day.filter((shift) => !shift.is_break) : day,
        ]),
    ),
);

const categoryColors = computed(() => {
    const map = new Map<string, string>();
    for (const day of Object.values(visibleShifts.value)) {
        for (const shift of day) {
            map.set(shift.category_name, shift.category_color);
        }
    }
    return map;
});
const categories = computed(() => [...categoryColors.value.keys()]);

// One row per day, one column per category (hours), so shifts of a day stack.
const chartData = computed(() =>
    Object.entries(visibleShifts.value).map(([date, day], index) => {
        const row: Record<string, number | string> = { date, index };
        for (const shift of day) {
            row[shift.category_name] =
                ((row[shift.category_name] as number) ?? 0) +
                shift.duration_seconds / 3600;
        }
        return row;
    }),
);

const chartConfig = computed<ChartConfig>(() =>
    Object.fromEntries(
        [...categoryColors.value].map(([name, color]) => [
            name,
            { label: name, color },
        ]),
    ),
);

const yAccessors = computed(() =>
    categories.value.map(
        (name) => (d: Record<string, number | string>) =>
            (d[name] as number) ?? 0,
    ),
);
const colors = computed(() =>
    categories.value.map((name) => chartConfig.value[name].color!),
);
const formatHours = (hours: number) => {
    const totalMinutes = Math.round(hours * 60);
    const h = Math.floor(totalMinutes / 60);
    const m = String(totalMinutes % 60).padStart(2, "0");
    return `${h}:${m}`;
};
const formatDay = (tick: number | Date) => {
    const row = chartData.value[Math.round(Number(tick))];
    return row ? String(row.date) : "";
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: "Dashboard",
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
        <div
            class="relative min-h-64 flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <div class="absolute inset-0 flex flex-col">
                <div class="flex items-center justify-end gap-2 px-4 pt-4">
                    <Switch id="hide-breaks" v-model="hideBreaks" />
                    <Label for="hide-breaks">Hide breaks</Label>
                </div>
                <ChartContainer
                    :config="chartConfig"
                    class="aspect-auto min-h-0 flex-1 p-4"
                >
                    <VisXYContainer :data="chartData">
                        <VisStackedBar
                            :x="
                                (d: Record<string, number | string>) =>
                                    d.index as number
                            "
                            :y="yAccessors"
                            :color="colors"
                            :bar-padding="0.2"
                        />
                        <VisAxis
                            type="x"
                            :tick-format="formatDay"
                            :num-ticks="chartData.length"
                            :grid-line="false"
                        />
                        <VisAxis
                            type="y"
                            :tick-format="(t: number) => `${t}h`"
                        />
                        <ChartTooltip />
                        <ChartCrosshair
                            :template="
                                componentToString(
                                    chartConfig,
                                    ChartTooltipContent,
                                    {
                                        labelFormatter: formatDay,
                                        valueFormatter: formatHours,
                                    },
                                )
                            "
                            :color="colors"
                        />
                    </VisXYContainer>
                </ChartContainer>
            </div>
        </div>
    </div>
</template>
