<script setup lang="ts">
import { ref } from 'vue';
import { Timer as TimerIcon, X } from '@lucide/vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import TimerWrapper from '@/components/TimerWrapper.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

const timerOpen = ref(false);

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <Dialog v-model:open="timerOpen">
            <Button
                class="ml-auto md:hidden"
                size="icon"
                variant="outline"
                aria-label="Open timer"
                title="Open timer"
                @click="timerOpen = true"
            >
                <TimerIcon class="size-4" />
            </Button>
            <DialogContent
                :show-close-button="false"
                class="fixed !inset-0 !m-0 !h-dvh !w-full !max-w-none !translate-x-0 !translate-y-0 grid-rows-[auto_1fr] gap-0 overflow-y-auto rounded-none border-0 p-4 shadow-none sm:!max-w-none sm:p-5"
            >
                <DialogHeader
                    class="flex flex-row items-center justify-between border-b pb-4 text-left"
                >
                    <DialogTitle>Timer</DialogTitle>
                    <DialogClose as-child>
                        <Button
                            size="icon"
                            variant="ghost"
                            aria-label="Close timer"
                            title="Close timer"
                        >
                            <X class="size-4" />
                        </Button>
                    </DialogClose>
                </DialogHeader>
                <TimerWrapper
                    size="full"
                    class="flex w-full items-center justify-center py-8"
                />
            </DialogContent>
        </Dialog>
    </header>
</template>
