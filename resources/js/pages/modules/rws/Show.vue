<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        nomor_rw: string;
        desa: string;
        kecamatan: string;
        kabupaten: string;
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

const breadcrumbs = [
    { title: 'Data Warga', href: '#' },
    { title: 'Wilayah', href: '/data-warga/rws' },
    { title: 'Detail Wilayah', href: `/data-warga/rws/${props.item.id}` },
];

const fields = [
    { label: 'Nomor RW', value: props.item.nomor_rw },
    { label: 'Desa', value: props.item.desa },
    { label: 'Kecamatan', value: props.item.kecamatan },
    { label: 'Kabupaten', value: props.item.kabupaten },
];

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/data-warga/rws/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/data-warga/rws/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            router.visit('/data-warga/rws');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Wilayah"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/data-warga/rws'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>

