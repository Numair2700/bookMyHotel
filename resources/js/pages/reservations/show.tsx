import { Head, useForm } from '@inertiajs/react';
import {
    CalendarDays,
    CheckCircle2,
    CreditCard,
    Leaf,
    ShieldCheck,
    Star,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { hotelImage } from '@/lib/hotel-image';

interface NightRow {
    stay_date: string;
    rate: number;
}

interface RefundQuote {
    amount: number;
    percent: number;
    within_free_window: boolean;
    policy_name: string | null;
    free_cancellation_hours: number | null;
    penalty_percentage: number;
}

interface ReservationDetail {
    id: number;
    hotel_id?: number;
    reference: string;
    hotel: string;
    check_in: string;
    check_out: string;
    guests: number;
    status: string;
    subtotal: number;
    discount_total: number;
    total_amount: number;
    is_sustainable: boolean;
    nights: NightRow[];
}

interface Props {
    reservation: ReservationDetail;
    refund_quote: RefundQuote | null;
    can_review: boolean;
    review: {
        rating: number;
        comment: string | null;
        approved: boolean;
    } | null;
}

const money = (n: number) =>
    `AED ${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

function PaymentPanel({ reservation }: { reservation: ReservationDetail }) {
    const { data, setData, post, processing, errors } = useForm({
        method: 'card',
        token: 'tok_visa_ok',
    });
    // Card fields are local only — they are never put into the Inertia payload,
    // mirroring client-side tokenisation. Only { method, token } is submitted.
    const [card, setCard] = useState({ number: '', expiry: '', cvc: '' });
    const [decline, setDecline] = useState(false);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        setData('token', decline ? 'tok_fail' : `tok_${Date.now()}`);
        post(`/reservations/${reservation.id}/pay`, { preserveScroll: true });
    };

    const field =
        'w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20';

    return (
        <form
            onSubmit={submit}
            className="rounded-xl border border-border bg-card p-6"
        >
            <h2 className="flex items-center gap-2 font-serif text-xl font-semibold text-foreground">
                <CreditCard className="h-5 w-5 text-primary" /> Pay{' '}
                {money(reservation.total_amount)}
            </h2>
            {(errors as Record<string, string | undefined>).payment && (
                <p className="mt-3 rounded-lg bg-destructive/10 px-3 py-2 text-sm text-destructive">
                    {(errors as Record<string, string | undefined>).payment}
                </p>
            )}

            <div className="mt-4 grid gap-3 sm:grid-cols-3">
                {['card', 'paypal', 'bank_transfer'].map((m) => (
                    <button
                        key={m}
                        type="button"
                        onClick={() => setData('method', m)}
                        className={`cursor-pointer rounded-lg border px-3 py-2 text-sm font-medium capitalize transition ${
                            data.method === m
                                ? 'border-primary bg-primary/5 text-primary'
                                : 'border-border text-muted-foreground hover:border-primary/40'
                        }`}
                    >
                        {m.replace('_', ' ')}
                    </button>
                ))}
            </div>

            {data.method === 'card' && (
                <div className="mt-4 space-y-3">
                    <input
                        className={field}
                        placeholder="Card number"
                        inputMode="numeric"
                        value={card.number}
                        onChange={(e) =>
                            setCard({ ...card, number: e.target.value })
                        }
                    />
                    <div className="grid grid-cols-2 gap-3">
                        <input
                            className={field}
                            placeholder="MM / YY"
                            value={card.expiry}
                            onChange={(e) =>
                                setCard({ ...card, expiry: e.target.value })
                            }
                        />
                        <input
                            className={field}
                            placeholder="CVC"
                            value={card.cvc}
                            onChange={(e) =>
                                setCard({ ...card, cvc: e.target.value })
                            }
                        />
                    </div>
                </div>
            )}

            <label className="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                <input
                    type="checkbox"
                    checked={decline}
                    onChange={(e) => setDecline(e.target.checked)}
                />
                Simulate a declined card
            </label>

            <button
                type="submit"
                disabled={processing}
                className="mt-5 w-full cursor-pointer rounded-lg bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground transition hover:opacity-90 disabled:opacity-60"
            >
                Pay securely
            </button>
            <p className="mt-3 text-center text-xs text-muted-foreground">
                Card details are tokenised in your browser and never reach our
                servers.
            </p>
        </form>
    );
}

function ReviewPanel({ reservationId }: { reservationId: number }) {
    const { data, setData, post, processing, errors } = useForm({
        rating: 9,
        comment: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(`/reservations/${reservationId}/review`, { preserveScroll: true });
    };

    const field =
        'w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20';

    return (
        <form
            onSubmit={submit}
            className="mt-6 rounded-xl border border-border bg-card p-6"
        >
            <h2 className="flex items-center gap-2 font-serif text-xl font-semibold text-foreground">
                <Star className="h-5 w-5 text-gold" /> Review your stay
            </h2>
            {((errors as Record<string, string | undefined>).review ||
                errors.rating) && (
                <p className="mt-3 rounded-lg bg-destructive/10 px-3 py-2 text-sm text-destructive">
                    {(errors as Record<string, string | undefined>).review ??
                        errors.rating}
                </p>
            )}
            <div className="mt-4">
                <label
                    className="mb-1 block text-sm font-medium text-foreground"
                    htmlFor="rating"
                >
                    Rating: {data.rating} / 10
                </label>
                <input
                    id="rating"
                    type="range"
                    min={1}
                    max={10}
                    value={data.rating}
                    onChange={(e) => setData('rating', Number(e.target.value))}
                    className="w-full accent-gold"
                />
            </div>
            <textarea
                rows={4}
                placeholder="Tell other guests about your stay…"
                value={data.comment}
                onChange={(e) => setData('comment', e.target.value)}
                className={`mt-3 ${field}`}
            />
            <button
                type="submit"
                disabled={processing}
                className="mt-4 cursor-pointer rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition hover:opacity-90 disabled:opacity-60"
            >
                Submit review
            </button>
        </form>
    );
}

export default function ReservationShow({
    reservation,
    refund_quote,
    can_review,
    review,
}: Props) {
    const cancel = useForm({});
    const cancellable =
        reservation.status === 'pending' || reservation.status === 'confirmed';
    const confirmed = reservation.status === 'confirmed';

    const doCancel = () => {
        if (confirm('Cancel this reservation?')) {
            cancel.post(`/reservations/${reservation.id}/cancel`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title={reservation.reference} />

            <section className="relative isolate">
                <img
                    src={hotelImage(reservation.hotel_id ?? reservation.id)}
                    alt=""
                    className="absolute inset-0 -z-10 h-full w-full object-cover"
                />
                <div className="absolute inset-0 -z-10 bg-gradient-to-t from-black/80 to-black/30" />
                <div className="mx-auto flex min-h-56 max-w-5xl flex-col justify-end px-4 py-8 sm:px-6">
                    <p className="text-sm text-white/80">
                        Booking {reservation.reference}
                    </p>
                    <h1 className="mt-1 font-serif text-3xl font-semibold text-white sm:text-4xl">
                        {reservation.hotel}
                    </h1>
                    <p className="mt-2 flex items-center gap-1.5 text-white/85">
                        <CalendarDays className="h-4 w-4" />{' '}
                        {reservation.check_in} → {reservation.check_out} ·{' '}
                        {reservation.guests}{' '}
                        {reservation.guests === 1 ? 'guest' : 'guests'}
                    </p>
                </div>
            </section>

            <div className="mx-auto max-w-5xl px-4 py-10 sm:px-6">
                {confirmed && (
                    <div className="mb-6 flex items-center gap-3 rounded-xl border border-eco/40 bg-eco/10 px-5 py-4">
                        <CheckCircle2 className="h-6 w-6 text-eco" />
                        <div>
                            <p className="font-medium text-foreground">
                                Your booking is confirmed.
                            </p>
                            <p className="text-sm text-muted-foreground">
                                A confirmation has been sent to your email.
                                Reference {reservation.reference}.
                            </p>
                        </div>
                    </div>
                )}

                <div className="grid gap-8 lg:grid-cols-5">
                    <div className="lg:col-span-3">
                        <div className="rounded-xl border border-border bg-card p-6">
                            <div className="flex items-center justify-between">
                                <h2 className="font-serif text-xl font-semibold text-foreground">
                                    Stay summary
                                </h2>
                                {reservation.is_sustainable && (
                                    <span className="inline-flex items-center gap-1 rounded-full bg-eco/15 px-2.5 py-1 text-xs font-medium text-eco">
                                        <Leaf className="h-3.5 w-3.5" />{' '}
                                        Sustainable stay
                                    </span>
                                )}
                            </div>
                            <table className="mt-4 w-full text-sm">
                                <tbody>
                                    {reservation.nights.map((night) => (
                                        <tr
                                            key={night.stay_date}
                                            className="border-b border-border/60"
                                        >
                                            <td className="py-2 text-muted-foreground">
                                                {night.stay_date}
                                            </td>
                                            <td className="py-2 text-right text-foreground">
                                                {money(night.rate)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            <dl className="mt-4 space-y-1.5 text-sm">
                                <div className="flex justify-between text-muted-foreground">
                                    <dt>Subtotal</dt>
                                    <dd>{money(reservation.subtotal)}</dd>
                                </div>
                                {reservation.discount_total > 0 && (
                                    <div className="flex justify-between text-eco">
                                        <dt>Discount</dt>
                                        <dd>
                                            −{' '}
                                            {money(reservation.discount_total)}
                                        </dd>
                                    </div>
                                )}
                                <div className="flex justify-between border-t border-border pt-2 font-serif text-lg font-semibold text-foreground">
                                    <dt>Total</dt>
                                    <dd>{money(reservation.total_amount)}</dd>
                                </div>
                            </dl>
                        </div>

                        {refund_quote && (
                            <div className="mt-6 rounded-xl border border-border bg-card p-6">
                                <h2 className="flex items-center gap-2 font-serif text-xl font-semibold text-foreground">
                                    <ShieldCheck className="h-5 w-5 text-primary" />{' '}
                                    Cancellation
                                </h2>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    {refund_quote.policy_name ??
                                        'Standard policy'}
                                    . Cancel now and you would be refunded{' '}
                                    <span className="font-semibold text-foreground">
                                        {money(refund_quote.amount)}
                                    </span>{' '}
                                    ({refund_quote.percent}%
                                    {refund_quote.within_free_window
                                        ? ' — within the free window'
                                        : ` — ${refund_quote.penalty_percentage}% penalty applies`}
                                    ).
                                </p>
                                {cancellable && (
                                    <button
                                        type="button"
                                        onClick={doCancel}
                                        disabled={cancel.processing}
                                        className="mt-4 cursor-pointer rounded-lg border border-destructive/40 px-4 py-2 text-sm font-medium text-destructive transition hover:bg-destructive/5 disabled:opacity-60"
                                    >
                                        Cancel booking
                                    </button>
                                )}
                            </div>
                        )}

                        {review ? (
                            <div className="mt-6 rounded-xl border border-border bg-card p-6">
                                <h2 className="flex items-center gap-2 font-serif text-xl font-semibold text-foreground">
                                    <Star className="h-5 w-5 text-gold" /> Your
                                    review
                                </h2>
                                <p className="mt-2 text-sm font-medium text-foreground">
                                    {review.rating}/10
                                </p>
                                {review.comment && (
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        “{review.comment}”
                                    </p>
                                )}
                                <p className="mt-2 text-xs text-muted-foreground">
                                    {review.approved
                                        ? 'Published'
                                        : 'Awaiting approval'}
                                </p>
                            </div>
                        ) : can_review ? (
                            <ReviewPanel reservationId={reservation.id} />
                        ) : null}
                    </div>

                    <div className="lg:col-span-2">
                        {reservation.status === 'pending' ? (
                            <PaymentPanel reservation={reservation} />
                        ) : (
                            <div className="rounded-xl border border-border bg-secondary/40 p-6 text-sm text-muted-foreground">
                                <p className="font-medium text-foreground capitalize">
                                    Status: {reservation.status}
                                </p>
                                <p className="mt-1">
                                    No payment is due on this booking.
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
