<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import LocationMapView from '@/components/LocationMapView.vue';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        latitude: string;
        longitude: string;
        nama_lokasi: string;
        alamat: string | null;
        title: string;
        foto: string | null;
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
    { title: 'Bank Sampah', href: '/bank-sampah' },
    { title: 'Detail Bank Sampah', href: `/bank-sampah/${props.item.id}` },
];


const fields = [
    { label: 'Nama Lokasi', value: props.item.nama_lokasi },
    { label: 'Alamat', value: props.item.alamat || '-', className: 'sm:col-span-2' },
    { label: 'Title', value: props.item.title },
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
    router.visit(`/bank-sampah/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/bank-sampah/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            router.visit('/bank-sampah');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Bank Sampah"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/bank-sampah'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    >
        <template #custom>
            <!-- Peta Lokasi -->
            <div class="mt-4">
                <LocationMapView
                    :latitude="item.latitude"
                    :longitude="item.longitude"
                    :marker-popup-text="item.title"
                />
            </div>

            <!-- Foto -->
            <div v-if="item.foto" class="mt-4">
                <div class="text-muted-foreground text-xs mb-2">Foto</div>
                <img :src="item.foto" :alt="item.title" class="max-w-full h-auto rounded-lg border" />
            </div>
        </template>
    </PageShow>
</template>

