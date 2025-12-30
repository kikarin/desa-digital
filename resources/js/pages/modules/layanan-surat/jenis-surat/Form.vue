<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { useToast } from '@/components/ui/toast/useToast';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Plus, Trash2 } from 'lucide-vue-next';

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const { toast } = useToast();

const formData = ref({
    nama: props.initialData?.nama || '',
    kode: props.initialData?.kode || '',
    atribut: [] as Array<{
        id?: number;
        nama_atribut: string;
        tipe_data: string;
        opsi_pilihan: string;
        opsi_pilihan_array: string[];
        is_required: boolean;
        nama_lampiran: string;
        minimal_file: number;
        is_required_lampiran: boolean;
        urutan: number;
    }>,
});

// Load existing atribut if editing
if (props.mode === 'edit' && props.initialData?.atribut) {
    formData.value.atribut = props.initialData.atribut.map((atr: any, index: number) => ({
        id: atr.id,
        nama_atribut: atr.nama_atribut || '',
        tipe_data: atr.tipe_data || 'text',
        opsi_pilihan: atr.opsi_pilihan || '',
        opsi_pilihan_array: atr.opsi_pilihan_array || [],
        is_required: atr.is_required || false,
        nama_lampiran: atr.nama_lampiran || '',
        minimal_file: atr.minimal_file || 0,
        is_required_lampiran: atr.is_required_lampiran || false,
        urutan: index + 1,
    }));
}

const tipeDataOptions = [
    { value: 'text', label: 'Text' },
    { value: 'number', label: 'Number' },
    { value: 'date', label: 'Date' },
    { value: 'select', label: 'Select' },
    { value: 'boolean', label: 'Boolean' },
];

const addAtribut = () => {
    formData.value.atribut.push({
        nama_atribut: '',
        tipe_data: 'text',
        opsi_pilihan: '',
        opsi_pilihan_array: [],
        is_required: false,
        nama_lampiran: '',
        minimal_file: 0,
        is_required_lampiran: false,
        urutan: formData.value.atribut.length + 1,
    });
};

const removeAtribut = (index: number) => {
    formData.value.atribut.splice(index, 1);
    // Update urutan
    formData.value.atribut.forEach((atr, idx) => {
        atr.urutan = idx + 1;
    });
};

const updateOpsiPilihan = (index: number, value: string) => {
    const atribut = formData.value.atribut[index];
    atribut.opsi_pilihan = value;
    atribut.opsi_pilihan_array = value.split(',').map((v) => v.trim()).filter((v) => v);
};

const handleSubmit = () => {
    // Validate
    if (!formData.value.nama || !formData.value.kode) {
        toast({
            title: 'Nama dan Kode harus diisi',
            variant: 'destructive',
        });
        return;
    }

    // Validate atribut
    for (let i = 0; i < formData.value.atribut.length; i++) {
        const atr = formData.value.atribut[i];
        if (!atr.nama_atribut) {
            toast({
                title: `Nama atribut ke-${i + 1} harus diisi`,
                variant: 'destructive',
            });
            return;
        }
        if (atr.tipe_data === 'select' && !atr.opsi_pilihan) {
            toast({
                title: `Opsi pilihan untuk atribut "${atr.nama_atribut}" harus diisi`,
                variant: 'destructive',
            });
            return;
        }
    }

    const submitData: any = {
        nama: formData.value.nama,
        kode: formData.value.kode,
        atribut: formData.value.atribut.map((atr) => ({
            id: atr.id,
            nama_atribut: atr.nama_atribut,
            tipe_data: atr.tipe_data,
            opsi_pilihan: atr.tipe_data === 'select' ? atr.opsi_pilihan : null,
            is_required: atr.is_required,
            nama_lampiran: atr.nama_lampiran || null,
            minimal_file: atr.nama_lampiran ? atr.minimal_file : 0,
            is_required_lampiran: atr.nama_lampiran ? atr.is_required_lampiran : false,
            urutan: atr.urutan,
        })),
    };

    if (props.mode === 'edit' && props.initialData?.id) {
        submitData.id = props.initialData.id;
        router.put(`/layanan-surat/jenis-surat/${props.initialData.id}`, submitData, {
            onSuccess: () => {
                toast({
                    title: 'Jenis surat berhasil diperbarui',
                    variant: 'success',
                });
            },
            onError: (errors) => {
                console.error('Error:', errors);
                toast({
                    title: 'Gagal memperbarui jenis surat',
                    variant: 'destructive',
                });
            },
        });
    } else {
        router.post('/layanan-surat/jenis-surat', submitData, {
            onSuccess: () => {
                toast({
                    title: 'Jenis surat berhasil ditambahkan',
                    variant: 'success',
                });
            },
            onError: (errors) => {
                console.error('Error:', errors);
                toast({
                    title: 'Gagal menambahkan jenis surat',
                    variant: 'destructive',
                });
            },
        });
    }
};
</script>

<template>
    <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Basic Info -->
        <Card>
            <CardHeader>
                <CardTitle>Informasi Dasar</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div>
                    <Label for="nama">Nama Jenis Surat <span class="text-red-500">*</span></Label>
                    <Input
                        id="nama"
                        v-model="formData.nama"
                        placeholder="Contoh: Surat Keterangan Domisili"
                        required
                    />
                    <p class="text-sm text-muted-foreground mt-1">Masukkan nama jenis surat</p>
                </div>

                <div>
                    <Label for="kode">Kode Jenis Surat <span class="text-red-500">*</span></Label>
                    <Input
                        id="kode"
                        v-model="formData.kode"
                        placeholder="Contoh: SKD"
                        required
                    />
                    <p class="text-sm text-muted-foreground mt-1">Kode untuk nomor surat, contoh: SKD, SKTM, SKCK</p>
                </div>
            </CardContent>
        </Card>

        <!-- Atribut Section -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle>Atribut</CardTitle>
                <Button type="button" @click="addAtribut" variant="outline" size="sm">
                    <Plus class="h-4 w-4 mr-2" />
                    Tambah Atribut
                </Button>
            </CardHeader>
            <CardContent class="space-y-4">
                <div
                    v-for="(atribut, index) in formData.atribut"
                    :key="index"
                    class="p-4 border rounded-lg space-y-4 bg-white"
                >
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-medium">Atribut {{ index + 1 }}</h4>
                        <Button
                            type="button"
                            @click="removeAtribut(index)"
                            variant="destructive"
                            size="sm"
                        >
                            <Trash2 class="h-4 w-4 mr-2" />
                            Hapus
                        </Button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label>Nama Atribut <span class="text-red-500">*</span></Label>
                            <Input
                                v-model="atribut.nama_atribut"
                                placeholder="Contoh: Nama, NIK"
                                required
                            />
                        </div>

                        <div>
                            <Label>Tipe Data <span class="text-red-500">*</span></Label>
                            <Select v-model="atribut.tipe_data" required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih tipe data" />
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
                    </div>

                    <div v-if="atribut.tipe_data === 'select'" class="mt-2">
                        <Label>Opsi Pilihan <span class="text-red-500">*</span></Label>
                        <Input
                            v-model="atribut.opsi_pilihan"
                            placeholder="Pisahkan dengan koma, contoh: Laki-laki, Perempuan"
                            @input="updateOpsiPilihan(index, ($event.target as HTMLInputElement).value)"
                            required
                        />
                        <p class="text-sm text-muted-foreground mt-1">
                            Pisahkan setiap opsi dengan koma
                        </p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <Checkbox
                            :id="`required-${index}`"
                            v-model:checked="atribut.is_required"
                        />
                        <Label :for="`required-${index}`" class="cursor-pointer">Wajib</Label>
                    </div>

                    <!-- Lampiran Section for this atribut -->
                    <div class="mt-4 pt-4 border-t">
                        <h5 class="font-medium mb-3">Lampiran (Opsional)</h5>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <Label>Nama Lampiran</Label>
                                <Input
                                    v-model="atribut.nama_lampiran"
                                    placeholder="Contoh: Upload Foto"
                                />
                            </div>

                            <div>
                                <Label>Minimal File</Label>
                                <Input
                                    v-model.number="atribut.minimal_file"
                                    type="number"
                                    min="0"
                                    placeholder="0"
                                />
                            </div>

                            <div class="flex items-center space-x-2 pt-6">
                                <Checkbox
                                    :id="`required-lampiran-${index}`"
                                    v-model:checked="atribut.is_required_lampiran"
                                />
                                <Label :for="`required-lampiran-${index}`" class="cursor-pointer">Wajib</Label>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="formData.atribut.length === 0" class="text-center py-8 text-muted-foreground">
                    Belum ada atribut. Klik "Tambah Atribut" untuk menambahkan.
                </div>
            </CardContent>
        </Card>

        <!-- Submit Button -->
        <div class="flex justify-end gap-2">
            <Button
                type="button"
                variant="outline"
                @click="router.visit('/layanan-surat/jenis-surat')"
            >
                Batal
            </Button>
            <Button type="submit">
                {{ mode === 'create' ? 'Simpan' : 'Update' }}
            </Button>
        </div>
    </form>
</template>
