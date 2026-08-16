import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

interface AuthUser {
    name: string;
    role?: string;
}

function Brand() {
    return (
        <Link href="/" className="group flex items-center">
            <span className="font-serif text-xl leading-none font-semibold tracking-tight text-foreground">
                Book<span className="text-gold">My</span>Hotel
            </span>
        </Link>
    );
}

export default function GuestLayout({ children }: PropsWithChildren) {
    const { auth } = usePage<{ auth: { user: AuthUser | null } }>().props;
    const user = auth?.user ?? null;

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <header className="sticky top-0 z-40 border-b border-border/70 bg-background/85 backdrop-blur">
                <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                    <Brand />

                    <nav className="hidden items-center gap-7 text-sm font-medium text-muted-foreground md:flex">
                        <Link
                            href="/"
                            className="transition-colors hover:text-foreground"
                        >
                            Home
                        </Link>
                        <Link
                            href="/contact"
                            className="transition-colors hover:text-foreground"
                        >
                            Contact
                        </Link>
                    </nav>

                    <div className="flex items-center gap-2 text-sm">
                        {user ? (
                            <>
                                {user.role === 'admin' && (
                                    <Link
                                        href="/admin/analytics"
                                        className="hidden rounded-md px-3 py-2 font-medium text-muted-foreground transition-colors hover:text-foreground sm:inline-block"
                                    >
                                        Admin
                                    </Link>
                                )}
                                {user.role === 'manager' && (
                                    <Link
                                        href="/manager/availability"
                                        className="hidden rounded-md px-3 py-2 font-medium text-muted-foreground transition-colors hover:text-foreground sm:inline-block"
                                    >
                                        Manager
                                    </Link>
                                )}
                                <Link
                                    href="/rewards"
                                    className="hidden rounded-md px-3 py-2 font-medium text-muted-foreground transition-colors hover:text-foreground sm:inline-block"
                                >
                                    Rewards
                                </Link>
                                <Link
                                    href="/reservations"
                                    className="rounded-md px-3 py-2 font-medium text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    My bookings
                                </Link>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    className="cursor-pointer rounded-md border border-border px-4 py-2 font-medium text-foreground transition-colors hover:bg-secondary"
                                >
                                    Sign out
                                </Link>
                            </>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="rounded-md px-3 py-2 font-medium text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    Sign in
                                </Link>
                                <Link
                                    href="/register"
                                    className="rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground transition-opacity hover:opacity-90"
                                >
                                    Create account
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <footer className="mt-16 border-t border-border/70 bg-secondary/40">
                <div className="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-3">
                    <div>
                        <Brand />
                        <p className="mt-4 max-w-xs text-sm leading-relaxed text-muted-foreground">
                            One place to book five-star stays across Asia and
                            Europe — with transparent cancellation and rewards
                            for sustainable choices.
                        </p>
                    </div>
                    <div className="text-sm">
                        <h3 className="font-medium text-foreground">Explore</h3>
                        <ul className="mt-3 space-y-2 text-muted-foreground">
                            <li>
                                <Link
                                    href="/"
                                    className="transition-colors hover:text-foreground"
                                >
                                    Search hotels
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/contact"
                                    className="transition-colors hover:text-foreground"
                                >
                                    Contact us
                                </Link>
                            </li>
                        </ul>
                    </div>
                    <div className="text-sm">
                        <h3 className="font-medium text-foreground">Chains</h3>
                        <ul className="mt-3 space-y-2 text-muted-foreground">
                            <li>Marriott · Hilton</li>
                            <li>Hyatt · Four Seasons</li>
                        </ul>
                    </div>
                </div>
                <div className="border-t border-border/70">
                    <div className="mx-auto max-w-6xl px-4 py-5 text-xs text-muted-foreground sm:px-6">
                        © {new Date().getFullYear()} BookMyHotel · Dubai
                    </div>
                </div>
            </footer>
        </div>
    );
}
