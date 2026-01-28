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
        lampiran_files: Array<string | { path?: string; name?: string }>;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Layanan Surat', href: '#' },
    { title: 'Verifikasi Pengajuan Surat RT', href: '/layanan-surat/pengajuan-surat-rt' },
    { title: 'Verifikasi', href: '#' },
];

const status = ref<'diverifikasi_rt' | 'ditolak' | null>(null);
const rtCatatan = ref('');

const canSubmit = computed(() => {
    if (!status.value) return false;
    if (status.value === 'ditolak') {
        return rtCatatan.value.length >= 10; // Minimal 10 karakter untuk alasan penolakan
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
        `/layanan-surat/pengajuan-surat-rt/${props.item.id}/verifikasi`,
        formData,
        {
            onSuccess: () => {
                toast({
                    title: 'Pengajuan surat berhasil diverifikasi',
                    variant: 'success',
                });
                router.visit('/layanan-surat/pengajuan-surat-rt');
            },
            onError: (errors: any) => {
                const errorMessage = errors.message || errors.rt_catatan?.[0] || 'Gagal memverifikasi pengajuan surat';
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
    <Head title="Verifikasi Pengajuan Surat RT" />
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
                                        :href="typeof file === 'string' ? `/storage/${file}` : `/storage/${file.path || file}`"
                                        target="_blank"
                                        class="block text-primary hover:underline text-sm"
                                    >
                                        {{ typeof file === 'string' ? file.split('/').pop() : (file.name || file.split('/').pop()) }}
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
                            <RadioGroup :model-value="status ?? undefined" @update:model-value="(val: string) => status = val as 'diverifikasi_rt' | 'ditolak' | null" class="mt-2">
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem value="diverifikasi_rt" id="diverifikasi_rt" />
                                    <Label for="diverifikasi_rt" class="cursor-pointer">Setujui (Diverifikasi RT)</Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <RadioGroupItem value="ditolak" id="ditolak" />
                                    <Label for="ditolak" class="cursor-pointer">Tolak</Label>
                                </div>
                            </RadioGroup>
                        </div>

                        <!-- Catatan -->
                        <div>
                            <Label for="rt_catatan">Catatan <span v-if="status === 'ditolak'" class="text-red-500">*</span></Label>
                            <Textarea
                                id="rt_catatan"
                                v-model="rtCatatan"
                                placeholder="Masukkan catatan verifikasi (wajib jika ditolak)"
                                :rows="4"
                                class="mt-2"
                            />
                            <p class="text-xs text-muted-foreground mt-1">
                                <span v-if="status === 'ditolak'">Catatan wajib diisi minimal 10 karakter</span>
                                <span v-else>Catatan opsional</span>
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-2">
                            <Button type="button" variant="outline" @click="router.visit('/layanan-surat/pengajuan-surat-rt')">
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
