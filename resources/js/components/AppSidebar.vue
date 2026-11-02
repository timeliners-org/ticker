<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { CalendarDays, LayoutGrid, UsersRound } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import TimerWrapper from '@/components/TimerWrapper.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';
import { computed } from 'vue';
import teams from '@/routes/teams';
import shifts from '@/routes/shifts';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Shifts',
        href: shifts.index(),
        icon: CalendarDays,
    },
    {
        title: 'Teams',
        href: teams.index(),
        icon: UsersRound,
    },
];

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <TimerWrapper
                class="hidden group-data-[collapsible=icon]:hidden md:block"
            />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
