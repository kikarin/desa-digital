<script setup lang="ts">
import { ref } from 'vue';
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import BoundaryMapPicker from '@/components/BoundaryMapPicker.vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const boundary = ref<number[][]>(props.initialData?.boundary || []);

// Form inputs configuration
const formInputs = [
    {
        name: 'nomor_rw',
        label: 'Nomor RW',
        type: 'text' as const,
        placeholder: '01',
        required: true,
        help: 'Masukkan nomor RW, contoh: 01',
    },
    {
        name: 'desa',
        label: 'Desa',
        type: 'text' as const,
        placeholder: 'Contoh: Galuga',
        required: true,
    },
    {
        name: 'kecamatan',
        label: 'Kecamatan',
        type: 'text' as const,
        placeholder: 'Contoh: Cibungbulang',
        required: true,
    },
    {
        name: 'kabupaten',
        label: 'Kabupaten',
        type: 'text' as const,
        placeholder: 'Contoh: Bogor',
        required: true,
    },
];

// Handle save form
const handleSave = (data: Record<string, any>) => {
    const formData: Record<string, any> = {
        ...data,
        boundary: boundary.value.length > 0 ? boundary.value : null,
    };

    if (props.mode === 'edit' && props.initialData?.id) {
        formData.id = props.initialData.id;
    }

    save(formData, {
        url: '/data-warga/rws',
        mode: props.mode,
        id: props.initialData?.id,
        redirectUrl: '/data-warga/rws',
        successMessage: props.mode === 'create' ? 'Data berhasil ditambahkan' : 'Data berhasil diperbarui',
        errorMessage: 'Gagal menyimpan data',
    });
};
</script>

<template>
    <div class="space-y-6">
        <FormInput :form-inputs="formInputs" :initial-data="initialData" @save="handleSave" />
        <BoundaryMapPicker v-model="boundary" />
    </div>
</template>

