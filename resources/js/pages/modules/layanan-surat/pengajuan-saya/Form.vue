<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card/index';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { useToast } from '@/components/ui/toast/useToast';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, computed, watch } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    item?: Record<string, any>;
    jenis_surat_options?: Record<number, string>;
    resident_options?: Record<number, string>;
    atribut?: Record<number, { nilai: string; lampiran_files: string[]; existing_files?: string[] }>;
}>();

// Helper untuk format tanggal
const formatDateForInput = (dateStr: string | null | undefined): string => {
    if (!dateStr) return new Date().toISOString().split('T')[0];
    try {
        const date = new Date(dateStr);
        return date.toISOString().split('T')[0];
    } catch {
        return new Date().toISOString().split('T')[0];
    }
};

// Initialize form data
const getInitialFormData = () => {
    const data = props.initialData || props.item;
    return {
        jenis_surat_id: data?.jenis_surat_id ? String(data.jenis_surat_id) : '',
        resident_id: data?.resident_id ? String(data.resident_id) : '',
        tanggal_surat: formatDateForInput(data?.tanggal_surat),
        atribut: {} as Record<number, { nilai: string; lampiran_files: File[]; existing_files?: string[] }>,
    };
};

const formData = ref(getInitialFormData());

const atributList = ref<Array<{
    id: number;
    nama_atribut: string;
    tipe_data: string;
    opsi_pilihan_array: string[];
    is_required: boolean;
    nama_lampiran: string;
    minimal_file: number;
    is_required_lampiran: boolean;
}>>([]);

const loadingAtribut = ref(false);

// Load atribut ketika jenis surat dipilih
const loadAtribut = async (jenisSuratId: string | number) => {
    if (!jenisSuratId) {
        atributList.value = [];
        formData.value.atribut = {};
        return;
    }

    loadingAtribut.value = true;
    try {
        const response = await axios.get(`/api/jenis-surat/${jenisSuratId}`);
        if (response.data && response.data.atribut) {
            atributList.value = response.data.atribut.map((atr: any) => ({
                ...atr,
                opsi_pilihan_array: atr.opsi_pilihan_array || [],
            }));

            // Initialize form data untuk atribut
            atributList.value.forEach((atr) => {
                ensureAtribut(atr.id);
                
                // Prefill data dari props.atribut jika ada (untuk edit mode)
                if (props.atribut?.[atr.id]) {
                    formData.value.atribut[atr.id].nilai = props.atribut[atr.id].nilai || '';
                    // Store existing files for display (not as File objects)
                    if (props.atribut[atr.id].existing_files) {
                        formData.value.atribut[atr.id].existing_files = props.atribut[atr.id].existing_files;
                    } else if (props.atribut[atr.id].lampiran_files) {
                        const files = props.atribut[atr.id].lampiran_files;
                        formData.value.atribut[atr.id].existing_files = Array.isArray(files) ? files : [];
                    }
                }
            });
        }
    } catch (error: any) {
        console.error('Gagal load atribut:', error);
        toast({
            title: 'Gagal memuat atribut jenis surat',
            variant: 'destructive',
        });
    } finally {
        loadingAtribut.value = false;
    }
};

watch(() => formData.value.jenis_surat_id, (newJenisSuratId) => {
    loadAtribut(newJenisSuratId);
}, { immediate: true });

// Load atribut immediately if jenis_surat_id already set (for edit mode)
if (formData.value.jenis_surat_id) {
    loadAtribut(formData.value.jenis_surat_id);
}

const handleFileChange = (atributId: number, files: FileList | null) => {
    if (!files) return;
    if (!formData.value.atribut[atributId]) {
        formData.value.atribut[atributId] = { nilai: '', lampiran_files: [] };
    }
    formData.value.atribut[atributId].lampiran_files = Array.from(files);
};

// Helper untuk memastikan atribut ada di formData
const ensureAtribut = (atributId: number) => {
    if (!formData.value.atribut[atributId]) {
        formData.value.atribut[atributId] = { nilai: '', lampiran_files: [] };
    }
};

const handleSubmit = async () => {
    try {
        const submitData = new FormData();
        submitData.append('jenis_surat_id', formData.value.jenis_surat_id);
        submitData.append('resident_id', formData.value.resident_id);
        submitData.append('tanggal_surat', formData.value.tanggal_surat);

        // Prepare atribut data - ensure ALL atribut are included
        const atributData: Record<number, { nilai: string; lampiran_files: File[] }> = {};
        const atributNilaiOnly: Record<number, string> = {}; // For JSON backup
        
        // Always include all atribut from atributList, even if empty
        atributList.value.forEach((atr) => {
            const atributValue = formData.value.atribut[atr.id];
            const nilai = atributValue?.nilai || '';
            
            atributData[atr.id] = {
                nilai: nilai,
                lampiran_files: atributValue?.lampiran_files || [],
            };
            
            // Store nilai only for JSON backup (files can't be in JSON)
            atributNilaiOnly[atr.id] = nilai;
        });

        // Append atribut data - Use both FormData format and JSON string for nilai
        Object.keys(atributData).forEach((atributId) => {
            const data = atributData[Number(atributId)];
            
            // Append nilai (always append, even if empty)
            const nilai = data.nilai || '';
            submitData.append(`atribut[${atributId}][nilai]`, nilai);
            
            // Append files (must use FormData, can't be in JSON)
            if (data.lampiran_files && data.lampiran_files.length > 0) {
                data.lampiran_files.forEach((file, index) => {
                    submitData.append(`atribut[${atributId}][lampiran_files][${index}]`, file);
                });
            }
        });
        
        // Also send nilai as JSON string as backup (files excluded from JSON)
        submitData.append('atribut_nilai_json', JSON.stringify(atributNilaiOnly));

        if (props.mode === 'edit' && props.initialData?.id) {
            submitData.append('id', String(props.initialData.id));
            submitData.append('_method', 'PUT'); // Laravel method spoofing
            
            // Use axios directly for PUT with FormData (Inertia might not handle it correctly)
            try {
                const response = await axios.post(`/layanan-surat/pengajuan-saya/${props.initialData.id}`, submitData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                
                toast({
                    title: 'Pengajuan surat berhasil diupdate',
                    variant: 'success',
                });
                
                // Redirect to show page
                router.visit(`/layanan-surat/pengajuan-saya/${props.initialData.id}`);
            } catch (error: any) {
                console.error('Error:', error);
                toast({
                    title: error.response?.data?.message || 'Gagal mengupdate pengajuan surat',
                    variant: 'destructive',
                });
            }
        } else {
            router.post('/layanan-surat/pengajuan-saya', submitData, {
                forceFormData: true,
                onSuccess: () => {
                    toast({
                        title: 'Pengajuan surat berhasil dibuat',
                        variant: 'success',
                    });
                },
                onError: (errors) => {
                    console.error('Error:', errors);
                    toast({
                        title: 'Gagal membuat pengajuan surat',
                        variant: 'destructive',
                    });
                },
            });
        }
    } catch (error: any) {
        console.error('Error submitting form:', error);
        toast({
            title: 'Gagal menyimpan pengajuan surat',
            variant: 'destructive',
        });
    }
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ mode === 'create' ? 'Buat' : 'Edit' }} Pengajuan Surat</CardTitle>
        </CardHeader>
        <CardContent>
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Jenis Surat -->
                <div>
                    <Label for="jenis_surat_id">Jenis Surat <span class="text-red-500">*</span></Label>
                    <Select v-model="formData.jenis_surat_id" required :disabled="mode === 'edit'">
                        <SelectTrigger>
                            <SelectValue placeholder="Pilih Jenis Surat" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="(nama, id) in jenis_surat_options"
                                :key="id"
                                :value="String(id)"
                            >
                                {{ nama }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Resident (jika create) -->
                <div v-if="mode === 'create'">
                    <Label for="resident_id">Warga <span class="text-red-500">*</span></Label>
                    <Select v-model="formData.resident_id" required>
                        <SelectTrigger>
                            <SelectValue placeholder="Pilih Warga" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="(nama, id) in resident_options"
                                :key="id"
                                :value="String(id)"
                            >
                                {{ nama }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Tanggal Surat -->
                <div>
                    <Label for="tanggal_surat">Tanggal Surat <span class="text-red-500">*</span></Label>
                    <Input
                        id="tanggal_surat"
                        v-model="formData.tanggal_surat"
                        type="date"
                        required
                    />
                </div>

                <!-- Loading Atribut -->
                <div v-if="loadingAtribut" class="text-center py-4">
                    Memuat atribut...
                </div>

                <!-- Atribut Form -->
                <div v-else-if="atributList.length > 0" class="space-y-6">
                    <h3 class="text-lg font-semibold">Isi Data Atribut</h3>
                    <div
                        v-for="atribut in atributList"
                        :key="atribut.id"
                        class="p-4 border rounded-lg space-y-4"
                        @vue:mounted="ensureAtribut(atribut.id)"
                    >
                        <div>
                            <Label>
                                {{ atribut.nama_atribut }}
                                <span v-if="atribut.is_required" class="text-red-500">*</span>
                            </Label>
                            <div class="mt-2">
                                <!-- Text Input -->
                                <Input
                                    v-if="atribut.tipe_data === 'text'"
                                    v-model="formData.atribut[atribut.id].nilai"
                                    :required="atribut.is_required"
                                    :placeholder="`Masukkan ${atribut.nama_atribut}`"
                                />
                                
                                <!-- Number Input -->
                                <Input
                                    v-else-if="atribut.tipe_data === 'number'"
                                    v-model="formData.atribut[atribut.id].nilai"
                                    type="number"
                                    :required="atribut.is_required"
                                    :placeholder="`Masukkan ${atribut.nama_atribut}`"
                                />
                                
                                <!-- Date Input -->
                                <Input
                                    v-else-if="atribut.tipe_data === 'date'"
                                    v-model="formData.atribut[atribut.id].nilai"
                                    type="date"
                                    :required="atribut.is_required"
                                />
                                
                                <!-- Select Input -->
                                <Select
                                    v-else-if="atribut.tipe_data === 'select'"
                                    v-model="formData.atribut[atribut.id].nilai"
                                    :required="atribut.is_required"
                                >
                                    <SelectTrigger>
                                        <SelectValue :placeholder="`Pilih ${atribut.nama_atribut}`" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="opsi in atribut.opsi_pilihan_array"
                                            :key="opsi"
                                            :value="opsi"
                                        >
                                            {{ opsi }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                
                                <!-- Boolean Input -->
                                <Select
                                    v-else-if="atribut.tipe_data === 'boolean'"
                                    v-model="formData.atribut[atribut.id].nilai"
                                    :required="atribut.is_required"
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">Ya</SelectItem>
                                        <SelectItem value="0">Tidak</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <!-- Lampiran -->
                        <div v-if="atribut.nama_lampiran">
                            <Label>
                                {{ atribut.nama_lampiran }}
                                <span v-if="atribut.is_required_lampiran" class="text-red-500">*</span>
                            </Label>
                            <Input
                                type="file"
                                :multiple="atribut.minimal_file > 1"
                                :required="atribut.is_required_lampiran"
                                @change="(e: Event) => handleFileChange(atribut.id, (e.target as HTMLInputElement).files)"
                                class="mt-2"
                            />
                            <p class="text-sm text-muted-foreground mt-1">
                                Minimal {{ atribut.minimal_file }} file
                            </p>
                            <!-- Existing Files (untuk edit mode) -->
                            <div v-if="formData.atribut[atribut.id]?.existing_files?.length" class="mt-2">
                                <p class="text-sm font-medium mb-1">File yang sudah ada:</p>
                                <div class="space-y-1">
                                    <a
                                        v-for="(file, index) in formData.atribut[atribut.id].existing_files"
                                        :key="index"
                                        :href="`/storage/${file}`"
                                        target="_blank"
                                        class="block text-sm text-primary hover:underline"
                                    >
                                        File {{ index + 1 }}: {{ file.split('/').pop() }}
                                    </a>
                                </div>
                                <p class="text-xs text-muted-foreground mt-1">
                                    Upload file baru untuk mengganti file yang sudah ada
                                </p>
                            </div>
                            <!-- New Files Selected -->
                            <div v-else-if="formData.atribut[atribut.id]?.lampiran_files?.length" class="mt-2">
                                <p class="text-sm">File terpilih: {{ formData.atribut[atribut.id].lampiran_files.length }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end space-x-2">
                    <Button type="button" variant="outline" @click="router.visit('/layanan-surat/pengajuan-saya')">
                        Batal
                    </Button>
                    <Button type="submit">
                        {{ mode === 'create' ? 'Simpan' : 'Update' }}
                    </Button>
                </div>
            </form>
        </CardContent>
    </Card>
</template>

