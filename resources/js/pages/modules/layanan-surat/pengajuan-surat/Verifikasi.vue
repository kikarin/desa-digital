<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card/index';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import axios from 'axios';
import { ref, onMounted, onBeforeUnmount, computed, nextTick, watch } from 'vue';
import type { BreadcrumbItem } from '@/types';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        jenis_surat_id: number;
        jenis_surat_nama: string;
        resident_nama: string;
        resident_nik: string;
        tanggal_surat: string;
        status: string;
    };
    atribut_detail?: Array<{
        id: number;
        atribut_nama: string;
        atribut_tipe: string;
        nilai: string;
        lampiran_files: string[];
    }>;
    admin_ttd_digital?: {
        id: number;
        tanda_tangan_digital: string;
    } | null;
    admin_ttd_foto?: {
        id: number;
        foto_tanda_tangan: string;
    } | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Layanan Surat', href: '#' },
    { title: 'Pengajuan Surat', href: '/layanan-surat/pengajuan-surat' },
    { title: 'Verifikasi', href: '#' },
];

const status = ref<'disetujui' | 'ditolak' | null>(null);
const tandaTanganType = ref<'digital' | 'foto' | null>(null);
const useExistingTtd = ref<'yes' | 'no'>('no');
const alasanPenolakan = ref('');
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
        // Set canvas size explicitly
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
        
        // Add event listeners directly to canvas
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

// Watch for when canvas should be visible and initialize it
watch([() => tandaTanganType.value, () => useExistingTtd.value], ([type, useExisting]) => {
    if (type === 'digital' && useExisting === 'no') {
        // Wait for DOM to update (v-if condition to render canvas)
        nextTick(() => {
            // Try multiple times with increasing delays
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
    // Also try to initialize in case canvas is already visible
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
    // Cleanup event listeners
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
    
    // Calculate position relative to canvas
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
        return alasanPenolakan.value.length >= 10;
    }
    if (status.value === 'disetujui') {
        if (!tandaTanganType.value) return false;
        if (tandaTanganType.value === 'digital') {
            if (useExistingTtd.value === 'yes') return true;
            return getCanvasData() !== null;
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

        if (status.value === 'disetujui') {
            formData.append('tanda_tangan_type', tandaTanganType.value as string);
            formData.append('use_existing_ttd', useExistingTtd.value);

            if (tandaTanganType.value === 'digital') {
                if (useExistingTtd.value === 'yes') {
                    // Use existing TTD - backend will handle
                    if (props.admin_ttd_digital?.tanda_tangan_digital) {
                        formData.append('tanda_tangan_digital', props.admin_ttd_digital.tanda_tangan_digital);
                    }
                } else {
                    // Use new canvas TTD
                    const canvasData = getCanvasData();
                    if (canvasData) {
                        // Send as base64 string directly
                        formData.append('tanda_tangan_digital', canvasData);
                    }
                }
            } else {
                if (useExistingTtd.value === 'yes') {
                    // Use existing foto TTD - backend will handle
                    if (props.admin_ttd_foto?.foto_tanda_tangan) {
                        formData.append('use_existing_foto_ttd', '1');
                    }
                } else {
                    // Upload new foto TTD
                    if (fotoTandaTangan.value) {
                        formData.append('foto_tanda_tangan', fotoTandaTangan.value);
                    }
                }
            }
        } else {
            formData.append('alasan_penolakan', alasanPenolakan.value);
        }

        const response = await axios.post(`/layanan-surat/pengajuan-surat/${props.item.id}/verifikasi`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        toast({
            title: 'Pengajuan surat berhasil diverifikasi',
            variant: 'success',
        });

        router.visit(`/layanan-surat/pengajuan-surat/${props.item.id}`);
    } catch (error: any) {
        toast({
            title: error.response?.data?.message || 'Gagal memverifikasi pengajuan surat',
            variant: 'destructive',
        });
    }
};
</script>

<template>
    <Head title="Verifikasi Pengajuan Surat" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <!-- Info Pengajuan -->
            <Card>
                <CardHeader>
                    <CardTitle>Informasi Pengajuan</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div><strong>Jenis Surat:</strong> {{ item.jenis_surat_nama }}</div>
                        <div><strong>Warga:</strong> {{ item.resident_nama }} ({{ item.resident_nik }})</div>
                        <div><strong>Tanggal Surat:</strong> {{ new Date(item.tanggal_surat).toLocaleDateString('id-ID') }}</div>
                    </div>
                </CardContent>
            </Card>

            <!-- Atribut Detail -->
            <Card v-if="atribut_detail && atribut_detail.length > 0">
                <CardHeader>
                    <CardTitle>Data Atribut</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <template v-for="atribut in atribut_detail" :key="atribut.id">
                            <!-- Nilai Atribut -->
                            <div v-if="atribut.nilai && atribut.nilai !== '-'" class="flex items-start gap-3 py-2">
                                <span class="font-medium text-muted-foreground min-w-[140px]">{{ atribut.atribut_nama }}:</span>
                                <span class="flex-1">
                                    <template v-if="atribut.atribut_tipe === 'date'">
                                        {{ new Date(atribut.nilai).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                                    </template>
                                    <template v-else-if="atribut.atribut_tipe === 'boolean'">
                                        {{ atribut.nilai === '1' || atribut.nilai === 'true' ? 'Ya' : 'Tidak' }}
                                    </template>
                                    <template v-else>
                                        {{ atribut.nilai }}
                                    </template>
                                </span>
                            </div>
                            <!-- Lampiran -->
                            <div v-if="atribut.lampiran_files && atribut.lampiran_files.length > 0" class="flex items-start gap-3 py-2">
                                <span class="font-medium text-muted-foreground min-w-[140px]">{{ atribut.atribut_nama }}:</span>
                                <div class="flex-1 space-y-1">
                                    <a
                                        v-for="(file, index) in atribut.lampiran_files"
                                        :key="index"
                                        :href="`/storage/${file}`"
                                        target="_blank"
                                        class="block text-primary hover:underline text-sm"
                                    >
                                        {{ file.split('/').pop() }}
                                    </a>
                                </div>
                            </div>
                        </template>
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
                            <div v-if="tandaTanganType && ((tandaTanganType === 'digital' && admin_ttd_digital) || (tandaTanganType === 'foto' && admin_ttd_foto))" class="mt-4">
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

                            <!-- Preview Existing Foto TTD -->
                            <div v-if="tandaTanganType === 'foto' && useExistingTtd === 'yes' && admin_ttd_foto?.foto_tanda_tangan" class="mt-4">
                                <Label>TTD yang akan digunakan:</Label>
                                <img :src="`/storage/${admin_ttd_foto.foto_tanda_tangan}`" alt="Existing TTD" class="mt-2 max-w-xs border rounded" />
                            </div>
                        </div>

                        <!-- Alasan Penolakan (jika ditolak) -->
                        <div v-if="status === 'ditolak'">
                            <Label for="alasan_penolakan">Alasan Penolakan <span class="text-red-500">*</span></Label>
                            <Textarea
                                id="alasan_penolakan"
                                v-model="alasanPenolakan"
                                placeholder="Masukkan alasan penolakan (minimal 10 karakter)"
                                :rows="4"
                                required
                                class="mt-2"
                            />
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-2">
                            <Button type="button" variant="outline" @click="router.visit(`/layanan-surat/pengajuan-surat/${item.id}`)">
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

