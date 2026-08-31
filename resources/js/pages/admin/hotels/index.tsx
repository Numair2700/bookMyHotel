import { Head, router, useForm } from '@inertiajs/react';
import { Leaf, Pencil, Star, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import StaffNav from '@/components/staff-nav';

interface HotelRow {
    id: number;
    name: string;
    chain: string | null;
    chain_id: number;
    city: string;
    country: string;
    region: string;
    address: string;
    description: string;
    star_rating: number;
    wifi_speed_mbps: number | null;
    has_workspace: boolean;
    sustainability_certified: boolean;
    reservations_count: number;
}

interface Props {
    hotels: HotelRow[];
    chains: { id: number; name: string }[];
}

export default function AdminHotelsIndex({ hotels, chains }: Props) {
    const [editingId, setEditingId] = useState<number | null>(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        chain_id: chains[0]?.id ?? 0,
        name: '',
        city: '',
        country: '',
        region: 'asia',
        address: '',
        description: '',
        star_rating: 5,
        wifi_speed_mbps: 200,
        has_workspace: false,
        sustainability_certified: false,
    });

    const cancelEdit = () => {
        setEditingId(null);
        reset();
    };

    const startEdit = (h: HotelRow) => {
        setEditingId(h.id);
        setData({
            chain_id: h.chain_id,
            name: h.name,
            city: h.city,
            country: h.country,
            region: h.region,
            address: h.address,
            description: h.description,
            star_rating: h.star_rating,
            wifi_speed_mbps: h.wifi_speed_mbps ?? 0,
            has_workspace: h.has_workspace,
            sustainability_certified: h.sustainability_certified,
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (editingId) {
            put(`/admin/hotels/${editingId}`, {
                preserveScroll: true,
                onSuccess: cancelEdit,
            });
        } else {
            post('/admin/hotels', {
                preserveScroll: true,
                onSuccess: () => reset(),
            });
        }
    };

    const remove = (h: HotelRow) => {
        if (h.reservations_count > 0) {
            return;
        }
        if (confirm(`Delete ${h.name}?`)) {
            router.delete(`/admin/hotels/${h.id}`, { preserveScroll: true });
        }
    };

    const field =
        'w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20';
    const label =
        'mb-1 block text-xs font-medium tracking-wide text-muted-foreground uppercase';

    return (
        <>
            <Head title="Manage hotels" />
            <StaffNav area="admin" />

            <div className="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <h1 className="font-serif text-3xl font-semibold text-foreground">
                        Hotels
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {hotels.length} properties across the four chains.
                    </p>
                    {(errors as Record<string, string | undefined>).hotel && (
                        <p className="mt-3 rounded-lg bg-destructive/10 px-3 py-2 text-sm text-destructive">
                            {
                                (errors as Record<string, string | undefined>)
                                    .hotel
                            }
                        </p>
                    )}

                    <div className="mt-6 rounded-xl border border-border bg-card">
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[42rem] text-left text-sm">
                                <thead>
                                    <tr className="text-xs tracking-wide text-muted-foreground uppercase">
                                        <th className="px-6 py-3 font-medium">
                                            Hotel
                                        </th>
                                        <th className="px-6 py-3 font-medium">
                                            Location
                                        </th>
                                        <th className="px-6 py-3 font-medium">
                                            Chain
                                        </th>
                                        <th className="px-6 py-3 text-right font-medium">
                                            Bookings
                                        </th>
                                        <th className="px-6 py-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {hotels.map((hotel) => (
                                        <tr
                                            key={hotel.id}
                                            className="border-t border-border"
                                        >
                                            <td className="px-6 py-3">
                                                <div className="flex items-center gap-2 font-medium text-foreground">
                                                    {hotel.name}
                                                    {hotel.sustainability_certified && (
                                                        <Leaf className="h-3.5 w-3.5 text-eco" />
                                                    )}
                                                </div>
                                                <div className="mt-0.5 flex items-center gap-0.5 text-gold">
                                                    {Array.from({
                                                        length: hotel.star_rating,
                                                    }).map((_, i) => (
                                                        <Star
                                                            key={i}
                                                            className="h-3 w-3 fill-current"
                                                        />
                                                    ))}
                                                </div>
                                            </td>
                                            <td className="px-6 py-3 text-muted-foreground">
                                                {hotel.city}, {hotel.country}
                                                <span className="block text-xs capitalize">
                                                    {hotel.region}
                                                </span>
                                            </td>
                                            <td className="px-6 py-3 text-muted-foreground">
                                                {hotel.chain}
                                            </td>
                                            <td className="px-6 py-3 text-right font-medium text-foreground">
                                                {hotel.reservations_count}
                                            </td>
                                            <td className="px-6 py-3">
                                                <div className="flex items-center justify-end gap-1.5">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            startEdit(hotel)
                                                        }
                                                        className="cursor-pointer rounded-lg border border-border p-2 text-muted-foreground transition hover:text-foreground"
                                                        aria-label={`Edit ${hotel.name}`}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            remove(hotel)
                                                        }
                                                        disabled={
                                                            hotel.reservations_count >
                                                            0
                                                        }
                                                        title={
                                                            hotel.reservations_count >
                                                            0
                                                                ? 'Has reservations — cannot delete'
                                                                : 'Delete hotel'
                                                        }
                                                        className="cursor-pointer rounded-lg border border-destructive/40 p-2 text-destructive transition hover:bg-destructive/5 disabled:cursor-not-allowed disabled:opacity-40"
                                                        aria-label={`Delete ${hotel.name}`}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <form
                    onSubmit={submit}
                    className="h-fit rounded-xl border border-border bg-card p-6 lg:sticky lg:top-24"
                >
                    <h2 className="font-serif text-lg font-semibold text-foreground">
                        {editingId ? 'Edit hotel' : 'New hotel'}
                    </h2>
                    <div className="mt-4 space-y-3">
                        <div>
                            <label className={label} htmlFor="name">
                                Name
                            </label>
                            <input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className={field}
                            />
                            {errors.name && (
                                <p className="mt-1 text-xs text-destructive">
                                    {errors.name}
                                </p>
                            )}
                        </div>
                        <div>
                            <label className={label} htmlFor="chain_id">
                                Chain
                            </label>
                            <select
                                id="chain_id"
                                value={data.chain_id}
                                onChange={(e) =>
                                    setData('chain_id', Number(e.target.value))
                                }
                                className={`${field} cursor-pointer`}
                            >
                                {chains.map((chain) => (
                                    <option key={chain.id} value={chain.id}>
                                        {chain.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className={label} htmlFor="city">
                                    City
                                </label>
                                <input
                                    id="city"
                                    value={data.city}
                                    onChange={(e) =>
                                        setData('city', e.target.value)
                                    }
                                    className={field}
                                />
                            </div>
                            <div>
                                <label className={label} htmlFor="country">
                                    Country
                                </label>
                                <input
                                    id="country"
                                    value={data.country}
                                    onChange={(e) =>
                                        setData('country', e.target.value)
                                    }
                                    className={field}
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className={label} htmlFor="region">
                                    Region
                                </label>
                                <select
                                    id="region"
                                    value={data.region}
                                    onChange={(e) =>
                                        setData('region', e.target.value)
                                    }
                                    className={`${field} cursor-pointer`}
                                >
                                    <option value="asia">Asia</option>
                                    <option value="europe">Europe</option>
                                </select>
                            </div>
                            <div>
                                <label className={label} htmlFor="star_rating">
                                    Stars
                                </label>
                                <select
                                    id="star_rating"
                                    value={data.star_rating}
                                    onChange={(e) =>
                                        setData(
                                            'star_rating',
                                            Number(e.target.value),
                                        )
                                    }
                                    className={`${field} cursor-pointer`}
                                >
                                    {[1, 2, 3, 4, 5].map((n) => (
                                        <option key={n} value={n}>
                                            {n}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <div>
                            <label className={label} htmlFor="address">
                                Address
                            </label>
                            <input
                                id="address"
                                value={data.address}
                                onChange={(e) =>
                                    setData('address', e.target.value)
                                }
                                className={field}
                            />
                        </div>
                        <div>
                            <label className={label} htmlFor="description">
                                Description
                            </label>
                            <textarea
                                id="description"
                                rows={3}
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                className={field}
                            />
                        </div>
                        <div>
                            <label className={label} htmlFor="wifi_speed_mbps">
                                Wi-Fi (Mbps)
                            </label>
                            <input
                                id="wifi_speed_mbps"
                                type="number"
                                min={0}
                                value={data.wifi_speed_mbps}
                                onChange={(e) =>
                                    setData(
                                        'wifi_speed_mbps',
                                        Number(e.target.value),
                                    )
                                }
                                className={field}
                            />
                        </div>
                        <label className="flex items-center gap-2 text-sm text-foreground">
                            <input
                                type="checkbox"
                                checked={data.has_workspace}
                                onChange={(e) =>
                                    setData('has_workspace', e.target.checked)
                                }
                            />
                            Has workspace
                        </label>
                        <label className="flex items-center gap-2 text-sm text-foreground">
                            <input
                                type="checkbox"
                                checked={data.sustainability_certified}
                                onChange={(e) =>
                                    setData(
                                        'sustainability_certified',
                                        e.target.checked,
                                    )
                                }
                            />
                            Sustainability certified
                        </label>
                    </div>
                    <div className="mt-4 flex gap-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex-1 cursor-pointer rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:opacity-60"
                        >
                            {editingId ? 'Save changes' : 'Create hotel'}
                        </button>
                        {editingId && (
                            <button
                                type="button"
                                onClick={cancelEdit}
                                className="cursor-pointer rounded-lg border border-border px-4 py-2.5 text-sm font-medium text-muted-foreground hover:text-foreground"
                            >
                                Cancel
                            </button>
                        )}
                    </div>
                </form>
            </div>
        </>
    );
}
