<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { computed } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    listPrograms?: { value: number; label: string }[];
    listItems?: { value: number; label: string; satuan?: string }[];
    programIdFromUrl?: number | null;
}>();

// Form inputs configuration - hide program select jika program_id sudah ada dari URL atau dari initialData
const formInputs = computed(() => {
    const inputs = [];
    
    // Hanya tampilkan field program jika tidak ada program_id dari URL atau dari initialData (untuk edit)
    const hasProgramId = props.programIdFromUrl || (props.mode === 'edit' && props.initialData?.assistance_program_id);
    
    if (!hasProgramId) {
        inputs.push({
            name: 'assistance_program_id',
            label: 'Program Bantuan',
            type: 'select' as const,
            placeholder: 'Pilih Program Bantuan',
            required: true,
            options: props.listPrograms || [],
        });
    }
    
    inputs.push(
        {
            name: 'assistance_item_id',
            label: 'Item Bantuan',
            type: 'select' as const,
            placeholder: 'Pilih Item Bantuan',
            required: true,
            options: props.listItems || [],
        },
        {
            name: 'jumlah',
            label: 'Jumlah',
            type: 'number' as const,
            placeholder: 'Contoh: 300000, 10',
            required: true,
            help: 'Masukkan jumlah/nominal bantuan',
        }
    );
    
    return inputs;
});

// Handle save form
const handleSave = (data: Record<string, any>) => {
    const formDataToSave: Record<string, any> = {
        ...data,
    };
    
    // Jika ada program_id dari URL atau dari initialData (edit mode), pastikan ter-set
    const programId = props.programIdFromUrl || (props.mode === 'edit' && props.initialData?.assistance_program_id);
    if (programId) {
        formDataToSave.assistance_program_id = programId;
    }

    if (props.mode === 'edit' && props.initialData?.id) {
        formDataToSave.id = props.initialData.id;
    }

    // Redirect URL dengan program_id jika ada (dari URL, initialData, atau dari data)
    let redirectUrl = '/program-bantuan/item-program';
    const finalProgramId = props.programIdFromUrl || (props.mode === 'edit' && props.initialData?.assistance_program_id) || formDataToSave.assistance_program_id;
    if (finalProgramId) {
        redirectUrl += `?program_id=${finalProgramId}`;
    }

    save(formDataToSave, {
        url: '/program-bantuan/item-program',
        mode: props.mode,
        id: props.initialData?.id,
        redirectUrl: redirectUrl,
        successMessage: props.mode === 'create' ? 'Data berhasil ditambahkan' : 'Data berhasil diperbarui',
        errorMessage: 'Gagal menyimpan data',
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="initialData" @save="handleSave" />
</template>

