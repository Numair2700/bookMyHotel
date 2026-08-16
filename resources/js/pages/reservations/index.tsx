import { hotelImage } from '@/lib/hotel-image';
import { Head, Link } from '@inertiajs/react';
import { CalendarDays } from 'lucide-react';

interface ReservationRow {
    id: number;
    reference: string;
    hotel: string;
    hotel_id?: number;
    check_in: string;
    check_out: string;
    status: string;
    total_amount: number;
}

interface Props {
    reservations: ReservationRow[];
}

const statusStyles: Record<string, string> = {
    pending: 'bg-gold/15 text-gold-foreground/90 ring-1 ring-gold/40',
    confirmed: 'bg-eco/15 text-eco ring-1 ring-eco/40',
    completed: 'bg-primary/10 text-primary ring-1 ring-primary/30',
    cancelled: 'bg-destructive/10 text-destructive ring-1 ring-destructive/30',
};

const money = (n: number) => `AED ${n.toLocaleString(undefined, { maximumFractionDigits: 0 })}`;

export default function ReservationsIndex({ reservations }: Props) {
    return (
        <>
            <Head title="My bookings" />

            <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6">
                <h1 className="font-serif text-3xl font-semibold text-foreground">My bookings</h1>
                <p className="mt-2 text-muted-foreground">Your past and upcoming stays.</p>

                {reservations.length === 0 ? (
                    <div className="mt-8 rounded-xl border border-dashed border-border bg-card p-12 text-center">
                        <p className="font-serif text-lg text-foreground">You have no bookings yet.</p>
                        <Link
                            href="/"
                            className="mt-6 inline-block rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-primary-foreground hover:opacity-90"
                        >
                            Find a stay
                        </Link>
                    </div>
                ) : (
                    <ul className="mt-8 space-y-4">
                        {reservations.map((reservation) => (
                            <li key={reservation.id}>
                                <Link
                                    href={`/reservations/${reservation.id}`}
                                    className="flex items-center gap-4 rounded-xl border border-border bg-card p-4 transition hover:shadow-md"
                                >
                                    <img
                                        src={hotelImage(reservation.hotel_id ?? reservation.id)}
                                        alt=""
                                        className="hidden h-20 w-28 shrink-0 rounded-lg object-cover sm:block"
                                    />
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="font-serif text-lg font-semibold text-foreground">
                                                {reservation.hotel}
                                            </span>
                                            <span
                                                className={`rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusStyles[reservation.status] ?? 'bg-secondary text-foreground'}`}
                                            >
                                                {reservation.status}
                                            </span>
                                        </div>
                                        <p className="mt-1 flex items-center gap-1.5 text-sm text-muted-foreground">
                                            <CalendarDays className="h-3.5 w-3.5" />
                                            {reservation.check_in} → {reservation.check_out}
                                        </p>
                                        <p className="mt-0.5 text-xs text-muted-foreground">Ref {reservation.reference}</p>
                                    </div>
                                    <div className="shrink-0 text-right">
                                        <p className="font-serif text-lg font-semibold text-foreground">
                                            {money(reservation.total_amount)}
                                        </p>
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
