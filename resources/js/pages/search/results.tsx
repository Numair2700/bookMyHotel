import { Head, Link } from '@inertiajs/react';
import { Briefcase, Leaf, MapPin, Star, Users } from 'lucide-react';
import { hotelCover } from '@/lib/hotel-image';

interface HotelInfo {
    id: number;
    name: string;
    city: string;
    country: string;
    region: string;
    star_rating: number;
    sustainability_certified: boolean;
    has_workspace: boolean;
    wifi_speed_mbps: number | null;
}

interface RoomTypeInfo {
    id: number;
    name: string;
    description: string | null;
    max_occupancy: number;
}

interface SearchResult {
    hotel: HotelInfo;
    room_type: RoomTypeInfo;
    nights: number;
    avg_nightly_rate: number;
    total_price: number;
}

interface Props {
    criteria: {
        destination: string | null;
        check_in: string;
        check_out: string;
        nights: number;
    };
    results: SearchResult[];
    count: number;
}

const money = (n: number) =>
    `AED ${n.toLocaleString(undefined, { maximumFractionDigits: 0 })}`;

export default function SearchResults({ criteria, results, count }: Props) {
    return (
        <>
            <Head title="Search results" />

            <div className="border-b border-border bg-secondary/40">
                <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6">
                    <h1 className="font-serif text-2xl font-semibold text-foreground sm:text-3xl">
                        {criteria.destination
                            ? `Stays in ${criteria.destination}`
                            : 'Available stays'}
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        {criteria.check_in} → {criteria.check_out} ·{' '}
                        {criteria.nights}{' '}
                        {criteria.nights === 1 ? 'night' : 'nights'} · {count}{' '}
                        {count === 1 ? 'room type' : 'room types'} available
                    </p>
                </div>
            </div>

            <div className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
                {results.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-border bg-card p-12 text-center">
                        <p className="font-serif text-lg text-foreground">
                            No rooms available for these dates.
                        </p>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Try a different destination or date range.
                        </p>
                        <Link
                            href="/"
                            className="mt-6 inline-block rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-primary-foreground hover:opacity-90"
                        >
                            New search
                        </Link>
                    </div>
                ) : (
                    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {results.map((result) => (
                            <article
                                key={`${result.hotel.id}-${result.room_type.id}`}
                                className="group flex flex-col overflow-hidden rounded-xl border border-border bg-card shadow-sm transition hover:shadow-md"
                            >
                                <div className="relative aspect-[16/10] overflow-hidden">
                                    <img
                                        src={hotelCover(result.hotel.id)}
                                        alt={result.hotel.name}
                                        className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                    />
                                    {result.hotel.sustainability_certified && (
                                        <span className="absolute top-3 left-3 inline-flex items-center gap-1 rounded-full bg-eco px-2.5 py-1 text-xs font-medium text-eco-foreground">
                                            <Leaf className="h-3.5 w-3.5" />{' '}
                                            Sustainable
                                        </span>
                                    )}
                                </div>

                                <div className="flex flex-1 flex-col p-5">
                                    <div className="flex items-center gap-1 text-gold">
                                        {Array.from({
                                            length: result.hotel.star_rating,
                                        }).map((_, i) => (
                                            <Star
                                                key={i}
                                                className="h-3.5 w-3.5 fill-current"
                                            />
                                        ))}
                                    </div>
                                    <h2 className="mt-2 font-serif text-lg leading-snug font-semibold text-foreground">
                                        {result.hotel.name}
                                    </h2>
                                    <p className="mt-1 flex items-center gap-1.5 text-sm text-muted-foreground">
                                        <MapPin className="h-3.5 w-3.5" />
                                        {result.hotel.city},{' '}
                                        {result.hotel.country}
                                    </p>

                                    <div className="mt-4 rounded-lg bg-secondary/60 p-3">
                                        <p className="text-sm font-medium text-foreground">
                                            {result.room_type.name}
                                        </p>
                                        <div className="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                            <span className="inline-flex items-center gap-1">
                                                <Users className="h-3.5 w-3.5" />{' '}
                                                Sleeps{' '}
                                                {result.room_type.max_occupancy}
                                            </span>
                                            {result.hotel.has_workspace && (
                                                <span className="inline-flex items-center gap-1">
                                                    <Briefcase className="h-3.5 w-3.5" />{' '}
                                                    Workspace
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    <div className="mt-auto flex items-end justify-between pt-5">
                                        <div>
                                            <p className="font-serif text-xl font-semibold text-foreground">
                                                {money(result.avg_nightly_rate)}
                                                <span className="text-sm font-normal text-muted-foreground">
                                                    {' '}
                                                    / night
                                                </span>
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {money(result.total_price)}{' '}
                                                total · {result.nights}{' '}
                                                {result.nights === 1
                                                    ? 'night'
                                                    : 'nights'}
                                            </p>
                                        </div>
                                        <Link
                                            href={`/hotels/${result.hotel.id}?check_in=${criteria.check_in}&check_out=${criteria.check_out}`}
                                            className="cursor-pointer rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:opacity-90"
                                        >
                                            View
                                        </Link>
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
