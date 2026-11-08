<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { dashboard, login } from '@/routes';

const page = usePage();
const name = page.props.name;
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col items-center gap-6 bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <h1 class="text-4xl font-bold">{{ name }}</h1>
        <img src="/favicon.svg" alt="Welcome Image" class="mx-auto h-60 w-60" />
        <nav class="flex items-center justify-center gap-4">
            <Link
                v-if="$page.props.auth.user"
                :href="dashboard()"
                class="inline-block w-full rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
            >
                Dashboard
            </Link>
            <template v-else>
                <Link
                    :href="login()"
                    class="inline-block w-full rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Log in
                </Link>
            </template>
        </nav>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>
