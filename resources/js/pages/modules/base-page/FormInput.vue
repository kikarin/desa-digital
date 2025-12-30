<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/toast/useToast';
import { useForm } from '@inertiajs/vue3';
import * as LucideIcons from 'lucide-vue-next';
import { AlertCircle, Eye, EyeOff, X, CalendarIcon } from 'lucide-vue-next';
import { computed, ref, watch, nextTick } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '../../../components/ui/alert';
import ButtonsForm from './ButtonsForm.vue';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { DateFormatter, getLocalTimeZone, today, parseDate } from '@internationalized/date';
import { cn } from '@/lib/utils';
import { CalendarPrevButton, CalendarNextButton } from '@/components/ui/calendar';

const props = defineProps<{
    formInputs: {
        name: string;
        label: string;
        type: 'text' | 'email' | 'password' | 'textarea' | 'select' | 'multi-select' | 'number' | 'radio' | 'icon' | 'checkbox' | 'date' | 'select-or-text' | 'multiple-file';
        placeholder?: string;
        required?: boolean;
        help?: string;
        options?: { value: string | number; label: string }[] | any;
        showPassword?: { value: boolean };
        maxlength?: number;
        pattern?: string;
    }[] | any;
    initialData?: Record<string, any>;
}>();

const emit = defineEmits(['save', 'cancel', 'field-updated']);

// Get formInputs - handle computed/ref
const getFormInputs = computed(() => {
    let inputs = props.formInputs;
    if (inputs && typeof inputs === 'object' && 'value' in inputs) {
        inputs = inputs.value;
    }
    return Array.isArray(inputs) ? inputs : [];
});

// Inisialisasi form menggunakan useForm dengan data awal
const form = useForm(props.initialData || {});

// Date formatter untuk date picker
const dateFormatter = new DateFormatter('en-US', {
    dateStyle: 'long',
});

// Store date values untuk setiap date input (DateValue type)
const dateValues = ref<Record<string, any>>({});

// Store popover open state untuk setiap date input
const popoverOpen = ref<Record<string, boolean>>({});

// Helper function untuk convert string date ke DateValue
const parseDateValue = (dateString: string | null | undefined) => {
    if (!dateString) return null;
    try {
        return parseDate(dateString);
    } catch {
        return null;
    }
};

// Helper function untuk convert DateValue ke string YYYY-MM-DD
const formatDateValue = (dateValue: any) => {
    if (!dateValue) return '';
    try {
        const date = dateValue.toDate(getLocalTimeZone());
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    } catch {
        return '';
    }
};

// Helper function untuk mengecek apakah value adalah File object
const isFile = (value: any): value is File => {
    return value instanceof File || (value && typeof value === 'object' && 'name' in value && 'size' in value && 'type' in value);
};

// Initialize date values dan select-or-text dari initialData
watch(() => props.initialData, (newData: Record<string, any> | undefined) => {
    if (newData) {
        getFormInputs.value.forEach((input: any) => {
            if (input.type === 'date' && newData[input.name]) {
                const parsedDate = parseDateValue(newData[input.name]);
                dateValues.value[input.name] = parsedDate;
                // Also set form value for date
                if (parsedDate) {
                    form[input.name] = formatDateValue(parsedDate);
                }
            }
            if (input.type === 'select-or-text') {
                if (newData[input.name + '_id']) {
                    form[input.name + '_id'] = newData[input.name + '_id'];
                    const selectedOption = input.options?.find((opt: any) => opt.value === newData[input.name + '_id']);
                    form[input.name] = selectedOption?.label || '';
                } else if (newData[input.name]) {
                    form[input.name + '_id'] = null;
                    form[input.name] = newData[input.name];
                }
            }
        });
    }
}, { immediate: true, deep: true });

// Handler untuk input no_kk (hanya angka, max 16 digit)
const handleNoKkInput = (e: Event, inputName: string) => {
    if (inputName === 'no_kk') {
        const target = e.target as HTMLInputElement;
        const value = target.value.replace(/[^0-9]/g, '').slice(0, 16);
        form[inputName] = value;
        target.value = value;
    }
};

// State untuk multi-select dropdown
const multiSelectOpen = ref<Record<string, boolean>>({});

// Memisahkan icon options ke computed property
const iconOptions = computed(() => {
    return Object.keys(LucideIcons)
        .filter((key) => key !== 'default')
        .map((key) => ({
            value: key,
            label: key,
            icon: key,
        }));
});

const formErrors = ref<Record<string, string>>({});

const { toast } = useToast();

const uploadedFiles = ref<Record<string, File[]>>({});
const filePreviews = ref<Record<string, string[]>>({});
const existingMedia = ref<Record<string, Array<{ id: number; url: string; name: string }>>>({});
const fileInputKeys = ref<Record<string, number>>({});

// Initialize existing media dari initialData
watch(() => props.initialData, (newData: Record<string, any> | undefined) => {
    if (newData) {
        getFormInputs.value.forEach((input: any) => {
            if (input.type === 'multiple-file' && newData[input.name]) {
                let fotos = Array.isArray(newData[input.name]) 
                    ? newData[input.name] 
                    : [];
                
                // Jika fotos adalah array of strings (path), convert ke format object
                if (fotos.length > 0 && typeof fotos[0] === 'string') {
                    fotos = fotos.map((path: string, index: number) => ({
                        id: index,
                        url: path.startsWith('http') ? path : `/storage/${path}`,
                        name: path.split('/').pop() || 'Foto',
                    }));
                }
                
                existingMedia.value[input.name] = fotos;
            }
        });
    }
}, { immediate: true, deep: true });

const handleFileSelect = (event: Event, fieldName: string) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const files = Array.from(target.files);
        
        if (!uploadedFiles.value[fieldName]) {
            uploadedFiles.value[fieldName] = [];
        }
        
        files.forEach((file) => {
            if (file.type.startsWith('image/')) {
                uploadedFiles.value[fieldName].push(file);
                
                // Create preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (!filePreviews.value[fieldName]) {
                        filePreviews.value[fieldName] = [];
                    }
                    filePreviews.value[fieldName].push(e.target?.result as string);
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Reset input dengan increment key untuk force re-render
        nextTick(() => {
            fileInputKeys.value[fieldName] = (fileInputKeys.value[fieldName] || 0) + 1;
        });
    }
};

const removeFile = (fieldName: string, index: number) => {
    if (uploadedFiles.value[fieldName]) {
        uploadedFiles.value[fieldName].splice(index, 1);
        if (filePreviews.value[fieldName]) {
            filePreviews.value[fieldName].splice(index, 1);
        }
    }
};

const removeExistingMedia = (fieldName: string, mediaId: number) => {
    if (!form.deleted_media_ids) {
        form.deleted_media_ids = [];
    }
    if (!form.deleted_media_ids.includes(mediaId)) {
        form.deleted_media_ids.push(mediaId);
    }
    
    if (existingMedia.value[fieldName]) {
        existingMedia.value[fieldName] = existingMedia.value[fieldName].filter(
            (media) => media.id !== mediaId
        );
    }
};

const handleSubmit = (e: Event) => {
    e.preventDefault();
    formErrors.value = {}; // reset error sebelum submit

    // Cek required field
    let isValid = true;
    const localErrors: Record<string, string> = {};
    getFormInputs.value.forEach((input: any) => {
        if (input.required && input.type !== 'multiple-file' && !form[input.name]) {
            isValid = false;
            localErrors[input.name] = `${input.label} wajib diisi`;
        }
    });

    if (!isValid) {
        toast({ title: 'Data is not valid', variant: 'destructive' });
        formErrors.value = localErrors;
        return;
    }

    // Attach files to form
    getFormInputs.value.forEach((input: any) => {
        if (input.type === 'multiple-file' && uploadedFiles.value[input.name]) {
            form[input.name] = uploadedFiles.value[input.name];
        }
    });

    emit('save', form, setFormErrors);
};

function setFormErrors(errors: Record<string, string>) {
    formErrors.value = errors;
}

const togglePassword = (field: { value: boolean }) => {
    field.value = !field.value;
};

// Multi-select functions
const toggleMultiSelect = (fieldName: string) => {
    multiSelectOpen.value[fieldName] = !multiSelectOpen.value[fieldName];
};

const selectMultiOption = (fieldName: string, value: string | number) => {
    const currentValues = form[fieldName] || [];
    if (currentValues.includes(value)) {
        form[fieldName] = currentValues.filter((v: any) => v !== value);
    } else {
        form[fieldName] = [...currentValues, value];
    }
    // Emit field-updated event untuk sinkronisasi
    emit('field-updated', { field: fieldName, value: form[fieldName] });
};

const removeMultiOption = (fieldName: string, value: string | number) => {
    const currentValues = form[fieldName] || [];
    form[fieldName] = currentValues.filter((v: any) => v !== value);
    // Emit field-updated event untuk sinkronisasi
    emit('field-updated', { field: fieldName, value: form[fieldName] });
};

const getSelectedLabels = (fieldName: string, options: { value: string | number; label: string }[]) => {
    const selectedValues = form[fieldName] || [];
    return options.filter((option) => selectedValues.includes(option.value));
};

const selectSearchQuery = ref<Record<string, string>>({});

const getFilteredOptions = (input: any) => {
    const query = selectSearchQuery.value[input.name] || '';
    let options = input.options;
    
    // Handle computed/ref - unwrap if needed
    if (options && typeof options === 'object' && 'value' in options) {
        options = options.value;
    }
    
    // Ensure it's an array
    if (!Array.isArray(options)) {
        options = [];
    }
    
    if (!query) return options;
    
    return options.filter((opt: any) => {
        if (!opt || typeof opt !== 'object') return false;
        const label = String(opt.label || '');
        return label.toLowerCase().includes(query.toLowerCase());
    });
};
</script>

<template>
    <div class="w-full">
        <!-- ALERT ERROR -->
        <Alert v-if="Object.keys(formErrors).length" variant="destructive" class="mb-4 shadow-none hover:shadow-none">
            <AlertCircle class="h-4 w-4" />
            <AlertTitle>Error</AlertTitle>
            <AlertDescription>
                <ul class="list-disc pl-5">
                    <li v-for="(msg, field) in formErrors" :key="field">{{ msg }}</li>
                </ul>
            </AlertDescription>
        </Alert>
        <form @submit="handleSubmit" class="space-y-6">
            <div v-for="input in getFormInputs" :key="input.name" class="grid grid-cols-1 items-start gap-2 md:grid-cols-12 md:gap-4">
                <label class="col-span-full text-sm font-medium md:col-span-4 md:pt-2">{{ input.label }}</label>
                <div class="col-span-full md:col-span-8">
                    <!-- MULTI-SELECT -->
                    <div v-if="input.type === 'multi-select'" class="relative">
                        <div
                            @click="toggleMultiSelect(input.name)"
                            class="border-input bg-background flex min-h-[40px] w-full cursor-pointer flex-wrap items-center gap-1 rounded-md border px-3 py-2 text-sm"
                            :class="{ 'border-ring ring-ring ring-2 ring-offset-2': multiSelectOpen[input.name] }"
                        >
                            <!-- Selected badges -->
                            <div v-if="form[input.name] && form[input.name].length > 0" class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="selected in getSelectedLabels(input.name, input.options || [])"
                                    :key="selected.value"
                                    variant="secondary"
                                    class="flex items-center gap-1 text-xs"
                                >
                                    {{ selected.label }}
                                    <X
                                        class="hover:text-destructive h-3 w-3 cursor-pointer"
                                        @click.stop="removeMultiOption(input.name, selected.value)"
                                    />
                                </Badge>
                            </div>
                            <!-- Placeholder -->
                            <div v-else class="text-muted-foreground">
                                {{ input.placeholder }}
                            </div>
                        </div>

                        <!-- Dropdown options -->
                        <div
                            v-if="multiSelectOpen[input.name]"
                            class="bg-popover absolute top-full right-0 left-0 z-50 mt-1 max-h-60 overflow-auto rounded-md border p-1 shadow-lg"
                        >
                            <div
                                v-for="option in input.options"
                                :key="option.value"
                                @click="selectMultiOption(input.name, option.value)"
                                class="hover:bg-accent hover:text-accent-foreground flex cursor-pointer items-center space-x-2 rounded-sm px-2 py-1.5 text-sm"
                            >
                                <Checkbox
                                    :model-value="(form[input.name] || []).includes(option.value)"
                                    @update:modelValue="
                                        (checked: boolean) => {
                                            const selected = form[input.name] || [];
                                            if (checked) {
                                                form[input.name] = [...selected, option.value];
                                            } else {
                                                form[input.name] = selected.filter((v: any) => v !== option.value);
                                            }
                                            // Emit field-updated event untuk sinkronisasi
                                            emit('field-updated', { field: input.name, value: form[input.name] });
                                        }
                                    "
                                />
                                <span>{{ option.label }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- ICON SELECT -->
                    <Select
                        v-else-if="input.type === 'icon'"
                        :required="input.required"
                        :model-value="form[input.name]"
                        @update:modelValue="(val: any) => (form[input.name] = val)"
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue :placeholder="input.placeholder">
                                <template v-if="form[input.name]">
                                    <component :is="LucideIcons[form[input.name] as keyof typeof LucideIcons]" class="mr-2 inline-block h-4 w-4" />
                                    {{ form[input.name] }}
                                </template>
                            </SelectValue>
                        </SelectTrigger>
                        <SelectContent class="max-h-[300px]">
                            <div class="grid grid-cols-4 gap-2 p-2">
                                <SelectItem
                                    v-for="option in iconOptions"
                                    :key="option.value"
                                    :value="option.value"
                                    class="hover:bg-accent flex cursor-pointer items-center gap-2 rounded-md p-2"
                                >
                                    <component :is="LucideIcons[option.icon as keyof typeof LucideIcons]" class="h-4 w-4" />
                                    <span class="text-sm">{{ option.label }}</span>
                                </SelectItem>
                            </div>
                        </SelectContent>
                    </Select>

                    <!-- TEXTAREA -->
                    <textarea
                        v-else-if="input.type === 'textarea'"
                        v-model="form[input.name]"
                        :placeholder="input.placeholder"
                        :required="input.required"
                        class="border-input bg-background text-foreground placeholder:text-muted-foreground focus-visible:ring-ring min-h-[100px] w-full rounded-md border px-3 py-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />

                    <!-- SELECT -->
                    <Select
                        v-else-if="input.type === 'select'"
                        :required="input.required"
                        :model-value="form[input.name]"
                        @update:modelValue="
                            (val: any) => {
                                form[input.name] = val;
                                emit('field-updated', { field: input.name, value: val });
                            }
                        "
                        :key="`select-${input.name}-${(input.options || []).length}`"
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue :placeholder="input.placeholder" />
                        </SelectTrigger>
                        <SelectContent>
                            <!-- Search input -->
                            <div class="p-2 border-b">
                                <Input
                                    v-model="selectSearchQuery[input.name]"
                                    type="text"
                                    placeholder="Cari..."
                                    class="w-full"
                                    @click.stop
                                    @keydown.stop
                                />
                            </div>
                            <!-- Filtered options -->
                            <div class="max-h-[300px] overflow-auto">
                                <SelectItem 
                                    v-for="(option, index) in getFilteredOptions(input)" 
                                    :key="`${input.name}-${index}-${String(option?.value ?? '')}`"
                                    :value="option.value"
                                >
                                    {{ option.label || '' }}
                                </SelectItem>
                                <div v-if="getFilteredOptions(input).length === 0" class="p-2 text-sm text-muted-foreground text-center">
                                    Tidak ada hasil ditemukan
                                </div>
                            </div>
                        </SelectContent>
                    </Select>

                    <!-- SELECT OR TEXT (combobox) -->
                    <div v-else-if="input.type === 'select-or-text'" class="space-y-2">
                        <Select
                            :required="input.required"
                            :model-value="form[input.name + '_id'] ? String(form[input.name + '_id']) : (form[input.name] && !form[input.name + '_id'] ? 'MANUAL_INPUT' : '')"
                            @update:modelValue="
                                (val: any) => {
                                    if (val === 'MANUAL_INPUT') {
                                        form[input.name + '_id'] = null;
                                        form[input.name] = form[input.name] || '';
                                    } else if (val && val !== '') {
                                        form[input.name + '_id'] = Number(val);
                                        const selectedOption = input.options?.find((opt: any) => opt.value === Number(val));
                                        form[input.name] = selectedOption?.label || '';
                                    } else {
                                        form[input.name + '_id'] = null;
                                        form[input.name] = '';
                                    }
                                    emit('field-updated', { field: input.name, value: val });
                                }
                            "
                            :key="`select-or-text-${input.name}-${(input.options || []).length}`"
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih" />
                            </SelectTrigger>
                            <SelectContent>
                                <div class="p-2 border-b">
                                    <Input
                                        v-model="selectSearchQuery[input.name]"
                                        type="text"
                                        placeholder="Cari..."
                                        class="w-full"
                                        @click.stop
                                        @keydown.stop
                                    />
                                </div>
                                <div class="max-h-[300px] overflow-auto">
                                    <SelectItem value="MANUAL_INPUT" class="font-semibold">
                                        Input Manual
                                    </SelectItem>
                                    <SelectItem 
                                        v-for="(option, index) in getFilteredOptions(input)" 
                                        :key="`${input.name}-${index}-${String(option?.value ?? '')}`"
                                        :value="String(option.value)"
                                    >
                                        {{ option.label || '' }}
                                    </SelectItem>
                                    <div v-if="getFilteredOptions(input).length === 0" class="p-2 text-sm text-muted-foreground text-center">
                                        Tidak ada hasil ditemukan
                                    </div>
                                </div>
                            </SelectContent>
                        </Select>
                        <Input
                            v-if="!form[input.name + '_id']"
                            v-model="form[input.name]"
                            type="text"
                            placeholder="Ketik manual"
                            :required="input.required && !form[input.name + '_id']"
                        />
                    </div>

                    <!-- RADIO -->
                    <div v-else-if="input.type === 'radio'" class="flex gap-4">
                        <label v-for="option in input.options" :key="option.value" class="inline-flex cursor-pointer items-center space-x-2">
                            <input
                                type="radio"
                                :name="input.name"
                                :value="option.value"
                                v-model="form[input.name]"
                                :required="input.required"
                                class="form-radio text-primary border-input focus:ring-ring"
                            />
                            <span class="text-sm">{{ option.label }}</span>
                        </label>
                    </div>

                    <!-- CHECKBOX GROUP -->
                    <div v-else-if="input.type === 'checkbox' && Array.isArray(input.options)" class="flex flex-col gap-2">
                        <div v-for="option in input.options" :key="option.value" class="flex items-center space-x-2">
                            <Checkbox
                                :id="`${input.name}-${option.value}`"
                                :value="option.value"
                                :checked="Array.isArray(form[input.name]) && form[input.name].includes(option.value)"
                                @update:checked="
                                    (checked: boolean) => {
                                        const selected = form[input.name] || [];
                                        if (checked) {
                                            form[input.name] = [...selected, option.value];
                                        } else {
                                            form[input.name] = selected.filter((v: any) => v !== option.value);
                                        }
                                    }
                                "
                            />
                            <label :for="`${input.name}-${option.value}`" class="text-sm">
                                {{ option.label }}
                            </label>
                        </div>
                    </div>

                    <!-- PASSWORD WITH TOGGLE -->
                    <div v-else-if="input.type === 'password'" class="relative">
                        <Input
                            v-model="form[input.name]"
                            :type="input.showPassword?.value ? 'text' : 'password'"
                            :placeholder="input.placeholder"
                            :required="input.required"
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="absolute top-1/2 right-2 -translate-y-1/2"
                            @click="togglePassword(input.showPassword!)"
                        >
                            <Eye v-if="!input.showPassword?.value" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                        </Button>
                    </div>

                    <!-- DATE PICKER -->
                    <Popover v-else-if="input.type === 'date'" v-model:open="popoverOpen[input.name]">
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                :class="cn(
                                    'w-full justify-start text-left font-normal',
                                    !dateValues[input.name] && 'text-muted-foreground'
                                )"
                                type="button"
                            >
                                <CalendarIcon class="mr-2 h-4 w-4" />
                                <span v-if="dateValues[input.name]">
                                    {{ dateFormatter.format(dateValues[input.name].toDate(getLocalTimeZone())) }}
                                </span>
                                <span v-else>{{ input.placeholder || 'Pick a date' }}</span>
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0 max-w-[350px]" align="start">
                            <Calendar
                                v-model="dateValues[input.name]"
                                :default-placeholder="today(getLocalTimeZone())"
                                layout="month-and-year"
                                :initial-focus="true"
                                class="w-full"
                                @update:model-value="(val: any) => {
                                    if (val) {
                                        dateValues[input.name] = val;
                                        form[input.name] = formatDateValue(val);
                                        emit('field-updated', { field: input.name, value: formatDateValue(val) });
                                        nextTick(() => {
                                            popoverOpen.value[input.name] = false;
                                        });
                                    }
                                }"
                            >
                                <template #header>
                                    <div class="flex items-center justify-between px-3 pt-3">
                                        <CalendarPrevButton />
                                        <div class="flex items-center gap-2">
                                            <Select
                                                :model-value="dateValues[input.name] ? String(dateValues[input.name].month) : String(today(getLocalTimeZone()).month)"
                                                @update:model-value="(month: string) => {
                                                    const current = dateValues[input.name] || today(getLocalTimeZone());
                                                    dateValues[input.name] = current.set({ month: Number(month) });
                                                }"
                                            >
                                                <SelectTrigger class="w-[80px] h-8">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="1">January</SelectItem>
                                                    <SelectItem value="2">February</SelectItem>
                                                    <SelectItem value="3">March</SelectItem>
                                                    <SelectItem value="4">April</SelectItem>
                                                    <SelectItem value="5">May</SelectItem>
                                                    <SelectItem value="6">June</SelectItem>
                                                    <SelectItem value="7">July</SelectItem>
                                                    <SelectItem value="8">August</SelectItem>
                                                    <SelectItem value="9">September</SelectItem>
                                                    <SelectItem value="10">October</SelectItem>
                                                    <SelectItem value="11">November</SelectItem>
                                                    <SelectItem value="12">December</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <Select
                                                :model-value="dateValues[input.name] ? String(dateValues[input.name].year) : String(today(getLocalTimeZone()).year)"
                                                @update:model-value="(year: string) => {
                                                    const current = dateValues[input.name] || today(getLocalTimeZone());
                                                    dateValues[input.name] = current.set({ year: Number(year) });
                                                }"
                                            >
                                                <SelectTrigger class="w-[90px] h-8">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="year in Array.from({ length: 100 }, (_, i) => new Date().getFullYear() - 50 + i)"
                                                        :key="year"
                                                        :value="String(year)"
                                                    >
                                                        {{ year }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <CalendarNextButton />
                                    </div>
                                </template>
                            </Calendar>
                        </PopoverContent>
                    </Popover>

                    <!-- MULTIPLE FILE UPLOAD -->
                    <div v-else-if="input.type === 'multiple-file'" class="space-y-4">
                        <div class="flex items-center gap-2">
                            <Input
                                :key="`file-input-${input.name}-${fileInputKeys[input.name] || 0}`"
                                type="file"
                                accept="image/*"
                                multiple
                                @change="(e: Event) => handleFileSelect(e, input.name)"
                                class="flex-1"
                            />
                        </div>
                        
                        <!-- Preview Existing Media -->
                        <div v-if="existingMedia[input.name] && existingMedia[input.name].length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div
                                v-for="media in existingMedia[input.name]"
                                :key="media.id"
                                class="relative group"
                            >
                                <img
                                    :src="media.url"
                                    :alt="media.name"
                                    class="w-full h-32 object-cover rounded-md border"
                                    @error="(e: Event) => {
                                        const img = e.target as HTMLImageElement;
                                        img.style.display = 'none';
                                    }"
                                />
                                <button
                                    type="button"
                                    @click="removeExistingMedia(input.name, media.id)"
                                    class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                        
                        <!-- Preview New Files -->
                        <div v-if="filePreviews[input.name] && filePreviews[input.name].length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div
                                v-for="(preview, index) in filePreviews[input.name]"
                                :key="index"
                                class="relative group"
                            >
                                <img
                                    :src="preview"
                                    alt="Preview"
                                    class="w-full h-32 object-cover rounded-md border"
                                />
                                <button
                                    type="button"
                                    @click="removeFile(input.name, index)"
                                    class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                        
                        <p v-if="input.help" class="text-muted-foreground text-sm">
                            {{ input.help }}
                        </p>
                    </div>

                    <!-- FILE INPUT (single file) -->
                    <div v-else-if="input.type === 'file'" class="space-y-2">
                        <Input
                            type="file"
                            accept="image/*"
                            :required="input.required"
                            @change="(e: Event) => {
                                const target = e.target as HTMLInputElement;
                                if (target.files && target.files.length > 0) {
                                    form[input.name] = target.files[0];
                                } else {
                                    form[input.name] = null;
                                }
                            }"
                        />
                        <div v-if="initialData?.[input.name] && typeof initialData[input.name] === 'string' && !form[input.name]" class="text-sm text-muted-foreground">
                            Foto saat ini: <a :href="initialData[input.name]" target="_blank" class="text-primary hover:underline">Lihat foto</a>
                        </div>
                        <div v-if="isFile(form[input.name])" class="text-sm text-muted-foreground">
                            File baru dipilih: {{ (form[input.name] as File).name }}
                        </div>
                    </div>

                    <!-- DEFAULT INPUT (text, email, number) -->
                    <Input 
                        v-else 
                        v-model="form[input.name]" 
                        :type="input.type" 
                        :placeholder="input.placeholder" 
                        :required="input.required"
                        :maxlength="input.maxlength"
                        :pattern="input.pattern"
                        @input="input.name === 'no_kk' ? (e: Event) => handleNoKkInput(e, input.name) : undefined"
                    />

                    <!-- Help text -->
                    <p v-if="input.help" class="text-muted-foreground mt-1 text-sm">
                        {{ input.help }}
                    </p>
                </div>
            </div>

            <!-- BUTTONS -->
            <div class="grid grid-cols-1 items-center md:grid-cols-12">
                <div class="hidden md:col-span-3 md:block"></div>
                <div class="col-span-full md:col-span-9">
                    <ButtonsForm @save="handleSubmit" @cancel="emit('cancel')" />
                </div>
            </div>
        </form>
    </div>

    <!-- Overlay untuk menutup multi-select dropdown -->
    <div v-if="Object.values(multiSelectOpen).some(Boolean)" @click="multiSelectOpen = {}" class="fixed inset-0 z-40"></div>
</template>
