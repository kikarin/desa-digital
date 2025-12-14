<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

// Form inputs configuration
const formInputs = [
    {
        name: 'nama_program',
        label: 'Nama Program',
        type: 'text' as const,
        placeholder: 'Contoh: BLT, BPNT',
        required: true,
        help: 'Masukkan nama program bantuan',
    },
    {
        name: 'tahun',
        label: 'Tahun',
        type: 'number' as const,
        placeholder: '2024',
        required: true,
        help: 'Masukkan tahun program',
    },
    {
        name: 'periode',
        label: 'Periode',
        type: 'text' as const,
        placeholder: 'Contoh: Triwulan 1, Bulan Januari',
        required: false,
        help: 'Periode program (opsional)',
    },
    {
        name: 'target_penerima',
        label: 'Target Penerima',
        type: 'radio' as const,
        required: true,
        options: [
            { value: 'KELUARGA', label: 'Keluarga' },
            { value: 'INDIVIDU', label: 'Individu' },
        ],
    },
    {
        name: 'status',
        label: 'Status',
        type: 'radio' as const,
        required: true,
        options: [
            { value: 'PROSES', label: 'Proses' },
            { value: 'SELESAI', label: 'Selesai' },
        ],
    },
    {
        name: 'keterangan',
        label: 'Keterangan',
        type: 'textarea' as const,
        placeholder: 'Keterangan tambahan (opsional)',
        required: false,
    },
];

// Handle save form
const handleSave = (data: Record<string, any>) => {
    const formData: Record<string, any> = {
        ...data,
    };

    if (props.mode === 'edit' && props.initialData?.id) {
        formData.id = props.initialData.id;
    }

    save(formData, {
        url: '/program-bantuan/program-bantuan',
        mode: props.mode,
        id: props.initialData?.id,
        redirectUrl: '/program-bantuan/program-bantuan',
        successMessage: props.mode === 'create' ? 'Data berhasil ditambahkan' : 'Data berhasil diperbarui',
        errorMessage: 'Gagal menyimpan data',
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="initialData" @save="handleSave" />
</template>

