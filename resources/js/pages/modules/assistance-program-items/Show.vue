<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        assistance_program_id: number;
        assistance_item_id: number;
        jumlah: number;
        satuan?: string;
        nama_program?: string;
        nama_item?: string;
        program?: {
            id: number;
            nama_program: string;
        } | null;
        item?: {
            id: number;
            nama_item: string;
            satuan: string;
        } | null;
        created_at: string;
        created_by_user: {
            id: number;
            name: string;
        } | null;
        updated_at: string;
        updated_by_user: {
            id: number;
            name: string;
        } | null;
    };
}>();

// Get program_id from URL atau dari item
const urlParams = new URLSearchParams(window.location.search);
const programIdFromUrl = urlParams.get('program_id');
const programId = programIdFromUrl ? parseInt(programIdFromUrl) : (props.item.assistance_program_id || null);

const breadcrumbs = computed(() => {
    const base = [
        { title: 'Program Bantuan', href: '/program-bantuan/program-bantuan' },
        { title: 'Item Program', href: programId ? `/program-bantuan/item-program?program_id=${programId}` : '/program-bantuan/item-program' },
        { title: 'Detail Item Program', href: '#' },
    ];
    return base;
});

const backUrl = computed(() => {
    if (programId) {
        return `/program-bantuan/item-program?program_id=${programId}`;
    }
    return '/program-bantuan/item-program';
});

// Format jumlah dengan IDR jika satuan Rupiah atau >= 1000
const formatJumlah = (jumlah: number, satuan?: string) => {
    const numJumlah = parseFloat(jumlah.toString());
    if (satuan === 'Rupiah' || numJumlah >= 1000) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(numJumlah);
    }
    return numJumlah.toLocaleString('id-ID') + ' ' + (satuan || '');
};

const fields = [
    { label: 'Program Bantuan', value: props.item.nama_program || props.item.program?.nama_program || '-' },
    { label: 'Item Bantuan', value: props.item.nama_item || props.item.item?.nama_item || '-' },
    { 
        label: 'Jumlah', 
        value: formatJumlah(props.item.jumlah, props.item.satuan || props.item.item?.satuan) 
    },
    { label: 'Satuan', value: props.item.satuan || props.item.item?.satuan || '-' },
];

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    let editUrl = `/program-bantuan/item-program/${props.item.id}/edit`;
    if (programId) {
        editUrl += `?program_id=${programId}`;
    }
    router.visit(editUrl);
};

const handleDelete = () => {
    router.delete(`/program-bantuan/item-program/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            // Redirect dengan preserve program_id jika ada
            if (programId) {
                router.visit(`/program-bantuan/item-program?program_id=${programId}`);
            } else {
                router.visit('/program-bantuan/item-program');
            }
            // Trigger refresh di Program Bantuan index untuk update badge
            window.dispatchEvent(new CustomEvent('refresh-assistance-programs-index'));
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Item Program"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="backUrl"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>

