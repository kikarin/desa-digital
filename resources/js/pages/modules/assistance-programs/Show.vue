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
        tanggal_penyaluran?: string;
        jam_mulai_pengambilan?: string;
        jam_selesai_pengambilan?: string;
        desil_min?: number;
        desil_max?: number;
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
    const labels: Record<string, string> = {
        'PROSES': 'Proses',
        'PENYALURAN': 'Penyaluran',
        'SELESAI': 'Selesai',
    };
    return labels[value] || value;
};

const getJadwalLabel = () => {
    if (!props.item.tanggal_penyaluran) {
        return '-';
    }
    
    const tanggal = new Date(props.item.tanggal_penyaluran).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
    
    if (props.item.jam_mulai_pengambilan && props.item.jam_selesai_pengambilan) {
        const jamMulai = props.item.jam_mulai_pengambilan.substring(0, 5);
        const jamSelesai = props.item.jam_selesai_pengambilan.substring(0, 5);
        return `${tanggal}, ${jamMulai} - ${jamSelesai} WIB`;
    } else if (props.item.jam_mulai_pengambilan) {
        const jamMulai = props.item.jam_mulai_pengambilan.substring(0, 5);
        return `${tanggal}, mulai ${jamMulai} WIB`;
    }
    
    return tanggal;
};

const getDesilLabel = () => {
    if (!props.item.desil_min || !props.item.desil_max) {
        return '-';
    }
    
    if (props.item.desil_min === props.item.desil_max) {
        return `Desil ${props.item.desil_min}`;
    }
    
    return `Desil ${props.item.desil_min} - ${props.item.desil_max}`;
};

const fields = [
    { label: 'Nama Program', value: props.item.nama_program },
    { label: 'Tahun', value: props.item.tahun.toString() },
    { label: 'Periode', value: props.item.periode || '-' },
    { label: 'Target Penerima', value: getTargetPenerimaLabel(props.item.target_penerima) },
    { label: 'Desil Target', value: getDesilLabel() },
    { label: 'Status', value: getStatusLabel(props.item.status) },
    { label: 'Jadwal Penyaluran', value: getJadwalLabel() },
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

