<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const props = defineProps<{
    get_Roles: Record<number, string>;
    rw_options?: Array<{ value: number; label: string }>;
    rt_options?: Array<{ value: number; label: string }>;
}>();

const breadcrumbs = [
    { title: 'Users', href: '/users' },
    { title: 'Tambah User', href: '/users/create' },
];
</script>

<template>
    <PageCreate title="Tambah User" :breadcrumbs="breadcrumbs" back-url="/users">
        <Form 
            mode="create" 
            :roles="get_Roles" 
            :rw_options="rw_options || []"
            :rt_options="rt_options || []"
        />
    </PageCreate>
</template>
