<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        jenis_surat_id: number;
        nama_atribut: string;
        tipe_data: string;
        opsi_pilihan: string;
        is_required: boolean;
        nama_lampiran: string;
        minimal_file: number;
        is_required_lampiran: boolean;
        urutan: number;
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
        jenis_surat?: {
            id: number;
            nama: string;
            kode: string;
        };
    };
}>();

const breadcrumbs = [
    { title: 'Layanan Surat', href: '#' },
    { title: 'Atribut Jenis Surat', href: '/layanan-surat/atribut-jenis-surat' },
    { title: 'Detail Atribut', href: `/layanan-surat/atribut-jenis-surat/${props.item.id}` },
];

const fields = [
    { label: 'Jenis Surat', value: props.item.jenis_surat?.nama || '-' },
    { label: 'Nama Atribut', value: props.item.nama_atribut },
    { label: 'Tipe Data', value: props.item.tipe_data },
    { label: 'Wajib', value: props.item.is_required ? 'Ya' : 'Tidak' },
    { label: 'Urutan', value: props.item.urutan },
];

if (props.item.tipe_data === 'select' && props.item.opsi_pilihan) {
    fields.push({ label: 'Opsi Pilihan', value: props.item.opsi_pilihan });
}

if (props.item.nama_lampiran) {
    fields.push(
        { label: 'Nama Lampiran', value: props.item.nama_lampiran },
        { label: 'Minimal File', value: props.item.minimal_file },
        { label: 'Lampiran Wajib', value: props.item.is_required_lampiran ? 'Ya' : 'Tidak' }
    );
}

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/layanan-surat/atribut-jenis-surat/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/layanan-surat/atribut-jenis-surat/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            router.visit('/layanan-surat/atribut-jenis-surat');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Atribut Jenis Surat"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/layanan-surat/atribut-jenis-surat'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>

