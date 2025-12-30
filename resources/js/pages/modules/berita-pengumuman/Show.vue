<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        tipe: string;
        title: string;
        foto: string | null;
        tanggal: string;
        deskripsi: string | null;
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
    { title: 'Berita & Pengumuman', href: '/berita-pengumuman' },
    { title: 'Detail Berita/Pengumuman', href: `/berita-pengumuman/${props.item.id}` },
];

const fields = [
    {
        label: 'Tipe',
        value: props.item.tipe === 'berita' ? 'Berita' : 'Event',
    },
    { label: 'Title', value: props.item.title },
    {
        label: 'Tanggal',
        value: new Date(props.item.tanggal).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        }),
    },
    { label: 'Deskripsi', value: props.item.deskripsi || '-', className: 'sm:col-span-2' },
];

const actionFields = [
    {
        label: 'Created At',
        value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }),
    },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    {
        label: 'Updated At',
        value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }),
    },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/berita-pengumuman/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/berita-pengumuman/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            router.visit('/berita-pengumuman');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Berita & Pengumuman"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/berita-pengumuman'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    >
        <template #custom>
            <div v-if="item.foto" class="mt-4">
                <div class="text-muted-foreground text-xs mb-2">Foto</div>
                <img :src="item.foto" :alt="item.title" class="max-w-full h-auto rounded-lg border" />
            </div>
        </template>
    </PageShow>
</template>

