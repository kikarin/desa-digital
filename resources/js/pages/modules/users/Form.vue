<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { computed, ref, watch } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    item?: Record<string, any>; // Tambahkan item prop untuk data dari Edit.vue
    roles: Record<number, string>;
    selectedRoles?: number[];
    rw_options?: Array<{ value: number; label: string }>;
    rt_options?: Array<{ value: number; label: string }>;
}>();

const roleOptions = Object.entries(props.roles).map(([id, name]) => ({
    value: Number(id),
    label: name,
}));

// State untuk form data
const localFormData = ref({
    name: props.initialData?.name || props.item?.name || '',
    email: props.initialData?.email || props.item?.email || '',
    password: '',
    password_confirmation: '',
    no_hp: props.initialData?.no_hp || props.item?.no_hp || '',
    role_id: props.selectedRoles || [],
    rw_id: props.initialData?.rw_id ?? props.item?.rw_id ?? null,
    rt_id: props.initialData?.rt_id ?? props.item?.rt_id ?? null,
    is_active: props.initialData?.is_active ?? props.item?.is_active ?? 1,
    id: props.initialData?.id || props.item?.id || undefined,
});

// Watch props untuk update localFormData saat props berubah (untuk prefill)
watch(() => [props.initialData, props.item], ([newInitialData, newItem]) => {
    if (newInitialData?.rw_id !== undefined) {
        localFormData.value.rw_id = newInitialData.rw_id;
    } else if (newItem?.rw_id !== undefined) {
        localFormData.value.rw_id = newItem.rw_id;
    }
    
    if (newInitialData?.rt_id !== undefined) {
        localFormData.value.rt_id = newInitialData.rt_id;
    } else if (newItem?.rt_id !== undefined) {
        localFormData.value.rt_id = newItem.rt_id;
    }
}, { immediate: true, deep: true });

// Watch role_id untuk reset rw_id dan rt_id jika role dihapus
watch(() => localFormData.value.role_id, (newRoles) => {
    const hasRW = newRoles.includes(35); // RW role ID
    const hasRT = newRoles.includes(36); // RT role ID
    
    if (!hasRW) {
        localFormData.value.rw_id = null;
    }
    if (!hasRT) {
        localFormData.value.rt_id = null;
    }
}, { deep: true });

const formData = computed(() => localFormData.value);

// State untuk toggle password visibility
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

// Check apakah role RW atau RT dipilih
const hasRWRole = computed(() => {
    return localFormData.value.role_id.includes(35); // RW role ID
});

const hasRTRole = computed(() => {
    return localFormData.value.role_id.includes(36); // RT role ID
});

const formInputs = computed(() => {
    const inputs: any[] = [
        {
            name: 'name',
            label: 'Name',
            type: 'text' as const,
            placeholder: 'Enter name',
            required: true,
        },
        {
            name: 'email',
            label: 'Email',
            type: 'email' as const,
            placeholder: 'Enter email',
            required: true,
        },
        {
            name: 'password',
            label: 'Password',
            type: 'password' as const,
            placeholder: props.mode === 'create' ? 'Enter password' : 'Kosongkan jika tidak ingin mengubah password',
            required: props.mode === 'create',
            help: 'Password harus minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka',
            showPassword: showPassword,
        },
        {
            name: 'password_confirmation',
            label: 'Konfirmasi Password',
            type: 'password' as const,
            placeholder: 'Konfirmasi password',
            required: props.mode === 'create',
            help: 'Password harus sama dengan password di atas',
            showPassword: showPasswordConfirmation,
        },
        {
            name: 'no_hp',
            label: 'No. HP',
            type: 'text' as const,
            placeholder: 'Enter phone number',
            required: true,
        },
        {
            name: 'role_id',
            label: 'Role',
            type: 'multi-select' as const,
            placeholder: 'Pilih Role (bisa lebih dari 1)',
            required: true,
            options: roleOptions,
            help: 'Pilih satu atau lebih role untuk user ini',
        },
        {
            name: 'is_active',
            label: 'Status',
            type: 'select' as const,
            placeholder: 'Pilih status',
            required: true,
            options: [
                { value: 1, label: 'Active' },
                { value: 0, label: 'Inactive' },
            ],
        },
    ];

    return inputs;
});

// Search query untuk RW dan RT
const rwSearchQuery = ref('');
const rtSearchQuery = ref('');

// Filtered options untuk RW dan RT
const filteredRwOptions = computed(() => {
    const query = rwSearchQuery.value.toLowerCase();
    if (!query) return props.rw_options || [];
    return (props.rw_options || []).filter((opt: any) => 
        opt.label.toLowerCase().includes(query)
    );
});

const filteredRtOptions = computed(() => {
    const query = rtSearchQuery.value.toLowerCase();
    if (!query) return props.rt_options || [];
    return (props.rt_options || []).filter((opt: any) => 
        opt.label.toLowerCase().includes(query)
    );
});

// Handler untuk field update dari FormInput
const handleFieldUpdated = (data: { field: string; value: any }) => {
    if (data.field === 'role_id') {
        // Update localFormData.role_id ketika role_id berubah dari FormInput
        const newRoles = Array.isArray(data.value) ? data.value : [];
        localFormData.value.role_id = newRoles;
    }
};

const handleSave = (form: any) => {
    const formData: Record<string, any> = {
        name: form.name,
        email: form.email,
        no_hp: form.no_hp,
        role_id: form.role_id || localFormData.value.role_id,
        is_active: form.is_active,
    };

    // Tambahkan rw_id jika role RW dipilih
    if (formData.role_id.includes(35) && localFormData.value.rw_id) {
        formData.rw_id = localFormData.value.rw_id;
    }

    // Tambahkan rt_id jika role RT dipilih
    if (formData.role_id.includes(36) && localFormData.value.rt_id) {
        formData.rt_id = localFormData.value.rt_id;
    }

    if (form.password) {
        formData.password = form.password;
        formData.password_confirmation = form.password_confirmation;
    }

    if (props.mode === 'edit' && props.initialData?.id) {
        formData.id = props.initialData.id;
    }

    save(formData, {
        url: '/users',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'User berhasil ditambahkan' : 'User berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan user' : 'Gagal memperbarui user',
        redirectUrl: '/users',
    });
};
</script>

<template>
    <div class="space-y-6">
        <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" @field-updated="handleFieldUpdated" />
        
        <!-- Field RW - muncul jika role RW dipilih -->
        <div v-if="hasRWRole" class="grid grid-cols-1 items-start gap-2 md:grid-cols-12 md:gap-4">
            <label class="col-span-full text-sm font-medium md:col-span-4 md:pt-2">
                RW <span class="text-red-500">*</span>
            </label>
            <div class="col-span-full md:col-span-8">
                <Select
                    :model-value="localFormData.rw_id ? String(localFormData.rw_id) : ''"
                    @update:model-value="(val: any) => {
                        localFormData.rw_id = val ? Number(val) : null;
                    }"
                    :key="`rw-select-${localFormData.rw_id || 'empty'}`"
                >
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Pilih RW" />
                    </SelectTrigger>
                    <SelectContent>
                        <div class="p-2 border-b">
                            <Input
                                v-model="rwSearchQuery"
                                type="text"
                                placeholder="Cari..."
                                class="w-full"
                                @click.stop
                                @keydown.stop
                            />
                        </div>
                        <div class="max-h-[300px] overflow-auto">
                            <SelectItem 
                                v-for="option in filteredRwOptions" 
                                :key="option.value"
                                :value="String(option.value)"
                            >
                                {{ option.label }}
                            </SelectItem>
                            <div v-if="filteredRwOptions.length === 0" class="p-2 text-sm text-muted-foreground text-center">
                                Tidak ada hasil ditemukan
                            </div>
                        </div>
                    </SelectContent>
                </Select>
                <p class="text-muted-foreground mt-1 text-sm">
                    Pilih RW untuk role RW
                </p>
            </div>
        </div>

        <!-- Field RT - muncul jika role RT dipilih -->
        <div v-if="hasRTRole" class="grid grid-cols-1 items-start gap-2 md:grid-cols-12 md:gap-4">
            <label class="col-span-full text-sm font-medium md:col-span-4 md:pt-2">
                RT <span class="text-red-500">*</span>
            </label>
            <div class="col-span-full md:col-span-8">
                <Select
                    :model-value="localFormData.rt_id ? String(localFormData.rt_id) : ''"
                    @update:model-value="(val: any) => {
                        localFormData.rt_id = val ? Number(val) : null;
                    }"
                    :key="`rt-select-${localFormData.rt_id || 'empty'}`"
                >
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Pilih RT" />
                    </SelectTrigger>
                    <SelectContent>
                        <div class="p-2 border-b">
                            <Input
                                v-model="rtSearchQuery"
                                type="text"
                                placeholder="Cari..."
                                class="w-full"
                                @click.stop
                                @keydown.stop
                            />
                        </div>
                        <div class="max-h-[300px] overflow-auto">
                            <SelectItem 
                                v-for="option in filteredRtOptions" 
                                :key="option.value"
                                :value="String(option.value)"
                            >
                                {{ option.label }}
                            </SelectItem>
                            <div v-if="filteredRtOptions.length === 0" class="p-2 text-sm text-muted-foreground text-center">
                                Tidak ada hasil ditemukan
                            </div>
                        </div>
                    </SelectContent>
                </Select>
                <p class="text-muted-foreground mt-1 text-sm">
                    Pilih RT untuk role RT
                </p>
            </div>
        </div>
    </div>
</template>
