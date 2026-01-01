<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card/index';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import axios from 'axios';
import { ref, onMounted, onBeforeUnmount, computed, nextTick, watch } from 'vue';
import type { BreadcrumbItem } from '@/types';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        kategori_proposal_nama: string;
        resident_nama: string;
        resident_nik: string;
        nama_kegiatan: string;
        deskripsi_kegiatan: string;
        usulan_anggaran_formatted: string;
        file_pendukung: string[];
        latitude: string;
        longitude: string;
        kecamatan: string;
        kelurahan_desa: string;
        deskripsi_lokasi_tambahan: string;
        thumbnail_foto_banner: string | null;
        status: string;
    };
    admin_ttd_digital?: {
        id: number;
        tanda_tangan_digital: string;
    } | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pengajuan Proposal', href: '/pengajuan-proposal' },
    { title: 'Verifikasi', href: '#' },
];

const status = ref<'disetujui' | 'ditolak' | null>(null);
const tandaTanganType = ref<'digital' | 'foto' | null>(null);
const useExistingTtd = ref<'yes' | 'no'>('no');
const catatanVerifikasi = ref('');
const fotoTandaTangan = ref<File | null>(null);
const fotoPreview = ref<string | null>(null);

// Canvas untuk TTD digital
const canvasRef = ref<HTMLCanvasElement | null>(null);
const isDrawing = ref(false);
const hasDrawn = ref(false);

// Canvas dimensions
const canvasWidth = 400;
const canvasHeight = 200;

const initCanvas = () => {
    if (canvasRef.value) {
        const canvas = canvasRef.value;
        canvas.width = canvasWidth;
        canvas.height = canvasHeight;
        
        const ctx = canvas.getContext('2d');
        if (ctx) {
            ctx.strokeStyle = '#111827';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }
        
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseleave', stopDrawing);
        canvas.addEventListener('touchstart', startDrawing, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', stopDrawing);
        
        return true;
    }
    return false;
};

watch([() => tandaTanganType.value, () => useExistingTtd.value], ([type, useExisting]) => {
    if (type === 'digital' && useExisting === 'no') {
        nextTick(() => {
            const tryInit = (attempt = 1) => {
                if (initCanvas()) {
                    // Success
                } else {
                    if (attempt < 5) {
                        setTimeout(() => tryInit(attempt + 1), attempt * 100);
                    }
                }
            };
            tryInit();
        });
    }
}, { immediate: false });

onMounted(() => {
    if (tandaTanganType.value === 'digital' && useExistingTtd.value === 'no') {
        nextTick(() => {
            if (!initCanvas()) {
                setTimeout(() => {
                    if (!initCanvas()) {
                        setTimeout(() => initCanvas(), 300);
                    }
                }, 100);
            }
        });
    }
});

onBeforeUnmount(() => {
    if (canvasRef.value) {
        const canvas = canvasRef.value;
        canvas.removeEventListener('mousedown', startDrawing);
        canvas.removeEventListener('mousemove', draw);
        canvas.removeEventListener('mouseup', stopDrawing);
        canvas.removeEventListener('mouseleave', stopDrawing);
        canvas.removeEventListener('touchstart', startDrawing);
        canvas.removeEventListener('touchmove', draw);
        canvas.removeEventListener('touchend', stopDrawing);
    }
});

const getEventPos = (e: MouseEvent | TouchEvent) => {
    if (!canvasRef.value) return { x: 0, y: 0 };
    const rect = canvasRef.value.getBoundingClientRect();
    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;
    
    const scaleX = canvasRef.value.width / rect.width;
    const scaleY = canvasRef.value.height / rect.height;
    
    const x = (clientX - rect.left) * scaleX;
    const y = (clientY - rect.top) * scaleY;
    
    return { x, y };
};

const startDrawing = (e: MouseEvent | TouchEvent) => {
    if (!canvasRef.value) return;
    e.preventDefault();
    e.stopPropagation();
    
    const canvas = canvasRef.value;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    
    isDrawing.value = true;
    hasDrawn.value = true;
    const pos = getEventPos(e);
    
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
};

const draw = (e: MouseEvent | TouchEvent) => {
    if (!isDrawing.value || !canvasRef.value) return;
    e.preventDefault();
    e.stopPropagation();
    
    const canvas = canvasRef.value;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    
    const pos = getEventPos(e);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
};

const stopDrawing = (e?: MouseEvent | TouchEvent) => {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    if (isDrawing.value && canvasRef.value) {
        const ctx = canvasRef.value.getContext('2d');
        if (ctx) {
            ctx.closePath();
        }
    }
    isDrawing.value = false;
};

const clearCanvas = () => {
    if (!canvasRef.value) return;
    const ctx = canvasRef.value.getContext('2d');
    if (!ctx) return;
    
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, canvasRef.value.width, canvasRef.value.height);
    ctx.strokeStyle = '#111827';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    hasDrawn.value = false;
};

const getCanvasData = (): string | null => {
    if (!canvasRef.value) return null;
    return canvasRef.value.toDataURL('image/png');
};

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        fotoTandaTangan.value = target.files[0];
        const reader = new FileReader();
        reader.onload = (e) => {
            fotoPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(target.files[0]);
    }
};

const canSubmit = computed(() => {
    if (!status.value) return false;
    if (status.value === 'ditolak') {
        return true; // Catatan verifikasi optional
    }
    if (status.value === 'disetujui') {
        if (!tandaTanganType.value) return false;
        if (tandaTanganType.value === 'digital') {
            if (useExistingTtd.value === 'yes') return true;
            return hasDrawn.value && getCanvasData() !== null;
        } else {
            if (useExistingTtd.value === 'yes') return true;
            return fotoTandaTangan.value !== null;
        }
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

    try {
        const formData = new FormData();
        formData.append('id', String(props.item.id));
        formData.append('status', status.value!);
        formData.append('catatan_verifikasi', catatanVerifikasi.value);

        if (status.value === 'disetujui') {
            formData.append('tanda_tangan_type', tandaTanganType.value as string);
            formData.append('use_existing_ttd', useExistingTtd.value);

            if (tandaTanganType.value === 'digital') {
                if (useExistingTtd.value === 'yes') {
                    if (props.admin_ttd_digital?.tanda_tangan_digital) {
                        formData.append('tanda_tangan_digital', props.admin_ttd_digital.tanda_tangan_digital);
                    }
                } else {
                    const canvasData = getCanvasData();
                    if (canvasData) {
                        formData.append('tanda_tangan_digital', canvasData);
                    }
                }
            } else {
                if (useExistingTtd.value === 'yes') {
                    formData.append('use_existing_foto_ttd', '1');
                } else {
                    if (fotoTandaTangan.value) {
                        formData.append('foto_tanda_tangan', fotoTandaTangan.value);
                    }
                }
            }
        }

        const response = await axios.post(`/pengajuan-proposal/${props.item.id}/verifikasi`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        toast({
            title: 'Pengajuan proposal berhasil diverifikasi',
            variant: 'success',
        });

        router.visit(`/pengajuan-proposal/${props.item.id}`);
    } catch (error: any) {
        toast({
            title: error.response?.data?.message || 'Gagal memverifikasi pengajuan proposal',
            variant: 'destructive',
        });
    }
};
</script>

<template>
    <Head title="Verifikasi Pengajuan Proposal" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <!-- Info Pengajuan -->
            <Card>
                <CardHeader>
                    <CardTitle>Informasi Pengajuan</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div><strong>Kategori Proposal:</strong> {{ item.kategori_proposal_nama }}</div>
                        <div><strong>Pemohon:</strong> {{ item.resident_nama }} ({{ item.resident_nik }})</div>
                        <div><strong>Nama Kegiatan:</strong> {{ item.nama_kegiatan }}</div>
                        <div><strong>Usulan Anggaran:</strong> {{ item.usulan_anggaran_formatted }}</div>
                    </div>
                </CardContent>
            </Card>

            <!-- Form Verifikasi -->
            <Card>
                <CardHeader>
                    <CardTitle>Verifikasi Pengajuan</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <!-- Status -->
                        <div>
                            <Label>Status Verifikasi <span class="text-red-500">*</span></Label>
                            <RadioGroup :model-value="status ?? undefined" @update:model-value="(val: string) => status = val as 'disetujui' | 'ditolak' | null" class="mt-2">
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem value="disetujui" id="disetujui" />
                                    <Label for="disetujui" class="cursor-pointer">Setujui</Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem value="ditolak" id="ditolak" />
                                    <Label for="ditolak" class="cursor-pointer">Tolak</Label>
                                </div>
                            </RadioGroup>
                        </div>

                        <!-- Catatan Verifikasi -->
                        <div>
                            <Label for="catatan_verifikasi">Catatan Verifikasi</Label>
                            <Textarea
                                id="catatan_verifikasi"
                                v-model="catatanVerifikasi"
                                placeholder="Masukkan catatan verifikasi (opsional)"
                                :rows="4"
                                class="mt-2"
                            />
                        </div>

                        <!-- Tanda Tangan (jika disetujui) -->
                        <div v-if="status === 'disetujui'">
                            <Label>Tipe Tanda Tangan <span class="text-red-500">*</span></Label>
                            <RadioGroup :model-value="tandaTanganType ?? undefined" @update:model-value="(val: string) => tandaTanganType = val as 'digital' | 'foto' | null" class="mt-2">
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem value="digital" id="digital" />
                                    <Label for="digital" class="cursor-pointer">Digital (Canvas)</Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem value="foto" id="foto" />
                                    <Label for="foto" class="cursor-pointer">Foto</Label>
                                </div>
                            </RadioGroup>

                            <!-- Use Existing TTD -->
                            <div v-if="tandaTanganType && ((tandaTanganType === 'digital' && admin_ttd_digital) || (tandaTanganType === 'foto'))" class="mt-4">
                                <Label>Gunakan TTD yang sudah ada?</Label>
                                <RadioGroup v-model="useExistingTtd" class="mt-2">
                                    <div class="flex items-center space-x-2">
                                        <RadioGroupItem value="yes" id="use-existing-yes" />
                                        <Label for="use-existing-yes" class="cursor-pointer">Ya, gunakan yang sudah ada</Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <RadioGroupItem value="no" id="use-existing-no" />
                                        <Label for="use-existing-no" class="cursor-pointer">Buat baru</Label>
                                    </div>
                                </RadioGroup>
                            </div>

                            <!-- Canvas TTD Digital -->
                            <div v-if="tandaTanganType === 'digital' && useExistingTtd === 'no'" class="mt-4">
                                <Label>Tanda Tangan Digital <span class="text-red-500">*</span></Label>
                                <div class="mt-2 space-y-2">
                                    <div class="border rounded overflow-hidden bg-white" style="width: 60%; height: 400px;">
                                        <canvas
                                            ref="canvasRef"
                                            class="w-full h-full cursor-crosshair"
                                            style="touch-action: none; display: block;"
                                        ></canvas>
                                    </div>
                                    <Button type="button" variant="outline" size="sm" @click="clearCanvas">
                                        Hapus
                                    </Button>
                                </div>
                            </div>

                            <!-- Preview Existing Digital TTD -->
                            <div v-if="tandaTanganType === 'digital' && useExistingTtd === 'yes' && admin_ttd_digital?.tanda_tangan_digital" class="mt-4">
                                <Label>TTD yang akan digunakan:</Label>
                                <img :src="admin_ttd_digital.tanda_tangan_digital" alt="Existing TTD" class="mt-2 max-w-xs border rounded" />
                            </div>

                            <!-- Upload Foto TTD -->
                            <div v-if="tandaTanganType === 'foto' && useExistingTtd === 'no'" class="mt-4">
                                <Label>Upload Foto Tanda Tangan <span class="text-red-500">*</span></Label>
                                <input
                                    type="file"
                                    accept="image/*"
                                    @change="handleFileChange"
                                    class="mt-2"
                                />
                                <div v-if="fotoPreview" class="mt-2">
                                    <img :src="fotoPreview" alt="Preview" class="max-w-xs border rounded" />
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-2">
                            <Button type="button" variant="outline" @click="router.visit(`/pengajuan-proposal/${item.id}`)">
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

