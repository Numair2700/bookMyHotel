import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    Briefcase,
    Leaf,
    MapPin,
    ShieldCheck,
    Star,
    Tag,
    Users,
    Wifi,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { hotelCover, roomImage } from '@/lib/hotel-image';

interface HotelInfo {
    id: number;
    name: string;
    chain: string | null;
    city: string;
    country: string;
    region: string;
    address: string;
    description: string;
    star_rating: number;
    wifi_speed_mbps: number | null;
    has_workspace: boolean;
    sustainability_certified: boolean;
}

interface RoomTypeInfo {
    id: number;
    name: string;
    description: string | null;
    max_occupancy: number;
    base_rate: number;
    total_rooms: number;
    // Priced for the searched dates; nights/total are null when browsing
    // without dates, in which case nightly_rate falls back to the base rate.
    nightly_rate: number;
    nights: number | null;
    total_price: number | null;
    // null when browsing without dates; true/false once a stay is searched.
    available_for_dates: boolean | null;
}

interface Props {
    hotel: HotelInfo;
    room_types: RoomTypeInfo[];
    cancellation_policies: {
        id: number;
        name: string;
        free_cancellation_hours: number;
        penalty_percentage: number;
    }[];
    promotions: {
        code: string;
        description: string;
        discount_type: string;
        discount_value: number;
    }[];
    reviews: {
        id: number;
        rating: number;
        comment: string | null;
        guest: string | null;
        created_at: string | null;
    }[];
    services: {
        id: number;
        name: string;
        category: string;
        description: string | null;
        price: number;
    }[];
    average_rating: number | null;
    booking: {
        check_in: string | null;
        check_out: string | null;
        guests: number | null;
    };
}

const money = (n: number) =>
    `AED ${n.toLocaleString(undefined, { maximumFractionDigits: 0 })}`;

function isoPlus(days: number): string {
    const d = new Date();
    d.setDate(d.getDate() + days);

    return d.toISOString().slice(0, 10);
}

function BookingWidget({
    hotelId,
    roomTypes,
    booking,
    isAuthed,
}: {
    hotelId: number;
    roomTypes: RoomTypeInfo[];
    booking: Props['booking'];
    isAuthed: boolean;
}) {
    // A room whose stay could not be fully priced for the searched dates is not
    // bookable; `null` means we are browsing without dates (everything shown).
    const bookable = roomTypes.filter((r) => r.available_for_dates !== false);
    const datesSearched = roomTypes.some((r) => r.available_for_dates !== null);
    const noneAvailable = datesSearched && bookable.length === 0;

    const { data, setData, post, processing, errors } = useForm({
        room_type_id: bookable[0]?.id ?? roomTypes[0]?.id ?? 0,
        check_in: booking.check_in ?? isoPlus(1),
        check_out: booking.check_out ?? isoPlus(4),
        guests: booking.guests ?? 2,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/reservations', { preserveScroll: true });
    };

    const selected =
        roomTypes.find((r) => r.id === data.room_type_id) ??
        bookable[0] ??
        roomTypes[0];
    const field =
        'w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20';
    const label =
        'mb-1 block text-xs font-medium tracking-wide text-muted-foreground uppercase';

    if (noneAvailable) {
        return (
            <div className="rounded-xl border border-border bg-card p-5 text-center shadow-sm">
                <p className="font-serif text-lg font-semibold text-foreground">
                    No rooms available
                </p>
                <p className="mt-2 text-sm text-muted-foreground">
                    This hotel is fully booked for your selected dates. Try a
                    different date range.
                </p>
                <Link
                    href="/"
                    className="mt-4 inline-block rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition hover:opacity-90"
                >
                    New search
                </Link>
            </div>
        );
    }

    return (
        <form
            onSubmit={submit}
            className="rounded-xl border border-border bg-card p-5 shadow-sm"
        >
            <p className="font-serif text-lg font-semibold text-foreground">
                {selected ? money(selected.nightly_rate) : ''}
                <span className="text-sm font-normal text-muted-foreground">
                    {' '}
                    {selected?.nights ? '/ night' : 'from / night'}
                </span>
            </p>
            {selected?.nights && selected.total_price !== null && (
                <p className="mt-0.5 text-xs text-muted-foreground">
                    {money(selected.total_price)} total · {selected.nights}{' '}
                    {selected.nights === 1 ? 'night' : 'nights'} for your dates
                </p>
            )}

            <div className="mt-4 space-y-3">
                <div>
                    <label className={label} htmlFor="room_type_id">
                        Room type
                    </label>
                    <select
                        id="room_type_id"
                        value={data.room_type_id}
                        onChange={(e) =>
                            setData('room_type_id', Number(e.target.value))
                        }
                        className={`${field} cursor-pointer`}
                    >
                        {bookable.map((room) => (
                            <option key={room.id} value={room.id}>
                                {room.name}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="grid grid-cols-2 gap-3">
                    <div>
                        <label className={label} htmlFor="check_in">
                            Check in
                        </label>
                        <input
                            id="check_in"
                            type="date"
                            required
                            min={isoPlus(0)}
                            value={data.check_in}
                            onChange={(e) =>
                                setData('check_in', e.target.value)
                            }
                            className={field}
                        />
                    </div>
                    <div>
                        <label className={label} htmlFor="check_out">
                            Check out
                        </label>
                        <input
                            id="check_out"
                            type="date"
                            required
                            min={data.check_in}
                            value={data.check_out}
                            onChange={(e) =>
                                setData('check_out', e.target.value)
                            }
                            className={field}
                        />
                    </div>
                </div>
                <div>
                    <label className={label} htmlFor="guests">
                        Guests
                    </label>
                    <select
                        id="guests"
                        value={data.guests}
                        onChange={(e) =>
                            setData('guests', Number(e.target.value))
                        }
                        className={`${field} cursor-pointer`}
                    >
                        {[1, 2, 3, 4, 5, 6].map((n) => (
                            <option key={n} value={n}>
                                {n} {n === 1 ? 'guest' : 'guests'}
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            {((errors as Record<string, string | undefined>).availability ||
                errors.guests) && (
                <p className="mt-3 rounded-lg bg-destructive/10 px-3 py-2 text-sm text-destructive">
                    {(errors as Record<string, string | undefined>)
                        .availability ?? errors.guests}
                </p>
            )}

            {isAuthed ? (
                <button
                    type="submit"
                    disabled={processing}
                    className="mt-4 w-full cursor-pointer rounded-lg bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground transition hover:opacity-90 disabled:opacity-60"
                >
                    Reserve
                </button>
            ) : (
                <Link
                    href="/login"
                    className="mt-4 block w-full rounded-lg bg-primary px-5 py-3 text-center text-sm font-semibold text-primary-foreground transition hover:opacity-90"
                >
                    Sign in to book
                </Link>
            )}
            <p className="mt-2 text-center text-xs text-muted-foreground">
                You won’t be charged until you confirm.
            </p>

            <input type="hidden" name="hotel_id" value={hotelId} />
        </form>
    );
}

export default function HotelShow({
    hotel,
    room_types,
    cancellation_policies,
    promotions,
    reviews,
    services,
    average_rating,
    booking,
}: Props) {
    const { auth } = usePage<{ auth: { user: unknown | null } }>().props;
    const policy = cancellation_policies[0];
    const offer = promotions[0];

    return (
        <>
            <Head title={hotel.name} />

            {/* Hero */}
            <section className="relative isolate">
                <img
                    src={hotelCover(hotel.id)}
                    alt={hotel.name}
                    className="absolute inset-0 -z-10 h-full w-full object-cover"
                />
                <div className="absolute inset-0 -z-10 bg-gradient-to-t from-black/80 via-black/40 to-black/30" />
                <div className="mx-auto flex min-h-[22rem] max-w-6xl flex-col justify-end px-4 py-10 sm:px-6">
                    <div className="flex flex-wrap items-center gap-3">
                        <div className="flex items-center gap-1 text-gold">
                            {Array.from({ length: hotel.star_rating }).map(
                                (_, i) => (
                                    <Star
                                        key={i}
                                        className="h-4 w-4 fill-current"
                                    />
                                ),
                            )}
                        </div>
                        {hotel.chain && (
                            <span className="text-sm text-white/80">
                                {hotel.chain}
                            </span>
                        )}
                        {hotel.sustainability_certified && (
                            <span className="inline-flex items-center gap-1 rounded-full bg-eco px-2.5 py-1 text-xs font-medium text-eco-foreground">
                                <Leaf className="h-3.5 w-3.5" /> Sustainable
                            </span>
                        )}
                    </div>
                    <h1 className="mt-3 font-serif text-4xl leading-tight font-semibold text-white sm:text-5xl">
                        {hotel.name}
                    </h1>
                    <p className="mt-2 flex items-center gap-1.5 text-white/85">
                        <MapPin className="h-4 w-4" /> {hotel.address}
                    </p>
                </div>
            </section>

            <div className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
                <div className="grid gap-10 lg:grid-cols-3">
                    {/* Main */}
                    <div className="space-y-10 lg:col-span-2">
                        <section>
                            <div className="flex items-center gap-3">
                                <h2 className="font-serif text-2xl font-semibold text-foreground">
                                    About this hotel
                                </h2>
                                {average_rating !== null && (
                                    <span className="rounded-md bg-primary px-2 py-1 text-sm font-semibold text-primary-foreground">
                                        {average_rating} / 10
                                    </span>
                                )}
                            </div>
                            <p className="mt-3 leading-relaxed text-muted-foreground">
                                {hotel.description}
                            </p>
                            <div className="mt-4 flex flex-wrap gap-2">
                                {hotel.wifi_speed_mbps && (
                                    <span className="inline-flex items-center gap-1.5 rounded-full border border-border px-3 py-1 text-sm text-foreground">
                                        <Wifi className="h-3.5 w-3.5 text-gold" />{' '}
                                        {hotel.wifi_speed_mbps} Mbps Wi-Fi
                                    </span>
                                )}
                                {hotel.has_workspace && (
                                    <span className="inline-flex items-center gap-1.5 rounded-full border border-border px-3 py-1 text-sm text-foreground">
                                        <Briefcase className="h-3.5 w-3.5 text-gold" />{' '}
                                        Dedicated workspace
                                    </span>
                                )}
                            </div>
                        </section>

                        <section>
                            <h2 className="font-serif text-2xl font-semibold text-foreground">
                                Room types
                            </h2>
                            <div className="mt-4 space-y-4">
                                {room_types.map((room) => (
                                    <div
                                        key={room.id}
                                        className={`flex flex-col overflow-hidden rounded-xl border border-border bg-card sm:flex-row${
                                            room.available_for_dates === false
                                                ? 'opacity-70'
                                                : ''
                                        }`}
                                    >
                                        <img
                                            src={roomImage(room.id)}
                                            alt={room.name}
                                            className={`h-44 w-full shrink-0 object-cover sm:h-auto sm:w-48${
                                                room.available_for_dates ===
                                                false
                                                    ? 'grayscale'
                                                    : ''
                                            }`}
                                        />
                                        <div className="flex flex-1 flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <h3 className="font-medium text-foreground">
                                                    {room.name}
                                                </h3>
                                                {room.description && (
                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                        {room.description}
                                                    </p>
                                                )}
                                                <p className="mt-2 inline-flex items-center gap-1 text-sm text-muted-foreground">
                                                    <Users className="h-3.5 w-3.5" />{' '}
                                                    Sleeps up to{' '}
                                                    {room.max_occupancy}
                                                </p>
                                            </div>
                                            <div className="shrink-0 sm:text-right">
                                                {room.available_for_dates ===
                                                false ? (
                                                    <span className="inline-flex rounded-full bg-secondary px-3 py-1 text-xs font-medium text-muted-foreground">
                                                        Not available for these
                                                        dates
                                                    </span>
                                                ) : (
                                                    <>
                                                        <p className="font-serif text-xl font-semibold text-foreground">
                                                            {money(
                                                                room.nightly_rate,
                                                            )}
                                                            <span className="text-sm font-normal text-muted-foreground">
                                                                {' '}
                                                                / night
                                                            </span>
                                                        </p>
                                                        {room.nights &&
                                                        room.total_price !==
                                                            null ? (
                                                            <p className="text-xs text-muted-foreground">
                                                                {money(
                                                                    room.total_price,
                                                                )}{' '}
                                                                total ·{' '}
                                                                {room.nights}{' '}
                                                                {room.nights ===
                                                                1
                                                                    ? 'night'
                                                                    : 'nights'}
                                                            </p>
                                                        ) : (
                                                            <p className="text-xs text-muted-foreground">
                                                                from / night
                                                            </p>
                                                        )}
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section>
                            <h2 className="font-serif text-2xl font-semibold text-foreground">
                                Guest reviews
                            </h2>
                            {reviews.length === 0 ? (
                                <p className="mt-3 text-sm text-muted-foreground">
                                    No reviews yet for this hotel.
                                </p>
                            ) : (
                                <div className="mt-4 space-y-4">
                                    {reviews.map((review) => (
                                        <figure
                                            key={review.id}
                                            className="rounded-xl border border-border bg-card p-5"
                                        >
                                            <div className="flex items-center justify-between">
                                                <figcaption className="text-sm font-medium text-foreground">
                                                    {review.guest ??
                                                        'Verified guest'}
                                                </figcaption>
                                                <span className="rounded-md bg-secondary px-2 py-0.5 text-sm font-semibold text-foreground">
                                                    {review.rating}/10
                                                </span>
                                            </div>
                                            {review.comment && (
                                                <blockquote className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                                    “{review.comment}”
                                                </blockquote>
                                            )}
                                        </figure>
                                    ))}
                                </div>
                            )}
                        </section>
                    </div>

                    {/* Sidebar */}
                    <aside className="space-y-5 lg:sticky lg:top-24 lg:self-start">
                        <BookingWidget
                            hotelId={hotel.id}
                            roomTypes={room_types}
                            booking={booking}
                            isAuthed={Boolean(auth?.user)}
                        />

                        {policy && (
                            <div className="rounded-xl border border-border bg-card p-5">
                                <h3 className="flex items-center gap-2 font-medium text-foreground">
                                    <ShieldCheck className="h-4 w-4 text-gold" />{' '}
                                    Cancellation
                                </h3>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    {policy.name}. After the free window, a{' '}
                                    {policy.penalty_percentage}% penalty
                                    applies.
                                </p>
                            </div>
                        )}

                        {offer && (
                            <div className="rounded-xl border border-gold/40 bg-gold/5 p-5">
                                <h3 className="flex items-center gap-2 font-medium text-foreground">
                                    <Tag className="h-4 w-4 text-gold" />{' '}
                                    Current offer
                                </h3>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    {offer.description}
                                </p>
                                <p className="mt-2 inline-block rounded-md border border-dashed border-gold px-2 py-1 font-mono text-sm text-foreground">
                                    {offer.code}
                                </p>
                            </div>
                        )}

                        {services.length > 0 && (
                            <div className="rounded-xl border border-border bg-card p-5">
                                <h3 className="font-medium text-foreground">
                                    Services on site
                                </h3>
                                <ul className="mt-3 space-y-2 text-sm">
                                    {services.map((service) => (
                                        <li
                                            key={service.id}
                                            className="flex items-center justify-between"
                                        >
                                            <span className="text-muted-foreground">
                                                {service.name}
                                            </span>
                                            <span className="font-medium text-foreground">
                                                {money(service.price)}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </aside>
                </div>
            </div>
        </>
    );
}
