import StaffNav from '@/components/staff-nav';
import { Head, router } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';

interface ReservationRow {
    id: number;
    reference: string;
    hotel: string;
    guest: string;
    check_in: string;
    check_out: string;
    status: string;
    total_amount: number;
}

interface Props {
    reservations: ReservationRow[];
    filters: { date: string | null; hotel_id: number | null };
}

const statusStyles: Record<string, string> = {
    pending: 'bg-gold/15 text-gold-foreground ring-1 ring-gold/40',
    confirmed: 'bg-eco/15 text-eco ring-1 ring-eco/40',
    completed: 'bg-primary/10 text-foreground ring-1 ring-primary/25',
    cancelled: 'bg-destructive/10 text-destructive ring-1 ring-destructive/30',
};

const money = (n: number) => `AED ${n.toLocaleString(undefined, { maximumFractionDigits: 0 })}`;

export default function AdminReservationsIndex({ reservations, filters }: Props) {
    const [date, setDate] = useState(filters.date ?? '');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/admin/reservations', date ? { date } : {}, { preserveState: true });
    };

    return (
        <>
            <Head title="All reservations" />
            <StaffNav area="admin" />

            <div className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="font-serif text-3xl font-semibold text-foreground">Reservations</h1>
                        <p className="mt-1 text-sm text-muted-foreground">{reservations.length} shown.</p>
                    </div>
                    <form onSubmit={submit} className="flex items-end gap-2">
                        <div>
                            <label className="mb-1 block text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                On date
                            </label>
                            <input
                                type="date"
                                value={date}
                                onChange={(e) => setDate(e.target.value)}
                                className="rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                            />
                        </div>
                        <button
                            type="submit"
                            className="cursor-pointer rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90"
                        >
                            Filter
                        </button>
                    </form>
                </div>

                <div className="mt-6 rounded-xl border border-border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[48rem] text-left text-sm">
                            <thead>
                                <tr className="text-xs tracking-wide text-muted-foreground uppercase">
                                    <th className="px-6 py-3 font-medium">Reference</th>
                                    <th className="px-6 py-3 font-medium">Hotel</th>
                                    <th className="px-6 py-3 font-medium">Guest</th>
                                    <th className="px-6 py-3 font-medium">Dates</th>
                                    <th className="px-6 py-3 font-medium">Status</th>
                                    <th className="px-6 py-3 text-right font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                {reservations.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-6 py-8 text-center text-muted-foreground">
                                            No reservations match this filter.
                                        </td>
                                    </tr>
                                ) : (
                                    reservations.map((reservation) => (
                                        <tr key={reservation.id} className="border-t border-border">
                                            <td className="px-6 py-3 font-mono text-xs text-foreground">
                                                {reservation.reference}
                                            </td>
                                            <td className="px-6 py-3 text-foreground">{reservation.hotel}</td>
                                            <td className="px-6 py-3 text-muted-foreground">{reservation.guest}</td>
                                            <td className="px-6 py-3 text-muted-foreground">
                                                {reservation.check_in} → {reservation.check_out}
                                            </td>
                                            <td className="px-6 py-3">
                                                <span
                                                    className={`rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusStyles[reservation.status] ?? 'bg-secondary'}`}
                                                >
                                                    {reservation.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-3 text-right font-medium text-foreground">
                                                {money(reservation.total_amount)}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    );
}
