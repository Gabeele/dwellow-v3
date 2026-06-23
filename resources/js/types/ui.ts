export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

/**
 * A single entry in a Laravel paginator's `links` array: the previous /
 * next controls and one entry per page number (plus `...` gap separators).
 * `url` is null for disabled controls and gaps.
 */
export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/**
 * The JSON shape of a Laravel `LengthAwarePaginator`, narrowed to the
 * fields the UI consumes. `T` is the row type after any `->through()` map.
 */
export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};
