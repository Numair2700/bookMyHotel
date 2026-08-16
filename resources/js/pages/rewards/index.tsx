import { Head } from '@inertiajs/react';
import { Leaf, Minus, Plus } from 'lucide-react';

interface LedgerEntry {
    id: number;
    points: number;
    reason: string;
    created_at: string | null;
}

interface Props {
    balance: number;
    ledger: LedgerEntry[];
}

export default function RewardsIndex({ balance, ledger }: Props) {
    return (
        <>
            <Head title="Reward points" />

            <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6">
                <h1 className="font-serif text-3xl font-semibold text-foreground">
                    Reward points
                </h1>
                <p className="mt-2 text-muted-foreground">
                    Earn a point for every 10 AED spent on a sustainable stay.
                </p>

                <div className="mt-6 overflow-hidden rounded-2xl border border-border bg-primary text-primary-foreground">
                    <div className="flex items-center justify-between p-8">
                        <div>
                            <p className="text-sm text-primary-foreground/70">
                                Available balance
                            </p>
                            <p className="mt-1 font-serif text-5xl font-semibold">
                                {balance.toLocaleString()}
                            </p>
                            <p className="mt-1 text-sm text-primary-foreground/70">
                                points
                            </p>
                        </div>
                        <Leaf className="h-14 w-14 text-primary-foreground/30" />
                    </div>
                </div>

                <h2 className="mt-10 font-serif text-xl font-semibold text-foreground">
                    Activity
                </h2>
                {ledger.length === 0 ? (
                    <p className="mt-3 text-sm text-muted-foreground">
                        No points activity yet.
                    </p>
                ) : (
                    <ul className="mt-4 divide-y divide-border rounded-xl border border-border bg-card">
                        {ledger.map((entry) => {
                            const earned = entry.points >= 0;

                            return (
                                <li
                                    key={entry.id}
                                    className="flex items-center gap-4 p-4"
                                >
                                    <span
                                        className={`grid h-9 w-9 shrink-0 place-items-center rounded-full ${
                                            earned
                                                ? 'bg-eco/15 text-eco'
                                                : 'bg-secondary text-muted-foreground'
                                        }`}
                                    >
                                        {earned ? (
                                            <Plus className="h-4 w-4" />
                                        ) : (
                                            <Minus className="h-4 w-4" />
                                        )}
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm text-foreground">
                                            {entry.reason}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {entry.created_at}
                                        </p>
                                    </div>
                                    <span
                                        className={`shrink-0 font-medium ${earned ? 'text-eco' : 'text-muted-foreground'}`}
                                    >
                                        {earned ? '+' : ''}
                                        {entry.points}
                                    </span>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>
        </>
    );
}
