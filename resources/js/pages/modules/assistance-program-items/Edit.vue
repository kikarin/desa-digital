<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const props = defineProps<{
    item: Record<string, any>;
}>();

// Get program_id from URL atau dari item (prioritas URL)
const urlParams = new URLSearchParams(window.location.search);
const programIdFromUrl = urlParams.get('program_id');
// Prioritas: URL > item.assistance_program_id
const programId = programIdFromUrl ? parseInt(programIdFromUrl) : (props.item?.assistance_program_id || null);

const breadcrumbs = computed(() => {
    const base = [
        { title: 'Program Bantuan', href: '/program-bantuan/program-bantuan' },
        { title: 'Item Program', href: programId ? `/program-bantuan/item-program?program_id=${programId}` : '/program-bantuan/item-program' },
        { title: 'Edit Item Program', href: '#' },
    ];
    return base;
});

const backUrl = computed(() => {
    if (programId) {
        return `/program-bantuan/item-program?program_id=${programId}`;
    }
    return '/program-bantuan/item-program';
});

const listPrograms = ref<{ value: number; label: string }[]>([]);
const listItems = ref<{ value: number; label: string; satuan?: string }[]>([]);

onMounted(async () => {
    try {
        // Fetch programs hanya jika tidak ada program_id (untuk edit, biasanya sudah ada dari item)
        if (!programId) {
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
    } catch (error) {
        console.error('Gagal mengambil data:', error);
    }
});
</script>

<template>
    <PageEdit title="Edit Item Program" :breadcrumbs="breadcrumbs" :back-url="backUrl">
        <Form 
            mode="edit" 
            :initial-data="item" 
            :list-programs="listPrograms" 
            :list-items="listItems"
            :program-id-from-url="programId"
        />
    </PageEdit>
</template>

