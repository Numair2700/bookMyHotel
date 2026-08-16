import { Head, router } from '@inertiajs/react';
import { Leaf, MapPin, ShieldCheck, Sparkles } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';

function isoPlus(days: number): string {
    const d = new Date();
    d.setDate(d.getDate() + days);

    return d.toISOString().slice(0, 10);
}

export default function Welcome() {
    const [form, setForm] = useState({
        destination: '',
        check_in: isoPlus(1),
        check_out: isoPlus(4),
        guests: 2,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/search', form);
    };

    const field =
        'w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm text-foreground shadow-sm outline-none transition focus:border-gold focus:ring-2 focus:ring-gold/30';
    const label =
        'mb-1.5 block text-xs font-medium tracking-wide text-muted-foreground uppercase';

    return (
        <>
            <Head title="Five-star stays across Asia & Europe" />

            {/* Hero */}
            <section className="relative isolate overflow-hidden">
                <img
                    src="/images/hotels/2.jpg"
                    alt=""
                    className="absolute inset-0 -z-10 h-full w-full object-cover"
                />
                <div className="absolute inset-0 -z-10 bg-gradient-to-b from-black/55 via-black/40 to-black/70" />

                <div className="mx-auto max-w-6xl px-4 pt-20 pb-40 sm:px-6 sm:pt-28 lg:pt-32">
                    <p className="flex items-center gap-2 text-sm font-medium tracking-wide text-white/80 uppercase">
                        <Sparkles className="h-4 w-4 text-gold" /> Four
                        five-star chains · Asia &amp; Europe
                    </p>
                    <h1 className="mt-4 max-w-2xl font-serif text-4xl leading-[1.05] font-semibold text-white sm:text-5xl lg:text-6xl">
                        Stay somewhere worth remembering.
                    </h1>
                    <p className="mt-5 max-w-xl text-lg leading-relaxed text-white/85">
                        Search live availability across Marriott, Hilton, Hyatt
                        and Four Seasons — transparent cancellation, and rewards
                        every time you choose a sustainable stay.
                    </p>
                </div>
            </section>

            {/* Search card — overlaps the hero (z-10 keeps it above the hero image) */}
            <div className="relative z-10 mx-auto -mt-28 max-w-5xl px-4 sm:px-6">
                <form
                    onSubmit={submit}
                    className="rounded-2xl border border-border bg-card p-4 shadow-xl shadow-black/5 sm:p-6"
                >
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="lg:col-span-1">
                            <label className={label} htmlFor="destination">
                                Destination
                            </label>
                            <input
                                id="destination"
                                type="text"
                                placeholder="City or country"
                                value={form.destination}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        destination: e.target.value,
                                    })
                                }
                                className={field}
                            />
                        </div>
                        <div>
                            <label className={label} htmlFor="check_in">
                                Check in
                            </label>
                            <input
                                id="check_in"
                                type="date"
                                min={isoPlus(0)}
                                value={form.check_in}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        check_in: e.target.value,
                                    })
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
                                min={form.check_in}
                                value={form.check_out}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        check_out: e.target.value,
                                    })
                                }
                                className={field}
                            />
                        </div>
                        <div>
                            <label className={label} htmlFor="guests">
                                Guests
                            </label>
                            <select
                                id="guests"
                                value={form.guests}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        guests: Number(e.target.value),
                                    })
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
                    <button
                        type="submit"
                        className="mt-4 w-full cursor-pointer rounded-lg bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground transition hover:opacity-90 sm:w-auto sm:px-8"
                    >
                        Search availability
                    </button>
                </form>
            </div>

            {/* Trust strip */}
            <section className="mx-auto max-w-6xl px-4 py-20 sm:px-6">
                <div className="grid gap-8 sm:grid-cols-3">
                    {[
                        {
                            icon: MapPin,
                            title: 'One search, every chain',
                            body: 'Compare rooms across four five-star groups in Asia and Europe from a single calendar.',
                        },
                        {
                            icon: ShieldCheck,
                            title: 'Transparent cancellation',
                            body: 'See the exact refund and policy before you pay — no surprises after you book.',
                        },
                        {
                            icon: Leaf,
                            title: 'Rewarded for sustainability',
                            body: 'Earn reward points whenever you choose a sustainability-certified property.',
                        },
                    ].map((f) => (
                        <div
                            key={f.title}
                            className="rounded-xl border border-border bg-card p-6"
                        >
                            <span className="grid h-11 w-11 place-items-center rounded-lg bg-secondary text-gold">
                                <f.icon className="h-5 w-5" />
                            </span>
                            <h3 className="mt-4 font-serif text-lg font-semibold text-foreground">
                                {f.title}
                            </h3>
                            <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                {f.body}
                            </p>
                        </div>
                    ))}
                </div>
            </section>
        </>
    );
}
