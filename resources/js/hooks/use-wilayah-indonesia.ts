import { useCallback, useEffect, useRef, useState } from 'react';

const BASE_URL = 'https://www.emsifa.com/api-wilayah-indonesia/api';

const ENDPOINTS = {
    provinces: 'provinces',
    regencies: 'regencies',
    districts: 'districts',
    villages: 'villages',
} as const;

export type WilayahOption = {
    id: string;
    name: string;
};

type LoadingLevel = keyof typeof ENDPOINTS;

async function fetchWilayah(path: string): Promise<WilayahOption[]> {
    const response = await fetch(`${BASE_URL}/${path}.json`);

    if (!response.ok) {
        throw new Error('Gagal memuat data wilayah.');
    }

    return response.json();
}

export function useWilayahIndonesia() {
    const [provinces, setProvinces] = useState<WilayahOption[]>([]);
    const [regencies, setRegencies] = useState<WilayahOption[]>([]);
    const [districts, setDistricts] = useState<WilayahOption[]>([]);
    const [villages, setVillages] = useState<WilayahOption[]>([]);
    const [loading, setLoading] = useState<Record<LoadingLevel, boolean>>({
        provinces: true,
        regencies: false,
        districts: false,
        villages: false,
    });
    const [error, setError] = useState<string | null>(null);

    const provincesRequest = useRef(0);
    const childRequest = useRef(0);

    const loadProvinces = useCallback(() => {
        const requestId = ++provincesRequest.current;

        fetchWilayah(ENDPOINTS.provinces)
            .then((data) => {
                if (requestId === provincesRequest.current) {
                    setProvinces(data);
                }
            })
            .catch(() => {
                if (requestId === provincesRequest.current) {
                    setError('Gagal memuat daftar provinsi.');
                }
            })
            .finally(() => {
                if (requestId === provincesRequest.current) {
                    setLoading((prev) => ({ ...prev, provinces: false }));
                }
            });
    }, []);

    useEffect(() => {
        loadProvinces();
    }, [loadProvinces]);

    const loadChildren = useCallback(
        async (level: Exclude<LoadingLevel, 'provinces'>, parentId: string) => {
            const requestId = ++childRequest.current;

            setLoading((prev) => ({ ...prev, [level]: true }));
            setError(null);

            try {
                const data = await fetchWilayah(
                    `${ENDPOINTS[level]}/${parentId}`,
                );

                if (requestId === childRequest.current) {
                    return data;
                }
            } catch {
                if (requestId === childRequest.current) {
                    setError(`Gagal memuat data ${level}.`);
                }
            } finally {
                if (requestId === childRequest.current) {
                    setLoading((prev) => ({ ...prev, [level]: false }));
                }
            }

            return [] as WilayahOption[];
        },
        [],
    );

    const handleProvinceChange = useCallback(
        (provinceId: string) => {
            setRegencies([]);
            setDistricts([]);
            setVillages([]);
            loadChildren('regencies', provinceId).then(setRegencies);
        },
        [loadChildren],
    );

    const handleCityChange = useCallback(
        (cityId: string) => {
            setDistricts([]);
            setVillages([]);
            loadChildren('districts', cityId).then(setDistricts);
        },
        [loadChildren],
    );

    const handleDistrictChange = useCallback(
        (districtId: string) => {
            setVillages([]);
            loadChildren('villages', districtId).then(setVillages);
        },
        [loadChildren],
    );

    return {
        provinces,
        regencies,
        districts,
        villages,
        loading,
        error,
        loadProvinces,
        handleProvinceChange,
        handleCityChange,
        handleDistrictChange,
    };
}
