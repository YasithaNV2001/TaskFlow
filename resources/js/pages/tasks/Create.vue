<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import TaskForm from '@/components/tasks/TaskForm.vue';
import { create, index } from '@/routes/tasks';
import type { Option, TaskPriority, TaskStatus } from '@/types';

// Props come from TaskController@create via Inertia::render()
defineProps<{
    statuses: Option<TaskStatus>[];
    priorities: Option<TaskPriority>[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Tasks', href: index() },
            { title: 'New task', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New task" />

    <div class="mx-auto w-full max-w-3xl space-y-6 p-4">
        <Heading title="New task" description="What do you need to get done?" />
        <TaskForm :statuses="statuses" :priorities="priorities" />
    </div>
</template>
