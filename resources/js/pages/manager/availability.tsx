import StaffNav from '@/components/staff-nav';
import { Head, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

interface RoomTypeRow {
    id: number;
    name: string;
    hotel: string | null;
    base_rate: number;
    total_rooms: number;
}

interface Props {
    room_types: RoomTypeRow[];
}

function isoPlus(days: number): string {
    const d = new Date();
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
}

const money = (n: number) => `AED ${n.toLocaleString(undefined, { maximumFractionDigits: 0 })}`;

export default function ManagerAvailability({ room_types }: Props) {
    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        room_type_id: room_types[0]?.id ?? 0,
        date: isoPlus(1),
        rooms_available: 5,
        rate: room_types[0]?.base_rate ?? 200,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put('/manager/availability', { preserveScroll: true });
    };

    const field = 'w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20';
    const label = 'mb-1 block text-xs font-medium tracking-wide text-muted-foreground uppercase';

    return (
        <>
            <Head title="Rates & availability" />
            <StaffNav area="manager" />

            <div className="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <h1 className="font-serif text-3xl font-semibold text-foreground">Rates &amp; availability</h1>
                    <p className="mt-1 text-sm text-muted-foreground">Set the nightly rate and rooms open on any date.</p>

                    <div className="mt-6 rounded-xl border border-border bg-card">
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[36rem] text-left text-sm">
                                <thead>
                                    <tr className="text-xs tracking-wide text-muted-foreground uppercase">
                                        <th className="px-6 py-3 font-medium">Room type</th>
                                        <th className="px-6 py-3 font-medium">Hotel</th>
                                        <th className="px-6 py-3 text-right font-medium">Base rate</th>
                                        <th className="px-6 py-3 text-right font-medium">Rooms</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {room_types.map((room) => (
                                        <tr key={room.id} className="border-t border-border">
                                            <td className="px-6 py-3 font-medium text-foreground">{room.name}</td>
                                            <td className="px-6 py-3 text-muted-foreground">{room.hotel}</td>
                                            <td className="px-6 py-3 text-right text-foreground">{money(room.base_rate)}</td>
                                            <td className="px-6 py-3 text-right text-foreground">{room.total_rooms}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <form onSubmit={submit} className="h-fit rounded-xl border border-border bg-card p-6 lg:sticky lg:top-24">
                    <h2 className="font-serif text-lg font-semibold text-foreground">Update a date</h2>
                    {recentlySuccessful && (
                        <p className="mt-2 rounded-lg bg-eco/10 px-3 py-2 text-sm text-eco">Availability updated.</p>
                    )}
                    <div className="mt-4 space-y-3">
                        <div>
                            <label className={label} htmlFor="room_type_id">Room type</label>
                            <select id="room_type_id" value={data.room_type_id} onChange={(e) => setData('room_type_id', Number(e.target.value))} className={`${field} cursor-pointer`}>
                                {room_types.map((room) => (
                                    <option key={room.id} value={room.id}>{room.hotel} — {room.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className={label} htmlFor="date">Date</label>
                            <input id="date" type="date" min={isoPlus(0)} value={data.date} onChange={(e) => setData('date', e.target.value)} className={field} />
                            {errors.date && <p className="mt-1 text-xs text-destructive">{errors.date}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className={label} htmlFor="rooms_available">Rooms open</label>
                                <input id="rooms_available" type="number" min={0} value={data.rooms_available} onChange={(e) => setData('rooms_available', Number(e.target.value))} className={field} />
                                {errors.rooms_available && <p className="mt-1 text-xs text-destructive">{errors.rooms_available}</p>}
                            </div>
                            <div>
                                <label className={label} htmlFor="rate">Rate (AED)</label>
                                <input id="rate" type="number" min={0} value={data.rate} onChange={(e) => setData('rate', Number(e.target.value))} className={field} />
                                {errors.rate && <p className="mt-1 text-xs text-destructive">{errors.rate}</p>}
                            </div>
                        </div>
                    </div>
                    <button type="submit" disabled={processing} className="mt-4 w-full cursor-pointer rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:opacity-60">
                        Save
                    </button>
                </form>
            </div>
        </>
    );
}
