import StaffNav from '@/components/staff-nav';
import { Head } from '@inertiajs/react';
import { Leaf, Star } from 'lucide-react';

interface HotelRow {
    id: number;
    name: string;
    chain: string | null;
    city: string;
    country: string;
    region: string;
    star_rating: number;
    sustainability_certified: boolean;
    reservations_count: number;
}

interface Props {
    hotels: HotelRow[];
}

export default function AdminHotelsIndex({ hotels }: Props) {
    return (
        <>
            <Head title="Manage hotels" />
            <StaffNav area="admin" />

            <div className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
                <h1 className="font-serif text-3xl font-semibold text-foreground">Hotels</h1>
                <p className="mt-1 text-sm text-muted-foreground">{hotels.length} properties across the four chains.</p>

                <div className="mt-6 rounded-xl border border-border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[42rem] text-left text-sm">
                            <thead>
                                <tr className="text-xs tracking-wide text-muted-foreground uppercase">
                                    <th className="px-6 py-3 font-medium">Hotel</th>
                                    <th className="px-6 py-3 font-medium">Location</th>
                                    <th className="px-6 py-3 font-medium">Chain</th>
                                    <th className="px-6 py-3 text-right font-medium">Bookings</th>
                                </tr>
                            </thead>
                            <tbody>
                                {hotels.map((hotel) => (
                                    <tr key={hotel.id} className="border-t border-border">
                                        <td className="px-6 py-3">
                                            <div className="flex items-center gap-2 font-medium text-foreground">
                                                {hotel.name}
                                                {hotel.sustainability_certified && (
                                                    <Leaf className="h-3.5 w-3.5 text-eco" />
                                                )}
                                            </div>
                                            <div className="mt-0.5 flex items-center gap-0.5 text-gold">
                                                {Array.from({ length: hotel.star_rating }).map((_, i) => (
                                                    <Star key={i} className="h-3 w-3 fill-current" />
                                                ))}
                                            </div>
                                        </td>
                                        <td className="px-6 py-3 text-muted-foreground">
                                            {hotel.city}, {hotel.country}
                                            <span className="block text-xs capitalize">{hotel.region}</span>
                                        </td>
                                        <td className="px-6 py-3 text-muted-foreground">{hotel.chain}</td>
                                        <td className="px-6 py-3 text-right font-medium text-foreground">
                                            {hotel.reservations_count}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    );
}
