export type PublicSite = {
    name: string;
    description: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    website: string | null;
};

export type PublicYayasan = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    logo_path: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    website: string | null;
    lembaga_count?: number;
};

export type PublicLembaga = {
    id: number;
    name: string;
    slug: string;
    type: string | null;
    type_label: string | null;
    description: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    photo_path: string | null;
    santris_count: number;
    yayasan?: {
        id: number;
        name: string;
        slug: string;
    } | null;
};

export type PublicCategory = {
    id: number;
    name: string;
    slug: string;
    team_slug: string;
};

export type PublicPost = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    cover_path: string | null;
    body?: string;
    published_at: string;
    team: {
        id: number;
        name: string;
        slug: string;
    };
    category: {
        id: number;
        name: string;
        slug: string;
    } | null;
    author?: {
        id: number;
        name: string;
    } | null;
};

export type PublicStats = {
    lembaga: number;
    santri: number;
    post: number;
};

export type PublicPagination<T> = {
    current_page: number;
    data: T[];
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
};
