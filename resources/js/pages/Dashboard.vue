<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle2,
    Circle,
    ListTodo,
    Loader,
} from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { formatDate, isOverdue, priorityClasses } from '@/lib/tasks';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { create, edit, index } from '@/routes/tasks';
import type { Task } from '@/types';

const props = defineProps<{
    stats: {
        total: number;
        todo: number;
        in_progress: number;
        done: number;
        overdue: number;
    };
    upcoming: Pick<Task, 'id' | 'title' | 'priority' | 'status' | 'due_date'>[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

// computed: a value derived from props that updates automatically (like useMemo)
const completion = computed(() =>
    props.stats.total === 0
        ? 0
        : Math.round((props.stats.done / props.stats.total) * 100),
);

const cards = computed(() => [
    { label: 'To do', value: props.stats.todo, icon: Circle, status: 'todo' },
    {
        label: 'In progress',
        value: props.stats.in_progress,
        icon: Loader,
        status: 'in_progress',
    },
    {
        label: 'Done',
        value: props.stats.done,
        icon: CheckCircle2,
        status: 'done',
    },
]);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Dashboard"
            :description="`${completion}% of your tasks are done`"
        />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Link
                v-for="card in cards"
                :key="card.label"
                :href="index({ query: { status: card.status } })"
                class="rounded-xl border p-4 transition hover:bg-muted/50"
            >
                <div
                    class="flex items-center justify-between text-sm text-muted-foreground"
                >
                    {{ card.label }}
                    <component :is="card.icon" class="size-4" />
                </div>
                <p class="mt-2 text-3xl font-semibold">{{ card.value }}</p>
            </Link>

            <div
                :class="
                    cn(
                        'rounded-xl border p-4',
                        stats.overdue > 0 && 'border-red-500/40 bg-red-500/5',
                    )
                "
            >
                <div
                    class="flex items-center justify-between text-sm text-muted-foreground"
                >
                    Overdue
                    <AlertTriangle class="size-4" />
                </div>
                <p
                    :class="
                        cn(
                            'mt-2 text-3xl font-semibold',
                            stats.overdue > 0 &&
                                'text-red-600 dark:text-red-400',
                        )
                    "
                >
                    {{ stats.overdue }}
                </p>
            </div>
        </div>

        <section class="rounded-xl border">
            <div class="flex items-center justify-between border-b p-4">
                <h2 class="font-medium">Upcoming deadlines</h2>
                <Button variant="outline" size="sm" as-child>
                    <Link :href="index()">View all tasks</Link>
                </Button>
            </div>

            <ul v-if="upcoming.length" class="divide-y">
                <li v-for="task in upcoming" :key="task.id">
                    <Link
                        :href="edit(task.id)"
                        class="flex items-center justify-between gap-3 p-4 hover:bg-muted/50"
                    >
                        <span class="truncate">{{ task.title }}</span>
                        <span class="flex shrink-0 items-center gap-2 text-xs">
                            <span
                                :class="
                                    cn(
                                        'rounded-full px-2 py-0.5 font-medium capitalize',
                                        priorityClasses[task.priority],
                                    )
                                "
                            >
                                {{ task.priority }}
                            </span>
                            <span
                                :class="
                                    isOverdue(task)
                                        ? 'font-medium text-red-600 dark:text-red-400'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{
                                    task.due_date
                                        ? formatDate(task.due_date)
                                        : ''
                                }}
                            </span>
                        </span>
                    </Link>
                </li>
            </ul>

            <div v-else class="p-8 text-center text-sm text-muted-foreground">
                <ListTodo class="mx-auto mb-2 size-6" />
                No upcoming deadlines.
                <Link
                    :href="create()"
                    class="font-medium text-foreground underline underline-offset-4"
                >
                    Add a task
                </Link>
            </div>
        </section>
    </div>
</template>
