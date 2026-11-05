<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, X } from '@lucide/vue';
import { ref, watch } from 'vue';
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

const open = defineModel<boolean>('open', { required: true });

const time = ref('08:00');
const saving = ref(false);
const error = ref('');

watch(open, (isOpen) => {
    if (isOpen) {
        time.value = '08:00';
        error.value = '';
    }
});

function xsrfToken(): string {
    const token = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
        ?.slice('XSRF-TOKEN='.length);

    return token ? decodeURIComponent(token) : '';
}

async function save() {
    const [hours, minutes] = time.value.split(':').map(Number);
    if (Number.isNaN(hours) || Number.isNaN(minutes)) return;

    saving.value = true;
    error.value = '';

    try {
        const response = await fetch('/quotas', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': xsrfToken(),
            },
            body: JSON.stringify({
                date: new Date().toLocaleDateString('sv-SE'),
                quota: hours * 3600 + minutes * 60,
            }),
        });

        if (!response.ok) {
            const result = await response.json().catch(() => null);
            throw new Error(
                result?.message ?? result?.error ?? 'Unable to save quota.',
            );
        }

        open.value = false;
        router.reload({ only: ['quota'] });
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Unable to save quota.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <form class="space-y-6" @submit.prevent="save">
                <DialogHeader>
                    <DialogTitle>Set today's quota</DialogTitle>
                    <DialogDescription>
                        Target working time for today (hours:minutes).
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="quota-time">Quota</Label>
                    <Input
                        id="quota-time"
                        v-model="time"
                        type="time"
                        required
                    />
                </div>
                <p v-if="error" class="text-sm text-destructive">
                    {{ error }}
                </p>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="outline">
                            <X class="size-4" />
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="saving">
                        <Check class="size-4" />
                        {{ saving ? 'Saving...' : 'Save' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
