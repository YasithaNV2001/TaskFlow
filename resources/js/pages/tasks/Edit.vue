<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import TaskForm from '@/components/tasks/TaskForm.vue';
import { index } from '@/routes/tasks';
import type { Option, Task, TaskPriority, TaskStatus } from '@/types';

defineProps<{
    task: Task;
    statuses: Option<TaskStatus>[];
    priorities: Option<TaskPriority>[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Tasks', href: index() },
            { title: 'Edit task', href: '' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit: ${task.title}`" />

    <div class="mx-auto w-full max-w-3xl space-y-6 p-4">
        <Heading title="Edit task" :description="task.title" />
        <!-- :key forces a fresh form if you navigate from one task's edit page to another -->
        <TaskForm
            :key="task.id"
            :task="task"
            :statuses="statuses"
            :priorities="priorities"
        />
    </div>
</template>
