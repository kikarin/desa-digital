<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ref, watch, computed } from 'vue';

const props = defineProps<{
    open: boolean;
    filters: {
        rw?: { value: number | null; options: Array<{ value: number; label: string }> };
        rt?: { value: number | null; options: Array<{ value: number; label: string; rw_id?: number }> };
        jenis_rumah?: { value: string | null; options: Array<{ value: string; label: string }> };
        nomor_rumah?: { value: string | null };
        status?: { value: number | string | null; options: Array<{ value: number | string; label: string }> };
        program?: { value: number | null; options: Array<{ value: number; label: string }> };
        status_bantuan?: { value: string | null; options: Array<{ value: string; label: string }> };
        tipe?: { value: string | null; options: Array<{ value: string; label: string }> };
    };
}>();

const emit = defineEmits(['update:open', 'apply', 'reset']);

const localFilters = ref<typeof props.filters>({});

const filteredRtOptions = computed(() => {
    if (!localFilters.value.rt || !localFilters.value.rw?.value) {
        return [];
    }
    return localFilters.value.rt.options.filter((rt: any) => rt.rw_id === localFilters.value.rw!.value);
});

watch(() => props.open, (newVal) => {
    if (newVal) {
        localFilters.value = JSON.parse(JSON.stringify(props.filters));
    }
});

watch(() => props.filters, (newVal) => {
    if (props.open) {
        localFilters.value = JSON.parse(JSON.stringify(newVal));
    }
}, { deep: true });

watch(() => localFilters.value.rw?.value, (newRwId, oldRwId) => {
    if (newRwId !== oldRwId && localFilters.value.rt) {
        localFilters.value.rt.value = null;
    }
});

const handleApply = () => {
    const filtersToApply = JSON.parse(JSON.stringify(localFilters.value));
    emit('apply', filtersToApply);
    emit('update:open', false);
};

const handleClose = () => {
    emit('update:open', false);
};

const handleReset = () => {
    const resetFilters: typeof props.filters = {};
    if (props.filters.rw) resetFilters.rw = { value: null, options: props.filters.rw.options };
    if (props.filters.rt) resetFilters.rt = { value: null, options: props.filters.rt.options };
    if (props.filters.jenis_rumah) resetFilters.jenis_rumah = { value: null, options: props.filters.jenis_rumah.options };
    if (props.filters.nomor_rumah) resetFilters.nomor_rumah = { value: null };
    if (props.filters.status) resetFilters.status = { value: null, options: props.filters.status.options };
    if (props.filters.program) resetFilters.program = { value: null, options: props.filters.program.options };
    if (props.filters.status_bantuan) resetFilters.status_bantuan = { value: null, options: props.filters.status_bantuan.options };
    if (props.filters.tipe) resetFilters.tipe = { value: null, options: props.filters.tipe.options };
    localFilters.value = resetFilters;
    emit('reset', resetFilters);
    emit('update:open', false);
};
</script>

<template>
    <Dialog :open="open" @update:open="(val: boolean) => emit('update:open', val)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Filter</DialogTitle>
                <DialogDescription>
                    Pilih filter yang ingin diterapkan
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <div v-if="localFilters.rw" class="space-y-2">
                    <Label>RW</Label>
                    <Select
                        :model-value="localFilters.rw.value ? String(localFilters.rw.value) : 'all'"
                        @update:model-value="(val: string) => localFilters.rw!.value = val && val !== 'all' ? Number(val) : null"
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih RW" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua RW</SelectItem>
                            <SelectItem
                                v-for="option in localFilters.rw.options"
                                :key="option.value"
                                :value="String(option.value)"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div v-if="localFilters.rt" class="space-y-2">
                    <Label>RT</Label>
                    <Select
                        :model-value="localFilters.rt.value ? String(localFilters.rt.value) : 'all'"
                        @update:model-value="(val: string) => localFilters.rt!.value = val && val !== 'all' ? Number(val) : null"
                        :disabled="!localFilters.rw?.value"
                    >
                        <SelectTrigger class="w-full" :disabled="!localFilters.rw?.value">
                            <SelectValue :placeholder="localFilters.rw?.value ? 'Pilih RT' : 'Pilih RW terlebih dahulu'" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua RT</SelectItem>
                            <SelectItem
                                v-for="option in filteredRtOptions"
                                :key="option.value"
                                :value="String(option.value)"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div v-if="localFilters.jenis_rumah" class="space-y-2">
                    <Label>Jenis Rumah</Label>
                    <Select
                        :model-value="localFilters.jenis_rumah.value || 'all'"
                        @update:model-value="(val: string) => localFilters.jenis_rumah!.value = val && val !== 'all' ? val : null"
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih Jenis Rumah" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Jenis</SelectItem>
                            <SelectItem
                                v-for="option in localFilters.jenis_rumah.options"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div v-if="localFilters.nomor_rumah" class="space-y-2">
                    <Label>Nomor Rumah</Label>
                    <Input
                        v-model="localFilters.nomor_rumah.value"
                        type="text"
                        placeholder="Masukkan nomor rumah"
                    />
                </div>

                <div v-if="localFilters.status" class="space-y-2">
                    <Label>Status</Label>
                    <Select
                        :model-value="localFilters.status.value ? String(localFilters.status.value) : 'all'"
                        @update:model-value="(val: string) => {
                            if (val && val !== 'all') {
                                const option = localFilters.status!.options.find((opt: any) => String(opt.value) === val);
                                localFilters.status!.value = option ? option.value : null;
                            } else {
                                localFilters.status!.value = null;
                            }
                        }"
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Status</SelectItem>
                            <SelectItem
                                v-for="option in localFilters.status.options"
                                :key="option.value"
                                :value="String(option.value)"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div v-if="localFilters.program" class="space-y-2">
                    <Label>Program Bantuan</Label>
                    <Select
                        :model-value="localFilters.program.value ? String(localFilters.program.value) : 'all'"
                        @update:model-value="(val: string) => localFilters.program!.value = val && val !== 'all' ? Number(val) : null"
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih Program Bantuan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Program</SelectItem>
                            <SelectItem
                                v-for="option in localFilters.program.options"
                                :key="option.value"
                                :value="String(option.value)"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div v-if="localFilters.status_bantuan" class="space-y-2">
                    <Label>Status Bantuan</Label>
                    <Select
                        :model-value="localFilters.status_bantuan.value || 'all'"
                        @update:model-value="(val: string) => localFilters.status_bantuan!.value = val && val !== 'all' ? val : null"
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih Status Bantuan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Status</SelectItem>
                            <SelectItem
                                v-for="option in localFilters.status_bantuan.options"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div v-if="localFilters.tipe" class="space-y-2">
                    <Label>Tipe</Label>
                    <Select
                        :model-value="localFilters.tipe.value || 'all'"
                        @update:model-value="(val: string) => localFilters.tipe!.value = val && val !== 'all' ? val : null"
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih Tipe" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Tipe</SelectItem>
                            <SelectItem
                                v-for="option in localFilters.tipe.options"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="handleReset">
                    Reset
                </Button>
                <Button @click="handleApply">
                    Terapkan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

