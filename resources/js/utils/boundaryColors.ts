/**
 * Helper function untuk mendapatkan warna boundary berdasarkan ID dan tipe
 */
export const getBoundaryColor = (id: number, type: 'rw' | 'rt' = 'rw'): string => {
    // Palet warna yang berbeda untuk RW dan RT
    const rwColors = [
        '#3b82f6', // Biru
        '#8b5cf6', // Ungu
        '#ec4899', // Pink
        '#f59e0b', // Orange
        '#10b981', // Hijau
        '#ef4444', // Merah
        '#06b6d4', // Cyan
        '#6366f1', // Indigo
    ];
    
    const rtColors = [
        '#60a5fa', // Biru terang
        '#a78bfa', // Ungu terang
        '#f472b6', // Pink terang
        '#fbbf24', // Orange terang
        '#34d399', // Hijau terang
        '#f87171', // Merah terang
        '#22d3ee', // Cyan terang
        '#818cf8', // Indigo terang
    ];
    
    const colors = type === 'rw' ? rwColors : rtColors;
    return colors[id % colors.length];
};

