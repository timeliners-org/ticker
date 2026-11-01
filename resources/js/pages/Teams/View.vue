<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Check,
    LogOut,
    Pencil,
    Plus,
    ShieldCheck,
    Trash2,
    UsersRound,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
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

type TeamMember = {
    id: number;
    user_id: number;
    is_admin: boolean;
    user?: {
        id: number;
        name: string;
    };
};

type TeamCategory = {
    id: number;
    name: string;
    color: string;
};

const props = defineProps<{
    team: {
        id: number;
        name: string;
        members: TeamMember[];
        shiftCategories: TeamCategory[];
        is_admin: boolean;
    };
}>();

const editingName = ref(false);
const addCategoryOpen = ref(false);
const leaveOpen = ref(false);
const deleteOpen = ref(false);
const nameForm = useForm({ name: props.team.name });
const categoryForm = useForm({ name: '', color: '#2D8A6E' });
const pickerColor = computed({
    get: () =>
        /^#[0-9A-Fa-f]{6}/.test(categoryForm.color)
            ? categoryForm.color.slice(0, 7)
            : '#2D8A6E',
    set: (value: string) => {
        categoryForm.color = value;
    },
});

function updateName() {
    nameForm.post(`/teams/${props.team.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingName.value = false;
        },
    });
}

function cancelNameEdit() {
    nameForm.clearErrors();
    nameForm.name = props.team.name;
    editingName.value = false;
}

function addCategory() {
    categoryForm.post(`/teams/${props.team.id}/categories`, {
        preserveScroll: true,
        onSuccess: () => {
            addCategoryOpen.value = false;
            categoryForm.reset();
        },
    });
}

function leaveTeam() {
    router.post(
        `/teams/${props.team.id}/leave`,
        {},
        {
            onSuccess: () => {
                leaveOpen.value = false;
            },
        },
    );
}

function deleteTeam() {
    router.delete(`/teams/${props.team.id}`, {
        onSuccess: () => {
            deleteOpen.value = false;
        },
    });
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Teams', href: '/teams' }],
    },
});
</script>

<template>
    <Head :title="team.name" />

    <main class="space-y-8 p-4 md:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 space-y-4">
                <Link
                    href="/teams"
                    class="inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft class="size-4" />
                    Teams
                </Link>

                <div v-if="editingName" class="max-w-xl">
                    <form
                        class="flex flex-wrap items-start gap-2"
                        @submit.prevent="updateName"
                    >
                        <div class="min-w-56 flex-1 space-y-1">
                            <Label for="team-title">Team name</Label>
                            <Input
                                id="team-title"
                                v-model="nameForm.name"
                                name="name"
                                maxlength="255"
                                required
                                autofocus
                                :aria-invalid="Boolean(nameForm.errors.name)"
                            />
                            <InputError :message="nameForm.errors.name" />
                        </div>
                        <div class="flex gap-1 pt-6">
                            <Button
                                type="submit"
                                size="icon"
                                :disabled="nameForm.processing"
                                aria-label="Save team name"
                                title="Save team name"
                            >
                                <Check class="size-4" />
                            </Button>
                            <Button
                                type="button"
                                size="icon"
                                variant="outline"
                                aria-label="Cancel editing"
                                title="Cancel editing"
                                @click="cancelNameEdit"
                            >
                                <X class="size-4" />
                            </Button>
                        </div>
                    </form>
                </div>

                <div v-else class="flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-semibold tracking-tight break-all">
                        {{ team.name }}
                    </h1>
                    <Button
                        v-if="team.is_admin"
                        size="icon"
                        variant="ghost"
                        aria-label="Edit team name"
                        title="Edit team name"
                        @click="editingName = true"
                    >
                        <Pencil class="size-4" />
                    </Button>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Dialog v-model:open="leaveOpen">
                    <DialogTrigger as-child>
                        <Button variant="outline">
                            <LogOut class="size-4" />
                            Leave team
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Leave {{ team.name }}?</DialogTitle>
                            <DialogDescription>
                                You will lose access to this team's members and
                                categories.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <DialogClose as-child>
                                <Button variant="outline">Cancel</Button>
                            </DialogClose>
                            <Button variant="destructive" @click="leaveTeam">
                                Leave team
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog v-if="team.is_admin" v-model:open="deleteOpen">
                    <DialogTrigger as-child>
                        <Button
                            variant="destructive"
                            size="icon"
                            aria-label="Delete team"
                            title="Delete team"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete {{ team.name }}?</DialogTitle>
                            <DialogDescription>
                                This permanently deletes the team and its
                                categories.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <DialogClose as-child>
                                <Button variant="outline">Cancel</Button>
                            </DialogClose>
                            <Button variant="destructive" @click="deleteTeam">
                                Delete team
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </div>

        <div
            class="grid gap-10 lg:grid-cols-[minmax(0,1.3fr)_minmax(18rem,0.7fr)]"
        >
            <section aria-labelledby="members-heading">
                <div class="mb-3 flex items-baseline justify-between gap-4">
                    <h2 id="members-heading" class="text-lg font-semibold">
                        Members
                    </h2>
                    <span class="text-sm text-muted-foreground">
                        {{ team.members.length }}
                        {{ team.members.length === 1 ? 'member' : 'members' }}
                    </span>
                </div>

                <ul class="border-b">
                    <li
                        v-for="member in team.members"
                        :key="member.id"
                        class="flex items-center justify-between gap-4 border-t py-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{
                                    member.user?.name ??
                                    `Member #${member.user_id}`
                                }}
                            </p>
                        </div>
                        <span
                            v-if="member.is_admin"
                            class="inline-flex shrink-0 items-center gap-1 text-xs font-medium text-emerald-700 dark:text-emerald-300"
                        >
                            <ShieldCheck class="size-4" />
                            Admin
                        </span>
                    </li>
                    <li
                        v-if="!team.members.length"
                        class="border-t py-5 text-sm text-muted-foreground"
                    >
                        No members found.
                    </li>
                </ul>
            </section>

            <section aria-labelledby="categories-heading">
                <div class="mb-3 flex items-center justify-between gap-4">
                    <h2 id="categories-heading" class="text-lg font-semibold">
                        Categories
                    </h2>
                    <Dialog v-if="team.is_admin" v-model:open="addCategoryOpen">
                        <DialogTrigger as-child>
                            <Button size="sm" variant="outline">
                                <Plus class="size-4" />
                                Add category
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <form
                                class="space-y-6"
                                @submit.prevent="addCategory"
                            >
                                <DialogHeader>
                                    <DialogTitle>New category</DialogTitle>
                                    <DialogDescription>
                                        Set a name and a color for the category.
                                    </DialogDescription>
                                </DialogHeader>

                                <div class="grid gap-4">
                                    <div class="grid gap-2">
                                        <Label for="category-name">Name</Label>
                                        <Input
                                            id="category-name"
                                            v-model="categoryForm.name"
                                            name="name"
                                            maxlength="255"
                                            required
                                            :aria-invalid="
                                                Boolean(
                                                    categoryForm.errors.name,
                                                )
                                            "
                                        />
                                        <InputError
                                            :message="categoryForm.errors.name"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="category-color"
                                            >HEX color</Label
                                        >
                                        <div class="flex items-center gap-3">
                                            <input
                                                v-model="pickerColor"
                                                aria-label="Choose category color"
                                                class="size-10 cursor-pointer rounded-md border border-input bg-background p-1"
                                                type="color"
                                            />
                                            <Input
                                                id="category-color"
                                                v-model="categoryForm.color"
                                                name="color"
                                                placeholder="#2D8A6E"
                                                autocomplete="off"
                                                required
                                                class="font-mono uppercase"
                                                :aria-invalid="
                                                    Boolean(
                                                        categoryForm.errors
                                                            .color,
                                                    )
                                                "
                                            />
                                        </div>
                                        <InputError
                                            :message="categoryForm.errors.color"
                                        />
                                    </div>
                                </div>

                                <DialogFooter>
                                    <DialogClose as-child>
                                        <Button type="button" variant="outline">
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        type="submit"
                                        :disabled="categoryForm.processing"
                                    >
                                        {{
                                            categoryForm.processing
                                                ? 'Adding...'
                                                : 'Add category'
                                        }}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <ul class="border-b">
                    <li
                        v-for="category in team.shiftCategories"
                        :key="category.id"
                        class="flex items-center gap-3 border-t py-3"
                    >
                        <span
                            class="size-4 shrink-0 rounded-sm border border-black/10"
                            :style="{ backgroundColor: category.color }"
                            aria-hidden="true"
                        />
                        <span
                            class="min-w-0 flex-1 truncate text-sm font-medium"
                        >
                            {{ category.name }}
                        </span>
                        <span class="font-mono text-xs text-muted-foreground">
                            {{ category.color }}
                        </span>
                    </li>
                    <li
                        v-if="!team.shiftCategories.length"
                        class="border-t py-5 text-sm text-muted-foreground"
                    >
                        No categories yet.
                    </li>
                </ul>
            </section>
        </div>
    </main>
</template>
