<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, Pencil, Plus, Search, Trash2, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    formatDate,
    isOverdue,
    priorityClasses,
    statusLabels,
} from '@/lib/tasks';
import { cn } from '@/lib/utils';
import { create, destroy, edit, index, toggle } from '@/routes/tasks';
import type {
    Option,
    Paginated,
    Task,
    TaskPriority,
    TaskStatus,
} from '@/types';

const props = defineProps<{
    tasks: Paginated<Task>;
    filters: { search: string; status: string; priority: string };
    statuses: Option<TaskStatus>[];
    priorities: Option<TaskPriority>[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tasks', href: index() }],
    },
});

// Local, reactive copies of the filters ("all" = no filter, because a select item can't be empty)
const search = ref(props.filters.search);
const status = ref(props.filters.status || 'all');
const priority = ref(props.filters.priority || 'all');

const hasFilters = computed(
    () =>
        search.value !== '' ||
        status.value !== 'all' ||
        priority.value !== 'all',
);

// Reload the list from the server with the current filters in the URL (?search=…&status=…)
function applyFilters() {
    router.get(
        index.url(),
        {
            search: search.value || undefined,
            status: status.value === 'all' ? undefined : status.value,
            priority: priority.value === 'all' ? undefined : priority.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

// watch: run code when a value changes (like useEffect with dependencies)
watch([status, priority], applyFilters);

// Debounce the search box: wait until the user stops typing for 300 ms
let searchTimer: ReturnType<typeof setTimeout> | undefined;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 300);
});

function clearFilters() {
    search.value = '';
    status.value = 'all';
    priority.value = 'all';
}

function toggleDone(task: Task) {
    router.patch(toggle.url(task.id), {}, { preserveScroll: true });
}

function deleteTask(task: Task) {
    if (confirm(`Delete "${task.title}"?`)) {
        router.delete(destroy.url(task.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Tasks" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Tasks"
                :description="`${tasks.total} ${tasks.total === 1 ? 'task' : 'tasks'}`"
            />
            <Button as-child>
                <Link :href="create()"><Plus /> New task</Link>
            </Button>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative w-full sm:w-64">
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search tasks…"
                    class="pl-8"
                    aria-label="Search tasks"
                />
            </div>

            <Select v-model="status">
                <SelectTrigger class="w-40" aria-label="Filter by status">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All statuses</SelectItem>
                    <SelectItem
                        v-for="s in statuses"
                        :key="s.value"
                        :value="s.value"
                    >
                        {{ s.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="priority">
                <SelectTrigger class="w-40" aria-label="Filter by priority">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All priorities</SelectItem>
                    <SelectItem
                        v-for="p in priorities"
                        :key="p.value"
                        :value="p.value"
                    >
                        {{ p.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Button
                v-if="hasFilters"
                variant="ghost"
                size="sm"
                @click="clearFilters"
            >
                <X /> Clear
            </Button>
        </div>

        <!-- Task list -->
        <ul v-if="tasks.data.length" class="divide-y rounded-xl border">
            <li
                v-for="task in tasks.data"
                :key="task.id"
                class="flex items-start gap-3 p-4"
            >
                <Checkbox
                    class="mt-1"
                    :model-value="task.status === 'done'"
                    :aria-label="`Mark '${task.title}' as ${task.status === 'done' ? 'not done' : 'done'}`"
                    @update:model-value="toggleDone(task)"
                />

                <div class="min-w-0 flex-1 space-y-1">
                    <p
                        :class="
                            cn(
                                'font-medium',
                                task.status === 'done' &&
                                    'text-muted-foreground line-through',
                            )
                        "
                    >
                        {{ task.title }}
                    </p>
                    <p
                        v-if="task.description"
                        class="line-clamp-2 text-sm text-muted-foreground"
                    >
                        {{ task.description }}
                    </p>

                    <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
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
                            class="rounded-full bg-muted px-2 py-0.5 text-muted-foreground"
                        >
                            {{ statusLabels[task.status] }}
                        </span>
                        <span
                            v-if="task.due_date"
                            :class="
                                cn(
                                    'inline-flex items-center gap-1',
                                    isOverdue(task)
                                        ? 'font-medium text-red-600 dark:text-red-400'
                                        : 'text-muted-foreground',
                                )
                            "
                        >
                            <CalendarDays class="size-3.5" />
                            {{ isOverdue(task) ? 'Overdue · ' : ''
                            }}{{ formatDate(task.due_date) }}
                        </span>
                    </div>
                </div>

                <div class="flex shrink-0 gap-1">
                    <Button variant="ghost" size="icon" as-child>
                        <Link
                            :href="edit(task.id)"
                            :aria-label="`Edit '${task.title}'`"
                        >
                            <Pencil />
                        </Link>
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        :aria-label="`Delete '${task.title}'`"
                        @click="deleteTask(task)"
                    >
                        <Trash2 />
                    </Button>
                </div>
            </li>
        </ul>

        <!-- Empty state (v-else pairs with the v-if above) -->
        <div v-else class="rounded-xl border border-dashed p-12 text-center">
            <p class="font-medium">
                {{
                    hasFilters ? 'No tasks match your filters' : 'No tasks yet'
                }}
            </p>
            <p class="mt-1 text-sm text-muted-foreground">
                {{
                    hasFilters
                        ? 'Try a different search or clear the filters.'
                        : 'Create your first task to get started.'
                }}
            </p>
            <Button v-if="!hasFilters" class="mt-4" as-child>
                <Link :href="create()"><Plus /> New task</Link>
            </Button>
        </div>

        <!-- Pagination: Laravel's paginator sends ready-made links -->
        <nav
            v-if="tasks.last_page > 1"
            class="flex flex-wrap items-center justify-between gap-3 text-sm"
            aria-label="Pagination"
        >
            <p class="text-muted-foreground">
                Showing {{ tasks.from }}–{{ tasks.to }} of {{ tasks.total }}
            </p>
            <div class="flex flex-wrap gap-1">
                <template v-for="link in tasks.links" :key="link.label">
                    <Button
                        v-if="link.url"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        as-child
                    >
                        <Link
                            :href="link.url"
                            preserve-scroll
                            v-html="link.label"
                        />
                    </Button>
                    <Button
                        v-else
                        variant="outline"
                        size="sm"
                        disabled
                        v-html="link.label"
                    />
                </template>
            </div>
        </nav>
    </div>
</template>
