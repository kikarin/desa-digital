<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card/index';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/components/ui/toast/useToast';
import { router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const { save } = useHandleFormSave();
const { toast } = useToast();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    jenis_surat_options?: Record<number, string>;
}>();

const formData = ref({
    jenis_surat_id: props.initialData?.jenis_surat_id || '',
    nama_atribut: props.initialData?.nama_atribut || '',
    tipe_data: props.initialData?.tipe_data || 'text',
    opsi_pilihan: props.initialData?.opsi_pilihan || '',
    is_required: props.initialData?.is_required || false,
    nama_lampiran: props.initialData?.nama_lampiran || '',
    minimal_file: props.initialData?.minimal_file || 0,
    is_required_lampiran: props.initialData?.is_required_lampiran || false,
    urutan: props.initialData?.urutan || 0,
});

const tipeDataOptions = [
    { value: 'text', label: 'Text' },
    { value: 'number', label: 'Number' },
    { value: 'date', label: 'Date' },
    { value: 'select', label: 'Select (Dropdown)' },
    { value: 'boolean', label: 'Boolean (Ya/Tidak)' },
];

const showOpsiPilihan = computed(() => formData.value.tipe_data === 'select');
const showLampiran = computed(() => !!formData.value.nama_lampiran);

const handleSave = () => {
    const data: Record<string, any> = {
        ...formData.value,
    };

    if (props.mode === 'edit' && props.initialData?.id) {
        data.id = props.initialData.id;
    }

    // Convert opsi_pilihan to string (comma-separated or JSON)
    if (data.tipe_data === 'select' && data.opsi_pilihan) {
        // If it's already an array, convert to comma-separated
        if (Array.isArray(data.opsi_pilihan)) {
            data.opsi_pilihan = data.opsi_pilihan.join(',');
        }
    } else if (data.tipe_data !== 'select') {
        data.opsi_pilihan = null;
    }

    // Clear lampiran fields if nama_lampiran is empty
    if (!data.nama_lampiran) {
        data.minimal_file = 0;
        data.is_required_lampiran = false;
    }

    save(data, {
        url: '/layanan-surat/atribut-jenis-surat',
        mode: props.mode,
        id: props.initialData?.id,
        redirectUrl: '/layanan-surat/atribut-jenis-surat',
        successMessage: props.mode === 'create' ? 'Atribut berhasil ditambahkan' : 'Atribut berhasil diperbarui',
        errorMessage: 'Gagal menyimpan atribut',
    });
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ mode === 'create' ? 'Tambah' : 'Edit' }} Atribut Jenis Surat</CardTitle>
        </CardHeader>
        <CardContent>
            <form @submit.prevent="handleSave" class="space-y-6">
                <!-- Jenis Surat -->
                <div>
                    <Label for="jenis_surat_id">Jenis Surat <span class="text-red-500">*</span></Label>
                    <Select v-model="formData.jenis_surat_id" required>
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

                <!-- Nama Atribut -->
                <div>
                    <Label for="nama_atribut">Nama Atribut <span class="text-red-500">*</span></Label>
                    <Input
                        id="nama_atribut"
                        v-model="formData.nama_atribut"
                        placeholder="Contoh: Nama Lengkap"
                        required
                    />
                </div>

                <!-- Tipe Data -->
                <div>
                    <Label for="tipe_data">Tipe Data <span class="text-red-500">*</span></Label>
                    <Select v-model="formData.tipe_data" required>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in tipeDataOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Opsi Pilihan (jika tipe_data = select) -->
                <div v-if="showOpsiPilihan">
                    <Label for="opsi_pilihan">Opsi Pilihan <span class="text-red-500">*</span></Label>
                    <Textarea
                        id="opsi_pilihan"
                        v-model="formData.opsi_pilihan"
                        placeholder="Masukkan opsi dipisahkan koma, contoh: Opsi 1, Opsi 2, Opsi 3"
                        required
                        rows="3"
                    />
                    <p class="text-sm text-muted-foreground mt-1">
                        Pisahkan setiap opsi dengan koma (,)
                    </p>
                </div>

                <!-- Wajib -->
                <div class="flex items-center space-x-2">
                    <Checkbox id="is_required" v-model:checked="formData.is_required" />
                    <Label for="is_required" class="cursor-pointer">Atribut wajib diisi</Label>
                </div>

                <!-- Urutan -->
                <div>
                    <Label for="urutan">Urutan</Label>
                    <Input
                        id="urutan"
                        v-model.number="formData.urutan"
                        type="number"
                        min="0"
                        placeholder="0"
                    />
                    <p class="text-sm text-muted-foreground mt-1">
                        Urutan tampilan atribut (0 = pertama)
                    </p>
                </div>

                <!-- Lampiran Section -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold mb-4">Aturan Lampiran (Opsional)</h3>

                    <div class="space-y-4">
                        <!-- Nama Lampiran -->
                        <div>
                            <Label for="nama_lampiran">Nama Lampiran</Label>
                            <Input
                                id="nama_lampiran"
                                v-model="formData.nama_lampiran"
                                placeholder="Contoh: KTP, KK, Surat Keterangan"
                            />
                            <p class="text-sm text-muted-foreground mt-1">
                                Kosongkan jika atribut ini tidak memerlukan lampiran
                            </p>
                        </div>

                        <!-- Minimal File (jika ada nama_lampiran) -->
                        <div v-if="showLampiran">
                            <Label for="minimal_file">Minimal File <span class="text-red-500">*</span></Label>
                            <Input
                                id="minimal_file"
                                v-model.number="formData.minimal_file"
                                type="number"
                                min="1"
                                required
                                placeholder="1"
                            />
                        </div>

                        <!-- Wajib Lampiran (jika ada nama_lampiran) -->
                        <div v-if="showLampiran" class="flex items-center space-x-2">
                            <Checkbox id="is_required_lampiran" v-model:checked="formData.is_required_lampiran" />
                            <Label for="is_required_lampiran" class="cursor-pointer">Lampiran wajib diupload</Label>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end space-x-2">
                    <Button type="button" variant="outline" @click="router.visit('/layanan-surat/atribut-jenis-surat')">
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

