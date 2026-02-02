<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { router } from '@inertiajs/vue3';
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
        layanan_darurat?: Array<{
            id: number;
            kategori: string;
            kategori_label: string;
            title: string;
            alamat: string | null;
            nomor_whatsapp: string | null;
            latitude: string;
            longitude: string;
        }>;
        files: Array<{
            id: number;
            file_path: string;
            file_type: string;
            file_name: string;
        }>;
        rt_verifikasi_id: number | null;
        rt_verifikasi_at: string | null;
        rt_catatan: string | null;
        rt_verifikasi_user: {
            id: number;
            name: string;
        } | null;
        created_at: string;
        created_by_user: {
            id: number;
            name: string;
        } | null;
    };
    can?: {
        Verifikasi?: boolean;
    };
}>();

const breadcrumbs = [
    { title: 'Aduan', href: '#' },
    { title: 'Verifikasi Aduan RT', href: '/aduan-masyarakat-rt' },
    { title: 'Detail Aduan', href: `/aduan-masyarakat-rt/${props.item.id}` },
];

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
                : props.item.status === 'diverifikasi_admin'
                    ? 'Diverifikasi Admin'
                    : props.item.status === 'diverifikasi_rt'
                        ? 'Diverifikasi RT'
                        : 'Menunggu Verifikasi' 
    },
    { label: 'Nama Lokasi', value: props.item.nama_lokasi || '-', className: 'sm:col-span-2' },
    { label: 'Deskripsi Lokasi', value: props.item.deskripsi_lokasi || '-', className: 'sm:col-span-2' },
    { label: 'Alasan Melaporkan', value: props.item.alasan_melaporkan || '-', className: 'sm:col-span-2' },
];

if (props.item.rt_verifikasi_at) {
    fields.push({ 
        label: 'Diverifikasi RT Pada', 
        value: props.item.rt_verifikasi_at ? new Date(props.item.rt_verifikasi_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-' 
    });
}

if (props.item.rt_verifikasi_user) {
    fields.push({ label: 'Diverifikasi Oleh RT', value: props.item.rt_verifikasi_user.name });
}

if (props.item.rt_catatan) {
    fields.push({ label: 'Catatan RT', value: props.item.rt_catatan, className: 'sm:col-span-2' });
}

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
];

const handleVerifikasi = () => {
    if (props.item.status === 'menunggu_verifikasi' && props.can?.Verifikasi) {
        router.visit(`/aduan-masyarakat-rt/${props.item.id}/verifikasi`);
    }
};
</script>

<template>
    <PageShow
        title="Detail Aduan RT"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/aduan-masyarakat-rt'"
    >
        <template #custom-action>
            <Button
                v-if="item.status === 'menunggu_verifikasi' && can?.Verifikasi"
                @click="handleVerifikasi"
                variant="default"
            >
                Verifikasi
            </Button>
        </template>
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

            <!-- Layanan Darurat yang Dipilih -->
            <div v-if="item.layanan_darurat && item.layanan_darurat.length > 0" class="mt-6">
                <div class="text-muted-foreground text-sm font-medium mb-3">Layanan Darurat yang Dipilih</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Card v-for="layanan in item.layanan_darurat" :key="layanan.id" class="p-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="font-semibold">{{ layanan.title }}</h4>
                                <span class="text-xs bg-primary/10 text-primary px-2 py-1 rounded">{{ layanan.kategori_label }}</span>
                            </div>
                            <p v-if="layanan.alamat" class="text-sm text-muted-foreground">{{ layanan.alamat }}</p>
                            <div v-if="layanan.nomor_whatsapp" class="flex items-center gap-2">
                                <a
                                    :href="`https://wa.me/${layanan.nomor_whatsapp.replace(/[^0-9+]/g, '').replace(/^0/, '62')}`"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors text-sm"
                                >
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                    </svg>
                                    Hubungi via WhatsApp
                                </a>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
            <div v-else class="mt-6">
                <div class="text-muted-foreground text-sm">Tidak ada layanan darurat yang dipilih</div>
            </div>
        </template>
    </PageShow>
</template>
