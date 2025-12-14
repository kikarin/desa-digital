<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        nama_item: string;
        tipe: string;
        satuan: string;
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
    { title: 'Data Master', href: '#' },
    { title: 'Item Bantuan', href: '/data-master/assistance-items' },
    { title: 'Detail Item Bantuan', href: `/data-master/assistance-items/${props.item.id}` },
];

const getTipeLabel = (value: string) => {
    return value === 'UANG' ? 'Uang' : 'Barang';
};

const fields = [
    { label: 'Nama Item', value: props.item.nama_item },
    { label: 'Tipe', value: getTipeLabel(props.item.tipe) },
    { label: 'Satuan', value: props.item.satuan },
];

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/data-master/assistance-items/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/data-master/assistance-items/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            router.visit('/data-master/assistance-items');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Item Bantuan"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/data-master/assistance-items'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>

