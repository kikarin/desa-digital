<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const urlParams = new URLSearchParams(window.location.search);
const programIdFromUrl = urlParams.get('program_id');

const breadcrumbs = computed(() => {
    const base = [
        { title: 'Program Bantuan', href: '/program-bantuan/program-bantuan' },
        { title: 'Item Program', href: programIdFromUrl ? `/program-bantuan/item-program?program_id=${programIdFromUrl}` : '/program-bantuan/item-program' },
        { title: 'Tambah Item Program', href: '#' },
    ];
    return base;
});

const listPrograms = ref<{ value: number; label: string }[]>([]);
const listItems = ref<{ value: number; label: string; satuan?: string }[]>([]);
const initialData = ref<{ assistance_program_id?: number }>({});

onMounted(async () => {
    try {
        // Fetch programs hanya jika tidak ada program_id di URL
        if (!programIdFromUrl) {
            const programsResponse = await axios.get('/api/assistance-programs', { params: { per_page: -1 } });
            if (programsResponse.data.data) {
                listPrograms.value = programsResponse.data.data.map((program: any) => ({
                    value: program.id,
                    label: `${program.nama_program} (${program.tahun} - ${program.periode})`,
                }));
            }
        }

        // Fetch items
        const itemsResponse = await axios.get('/api/assistance-items', { params: { per_page: -1 } });
        if (itemsResponse.data.data) {
            listItems.value = itemsResponse.data.data.map((item: any) => ({
                value: item.id,
                label: `${item.nama_item} (${item.satuan})`,
                satuan: item.satuan,
            }));
        }

        // Set initial program_id jika ada dari URL
        if (programIdFromUrl) {
            initialData.value = {
                assistance_program_id: parseInt(programIdFromUrl),
            };
        }
    } catch (error) {
        console.error('Gagal mengambil data:', error);
    }
});

const backUrl = computed(() => {
    if (programIdFromUrl) {
        return `/program-bantuan/item-program?program_id=${programIdFromUrl}`;
    }
    return '/program-bantuan/item-program';
});
</script>

<template>
    <PageCreate title="Tambah Item Program" :breadcrumbs="breadcrumbs" :back-url="backUrl">
        <Form 
            mode="create" 
            :list-programs="listPrograms" 
            :list-items="listItems" 
            :initial-data="initialData"
            :program-id-from-url="programIdFromUrl ? parseInt(programIdFromUrl) : null"
        />
    </PageCreate>
</template>

