<script setup lang="ts">
import { ref, computed } from 'vue';
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import BoundaryMapPicker from '@/components/BoundaryMapPicker.vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    listRw?: { value: number; label: string }[];
}>();

const rwOptions = computed(() => props.listRw || []);
const boundary = ref<number[][]>(props.initialData?.boundary || []);

const formInputs = computed(() => [
    {
        name: 'rw_id',
        label: 'RW',
        type: 'select' as const,
        placeholder: 'Pilih RW',
        required: true,
        options: rwOptions.value,
    },
    {
        name: 'nomor_rt',
        label: 'Nomor RT',
        type: 'text' as const,
        placeholder: '01',
        required: true,
        help: 'Masukkan nomor RT, contoh: 01',
    },
    {
        name: 'keterangan',
        label: 'Keterangan',
        type: 'textarea' as const,
        placeholder: 'Keterangan (opsional)',
        required: false,
    },
]);

const handleSave = (data: Record<string, any>) => {
    const formData: Record<string, any> = {
        ...data,
        rw_id: Number(data.rw_id),
        boundary: boundary.value.length > 0 ? boundary.value : null,
    };

    if (props.mode === 'edit' && props.initialData?.id) {
        formData.id = props.initialData.id;
    }

    save(formData, {
        url: '/data-warga/rts',
        mode: props.mode,
        id: props.initialData?.id,
        redirectUrl: '/data-warga/rts',
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

