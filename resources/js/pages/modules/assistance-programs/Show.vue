<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        nama_program: string;
        tahun: number;
        periode: string | null;
        target_penerima: string;
        status: string;
        keterangan: string | null;
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
    { title: 'Program Bantuan', href: '/program-bantuan/program-bantuan' },
    { title: 'Detail Program Bantuan', href: `/program-bantuan/program-bantuan/${props.item.id}` },
];

const getTargetPenerimaLabel = (value: string) => {
    return value === 'KELUARGA' ? 'Keluarga' : 'Individu';
};

const getStatusLabel = (value: string) => {
    return value === 'SELESAI' ? 'Selesai' : 'Proses';
};

const fields = [
    { label: 'Nama Program', value: props.item.nama_program },
    { label: 'Tahun', value: props.item.tahun.toString() },
    { label: 'Periode', value: props.item.periode || '-' },
    { label: 'Target Penerima', value: getTargetPenerimaLabel(props.item.target_penerima) },
    { label: 'Status', value: getStatusLabel(props.item.status) },
    { label: 'Keterangan', value: props.item.keterangan || '-' },
];

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/program-bantuan/program-bantuan/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/program-bantuan/program-bantuan/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            router.visit('/program-bantuan/program-bantuan');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Program Bantuan"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/program-bantuan/program-bantuan'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>

