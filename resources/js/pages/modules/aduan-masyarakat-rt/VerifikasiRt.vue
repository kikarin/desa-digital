<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card/index';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import { ref, computed } from 'vue';
import type { BreadcrumbItem } from '@/types';
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
        created_by_user: {
            id: number;
            name: string;
        } | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Aduan', href: '#' },
    { title: 'Verifikasi Aduan RT', href: '/aduan-masyarakat-rt' },
    { title: 'Verifikasi', href: '#' },
];

const status = ref<'diverifikasi_rt' | 'dibatalkan' | null>(null);
const rtCatatan = ref('');

const canSubmit = computed(() => {
    if (!status.value) return false;
    if (status.value === 'dibatalkan') {
        return rtCatatan.value.length >= 10; // Minimal 10 karakter untuk alasan pembatalan
    }
    if (status.value === 'diverifikasi_rt') {
        return true; // Catatan opsional jika disetujui
    }
    return false;
});

const handleSubmit = async () => {
    if (!canSubmit.value) {
        toast({
            title: 'Lengkapi semua field yang diperlukan',
            variant: 'destructive',
        });
        return;
    }

    const formData = new FormData();
    formData.append('id', String(props.item.id));
    formData.append('status', status.value!);
    formData.append('rt_catatan', rtCatatan.value || '');

    router.post(
        `/aduan-masyarakat-rt/${props.item.id}/verifikasi`,
        formData,
        {
            onSuccess: () => {
                toast({
                    title: 'Aduan berhasil diverifikasi',
                    variant: 'success',
                });
                router.visit('/aduan-masyarakat-rt');
            },
            onError: (errors: any) => {
                const errorMessage = errors.message || errors.rt_catatan?.[0] || 'Gagal memverifikasi aduan';
                toast({
                    title: errorMessage,
                    variant: 'destructive',
                });
            },
        }
    );
};
</script>

<template>
    <Head title="Verifikasi Aduan RT" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <!-- Info Aduan -->
            <Card>
                <CardHeader>
                    <CardTitle>Informasi Aduan</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div><strong>Kategori:</strong> {{ item.kategori_aduan_nama }}</div>
                        <div><strong>Judul:</strong> {{ item.judul }}</div>
                        <div><strong>Pelapor:</strong> {{ item.created_by_user?.name || '-' }}</div>
                        <div><strong>Jenis Aduan:</strong> {{ item.jenis_aduan === 'publik' ? 'Publik' : 'Private' }}</div>
                    </div>
                </CardContent>
            </Card>

            <!-- Detail Aduan -->
            <Card>
                <CardHeader>
                    <CardTitle>Detail Aduan</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div>
                            <strong>Detail:</strong>
                            <p class="mt-1 text-sm">{{ item.detail_aduan }}</p>
                        </div>
                        <div v-if="item.nama_lokasi">
                            <strong>Nama Lokasi:</strong>
                            <p class="mt-1 text-sm">{{ item.nama_lokasi }}</p>
                        </div>
                        <div v-if="item.deskripsi_lokasi">
                            <strong>Deskripsi Lokasi:</strong>
                            <p class="mt-1 text-sm">{{ item.deskripsi_lokasi }}</p>
                        </div>
                        <div v-if="item.alasan_melaporkan">
                            <strong>Alasan Melaporkan:</strong>
                            <p class="mt-1 text-sm">{{ item.alasan_melaporkan }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Peta Lokasi -->
            <Card v-if="item.latitude && item.longitude">
                <CardHeader>
                    <CardTitle>Lokasi Aduan</CardTitle>
                </CardHeader>
                <CardContent>
                    <LocationMapView
                        :latitude="item.latitude"
                        :longitude="item.longitude"
                        :marker-popup-text="item.judul"
                    />
                </CardContent>
            </Card>

            <!-- Files -->
            <Card v-if="item.files && item.files.length > 0">
                <CardHeader>
                    <CardTitle>Bukti Laporan</CardTitle>
                </CardHeader>
                <CardContent>
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
                </CardContent>
            </Card>

            <!-- Form Verifikasi -->
            <Card>
                <CardHeader>
                    <CardTitle>Verifikasi Aduan</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <!-- Status -->
                        <div>
                            <Label>Status Verifikasi <span class="text-red-500">*</span></Label>
                            <RadioGroup :model-value="status ?? undefined" @update:model-value="(val: string) => status = val as 'diverifikasi_rt' | 'dibatalkan' | null" class="mt-2">
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem value="diverifikasi_rt" id="diverifikasi_rt" />
                                    <Label for="diverifikasi_rt" class="cursor-pointer">Setujui (Diverifikasi RT)</Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem value="dibatalkan" id="dibatalkan" />
                                    <Label for="dibatalkan" class="cursor-pointer">Batalkan</Label>
                                </div>
                            </RadioGroup>
                        </div>

                        <!-- Catatan -->
                        <div>
                            <Label for="rt_catatan">Catatan <span v-if="status === 'dibatalkan'" class="text-red-500">*</span></Label>
                            <Textarea
                                id="rt_catatan"
                                v-model="rtCatatan"
                                placeholder="Masukkan catatan verifikasi (wajib jika dibatalkan)"
                                :rows="4"
                                class="mt-2"
                            />
                            <p class="text-xs text-muted-foreground mt-1">
                                <span v-if="status === 'dibatalkan'">Catatan wajib diisi minimal 10 karakter</span>
                                <span v-else>Catatan opsional</span>
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-2">
                            <Button type="button" variant="outline" @click="router.visit('/aduan-masyarakat-rt')">
                                Batal
                            </Button>
                            <Button type="submit" :disabled="!canSubmit">
                                Verifikasi
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
