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
        name: 'nama_item',
        label: 'Nama Item',
        type: 'text' as const,
        placeholder: 'Contoh: Uang Tunai, Beras, Minyak',
        required: true,
        help: 'Masukkan nama item bantuan',
    },
    {
        name: 'tipe',
        label: 'Tipe',
        type: 'radio' as const,
        required: true,
        options: [
            { value: 'UANG', label: 'Uang' },
            { value: 'BARANG', label: 'Barang' },
        ],
    },
    {
        name: 'satuan',
        label: 'Satuan',
        type: 'text' as const,
        placeholder: 'Contoh: Rupiah, Kg, Liter',
        required: true,
        help: 'Masukkan satuan item',
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
        url: '/data-master/assistance-items',
        mode: props.mode,
        id: props.initialData?.id,
        redirectUrl: '/data-master/assistance-items',
        successMessage: props.mode === 'create' ? 'Data berhasil ditambahkan' : 'Data berhasil diperbarui',
        errorMessage: 'Gagal menyimpan data',
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="initialData" @save="handleSave" />
</template>

