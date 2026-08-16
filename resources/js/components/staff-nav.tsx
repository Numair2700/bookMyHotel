import { Link, usePage } from '@inertiajs/react';

const tabs: Record<'admin' | 'manager', { label: string; href: string }[]> = {
    admin: [
        { label: 'Analytics', href: '/admin/analytics' },
        { label: 'Hotels', href: '/admin/hotels' },
        { label: 'Reservations', href: '/admin/reservations' },
    ],
    manager: [
        { label: 'Rates & availability', href: '/manager/availability' },
        { label: 'Promotions', href: '/manager/promotions' },
    ],
};

export default function StaffNav({ area }: { area: 'admin' | 'manager' }) {
    const { url } = usePage();

    return (
        <div className="border-b border-border">
            <div className="mx-auto flex max-w-6xl gap-1 px-4 sm:px-6">
                {tabs[area].map((tab) => {
                    const active = url.startsWith(tab.href);
                    return (
                        <Link
                            key={tab.href}
                            href={tab.href}
                            className={`-mb-px border-b-2 px-4 py-3 text-sm font-medium transition-colors ${
                                active
                                    ? 'border-primary text-foreground'
                                    : 'border-transparent text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            {tab.label}
                        </Link>
                    );
                })}
            </div>
        </div>
    );
}
