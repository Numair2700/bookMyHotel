import StaffNav from '@/components/staff-nav';
import { Head, router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { type FormEvent } from 'react';

interface PromotionRow {
    id: number;
    hotel: string | null;
    code: string;
    description: string;
    discount_type: string;
    discount_value: number;
    valid_from: string;
    valid_to: string;
    active: boolean;
}

interface Props {
    promotions: PromotionRow[];
    hotels: { id: number; name: string }[];
}

function isoPlus(days: number): string {
    const d = new Date();
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
}

export default function ManagerPromotions({ promotions, hotels }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        hotel_id: hotels[0]?.id ?? 0,
        code: '',
        description: '',
        discount_type: 'percentage',
        discount_value: 10,
        valid_from: isoPlus(0),
        valid_to: isoPlus(90),
        active: true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/manager/promotions', { preserveScroll: true, onSuccess: () => reset('code', 'description') });
    };

    const withdraw = (id: number) => {
        if (confirm('Withdraw this promotion?')) {
            router.delete(`/manager/promotions/${id}`, { preserveScroll: true });
        }
    };

    const field = 'w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20';
    const label = 'mb-1 block text-xs font-medium tracking-wide text-muted-foreground uppercase';

    return (
        <>
            <Head title="Promotions" />
            <StaffNav area="manager" />

            <div className="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <h1 className="font-serif text-3xl font-semibold text-foreground">Promotions</h1>
                    <p className="mt-1 text-sm text-muted-foreground">{promotions.length} promotions.</p>

                    <div className="mt-6 space-y-3">
                        {promotions.map((promotion) => (
                            <div
                                key={promotion.id}
                                className="flex items-center justify-between gap-4 rounded-xl border border-border bg-card p-4"
                            >
                                <div className="min-w-0">
                                    <div className="flex items-center gap-2">
                                        <span className="font-mono text-sm font-semibold text-foreground">
                                            {promotion.code}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {promotion.discount_type === 'percentage'
                                                ? `${promotion.discount_value}% off`
                                                : `AED ${promotion.discount_value} off`}
                                        </span>
                                        {!promotion.active && (
                                            <span className="rounded-full bg-secondary px-2 py-0.5 text-xs text-muted-foreground">
                                                inactive
                                            </span>
                                        )}
                                    </div>
                                    <p className="mt-0.5 truncate text-sm text-muted-foreground">{promotion.description}</p>
                                    <p className="text-xs text-muted-foreground">
                                        {promotion.hotel} · {promotion.valid_from} → {promotion.valid_to}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => withdraw(promotion.id)}
                                    className="shrink-0 cursor-pointer rounded-lg border border-destructive/40 p-2 text-destructive transition hover:bg-destructive/5"
                                    aria-label="Withdraw promotion"
                                >
                                    <Trash2 className="h-4 w-4" />
                                </button>
                            </div>
                        ))}
                    </div>
                </div>

                <form onSubmit={submit} className="h-fit rounded-xl border border-border bg-card p-6 lg:sticky lg:top-24">
                    <h2 className="font-serif text-lg font-semibold text-foreground">New promotion</h2>
                    <div className="mt-4 space-y-3">
                        <div>
                            <label className={label} htmlFor="hotel_id">Hotel</label>
                            <select id="hotel_id" value={data.hotel_id} onChange={(e) => setData('hotel_id', Number(e.target.value))} className={`${field} cursor-pointer`}>
                                {hotels.map((hotel) => (
                                    <option key={hotel.id} value={hotel.id}>{hotel.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className={label} htmlFor="code">Code</label>
                            <input id="code" value={data.code} onChange={(e) => setData('code', e.target.value)} className={field} />
                            {errors.code && <p className="mt-1 text-xs text-destructive">{errors.code}</p>}
                        </div>
                        <div>
                            <label className={label} htmlFor="description">Description</label>
                            <input id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} className={field} />
                            {errors.description && <p className="mt-1 text-xs text-destructive">{errors.description}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className={label} htmlFor="discount_type">Type</label>
                                <select id="discount_type" value={data.discount_type} onChange={(e) => setData('discount_type', e.target.value)} className={`${field} cursor-pointer`}>
                                    <option value="percentage">Percentage</option>
                                    <option value="fixed">Fixed</option>
                                </select>
                            </div>
                            <div>
                                <label className={label} htmlFor="discount_value">Value</label>
                                <input id="discount_value" type="number" min={0} value={data.discount_value} onChange={(e) => setData('discount_value', Number(e.target.value))} className={field} />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className={label} htmlFor="valid_from">From</label>
                                <input id="valid_from" type="date" value={data.valid_from} onChange={(e) => setData('valid_from', e.target.value)} className={field} />
                            </div>
                            <div>
                                <label className={label} htmlFor="valid_to">To</label>
                                <input id="valid_to" type="date" value={data.valid_to} onChange={(e) => setData('valid_to', e.target.value)} className={field} />
                            </div>
                        </div>
                    </div>
                    <button type="submit" disabled={processing} className="mt-4 w-full cursor-pointer rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:opacity-60">
                        Create promotion
                    </button>
                </form>
            </div>
        </>
    );
}
