import { useToast } from '@/components/ui/toast/useToast';
import { router } from '@inertiajs/vue3';

type SaveOptions = {
    url: string;
    redirectUrl?: string;
    mode: 'create' | 'edit';
    id?: number | string;
    successMessage?: string;
    errorMessage?: string;
    onSuccess?: () => void;
};

export function useHandleFormSave() {
    const { toast } = useToast();

    /**
     * Mengembalikan error field ke pemanggil, hanya error umum yang pakai toast
     */
    const handleError = (errors: Record<string, any>, fallbackMessage: string, setFormErrors?: (errors: Record<string, string>) => void) => {
        if (!errors || Object.keys(errors).length === 0) {
            toast({ title: fallbackMessage, variant: 'destructive' });
            return;
        }
        
        // Check apakah ada error message umum (bukan field-specific)
        if (errors.message) {
            toast({ title: errors.message, variant: 'destructive' });
            return;
        }
        
        // Jika ada setFormErrors, lempar error ke form
        if (setFormErrors) {
            setFormErrors(errors);
        } else {
            // Fallback: tetap pakai toast jika tidak ada handler
            Object.entries(errors).forEach(([field, message]) => {
                // Skip jika message adalah array (validation error)
                const errorMessage = Array.isArray(message) ? message[0] : message;
                toast({
                    title: errorMessage || `${field}: ${errorMessage}`,
                    variant: 'destructive',
                });
            });
        }
    };

    const save = (data: Record<string, any>, options: SaveOptions & { setFormErrors?: (errors: Record<string, string>) => void }) => {
        const {
            url,
            redirectUrl = url,
            mode,
            id,
            successMessage = 'Data berhasil disimpan',
            errorMessage = 'Gagal menyimpan data',
            onSuccess,
            setFormErrors,
        } = options;

        const request =
            mode === 'create'
                ? router.post(url, data, {
                      onSuccess: () => {
                          toast({ title: successMessage, variant: 'success' });
                          if (onSuccess) {
                              onSuccess();
                          } else {
                              router.visit(redirectUrl);
                          }
                      },
                      onError: (errors) => handleError(errors, errorMessage, setFormErrors),
                  })
                : router.put(`${url}/${id}`, data, {
                      onSuccess: () => {
                          toast({ title: successMessage, variant: 'success' });
                          if (onSuccess) {
                              onSuccess();
                          } else {
                              router.visit(redirectUrl);
                          }
                      },
                      onError: (errors) => handleError(errors, errorMessage, setFormErrors),
                  });

        return request;
    };

    return { save };
}
