<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import LocationMapView from '@/components/LocationMapView.vue';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        kategori_aduan_nama: string;
        judul: string;
        detail_aduan: string;
        latitude: string | null;
        longitude: string | null;
        nama_lokasi: string | null;
        deskripsi_lokasi: string | null;
        jenis_aduan: string;
        alasan_melaporkan: string | null;
        status: string;
        files: Array<{
            id: number;
            file_path: string;
            file_type: string;
            file_name: string;
        }>;
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
    { title: 'Aduan Saya', href: '/aduan-saya' },
    { title: 'Detail Aduan', href: `/aduan-saya/${props.item.id}` },
];


const canBeEdited = computed(() => {
    return props.item.status === 'menunggu_verifikasi' || props.item.status === 'dibatalkan';
});

const fields = [
    { label: 'Kategori', value: props.item.kategori_aduan_nama },
    { label: 'Judul', value: props.item.judul },
    { label: 'Detail Aduan', value: props.item.detail_aduan, className: 'sm:col-span-2' },
    { label: 'Jenis Aduan', value: props.item.jenis_aduan === 'publik' ? 'Publik' : 'Private' },
    { 
        label: 'Status', 
        value: props.item.status === 'selesai' 
            ? 'Selesai' 
            : props.item.status === 'dibatalkan' 
                ? 'Dibatalkan' 
                : 'Menunggu Verifikasi' 
    },
    { label: 'Nama Lokasi', value: props.item.nama_lokasi || '-', className: 'sm:col-span-2' },
    { label: 'Deskripsi Lokasi', value: props.item.deskripsi_lokasi || '-', className: 'sm:col-span-2' },
    { label: 'Alasan Melaporkan', value: props.item.alasan_melaporkan || '-', className: 'sm:col-span-2' },
];

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];


const handleEdit = () => {
    if (canBeEdited.value) {
        router.visit(`/aduan-saya/${props.item.id}/edit`);
    }
};

</script>

<template>
    <PageShow
        title="Aduan Saya"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/aduan-saya'"
        :on-edit="canBeEdited ? handleEdit : undefined"
    >
        <template #custom>
            <!-- Peta Lokasi -->
            <div class="mt-4">
                <LocationMapView
                    :latitude="item.latitude"
                    :longitude="item.longitude"
                    :marker-popup-text="item.judul"
                />
            </div>

            <!-- Files -->
            <div v-if="item.files && item.files.length > 0" class="mt-4">
                <div class="text-muted-foreground text-xs mb-2">Bukti Laporan</div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div v-for="file in item.files" :key="file.id" class="space-y-2">
                        <div v-if="file.file_type === 'foto'">
                            <img :src="file.file_path" :alt="file.file_name" class="w-full h-32 object-cover rounded border" />
                        </div>
                        <div v-else class="border rounded p-4 text-center">
                            <video :src="file.file_path" controls class="w-full h-32 rounded"></video>
                        </div>
                        <p class="text-xs text-muted-foreground truncate">{{ file.file_name }}</p>
                    </div>
                </div>
            </div>
        </template>
    </PageShow>
</template>

