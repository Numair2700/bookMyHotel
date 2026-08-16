import { Head, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

export default function Contact() {
    const { data, setData, post, processing, errors, reset, recentlySuccessful } = useForm({
        name: '',
        email: '',
        subject: '',
        message: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/enquiries', { preserveScroll: true, onSuccess: () => reset() });
    };

    const field =
        'w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm text-foreground shadow-sm outline-none transition focus:border-gold focus:ring-2 focus:ring-gold/30';
    const label = 'mb-1.5 block text-sm font-medium text-foreground';
    const error = 'mt-1 text-sm text-destructive';

    return (
        <>
            <Head title="Contact us" />

            <div className="mx-auto max-w-2xl px-4 py-16 sm:px-6">
                <h1 className="font-serif text-3xl font-semibold text-foreground">Get in touch</h1>
                <p className="mt-2 text-muted-foreground">
                    Questions about a booking, a group stay or anything else? Send us a note and we’ll reply by email.
                </p>

                {recentlySuccessful && (
                    <div className="mt-6 rounded-lg border border-eco/40 bg-eco/10 px-4 py-3 text-sm text-foreground">
                        Thanks for getting in touch — we’ll be in contact shortly.
                    </div>
                )}

                <form onSubmit={submit} className="mt-8 space-y-5">
                    <div className="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label className={label} htmlFor="name">
                                Name
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className={field}
                            />
                            {errors.name && <p className={error}>{errors.name}</p>}
                        </div>
                        <div>
                            <label className={label} htmlFor="email">
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className={field}
                            />
                            {errors.email && <p className={error}>{errors.email}</p>}
                        </div>
                    </div>
                    <div>
                        <label className={label} htmlFor="subject">
                            Subject
                        </label>
                        <input
                            id="subject"
                            type="text"
                            value={data.subject}
                            onChange={(e) => setData('subject', e.target.value)}
                            className={field}
                        />
                        {errors.subject && <p className={error}>{errors.subject}</p>}
                    </div>
                    <div>
                        <label className={label} htmlFor="message">
                            Message
                        </label>
                        <textarea
                            id="message"
                            rows={5}
                            value={data.message}
                            onChange={(e) => setData('message', e.target.value)}
                            className={field}
                        />
                        {errors.message && <p className={error}>{errors.message}</p>}
                    </div>
                    <button
                        type="submit"
                        disabled={processing}
                        className="cursor-pointer rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-primary-foreground transition hover:opacity-90 disabled:opacity-60"
                    >
                        Send message
                    </button>
                </form>
            </div>
        </>
    );
}
