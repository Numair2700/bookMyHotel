import StaffNav from '@/components/staff-nav';
import { Head } from '@inertiajs/react';
import { BedDouble, Coins, TrendingUp } from 'lucide-react';

interface HotelBreakdown {
    hotel_id: number;
    hotel: string;
    room_nights: number;
    room_revenue: number;
    average_daily_rate: number | null;
}

interface Summary {
    room_nights: number;
    room_revenue: number;
    average_daily_rate: number | null;
}

interface Props {
    from: string;
    to: string;
    summary: Summary;
    per_hotel: HotelBreakdown[];
}

const money = (n: number) => `AED ${n.toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
const adr = (n: number | null) => (n === null ? '—' : `AED ${n.toLocaleString(undefined, { maximumFractionDigits: 2 })}`);

export default function AdminAnalytics({ from, to, summary, per_hotel }: Props) {
    const tiles = [
        { label: 'Booked room nights', value: summary.room_nights.toLocaleString(), icon: BedDouble },
        { label: 'Room revenue', value: money(summary.room_revenue), icon: Coins },
        { label: 'Average daily rate', value: adr(summary.average_daily_rate), icon: TrendingUp },
    ];

    return (
        <>
            <Head title="Analytics" />
            <StaffNav area="admin" />

            <div className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="font-serif text-3xl font-semibold text-foreground">Analytics</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Performance across all hotels · {from} → {to}
                        </p>
                    </div>
                </div>

                <div className="mt-6 grid gap-5 sm:grid-cols-3">
                    {tiles.map((tile) => (
                        <div key={tile.label} className="rounded-xl border border-border bg-card p-6">
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">{tile.label}</p>
                                <tile.icon className="h-5 w-5 text-muted-foreground" />
                            </div>
                            <p className="mt-3 font-serif text-4xl font-semibold text-foreground">{tile.value}</p>
                        </div>
                    ))}
                </div>

                <div className="mt-10 rounded-xl border border-border bg-card">
                    <div className="border-b border-border px-6 py-4">
                        <h2 className="font-serif text-xl font-semibold text-foreground">Per-hotel breakdown</h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[36rem] text-left text-sm">
                            <thead>
                                <tr className="text-xs tracking-wide text-muted-foreground uppercase">
                                    <th className="px-6 py-3 font-medium">Hotel</th>
                                    <th className="px-6 py-3 text-right font-medium">Room nights</th>
                                    <th className="px-6 py-3 text-right font-medium">Room revenue</th>
                                    <th className="px-6 py-3 text-right font-medium">ADR</th>
                                </tr>
                            </thead>
                            <tbody>
                                {per_hotel.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-6 py-8 text-center text-muted-foreground">
                                            No confirmed stays in this period.
                                        </td>
                                    </tr>
                                ) : (
                                    per_hotel.map((row) => (
                                        <tr key={row.hotel_id} className="border-t border-border">
                                            <td className="px-6 py-3 font-medium text-foreground">{row.hotel}</td>
                                            <td className="px-6 py-3 text-right text-foreground">{row.room_nights}</td>
                                            <td className="px-6 py-3 text-right text-foreground">{money(row.room_revenue)}</td>
                                            <td className="px-6 py-3 text-right text-foreground">{adr(row.average_daily_rate)}</td>
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
