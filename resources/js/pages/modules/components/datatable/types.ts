export type Column = {
    key: string;
    label: string;
    className?: string | ((row: any) => string);
    searchable?: boolean;
    orderable?: boolean;
    visible?: boolean;
    format?: (row: any) => string;
};

export type Sort = {
    key: string;
    order: 'asc' | 'desc';
};

export type DataTableProps = {
    columns: Column[];
    rows: any[];
    selected: number[];
    total: number;
    search: string;
    sort: Sort;
    page: number;
    perPage: number;
    hidePagination?: boolean;
};
