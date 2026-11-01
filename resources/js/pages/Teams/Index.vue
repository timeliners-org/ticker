<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import teamRoutes from '@/routes/teams';
import { ArrowUpRight, Plus, UsersRound } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type TeamSummary = {
    id: number;
    name: string;
    members: number;
};

defineProps<{
    teams: TeamSummary[];
}>();

const createOpen = ref(false);
const form = useForm({ name: '' });

function createTeam() {
    form.post(teamRoutes.index().url, {
        preserveScroll: true,
        onSuccess: () => {
            createOpen.value = false;
            form.reset();
        },
    });
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Teams', href: teamRoutes.index() }],
    },
});
</script>

<template>
    <Head title="Teams" />

    <main class="space-y-8 p-4 md:p-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="space-y-1">
                <p class="text-sm font-medium text-muted-foreground">
                    Workspace
                </p>
                <h1 class="text-3xl font-semibold tracking-tight">Teams</h1>
            </div>

            <Dialog v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button>
                        <Plus class="size-4" />
                        Create team
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <form class="space-y-6" @submit.prevent="createTeam">
                        <DialogHeader>
                            <DialogTitle>Create a team</DialogTitle>
                            <DialogDescription>
                                Choose a name for your new team.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="team-name">Team name</Label>
                            <Input
                                id="team-name"
                                v-model="form.name"
                                name="name"
                                maxlength="255"
                                autocomplete="off"
                                required
                                autofocus
                                :aria-invalid="Boolean(form.errors.name)"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <DialogFooter>
                            <DialogClose as-child>
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button type="submit" :disabled="form.processing">
                                {{
                                    form.processing
                                        ? 'Creating...'
                                        : 'Create team'
                                }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </header>

        <section aria-labelledby="team-list-title">
            <h2 id="team-list-title" class="sr-only">Your teams</h2>

            <div v-if="teams.length" class="border-b">
                <Link
                    v-for="team in teams"
                    :key="team.id"
                    :href="`/teams/${team.id}`"
                    class="group flex items-center justify-between gap-4 border-t py-4 transition-colors hover:bg-muted/40"
                >
                    <div class="flex min-w-0 items-center gap-4">
                        <div
                            class="flex size-11 shrink-0 items-center justify-center rounded-md bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
                        >
                            <UsersRound class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-medium">{{ team.name }}</p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ team.members }}
                                {{ team.members === 1 ? 'member' : 'members' }}
                            </p>
                        </div>
                    </div>
                    <span
                        class="text-sm font-medium text-muted-foreground transition-colors group-hover:text-foreground"
                    >
                        Open <ArrowUpRight class="size-4" aria-hidden="true" />
                    </span>
                </Link>
            </div>

            <div
                v-else
                class="flex min-h-64 flex-col items-center justify-center border-y px-6 text-center"
            >
                <div
                    class="mb-4 flex size-12 items-center justify-center rounded-md bg-muted text-muted-foreground"
                >
                    <UsersRound class="size-5" />
                </div>
                <h2 class="font-medium">No teams yet</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Create a team to get started.
                </p>
                <Button class="mt-4" @click="createOpen = true">
                    <Plus class="size-4" />
                    Create team
                </Button>
            </div>
        </section>
    </main>
</template>
