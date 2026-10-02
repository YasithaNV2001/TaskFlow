<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { index, store, update } from '@/routes/tasks';
import type { Option, Task, TaskPriority, TaskStatus } from '@/types';

// Props: data passed in from the parent page (like React props)
const props = defineProps<{
    task?: Task; // present when editing, missing when creating
    statuses: Option<TaskStatus>[];
    priorities: Option<TaskPriority>[];
}>();

// useForm keeps the field values, validation errors and "processing" state together
const form = useForm({
    title: props.task?.title ?? '',
    description: props.task?.description ?? '',
    status: props.task?.status ?? ('todo' as TaskStatus),
    priority: props.task?.priority ?? ('medium' as TaskPriority),
    due_date: props.task?.due_date ?? '',
});

function submit() {
    // Empty strings become null so the database stores "no due date" / "no description"
    form.transform((data) => ({
        ...data,
        description: data.description || null,
        due_date: data.due_date || null,
    }));

    if (props.task) {
        form.put(update.url(props.task.id));
    } else {
        form.post(store.url());
    }
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="title">Title</Label>
            <!-- v-model: two-way binding between the input and form.title -->
            <Input
                id="title"
                v-model="form.title"
                required
                maxlength="255"
                placeholder="e.g. Finish the database assignment"
            />
            <InputError :message="form.errors.title" />
        </div>

        <div class="grid gap-2">
            <Label for="description">
                Description
                <span class="text-muted-foreground">(optional)</span>
            </Label>
            <textarea
                id="description"
                v-model="form.description"
                rows="4"
                maxlength="2000"
                placeholder="Notes, links, sub-steps…"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            />
            <InputError :message="form.errors.description" />
        </div>

        <div class="grid gap-6 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label for="status">Status</Label>
                <Select v-model="form.status">
                    <SelectTrigger id="status" class="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <!-- v-for: render one item per option (like .map() in React) -->
                        <SelectItem
                            v-for="status in statuses"
                            :key="status.value"
                            :value="status.value"
                        >
                            {{ status.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.status" />
            </div>

            <div class="grid gap-2">
                <Label for="priority">Priority</Label>
                <Select v-model="form.priority">
                    <SelectTrigger id="priority" class="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="priority in priorities"
                            :key="priority.value"
                            :value="priority.value"
                        >
                            {{ priority.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.priority" />
            </div>

            <div class="grid gap-2">
                <Label for="due_date">Due date</Label>
                <Input id="due_date" v-model="form.due_date" type="date" />
                <InputError :message="form.errors.due_date" />
            </div>
        </div>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                {{ task ? 'Save changes' : 'Create task' }}
            </Button>
            <Button variant="ghost" as-child>
                <Link :href="index()">Cancel</Link>
            </Button>
        </div>
    </form>
</template>
